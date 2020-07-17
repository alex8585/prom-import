<?php


class Ale_Prom_Import_Public {

	private $ale_prom_import;
	private $version;

	public function __construct( $ale_prom_import, $version ) {
		$this->view = new Ale_Prom_Import_View();
		$this->ale_prom_import = $ale_prom_import;
		$this->version = $version;
		$this->flash = new Ale_Prom_Import_Flash();
		
	}

	/**
	 * Register the stylesheets for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		//wp_enqueue_style( $this->ale_prom_import, plugin_dir_url( __FILE__ ) . 'css/ale-prom-import-public.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {
		
		//wp_enqueue_script( $this->ale_prom_import, plugin_dir_url( __FILE__ ) . 
		//	'js/ale-prom-import-public.js', array( 'jquery' ), $this->version, false );
	}

	public function plugin_init() {
			
	}

	public function init() {
		
	}

	public function wp() {
		
	}

}
