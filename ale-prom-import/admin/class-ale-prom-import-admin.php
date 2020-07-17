<?php


class Ale_Prom_Import_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $ale_prom_import    The ID of this plugin.
	 */
	private $ale_prom_import;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $ale_prom_import       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $ale_prom_import, $version ) {

		$this->ale_prom_import = $ale_prom_import;
		$this->version = $version;
		$this->setings_page = 'ale-prom-import';
		$this->view = new Ale_Prom_Import_View();


		$this->catConfigPath = PROM_IMPORT_SCRIPT_CONFIG_PATH;
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {
		//wp_enqueue_style( $this->ale_prom_import, plugin_dir_url( __FILE__ ) . 'css/ale-prom-import-admin.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {
		
		wp_register_script( $this->ale_prom_import, plugin_dir_url( __FILE__ ) . 'js/ale-prom-import-admin.js', array( 'jquery' ), $this->version, false );
	}

	public function plugin_init() {
		
		add_action('admin_menu', [$this,'prom_import_menu']);
		add_action( 'wp_ajax_inline_save_precent', [$this,'inline_save_precent'] ); 
		add_action( 'wp_ajax_save_default_percent', [$this,'save_default_percent'] ); 
	}
	public function admin_init() {

	}

	public function getDefaultPercentForm() {
		$defaultPercent = get_option('prom_import_default_percent', 0);
		?>
			<div style='display:none;margin:0px' class='default-percent-error notice notice-error'>
				<p></p>
			</div>
			<div style='display:none;margin:0px' class='default-percent-notice notice notice-success'>
				<p></p>
			</div>
			<br>
			<form class='default-percent-form'>
				<?php wp_nonce_field( 'save_default_percent_nonce', '_percent_nonce', false ); ?>
				<label style="display:inline-block;margin-bottom:5px" for='default-percent'>
				<b><?php _e('Наценка по умолчанию', 'ale-prom-import'); ?> </b>
				
				</label><br>
				<input type='text'  name='default-percent' value='<?php echo $defaultPercent ?>'>
				<button class="default-percent-save button button-primary"> <?php _e('Сохранить', 'ale-prom-import');  ?></button>
			</form>
		<?php
	}


	public function save_default_percent() {
		check_ajax_referer( 'save_default_percent_nonce', '_percent_nonce' );
		$percent = isset( $_POST['percent'] ) ? sanitize_text_field($_POST['percent']) : '';
		
		if ( ($percent !== '0') && !floatval($percent)) {
			
			$return = array(
				'result'   => 'error',
				'msg' => __('Не правильное значение для наценки', 'ale-prom-import'),
			);
			wp_send_json( $return );
			wp_die();
		}
		

		$percent = floatval($percent);
		
		$updated = update_option('prom_import_default_percent', $percent);

		$return = array(
			'result'   => 'success',
			'msg' => __('Наценка сохранена', 'ale-prom-import'),
			'val' => $percent,
		);
		wp_send_json( $return );
		wp_die();
	}


	public function inline_save_precent() {
	
		check_ajax_referer( 'taxinlineeditnonce', '_inline_edit' );
	
		if ( ! isset( $_POST['tax_ID'] ) || ! (int) $_POST['tax_ID'] ) {
			wp_die( -1 );
		}

		$taxonomy = sanitize_key( $_POST['taxonomy'] );
		$tax      = get_taxonomy( $taxonomy );
		$category_id = intval(sanitize_key($_POST['tax_ID']));

		$percent = isset($_POST['percent']) ? $_POST['percent'] : 0;

		$percent = floatval(sanitize_text_field($percent));


		if ( ! $tax || !$category_id ) {
			wp_die( -2 );
		}
	
		if ( ! current_user_can( 'edit_term', $category_id ) ) {
			wp_die( -3 );
		}

		$GLOBALS['hook_suffix'] ='toplevel_page_ale-prom-import';

		$newConfElem = new stdClass();
		$category = get_term( $category_id, $taxonomy );
		$foreign_id = get_term_meta($category_id, 'foreign_id', true); 

		$newConfElem->category_id = $category_id;
		$newConfElem->foreign_id = $foreign_id;
		$newConfElem->name = $category->name;
		$newConfElem->percent = $percent;


		$confArr = $this->getCatConfigArr() ;
		$this->setElemToConfig($confArr, $newConfElem);

		if(!file_exists ( $this->catConfigPath )) {
            wp_die( __( 'Item not updated, config file not found' ) );
		}
		
		$jsonStr = json_encode($this->catConfArr,JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
		$updated = file_put_contents($this->catConfigPath, $jsonStr);
		
		
		if ( !$updated ) {
			wp_die( __( 'Item not updated, config file error' ) );
		} 
	
		$wp_list_table = $this->get_list_table( 'ALE_Prom_Categories' );
		$wp_list_table->set_conf_cats($this->catConfArr);
		$level  = 0;
		$wp_list_table->single_row( $category, $level );
		wp_die();
	}

	public function setElemToConfig($confArr, $newConfElem) {
		$updated = false;
		foreach($confArr as &$confElem) {
			if($confElem->category_id == $newConfElem->category_id) {
				$confElem = $newConfElem;
				$updated = true;
			}
		}
		unset($confElem);
		if(!$updated) {
			$confArr[$newConfElem->category_id] = $newConfElem;
		}

		$this->catConfArr = $confArr;
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
        $this->catConfArr = (array)$catConf;
        return $this->catConfArr;
	}


	public function prom_import_menu() {
		add_menu_page( 
			__('Настройи импорта', ALE_CI),
			__('Настройи импорта', ALE_CI),  
			'manage_options', 
			$this->setings_page,
			[$this, 'prom_import_menu_page'], 
			'dashicons-products', 
			120
		);
		
	}

	public function prom_import_menu_page() {
		
		wp_enqueue_script( $this->ale_prom_import );
		$conf = $this->getCatConfigArr() ;
		
		$wp_list_table = $this->get_list_table( 'ALE_Prom_Categories' );
		$wp_list_table->set_conf_cats($conf);
		$wp_list_table->prepare_items();
		

		echo "<h1>";
				_e('Наценки для категорий', 'ale-prom-import'); 
		echo "</h1>";
		$this->getDefaultPercentForm();
		$wp_list_table->display();
		$wp_list_table->inline_edit();
		
	}

	

	function get_list_table( $class, $args = array() ) {
	
		$core_classes = array(
			'ALE_Prom_Categories' => 'terms',
		);
	
		if ( isset( $core_classes[ $class ] ) ) {
			foreach ( (array) $core_classes[ $class ] as $required ) {
				//require_once ABSPATH . 'wp-admin/includes/class-wp-' . $required . '-list-table.php';
			}
			
			$args['screen'] = get_current_screen();
			if(empty($args['screen'])) {
				$args['screen'] = null;
			}  else {
				$args['screen']->post_type = 'product';
				$args['screen']->taxonomy = 'product_cat';
			}
			return new $class( $args );
		}

		return false;
	}

	
	

}

