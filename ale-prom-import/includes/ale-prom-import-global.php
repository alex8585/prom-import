<?php




if(function_exists('print_filters_for') ) {
    function print_filters_for( $hook = '' ) {
        global $wp_filter;
        if( empty( $hook ) || !isset( $wp_filter[$hook] ) )
            return;
    
        print '<pre>';
        print_r( $wp_filter[$hook] );
        print '</pre>';
    }
}

