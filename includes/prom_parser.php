<?php

use \Automattic\WooCommerce\Admin\CategoryLookup;


class PromParser
{
    

    public $catIdsArr;
    public $configPath;
    public $defaultPercent;
    public $catConfArr;
    public $productsMap;
    public $xmlFilePath;
    public $xml;
    public $lastCatConf;
    public $dbDefaultPercent;

    public function __construct($options =[])
    { 
        $this->defaultPercent = get_option('prom_import_default_percent', 0);

        $this->catConfigPath  = isset($options['config_path']) ? $options['config_path'] :'';
        $this->xmlFilePath = isset($options['xml_file_path']) ? $options['xml_file_path'] : '';
        
        $this->sourceUrl = isset($options['xml_file_path']) ? $options['xml_file_path'] : '';
        $this->sourceName = isset($options['source_name']) ? $options['source_name'] : 'sexopt_com_ua';

        if(!$this->catConfigPath) {
            $this->catConfigPath = THIS_PLUGIN_PATH . '/categories_config.json';
        }

        if(!$this->xmlFilePath) {
            $this->xmlFilePath = THIS_PLUGIN_PATH . '/' .$this->sourceName .'.xml';
        }

        if(!$this->sourceUrl) {
            $this->sourceUrl = 'https://sexopt.com.ua/content/export/106.xml'; 
        }

        $this->imagesPath = THIS_PLUGIN_PATH . '/images';


        $this->catIdsArr = [];
        $this->catConfArr = [];
        $this->productsMap = [];

        $this->xml = '';
        $this->lastCatConf ='';
        $this->dbDefaultPercent ='';
    }


	public function uploadXmlFile() {
		$url = $this->sourceUrl; 
		$file_name = $this->xmlFilePath;  
			
		if(file_put_contents($file_name, file_get_contents($url))) { 
			return $file_name;
		} 

		echo ("File downloading failed."); 
		return false;
	}

    public function runImportAll() {
        $this->uploadXmlFile();
        $this->loadXMLfromFile();
        $this->importCategories();
        $this->importProducts();
	}
    
    public function getXML() {
        if( !empty( $this->xml ) ){
            return $this->xml;
        }
        $this->xml = $this->loadXMLfromFile();
        return $this->xml;
	}
	
    public function loadXMLfromFile() {
        $file_name = $this->xmlFilePath;

        if(!file_exists ( $file_name )) {
            $this->uploadXmlFile();
        }

        $this->xml = simplexml_load_file($file_name);
        return $this->xml;
    }


    public function getProductsMap($fromDb = false) {
        if($fromDb || empty($this->productsMap)) {
            $this->productsMap = $this->getDbMapProducts();
        }
        return $this->productsMap;
    }

