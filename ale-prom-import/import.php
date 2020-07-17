#!/usr/bin/php
<?php
@ob_end_clean();
require ('/home/alex/projects/wp3/' . 'wp-load.php' );

//require( dirname( __FILE__ ) . '/wp-blog-header.php' );


require_once(ABSPATH . 'wp-admin/includes/image.php');
require_once(ABSPATH . 'wp-admin/includes/file.php');
require_once(ABSPATH . 'wp-admin/includes/media.php');
require_once ABSPATH . 'wp-admin/includes/taxonomy.php';

$file = './data-2.csv';


$row = 0;
$filds = [];
$products =[];
if (($handle = fopen($file, "r")) !== FALSE) {
    while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
        $num = count($data);
        $row++;
        for ($c=0; $c < $num; $c++) {
            if($row == 1) {
                if(isset($data[$c])) {
                    $filds[] = $data[$c];
                }
               
            } else {
                if(isset($data[$c]) && $data[$c] != '' ) {
                    $products[$row-2][$filds[$c]] = $data[$c];
                }
                
            }
        }
        
        //if($row > 10) break;
    }
    fclose($handle);
}

//print_r($products);


$newProducts = [];
foreach($products as $p) {
    if(!isset($newProducts[$p['id']])) {
        $newProducts[$p['id']] = $p;
    }
    if(isset($p['param_value'])) {
        $newProducts[$p['id']]['variations'][$p['param_id']]['attributes']['size'] = $p['param_value'];
        $newProducts[$p['id']]['variations'][$p['param_id']]['price'] = $p['price'];
        $newProducts[$p['id']]['variations'][$p['param_id']]['sku'] = $p['article_and_param'];
        $newProducts[$p['id']]['variations'][$p['param_id']]['stock_quantity'] = $p['count_goods'];
    }
   
    //$newProducts[$p['id']]['variations'][$p['param_id']]['attributes'][$p['id']]['option'] = $p['param_value'];
  
   
    foreach($p as $k=>$v) {
        if(in_array($k,['id',
        'name','article',
        'par_cat_id','cat_id',
        'param_name','param_value',
        'img_src','param_id','img_name','article_and_param','article_and_param_val','count_goods','price'])) {
            continue;
        } 

        if(isset($p['param_value'])) {
            if(!isset($newProducts[$p['id']]['attributes']['size'])) {
                $newProducts[$p['id']]['attributes']['size'][] = $p['param_value'];
            }elseif(!in_array($p['param_value'], $newProducts[$p['id']]['attributes']['size'])) {
                $newProducts[$p['id']]['attributes']['size'][] = $p['param_value'];
            }
        }


        if(!isset($newProducts[$p['id']]['attributes'][$k])) {
            $newProducts[$p['id']]['attributes'][$k][] = $v;
        }elseif(!in_array($v,$newProducts[$p['id']]['attributes'][$k]) && $k != 'pillowcase') {
            $newProducts[$p['id']]['attributes'][$k][] = $v;
        }
    }
   
}

//print_r($newProducts);
//die;

//DELETE FROM wp_posts WHERE ID in (SELECT post_id from wp_postmeta WHERE `meta_key` = 'foreign_id');DELETE from wp_postmeta WHERE `meta_key` = 'foreign_id' 


$foreign_ids = $wpdb->get_col(
    $wpdb->prepare("SELECT DISTINCT
        meta_value FROM $wpdb->postmeta WHERE meta_key = %s ORDER BY meta_value ASC", 
    'foreign_id') 
);
//print_r($foreign_ids);



$systemAttributes = $wpdb->get_col( "SELECT attribute_name FROM {$wpdb->prefix}woocommerce_attribute_taxonomies ") ;
 

