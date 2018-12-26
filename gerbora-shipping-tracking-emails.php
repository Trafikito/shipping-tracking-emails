<?php
/*
Plugin Name: Woocommeroce Order Tracking Plugin
Description: This Plugin sends customers their order tracking related information and sends them link so that they can track their orders. 
Author: Brst developer
*/


if (!defined('ABSPATH')) {
    exit;
}


if (!class_exists('Gerbora_Shipping_Tracking_Emails')) {
    
    /**
     * gb_Woocommerce_Order_Tracking main class.
     */
    class Gerbora_Shipping_Tracking_Emails
    {
        
        /**
         * Plugin version.
         *
         * @var string
         */
        const VERSION = '1.0';
        
        /**
         * Instance of this class.
         *
         * @var object
         */
        private static $instance;
        
        /**
         * URL of plugin directory
         *
         * @var string
         */
        protected $url = '';
        
        /**
         * Path of plugin directory
         *
         * @var string
         */
        protected $path = '';
        
        /**
         * Plugin basename
         *
         * @var string
         */
        protected $basename = '';
        
        /**
         * Provider list
         *
         * @var array
         */
        public $provider_list = array();
        
        /**
         * Provider list
         *
         * @var array
         */
        public $order_status = array();
        
        /**
         * Initialize the plugin.
         */
        public function __construct()
        {
            
            $this->provider_list = get_option('gb_provider_list');
            $this->basename      = dirname(plugin_basename(__FILE__));
            $this->url           = plugin_dir_url(__FILE__);
            $this->path          = plugin_dir_path(__FILE__);
            $this->order_status  = get_option('gb_order_status') ? str_replace('wc-', '', get_option('gb_order_status')) : 'completed';
            
            add_action('plugins_loaded', array(
                $this,
                'add_hooks'
            ));
            
            load_plugin_textdomain('mwot', false, $this->basename . '/languages/');
            
            register_activation_hook(__FILE__, array(
                $this,
                'plugin_activate'
            ));
            register_deactivation_hook(__FILE__, array(
                $this,
                'plugin_deactivate'
            ));
            
        }
        
        /**
         * Return an instance of this class.
         *
         * @return object A single instance of this class.
         */
        public static function get_instance()
        {
            if (!isset(self::$instance)) {
                self::$instance = new self();
            }
            return self::$instance;
        }
        
        /**
         * Add hooks and filters
         *
         * @return void
         */
        public function add_hooks()
        {
            
            add_action('add_meta_boxes', array(
                $this,
                'adding_meta_boxes'
            ), 10, 2);
            add_action('admin_menu', array(
                $this,
                'gb_create_menu'
            ));
            add_action('save_post', array(
                $this,
                'save_meta_boxes'
            ));
            add_action('admin_enqueue_scripts', array(
                $this,
                'register_script'
            ));
            add_action('wp_ajax_gb_send_tracking', array(
                $this,
                'send_tracking'
            ));
            add_action('wp_ajax_gb_add_provider', array(
                $this,
                'add_provider'
            ));
            add_action('wp_ajax_gb_update_provider', array(
                $this,
                'update_provider'
            ));
            add_action('wp_ajax_gb_delete_provider', array(
                $this,
                'delete_provider'
            ));
            add_action('wp_ajax_gb_update_order_provider', array(
                $this,
                'update_order_provider'
            ));
                       
            add_action('woocommerce_order_details_after_order_table', array(
                $this,
                'add_order_shipment_tracking'
            ), 5);
            if (!function_exists('WC')) {
                add_action('admin_notices', array(
                    $this,
                    'admin_notice_error'
                ));
            }
            
        }
        
        /**
         * Activate the plugin
         *
         * @return void
         */
        public function plugin_activate()
        {
            
            if (empty($this->provider_list)) {
                
                delete_option('gb_provider_list');
                
                $list = array(
                    array(
                        'id' => 1,
                        'provider' => 'DHL Express Shipment',
                        'status' => 'on',
                        'tracking_url' => 'http://dhl.com/#/?s=',
                        'estimated_delivery' => '5 Calendar Days',
                        'tracking_number' => 5343434,
                        'new_order_status' => 'completed'
                    ),
                    array(
                        'id' => 1,
                        'provider' => 'DHL Express Shipment2',
                        'status' => 'on',
                        'tracking_url' => 'http://dhl.com/#/?s=',
                        'estimated_delivery' => '2 Calendar Days',
                        'tracking_number' => 212121,
                        'new_order_status' => 'completed'
                    )
                );
                add_option('gb_provider_list', $list);
                
            }
            
        }
        
        
        
        /**
         * Deactivate the plugin
         * Uninstall routines should be in uninstall.php
         *
         * @return void
         */
        public function plugin_deactivate()
        {
        }
        /**
         * Enqueue scripts required by plugin
         */
        public function register_script()
        {
            wp_enqueue_style('gb-style', $this->url . 'css/style.css', array());
            wp_enqueue_script('jquery-ui-datepicker');
            wp_enqueue_script('jquery-ui-sortable');
            wp_enqueue_script('gb-functions', $this->url . 'js/functions.js', array(
                'jquery'
            ), false, true);
        }
        
        /**
         * AJAX handler
         *
         * post submissions tracking.
         *
         **/
        public function send_tracking()
        {
            
            
        }
        
        
        public function add_provider()
        {
            
            
            
        }
        
        public function delete_provider()
        {
            
            
        }
        
        public function update_provider()
        {
            
            
            
        }
        
        public function update_order_provider()
        {
            
            
            
        }
        
        /**
         * WooCommerce fallback notice.
         *
         * @return string
         */
        
        public function admin_notice_error()
        {
            
            $class   = 'notice notice-error';
            $message = __('Gerbora Shipping Tracking Emails Plugin is enabled but not effective. It requires WooCommerce in order to work.', 'gb');
            
            printf('<div class="%1$s"><p>%2$s</p></div>', $class, $message);
            
        }
        
        /**
         * Store custom field meta box data
         *
         * @param int $post_id The post ID.
         * @link https://codex.wordpress.org/Plugin_API/Action_Reference/save_post
         */
        public function save_meta_boxes($post_id)
        {   
            
        }
        
        /**
         * Add a new settings tab to the WooCommerce settings tabs array.
         *
         * @param array $settings_tabs Array of WooCommerce setting tabs & their labels, excluding the Subscription tab.
         * @return array $settings_tabs Array of WooCommerce setting tabs & their labels, including the Subscription tab.
         */
        public function gb_create_menu()
        {
            if (!current_user_can('manage_options')) {
                wp_die(__('You do not have sufficient permissions to access this page.'));
            }
            add_menu_page('GB Shipping Tracking Emails', 'GB Shipping Tracking Emails', 'manage_options', 'gb_shipping_tracking', array(
                $this,
                'settings_tab'
            ));
            add_submenu_page('gb_shipping_tracking', 'Manage Providers', 'Manage Providers', 'manage_options', 'gb_shipping_tracking');
            add_submenu_page('gb_shipping_tracking', 'Add New Provider', 'Add New Provider', 'manage_options', 'add_new_provider', array(
                $this,
                'add_new_provider'
            ));
            return true;
        }
        
        public function add_new_provider()
        {
		?>
		<div class="wrap">
			<table class="wp-list-table widefat fixed striped pages tabel-shipping-gb">
				<h1 class="wp-heading-inline"><?php _e('Add New Provider');?></h1>	
			</table>
		</div>
		<?php
        }
        
        
        /**
         * Shipping Tracking Page Admin 
         * @uses self::get_settings()
         */
        public function settings_tab()
        {
		?>
		<div class="wrap">
		<table class="wp-list-table widefat fixed striped pages tabel-shipping-gb">
		<h1 class="wp-heading-inline"><?php
		   _e('Shipment Tracking Settings');
		   ?></h1>
		<tbody>
		   <tr valign="top" class="titledesc">
			  <th scope="row"><?php
				 _e('Provider');
				 ?></th>
			  <th scope="row"><?php
				 _e('Status');
				 ?></th>
			  <th scope="row"><?php
				 _e('Has Tracking Url?');
				 ?></th>
			  <th scope="row"><?php
				 _e('Est. Delivery');
				 ?></th>
			  <th scope="row"><?php
				 _e('New Order Status');
				 ?></th>
		   </tr>
		   <div id="provider-sortable">

			<?php
            if (!empty($this->provider_list)):
                echo '<pre>';
                foreach ($this->provider_list as $key => $provider):
				?>
				<tr>
					<td><?php echo $provider['provider'];?></td>
					<td><?php  echo $provider['status'];?></td>
					<td>Yes ( <?php echo $provider['tracking_number'];?> )</td>
					<td><?php echo $provider['estimated_delivery'];?></td>
					<td><?php echo $provider['new_order_status'];?></td>
				</tr>										
				<?php
                endforeach;
            endif;
				?>
				</tbody>
			</table>
			</div>
			<?php
            
        }
    }
    Gerbora_Shipping_Tracking_Emails::get_instance();
}