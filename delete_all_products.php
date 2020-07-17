<?php 

if(php_sapi_name() !== 'cli') 
    die();

ob_start();

require( dirname(dirname( __FILE__ )) . '/wp-load.php' );
require_once(ABSPATH . 'wp-admin/includes/image.php');
require_once(ABSPATH . 'wp-admin/includes/file.php');
require_once(ABSPATH . 'wp-admin/includes/media.php');

ob_end_clean();

require('./includes/prom_parser.php' ); 

define('THIS_PLUGIN_PATH', dirname( __FILE__ ));


function wh_deleteProduct($id, $force = FALSE)
{
    $product = wc_get_product($id);

    if(empty($product))
        return new WP_Error(999, sprintf(__('No %s is associated with #%d', 'woocommerce'), 'product', $id));

    // If we're forcing, then delete permanently.
    if ($force)
    {
        if ($product->is_type('variable'))
        {
            foreach ($product->get_children() as $child_id)
            {
                $child = wc_get_product($child_id);
                $child->delete(true);
            }
        }
        elseif ($product->is_type('grouped'))
        {
            foreach ($product->get_children() as $child_id)
            {
                $child = wc_get_product($child_id);
                $child->set_parent_id(0);
                $child->save();
            }
        }

        $product->delete(true);
        $result = $product->get_id() > 0 ? false : true;
    }
    else
    {
        $product->delete();
        $result = 'trash' === $product->get_status();
    }

    if (!$result)
    {
        return new WP_Error(999, sprintf(__('This %s cannot be deleted', 'woocommerce'), 'product'));
    }

    // Delete parent product transients.
    if ($parent_id = wp_get_post_parent_id($id))
    {
        wc_delete_product_transients($parent_id);
    }
    return true;
}


$start = microtime(true);


global  $wpdb;
$post_ids = $wpdb->get_col("SELECT ID FROM wp_posts WHERE post_type = 'product'");
print_r($post_ids);


$cnt = 0;
foreach ( (array) $post_ids as $post_id ) {
    $post = get_post(  $post_id );

    $attachments = get_children( array( 'post_type'=>'attachment', 'post_parent'=>$post_id ) );
    
    if( $attachments ){
        foreach( $attachments as $attachment ) {
            
            print_r($attachment); die;
            if ( ! wp_delete_attachment( $attachment->ID, true ) ) {
               echo 'eroror' . $attachment->ID;
            }
            
        }
    }
	
    if ( ! wh_deleteProduct($post_id, true) ) {
        echo 'eroror' . $post_id;
    }
    
    $cnt++;
}





$time = microtime(true) - $start;

echo $cnt . PHP_EOL ;
echo $time . PHP_EOL ;