$i = 0;
foreach($newProducts as $p) {
    $i++; if( $i > 3) break;
    if(!in_array(intval($p['article_and_param']), $foreign_ids)) {
        if(!isset($p['price'])) {
            continue;
        }


        $objProduct = new WC_Product();

        $objProduct->set_name($p['name']);
        $objProduct->set_status("publish");  
        $objProduct->set_catalog_visibility('visible'); 
        try {
            $objProduct->set_sku($p['article']); 
        } catch(WC_Data_Exception $e) {
            echo $e;
        }
        
       
        $objProduct->set_price($p['price']); 
        
        $objProduct->set_regular_price($p['price']);
        $objProduct->set_sale_price($p['price']);
    
    
        if(isset($p['count_goods'])) {
            $objProduct->set_manage_stock(true); 
            $objProduct->set_stock_quantity($p['count_goods']);
            if($p['count_goods'] > 0) {
                $objProduct->set_stock_status('instock'); 
            } else {
                $objProduct->set_stock_status('outofstock'); 
            }
        }
       

        
    
       /* if($p['img_src']) {
            $attachment_id = uploadImage($p['img_src'], $p['id']);
            $objProduct->set_image_id($attachment_id);
        }*/
       
        
        $result = get_term_by( 'name', $p['par_cat_id'], 'product_cat');
        $par_cat_id  = isset($result->term_id) ? $result->term_id : '';
        if(!$par_cat_id) {
            $result = wp_insert_term( $p['par_cat_id'], 'product_cat', array(
                'parent'      => 0,
            ) );
            $par_cat_id = $result['term_id'];
        }


        $result = get_term_by( 'name', $p['cat_id'], 'product_cat');
        $cat_id  = isset($result->term_id) ? $result->term_id : '';
        if(!$cat_id) {
            $result = wp_insert_term( $p['cat_id'], 'product_cat', array(
                'parent'      => $par_cat_id,
            ) );
            $cat_id = $result['term_id'];
        }


        $objProduct->set_category_ids( [$cat_id] );


        
       
        $attrObjArr=[];
        foreach($p['attributes'] as $attrk=>$attrv) {
            $terms = [];
            if(!in_array($attrk ,$systemAttributes)) {
                create_product_attribute( ['name'=>$attrk] );     
                register_taxonomy( 'pa_'.$attrk, [ 'product' ]);
                if(is_array($attrv)) {
                    foreach($attrv as $termName) {
                        wp_insert_term( $termName, 'pa_'.$attrk);
                    }
                } else {
                    wp_insert_term( $attrv, 'pa_'.$attrk);
                }
                
                
                $systemAttributes[] = $attrk;
            }
            $term_id = wc_attribute_taxonomy_id_by_name( $attrk );
            //print_r(wc_get_attribute($term_id)); 
         
           // print_r($attrv);
    
            //if($attrk == 'size') { continue;}
            $attrObj = new  WC_Product_Attribute();
            $attrObj->set_visible(true);
            $attrObj->set_id($term_id);
            $attrObj->set_name( 'pa_'.$attrk );
            if($attrk == 'size') {
                $attrObj->set_variation(true);
               
            }
            $attrObj->set_options($attrv);
            $attrObjArr[] = $attrObj;
        }



       // print_r($attrObjArr); 
        

        //die;
        
       
       
        
        $objProduct->set_attributes($attrObjArr);
        $product_id = $objProduct->save();
        

     
     
        /*
        add_post_meta( $product_id, 'foreign_id', $p['article_and_param'], true );
        
        echo 'insetr!!  '. $p['article_and_param'] . PHP_EOL ;


        //DELETE from wp_postmeta WHERE `post_id` in (SELECT ID FROM wp_posts WHERE post_type='product' or post_type='product_variation');
        //DELETE FROM wp_posts WHERE post_type='product' or  post_type='product_variation'




        //DELETE from wp_postmeta WHERE `post_id` in (SELECT ID FROM wp_posts WHERE post_type='product');
        //DELETE FROM wp_posts WHERE post_type='product'
        //DELETE FROM wp_posts WHERE post_type='product_variation'
        //DELETE from wp_postmeta WHERE `post_id` in (SELECT ID FROM wp_posts WHERE post_type='product_variation')
        //DELETE FROM wp_posts WHERE ID in (SELECT post_id from wp_postmeta WHERE `meta_key` = 'foreign_id');DELETE from wp_postmeta WHERE `meta_key` = 'foreign_id' 
        $variations = isset($p['variations']) ? $p['variations'] : [];
        if($variations) {
            
            wp_set_object_terms($product_id, 'variable', 'product_type');
            //wp_set_object_terms( $product_id, $p['attributes']['size'], 'pa_'.$attrk );
           
            foreach($variations as $variant) {
                create_product_variation( $objProduct, $variant );
               // do_action( 'product_variation_linked', $objProduct->get_id() );
            }
              
        }
       */ 

    } else {

    }
   
}






