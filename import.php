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


$start = microtime(true);

$parser = new PromParser();



//$parser->uploadXmlFile();

$parser->loadXMLfromFile();

$parser->importCategories();
//$parser->generateConfigJson();

$parser->importProducts();




$cnt = $parser->getXMLproductsCount();
$time = microtime(true) - $start;


echo $cnt . PHP_EOL ;
echo $time . PHP_EOL ;

