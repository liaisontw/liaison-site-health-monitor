<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://github.com/liaisontw
 * @since      1.0.0
 *
 * @package    liaison_site_health_monitor
 * @subpackage liaison_site_health_monitor/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    liaison_site_health_monitor
 * @subpackage liaison_site_health_monitor/admin
 * @author     liason <liaison.tw@gmail.com>
 */
if ( ! class_exists( 'LIAISIHM_metrics' ) )
	require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-liaison-site-health-monitor-metrics.php';

class LIAISIHM_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;
	protected $db;
	protected $metrics;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;
		$this->metrics = new LIAISIHM_metrics();	
		
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-liaison-site-health-monitor-db.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-liaison-site-health-monitor-query-profiler.php';

		//in activator
		//register_activation_hook( __FILE__, [ 'LIAISIHM_DB', 'install' ] );

		add_action( 'admin_menu', function() {
			LIAISIHM_Query_Profiler::init();
		});


		add_action( 'admin_menu', array($this, 'admin_menu') );

	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in liaison_site_health_monitor_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The liaison_site_health_monitor_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */
		$file_path = plugin_dir_path( __FILE__ ) . 'css/liaison-site-health-monitor-admin.css';
    	$file_url  = plugin_dir_url( __FILE__ ) . 'css/liaison-site-health-monitor-admin.css';

		/**
		 * 工程品質亮點：自動版本化 (Auto-versioning)
		 * 使用 filemtime 取得檔案最後修改的時間戳。
		 * 這樣只要檔案內容一變，版本號就會變，完全解決快取問題。
		 */
		$version = file_exists( $file_path ) ? filemtime( $file_path ) : $this->version;

		wp_enqueue_style(
			$this->plugin_name, // 標籤名稱
			$file_url,          // 檔案網址
			array(),            // 相依性
			$version,           // 版本號 (這就是解決 Cache 的關鍵)
			'all'               // 媒體類型
		);

		/*
		wp_enqueue_style( 
			$this->plugin_name, 
			plugin_dir_url( __FILE__ ) . 'css/liaison-site-health-monitor-admin.css', 
			array(), 
			$this->version, 
			'all' 
		);
		*/

	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in liaison_site_health_monitor_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The liaison_site_health_monitor_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/liaison-site-health-monitor-admin.js', array( 'jquery' ), $this->version, false );

	}

	/**
     * liaison_site_health_monitor_menu_settings function.
     * Add a menu item
     * @access public
     * @return void
     */
	public function admin_menu() {
		/*
		add_options_page( 'liaison site health monitor Options', 
						  'liaison site health monitor', 
						  'manage_options', 
						  'liaison_site_health_monitor_options', 
						  array(&$this, 'liaison_site_health_monitor_menu_options')				  
		);
		*/
		add_menu_page(
			'Site Health Monitor',
			'Site Health Monitor',
			'manage_options',
			'site-health-monitor',
			array($this, 'render_page_tabs'),
			//'shm_render_admin_page',
			'dashicons-heart',
			60
		);
	}

	public function render_page_tabs() {
		$wp_version = get_bloginfo('version');
		$rows = LIAISIHM_DB::get_top_slow_queries();
	
		$memory = $this->metrics->shm_get_memory_usage();
		$db_time = $this->metrics->shm_get_db_query_time();
		$plugins = $this->metrics->shm_get_active_plugins_count();
		$rest_time = $this->metrics->shm_get_rest_response_time();

		require_once( trailingslashit( dirname( __FILE__ ) ) . 'partials/liaison-site-health-monitor-admin-display.php' );	
	}

	public function liaison_site_health_monitor_menu_options() {
		

	}
}