function create_product_variation( $objProduct, $variation_data ) {
    $product_id = $objProduct->get_id();

    $variation_post = array(
        'post_title'  =>  $objProduct->get_title(),
        'post_name'   => 'product-'.$product_id.'-variation',
        'post_status' => 'publish',
        'post_parent' => $product_id,
        'post_type'   => 'product_variation',
        'guid'        =>  $objProduct->get_permalink()
    );

    $variation_id = wp_insert_post( $variation_post );
    $variation = new WC_Product_Variation( $variation_id );

    
    $product_attributes = array();

    foreach( $variation_data['attributes'] as $key => $term_name ) {
        $termObj = get_term_by( 'name', $term_name, 'pa_'.$key);
       // echo $termObj->slug . PHP_EOL;
        update_post_meta( $variation_id, 'attribute_pa_'.$key, $termObj->slug );
    }


    // SKU
    if( ! empty( $variation_data['sku'] ) ) {
        try {
            $variation->set_sku( $variation_data['sku'] );
        } catch(WC_Data_Exception $e) {
            echo $e;
        }
    }
        
    
    
    $variation->set_regular_price($variation_data['price']);
    $variation->set_sale_price($variation_data['price']);


    // Stock
    if( ! empty($variation_data['stock_quantity']) ){
        $variation->set_stock_quantity( $variation_data['stock_quantity'] );
        $variation->set_manage_stock(true);
        $variation->set_stock_status('');
    } else {
        $variation->set_manage_stock(false);
    }
    
    $variation->set_weight(''); // weight (reseting)
    
    $variation->save(); // Save the data
}





function create_product_attribute( $data ) {
    global $wpdb;
    

    if ( ! isset( $data['name'] ) ) {
        $data['name'] = '';
    }

    // Set the attribute slug
    if ( ! isset( $data['slug'] ) ) {
        $data['slug'] = wc_sanitize_taxonomy_name( stripslashes( $data['name'] ) );
    } else {
        $data['slug'] = preg_replace( '/^pa\_/', '', wc_sanitize_taxonomy_name( stripslashes( $data['slug'] ) ) );
    }

    // Set attribute type when not sent
    if ( ! isset( $data['type'] ) ) {
        $data['type'] = 'select';
    }

    // Set order by when not sent
    if ( ! isset( $data['order_by'] ) ) {
        $data['order_by'] = 'menu_order';
    }

    $insert = $wpdb->insert(
        $wpdb->prefix . 'woocommerce_attribute_taxonomies',
        array(
            'attribute_label'   => $data['name'],
            'attribute_name'    => $data['slug'],
            'attribute_type'    => $data['type'],
            'attribute_orderby' => $data['order_by'],
            'attribute_public'  => isset( $data['has_archives'] ) && true === $data['has_archives'] ? 1 : 0,
        ),
        array( '%s', '%s', '%s', '%s', '%d' )
    );

    // Checks for an error in the product creation
    if ( is_wp_error( $insert ) ) {
        throw new WC_API_Exception( 'woocommerce_api_cannot_create_product_attribute', $insert->get_error_message(), 400 );
    }

    $id = $wpdb->insert_id;

    do_action( 'woocommerce_api_create_product_attribute', $id, $data );

    // Clear transients
    delete_transient( 'wc_attribute_taxonomies' );
    WC_Cache_Helper::incr_cache_prefix( 'woocommerce-attributes' );

    return $id;
    
}



function uploadImage($file, $parent_id) {
    $filename = basename($file);
    $local_file_path = ABSPATH . 'import/large/' . $filename;
   
    if(file_exists($local_file_path)) {
        $file =  $local_file_path;
    } else {
        echo $file . PHP_EOL;
    }
    
    $upload_file = wp_upload_bits($filename, null, file_get_contents($file));
    if (!$upload_file['error']) {
        $wp_upload_dir = wp_upload_dir();
        $wp_filetype = wp_check_filetype($filename, null );
        $attachment = array(
            'post_parent' => $parent_id,
            'guid'           => $wp_upload_dir['url'] . '/' . $filename,
            'post_mime_type' => $wp_filetype['type'],
            'post_title' => preg_replace('/\.[^.]+$/', '', $filename),
            'post_content' => '',
            'post_status' => 'inherit'
        );
        $attachment_id = wp_insert_attachment( $attachment, $upload_file['file'] );
        if (!is_wp_error($attachment_id)) {
            $attachment_data = wp_generate_attachment_metadata( $attachment_id, $upload_file['file'] );
            wp_update_attachment_metadata( $attachment_id,  $attachment_data );
            return $attachment_id;
        }
        
    }
    return false;
}