    public function getDbMapProducts() {
        global  $wpdb;
        $r = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT post_id,meta_key,meta_value
                 FROM $wpdb->postmeta WHERE post_id 
                 IN (SELECT post_id FROM $wpdb->postmeta WHERE meta_key = %s AND meta_value = %s) 
                 AND (meta_key = %s OR meta_key = %s OR meta_key =%s)   ORDER BY post_id ASC", 
                 [
                    'foreign_source',
                    $this->sourceName,
                    'foreign_id', 
                    'foreign_price',
                    '_stock_status',
                ]) 
        );
        $productsMap = [];
        foreach($r as $meta) {
            $postId = $meta->post_id;
            if($meta->meta_key == 'foreign_price') {
                $productsMap[$postId]['product_id'] = $postId;
                $productsMap[$postId]['foreign_price'] = $meta->meta_value;
            }
            if($meta->meta_key == 'foreign_id') {
                $productsMap[$postId]['foreign_id'] = $meta->meta_value;
            }
            if($meta->meta_key == '_stock_status') {
                $productsMap[$postId]['_stock_status'] = $meta->meta_value;
            }
        }
        return $productsMap;
    }

    public function getCatIdsArr($fromDb = false) {
        if($fromDb || empty($this->catIdsArr)) {
            $this->catIdsArr = $this->getCatIdsArrFromDb();
        }
        return $this->catIdsArr;
    }

    public function getCatIdsArrFromDb() {
        global $wpdb;
        $r = $wpdb->get_results("SELECT term_id FROM $wpdb->term_taxonomy  WHERE `taxonomy` = 'product_cat'", ARRAY_A);
        $catIdsArr =[];
        foreach($r  as $term) {
            $term_id = $term['term_id'];
            $catIdsArr[$term_id]['foreign_id'] = get_term_meta( $term_id, 'foreign_id',true);
            $catIdsArr[$term_id]['foreign_parent_id'] = get_term_meta( $term_id, 'foreign_parent_id',true);
        }
        
        return $catIdsArr;
    }

    public function getCatIdByForeignId($foreign_id) {
        $catIdsArr = $this->getCatIdsArr();
        $id = false;
        foreach($catIdsArr as $cur_id => $t) {
            if($t['foreign_id'] == $foreign_id) {
                $id = $cur_id;
                break;
            }
        }
        return $id;
    }

    public function importCategories() {
        $xml = $this->getXML();
        $shop = $xml->shop;  
        $catIdsArr = $this->getCatIdsArr(true);
       

        foreach($shop->categories->children() as $cat) {
            $attrArr = [];
            foreach($cat->attributes() as $c => $v) {
                $attrArr[$c] = (string)$v[0];
            }
            $catName = (string)$cat[0];
          
            $foreign_id = isset( $attrArr['id'] ) ? $attrArr['id'] : 0;
            $foreign_parent_id = isset( $attrArr['parentId'] ) ? $attrArr['parentId'] : 0;

            $catId = $this->getCatIdByForeignId($foreign_id);

            if($catId !== false) continue;
            
            $cat = wp_insert_term(
                $catName, 
                'product_cat'
            );
    
            if(is_wp_error($cat)) {
                $cat = wp_insert_term(
                    $catName, 
                    'product_cat',
                    [
                        'slug'      => $catName.'_'.$foreign_id,
                    ]
                );
            }

            if(is_wp_error($cat)) {
                $cat = wp_insert_term(
                    $catName.'_'.$foreign_id, 
                    'product_cat',
                    [
                        'slug'      => $catName.'_'.$foreign_id,
                    ]
                );

                if(!is_wp_error($cat)){
                    update_term_meta( $cat_id, 'foreign_name', $catName );
                }

            }
            
           if(!is_wp_error($cat)){
                $cat_id = isset( $cat['term_id'] ) ? $cat['term_id'] : 0;
                update_term_meta( $cat_id, 'foreign_id', absint( $foreign_id ) );
                update_term_meta( $cat_id, 'foreign_parent_id', absint( $foreign_parent_id ) );

                $catIdsArr[$cat_id]['foreign_id'] = $foreign_id;
                $catIdsArr[$cat_id]['foreign_parent_id'] = $foreign_parent_id;
           } else {
                var_dump($cat);
           }

        }
        $this->catIdsArr = $catIdsArr;

        
        $this->setParentCategories();

        //$this->generateConfigJson();
    }

    public function setParentCategories() {
         $catIdsArr = $this->getCatIdsArr(true);
         remove_all_actions( 'edited_product_cat',99);
         foreach($catIdsArr  as $term_id=>$term) {
             $foreign_parent_id = $term['foreign_parent_id'];
             $paren_id = $this->searchInCatIdsByForeignParentId($foreign_parent_id);
             $catIdsArr[$term_id]['parent_id'] = $paren_id;

             if($paren_id && term_exists($paren_id, 'product_cat')) {
                 wp_update_term( $term_id, 'product_cat', [
                     'parent' => $paren_id,
                 ]);
             } else {
                wp_update_term( $term_id, 'product_cat', [
                    'parent' => -1,
                ]);
             }
         }
        
         CategoryLookup::instance()->regenerate();
         $this->catIdsArr = $catIdsArr;
    }

    public function saveLastCatConfToDb() {
        if(!file_exists ( $this->catConfigPath )) {
           return false; 
        }
        $catConfJson = file_get_contents($this->catConfigPath);
        update_option( 'last_cat_config', $catConfJson);
        return true;
    }

    public function getLastCatConfFromDb() {
        $catConfJson = get_option( 'last_cat_config');
        $catConf = json_decode($catConfJson);
        return $catConf;
    }

    public function getLastCatConf() {
        if(empty($this->lastCatConf)) {
            $this->lastCatConf = $this->getLastCatConfFromDb();
        }
        return $this->lastCatConf;
    }

    public function generateConfigJson($fromDb = false) {
        if(file_exists ( $this->catConfigPath )) {
            return;
        }

        global $wpdb;
       
        $catIdsArr = $this->getCatIdsArr($fromDb);
        $idsArr = array_keys($catIdsArr);
        $r = $wpdb->get_results("SELECT * FROM $wpdb->terms  WHERE term_id IN (".implode(',',$idsArr).")");

        $jsonArr = [];
        foreach($r  as $term) {
            $jsonArr[$term->term_id]['name'] = $term->name;
            $jsonArr[$term->term_id]['category_id'] = $term->term_id;
            $jsonArr[$term->term_id]['foreign_id'] = $catIdsArr[$term->term_id]['foreign_id'];
            $jsonArr[$term->term_id]['percent'] = $this->defaultPercent;
        }

        $jsonStr = json_encode($jsonArr,JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        file_put_contents($this->catConfigPath, $jsonStr);
        
    }

    public function searchInCatIdsByForeignParentId($foreign_parent_id) {
        $catIdsArr = $this->getCatIdsArr();
        foreach($catIdsArr as $id => $t) {
            if($t['foreign_id'] == $foreign_parent_id) {
                return $id;
            }
        }
        return  false;
    }

    

    public function getCatIdFromForeignCatId($foreign_id) {
        $catIdsArr = $this->getCatIdsArr();
        $category_id = 0;
        foreach($catIdsArr as $id=>$cat) {
            if($foreign_id  == $cat['foreign_id']) {
                $category_id = $id;
                return $category_id;
            }
        }
        return $category_id;
    }

    public function transliterate($str) {
        $cyr = [
            'а','б','в','г','д','е','ё','ж','з','и','й','к','л','м','н','о','п',
            'р','с','т','у','ф','х','ц','ч','ш','щ','ъ','ы','ь','э','ю','я',
            'А','Б','В','Г','Д','Е','Ё','Ж','З','И','Й','К','Л','М','Н','О','П',
            'Р','С','Т','У','Ф','Х','Ц','Ч','Ш','Щ','Ъ','Ы','Ь','Э','Ю','Я'
        ];
        $lat = [
            'a','b','v','g','d','e','io','zh','z','i','y','k','l','m','n','o','p',
            'r','s','t','u','f','h','ts','ch','sh','sht','a','i','y','e','yu','ya',
            'A','B','V','G','D','E','Io','Zh','Z','I','Y','K','L','M','N','O','P',
            'R','S','T','U','F','H','Ts','Ch','Sh','Sht','A','I','Y','e','Yu','Ya'
        ];
    
        $result = str_replace($cyr, $lat, $str);
        
        return sanitize_title($result);
    }

    public function create_product_attribute( $label_name, $slug ){
        global $wpdb;
    
       
        if ( strlen( $slug ) >= 28 ) {
            return new WP_Error( 'invalid_product_attribute_slug_too_long', sprintf( __( 'Name "%s" is too long (28 characters max). Shorten it, please.', 'woocommerce' ), $slug ), array( 'status' => 400 ) );
        } elseif ( wc_check_if_attribute_name_is_reserved( $slug ) ) {
            return new WP_Error( 'invalid_product_attribute_slug_reserved_name', sprintf( __( 'Name "%s" is not allowed because it is a reserved term. Change it, please.', 'woocommerce' ), $slug ), array( 'status' => 400 ) );
        } elseif ( taxonomy_exists( wc_attribute_taxonomy_name( $label_name ) ) ) {
            return new WP_Error( 'invalid_product_attribute_slug_already_exists', sprintf( __( 'Name "%s" is already in use. Change it, please.', 'woocommerce' ), $label_name ), array( 'status' => 400 ) );
        }
    
        $data = array(
            'attribute_label'   => $label_name,
            'attribute_name'    => $slug,
            'attribute_type'    => 'select',
            'attribute_orderby' => 'menu_order',
            'attribute_public'  => 0, // Enable archives ==> true (or 1)
        );
        
        $results = $wpdb->insert( "{$wpdb->prefix}woocommerce_attribute_taxonomies", $data );
       
        if ( is_wp_error( $results ) ) {
            return new WP_Error( 'cannot_create_attribute', $results->get_error_message(), array( 'status' => 400 ) );
        }
    
        $id = $wpdb->insert_id;
    
        do_action('woocommerce_attribute_added', $id, $data);
    
        wp_schedule_single_event( time(), 'woocommerce_flush_rewrite_rules' );
    
        delete_transient('wc_attribute_taxonomies');
    }

    public function getProductArrFromXML($xmlProd) {
        $attrArr = [];
        foreach($xmlProd->attributes() as $c => $v) {
            $attrArr[$c] = (string)$v[0];
        }
    
        $product = [];
    
        $product['foreign_id'] = $attrArr['id'];
        $product['available'] = $attrArr['available'];
        $product['groupId'] =  $attrArr['group_id']; 
        $product['name'] = (string)$xmlProd->name;
        $product['url'] = (string)$xmlProd->url;
        $product['price'] = floatval((string)$xmlProd->price);
        $product['currencyId'] = (string)$xmlProd->currencyId;
        $product['categoryId'] = (string)$xmlProd->categoryId;
        $product['picture'] = (array)$xmlProd->picture;
        $product['vendorCode'] = (string)$xmlProd->vendorCode;
        $product['vendor'] = (string)$xmlProd->vendor;
        $product['description'] = (string)$xmlProd->description;
        $product['params'] = [];
        if(isset($xmlProd->param) ) {
            $cnt = count($xmlProd->param); 
            for($j=0;$j<$cnt;$j++) {
                foreach($xmlProd->param[$j]->attributes() as $a => $b) {
                    $product['params'][(string)$b] = (string)$xmlProd->param[$j];
                }
            }
        }
    
        return $product;
    }

    public function setAttributesToProduct($product, $wcProduct) {
        $attrObjArr =[];
        foreach($product['params'] as $attrk=>$attrv) {

            $termName = $attrv;
            $taxonomyName = $attrk;
            $taxonomySlug =  $this->transliterate($attrk);
            $paTxaSlug = 'pa_'.$taxonomySlug;

            if(!taxonomy_exists($paTxaSlug)) {
                $this->create_product_attribute( $taxonomyName, $taxonomySlug );
                register_taxonomy( $paTxaSlug, [ 'product' ]);
            }

            if(!term_exists(  $termName, $paTxaSlug )) {
                wp_insert_term(  $termName, $paTxaSlug);
            }

            $option_id = wc_attribute_taxonomy_id_by_name( $taxonomySlug );
            
        
            $attrObj = new  WC_Product_Attribute();
            $attrObj->set_visible(true);
            $attrObj->set_id($option_id);
            $attrObj->set_name( $paTxaSlug );
        
            $attrObj->set_options(array( $termName));
            $attrObjArr[] = $attrObj;

            
        } 
        
        $wcProduct->set_attributes($attrObjArr);
        $product_id = $wcProduct->save();
        return $product_id;
    }

    public function getCatConfigArr() {
        if(!empty($this->catConfArr)) {
            return $this->catConfArr;
        }

        if(!file_exists ( $this->catConfigPath )) {
            return false;
        }

        $catConfJson = file_get_contents($this->catConfigPath);
        $catConf = json_decode($catConfJson);
        $this->catConfArr = $catConf;
        return $this->catConfArr;
    }

    public function getNewPrice($product, $wcProduct, $category_id) {
        $catConfArr = $this->getCatConfigArr();
       
        $productCat = $this->getCatFromConfObj($catConfArr, $category_id);

        if(!$productCat || empty($productCat->percent)) {
            $percent = floatval($this->defaultPercent);
        } else {
            $percent = floatval($productCat->percent);
        }
        if(empty($percent)) {
            return $product['price'];
        }

        $newPrice = $product['price'] + ($product['price'] * $percent)/100;
        
        return $newPrice;
        
    }

    public function getCatFromConfObj($catConf, $category_id) {
        foreach($catConf as $cat) {
            if($cat->category_id == $category_id) {
                return $cat;
            }
        }
        return false;
    }

    public function getProductsForeignIds($productsMap) {
        $productsForeignIds = [];
        foreach($productsMap as $product) {
            $productsForeignIds[] = $product['foreign_id'];
        }
        return $productsForeignIds;
    }

    public function getProductFromMap($productsMap, $product) {
        foreach($productsMap as $mapProduct) {
            if($mapProduct['foreign_id'] == $product['foreign_id']) {
                return $mapProduct;
            }
        }
        return false;
    }

    public function getXMLproductsCount() {
        $xml = $this->getXML();
        $xmlProducts = $xml->shop->offers->children();
        return count($xmlProducts);
    }

    public function importProducts() {
        global $wpdb;
        $xml = $this->getXML();
        $xmlProducts = $xml->shop->offers->children();
        $productsMap = $this->getProductsMap();
        $productsForeignIds = $this->getProductsForeignIds($productsMap);
        $isChangeDPercent  =  $this->isChangeDefaultPercent();

        $i = 0;
        $foreignIdsArr = [];
        foreach($xmlProducts as $xmlProd) {
            //$i++; if (  $i >= 20 ) break;
            
            $product = $this->getProductArrFromXML($xmlProd);
            
            $foreignIdsArr[] =  $product['foreign_id'];
            if(!in_array(intval( $product['foreign_id']), $productsForeignIds)) {
            
                $wcProduct = new WC_Product();

                $wcProduct->set_status("publish");  
                $wcProduct->set_catalog_visibility('visible');

                if($product['available']) {
                    $wcProduct->set_stock_status( 'instock' );
                } else {
                    $wcProduct->set_stock_status( 'outofstock' );
                }
               
               
                $wcProduct->set_name($product['name']);
                $wcProduct->set_sku($product['vendorCode']); 
                $wcProduct->set_description($product['description']);
               

                $category_id = $this->getCatIdFromForeignCatId($product['categoryId']);
                $wcProduct->set_category_ids((array)$category_id );
                
                $wcProduct->update_meta_data( 'foreign_id', $product['foreign_id'] );
                $wcProduct->update_meta_data( 'foreign_price', $product['price'] );
                $wcProduct->update_meta_data( 'foreign_source', $this->sourceName );
                
                $newPrice = $this->getNewPrice($product, $wcProduct,$category_id);

                $wcProduct->set_price($newPrice); 
                $wcProduct->set_regular_price($newPrice);
                $wcProduct->set_sale_price($newPrice);


                $productId = $wcProduct->save();

                if(isset($product['picture']) && is_array($product['picture'])) {
                    $this->importGallery($wcProduct, $product);
                }

                $this->setAttributesToProduct($product, $wcProduct);
              
            } else {
               
                $mapProduct = $this->getProductFromMap($productsMap, $product);

                $isAvailableUpd = $this->isRequireUpdateAvailable($mapProduct, $product);
                $isPriceUpd = $this->isRequireUpdatePrice($mapProduct, $product);
                $isCatConfChanged =  $this->isReqUpdCatConfChanged($product);
                

               

                if($isChangeDPercent || $isCatConfChanged || $isPriceUpd || $isAvailableUpd) {
                    $productId = $mapProduct['product_id'];
                    $wcProduct = wc_get_product( $productId );

                    if($product['available']) {
                        $wcProduct->set_stock_status( 'instock' );
                    } else {
                        $wcProduct->set_stock_status( 'outofstock' );
                    }

                    $category_id = $this->getCatIdFromForeignCatId($product['categoryId']);
                    $newPrice = $this->getNewPrice($product, $wcProduct,$category_id);
                    

                    $wcProduct->set_price($newPrice); 
                    $wcProduct->set_regular_price($newPrice);
                    $wcProduct->set_sale_price($newPrice);

                    $wcProduct->update_meta_data( 'foreign_price', $product['price'] );

                    $productId = $wcProduct->save();
                   
                }

            }
            
        }
        $this->saveLastDefaultPercent();
        $this->saveLastCatConfToDb();
        $this->disableIfNotInXML($foreignIdsArr, $productsMap);
        
    }

    public function saveLastDefaultPercent() {
        update_option( 'last_default_percent', $this->defaultPercent);
    }

    public function getDbDefaultPercent() {
        if(empty($this->dbDefaultPercent)) {
            $this->dbDefaultPercent = get_option( 'last_default_percent');
        }
        return $this->dbDefaultPercent;
    }

    public function isChangeDefaultPercent() {
        $dbDefaultPercent = $this->getDbDefaultPercent();
        if($this->defaultPercent != $dbDefaultPercent) {
            return true;
        }
        return false;
    }

    public function isReqUpdCatConfChanged($product) {
        $category_id = $this->getCatIdFromForeignCatId($product['categoryId']);

        if(!term_exists( $category_id, 'product_cat') ) {
            return false;
        }

        $lastCatConf = $this->getLastCatConf(); 
        $catConfArr = $this->getCatConfigArr();

        if(!$lastCatConf ||  !$catConfArr ) { return true; }

        $lastProductCat = $this->getCatFromConfObj($lastCatConf , $category_id);
        $productCat = $this->getCatFromConfObj($catConfArr , $category_id);

        if(!$lastProductCat && $productCat) { return true;}

        if($lastProductCat && !$productCat) { return true;}

        
       
        if(!$lastProductCat && !$productCat) { return false;}
        
        
        if($lastProductCat->percent == $productCat->percent) {
            return false;
        }

        return true;
    }

    

    public function importGallery($wcProduct, $product) {
        $productId = $wcProduct->get_id();
        
        $foreignId = $product['foreign_id'];
        $fileDirPath = $this->imagesPath .'/'.$foreignId;

        $pictures = [];
        if (file_exists( $fileDirPath)) {
            $files = scandir($fileDirPath);
            foreach($files as $file) {
                $imagePath = $fileDirPath . '/'. $file;
                if( is_file($imagePath) ) {
                    $pictures[] = $imagePath;
                }
            }
        } else {
            $pictures = $product['picture'];
        }
        
        
        $galeryArrIds = [];
        foreach($pictures as $k=>$picture) {
            $attachment_id = $this->uploadImage($picture, $productId);
            if($k == 0) {
                $wcProduct->set_image_id($attachment_id);
            } else {
                $galeryArrIds[] = $attachment_id;
            }
           
        }
    
        if($galeryArrIds) {
            $wcProduct->set_gallery_image_ids($galeryArrIds);
        }

        $media = get_attached_media('image', $productId);
        update_post_meta($productId, '_product_image_gallery', implode(',', array_keys($media)));
        $productId = $wcProduct->save();
    
        return $productId;
    }

    public function uploadImage($file, $parent_id) {
        $filename = basename($file);
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

    public function preUploadAllPictures() {
        $xml = $this->getXML();
        $xmlProducts = $xml->shop->offers->children();
        foreach($xmlProducts as $xmlProd) {
            $product = $this->getProductArrFromXML($xmlProd);
            if(!isset($product['picture']) || !is_array($product['picture'])) {
                continue;
            }
            $foreignId = $product['foreign_id'];
            $pictures = $product['picture'];
            foreach($pictures as $index=>$pictureUrl) {
              
                
                $ext = pathinfo($pictureUrl, PATHINFO_EXTENSION); 
                $name2 =pathinfo($pictureUrl, PATHINFO_FILENAME);

                $file_path = $this->imagesPath .'/'.$foreignId ;

                if (!file_exists( $file_path)) {
                    mkdir( $file_path, 0777, true);
                }

                $file_name = $file_path . '/'. $name2 . '.' .$ext ;

                if(file_put_contents($file_name, file_get_contents($pictureUrl))) { 
                   echo $file_name ;
                } 
            }
        }
    }

    public function disableIfNotInXML($foreignIdsArr, $productsMap) {
        foreach($productsMap as $productId=>$productMap) {
            if( !in_array($productMap['foreign_id'],$foreignIdsArr)) {
                $wcProduct = wc_get_product( $productId );
                $wcProduct->set_stock_status( 'outofstock' );
                $productId = $wcProduct->save();
            }
        }
    }

    public function isRequireUpdateAvailable($mapProduct, $product) {
        if($mapProduct['_stock_status'] == 'instock') {
            if($product['available']) {
                return false;
            }
            return true;
        }

        if($mapProduct['_stock_status'] == 'outofstock') {
            if($product['available']) {
                return true;
            }
            return false;
        }
    }

    public function isRequireUpdatePrice($mapProduct, $product) {
        if($mapProduct['foreign_price'] != $product['price']) {
            return true;
        }
        return false;
    }
    
    


}