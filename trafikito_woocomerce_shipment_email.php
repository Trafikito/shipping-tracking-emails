<?php
defined('ABSPATH') or exit;

/*
* Plugin Name: WooCommerce: email with shipment link
* Description: WooCommerce plugin to send emails with shipping tracking information.
* Author: <a href="https://trafikito.com/?utm_source=wp_shipping_track_plugin&utm_medium=author&utm_campaign=desc_brand">Trafikito.com</a> - get free notifications when your server is going out of resources. <a href="https://trafikito.com/?utm_source=wp_shipping_track_plugin&utm_medium=author&utm_campaign=desc_install">Install now</a> for free.
* Author URL:  https://trafikito.com/
* Text Domain: trafikito_woocomerce_shipment_email_
*/

if (!class_exists('Trafikito_woocomerce_shipment_email')) {


  register_activation_hook(__FILE__, array('Trafikito_woocomerce_shipment_email', 'plugin_activate'));
  register_deactivation_hook(__FILE__, array('Trafikito_woocomerce_shipment_email', 'plugin_deactivate'));
  register_uninstall_hook(__FILE__, array('Trafikito_woocomerce_shipment_email', 'plugin_uninstall'));


  class Trafikito_woocomerce_shipment_email
  {
    const VERSION = '1.1';
    const BASE_FULL = 'trafikito_woocomerce_shipment_email';
    // plugin main file: self::BASE_FULL/self::BASE_FULL.php
    const BASE_SHORT = 'twse_';
    private static $instance;

    protected $url = '';
    protected $path = '';
    protected $basename = '';
    public $providers = null;
    public $order_status = array();

    public function __construct()
    {

      $this->basename = dirname(plugin_basename(__FILE__));
      $this->url = plugin_dir_url(__FILE__);
      $this->path = plugin_dir_path(__FILE__);

      add_action('plugins_loaded', array($this, 'add_hooks'));
      load_plugin_textdomain(self::BASE_SHORT, false, $this->basename . '/languages/');
    }

    public static function get_instance()
    {
      if (!isset(self::$instance)) {
        self::$instance = new self();
      }
      return self::$instance;
    }

    private function getProviders($refresh)
    {
      // load only when needed. No need to load on each pageview.
      if ($this->providers == null || $refresh === true) {
        $this->providers = get_option(self::BASE_FULL . '_providers');
      }
      return $this->providers;
    }

    public function add_hooks()
    {
      if (!function_exists('WC')) {
        add_action('admin_notices', array($this, 'admin_notice_error'));
        return;
      }

      add_filter('plugin_action_links_' . self::BASE_FULL . '/' . self::BASE_FULL . '.php', array($this, 'plugin_action_links'), 10, 2);
      add_action('add_meta_boxes', array($this, 'adding_meta_boxes'), 10, 2);
      add_action('save_post', array($this, 'save_meta_boxes'));

      add_action('admin_enqueue_scripts', array($this, 'register_script'));

      add_filter('woocommerce_get_sections_email', array($this, 'settings_section'));
      add_action('woocommerce_settings_tabs_email', array($this, 'settings_tab'));
    }

    public function register_script()
    {

      if (isset($_GET['section']) && $_GET['section'] === 'shipping_tracking_email' && isset($_GET['tab']) && $_GET['tab'] === 'email') {
        wp_enqueue_script(self::BASE_SHORT . '-settings', $this->url . 'js/settings.js', array('jquery'), '1.0.0', true);
      }
      if (isset($_GET['post']) && isset($_GET['action']) && $_GET['action'] === 'edit') {
        wp_enqueue_script('jquery-ui-datepicker');
        wp_enqueue_script(self::BASE_SHORT . '-metabox', $this->url . 'js/metabox.js', array('jquery'), '1.0.0', true);
      }
    }

    public function settings_section($settings)
    {
      $settings['shipping_tracking_email'] = __('Shipping tracking', self::BASE_SHORT);
      return $settings;
    }

    public function plugin_action_links($links)
    {
      $mylinks = array(
        '<a href="' . admin_url('admin.php?page=wc-settings&tab=email&section=shipping_tracking_email') . '">' . __('Settings', self::BASE_SHORT) . '</a>',
        '<a target="_blank" href="https://trafikito.com/?utm_source=wp_shipping_track_plugin&utm_medium=author&utm_campaign=action_link">Trafikito</a>',
      );
      return array_merge($links, $mylinks);
    }

    // PLUGIN install-uninstall start

    public static function plugin_activate()
    {
      if (!current_user_can('activate_plugins')) {
        return;
      }

      if (!get_option(self::BASE_FULL . '_providers')) {
        $list = array(
          array(
            'provider_id' => 1,
            'provider' => 'DHL Express',
            'status' => 'on',
            'tracking_url' => 'http://www.dhl.com/en/express/tracking.html?AWB={{TRACKING_NUMBER}}&brand=DHL',
            'estimated_delivery_days' => 5,
            'estimated_delivery_days_type' => 'workdays',
            'order_status_after_email' => 'wc-completed'
          ),
        );
        add_option(self::BASE_FULL . '_providers', $list);
      }
    }

    public static function plugin_deactivate()
    {
      if (!current_user_can('activate_plugins')) {
        return;
      }
    }

    public static function plugin_uninstall()
    {
      delete_option(self::BASE_FULL . '_providers');
    }

    // PLUGIN install-uninstall end

    public function admin_notice_error()
    {
      $message = __('WooCommerce: email with shipment link requires WooCommerce to be activated', self::BASE_SHORT);
      printf(
        '<div class="notice notice-warning"><p><strong>%s</strong> <a href="' . admin_url('plugins.php') . '">' . __('view plugins', self::BASE_SHORT) . '</a></p></div>',
        $message
      );
    }

    private function show_settings_providers_table()
    {
      if (isset($_GET['section']) && $_GET['section'] === 'shipping_tracking_email' && isset($_GET['tab']) && $_GET['tab'] === 'email') {
        $providers = self::getProviders(true);
        $baseShort = self::BASE_SHORT;
        $orderStatuses = wc_get_order_statuses();
        wp_nonce_field(self::BASE_SHORT . 'update_providers', self::BASE_SHORT . 'n');
        include_once dirname(__FILE__) . '/views/html-admin-page-email-shipping-email.php';
      }
    }

    public function settings_tab()
    {
      $nonce = self::BASE_SHORT . 'n';
      if (isset($_POST[$nonce]) && wp_verify_nonce($_POST[$nonce], self::BASE_SHORT . 'update_providers')) {
        // get all providers IDs
        $providerIds = [];
        $find = 'row__' . self::BASE_SHORT . '_provider_';
        foreach ($_POST as $key => $value) {
          if (strpos($key, $find) !== false) {
            $providerId = str_replace($find, '', $key);
            if (!in_array($providerId, $providerIds)) {
              array_push($providerIds, $providerId);
            }
          }
        }

        $all = array();
        $fieldPrefix = self::BASE_SHORT . '_provider_';

        // save all providers
        foreach ($providerIds as $providerId) {
          $prefix = "$fieldPrefix$providerId";
          array_push($all, array(
            'provider_id' => (int)preg_replace('/\D/', '', $providerId),
            'provider' => sanitize_text_field($_POST["{$prefix}_provider"]),
            'status' => sanitize_text_field($_POST["{$prefix}_status"]),
            'tracking_url' => sanitize_text_field($_POST["{$prefix}_tracking_url"]),
            'estimated_delivery_days' => (int)preg_replace('/\D/', '', $_POST["{$prefix}_days"]),
            'estimated_delivery_days_type' => sanitize_text_field($_POST["{$prefix}_days_type"]),
            'order_status_after_email' => sanitize_text_field($_POST["{$prefix}_order_status"]),
          ));
        }

        update_option(self::BASE_FULL . '_providers', $all);
      }

      $this->show_settings_providers_table();
    }


    // META BOX at order view start

    public function adding_meta_boxes()
    {
      add_meta_box(
        self::BASE_SHORT . '-shipment-tracking',
        __('Send shipping email', self::BASE_SHORT),
        array($this, self::BASE_FULL . '_order_view_metabox'),
        'shop_order',
        'side',
        'high'
      );
    }

    public function admin_notice_no_permission_to_edit()
    {
      $message = __('Email with shipment link was not saved because user has no permission to edit the order.', self::BASE_SHORT);
      echo '<div class="notice notice-error"><p>' . $message . '</p></div>';
    }

    public function save_meta_boxes($post_id)
    {

      if (
        !isset($_POST[self::BASE_SHORT . '_provider'])
        || !isset($_POST[self::BASE_SHORT . '_provider'])
        || !isset($_POST[self::BASE_SHORT . '_tracking_number'])
        || !isset($_POST[self::BASE_SHORT . '_shipped_at'])
        || !isset($_POST[self::BASE_SHORT . '_estimated_days'])
        || !isset($_POST[self::BASE_SHORT . '_estimated_days_type'])
        || !isset($_POST[self::BASE_SHORT . '_order_status'])
      ) {
        return;
      }

      if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
      }
      if (!current_user_can('edit_post', $post_id)) {
        add_action('admin_notices', array($this, 'admin_notice_no_permission_to_edit'));
        return;
      };

      $provider_id = sanitize_text_field($_POST[self::BASE_SHORT . '_provider']);
      $tracking_number = sanitize_text_field($_POST[self::BASE_SHORT . '_tracking_number']);
      $tracking_url = sanitize_text_field($_POST[self::BASE_SHORT . '_url']);
      $shipped_at = sanitize_text_field($_POST[self::BASE_SHORT . '_shipped_at']);
      $estimated_days = sanitize_text_field($_POST[self::BASE_SHORT . '_estimated_days']);
      $estimated_days_type = sanitize_text_field($_POST[self::BASE_SHORT . '_estimated_days_type']);
      $order_status = sanitize_text_field($_POST[self::BASE_SHORT . '_order_status']);

      $all_settings = array(
        'provider_id' => $provider_id,
        'tracking_number' => $tracking_number,
        'tracking_url' => $tracking_url,
        'shipped_at' => $shipped_at,
        'estimated_days' => $estimated_days,
        'estimated_days_type' => $estimated_days_type,
        'order_status' => $order_status,
      );

      $allProviders = $this->getProviders(false);
      $provider_name = '';
      foreach ($allProviders as $singleProvider) {
        if ($singleProvider['provider_id'] == $provider_id) {
          $provider_name = $singleProvider['provider'];
        }
      }

      update_post_meta($post_id, self::BASE_FULL . '_data', json_encode($all_settings));

      $order = new WC_Order($post_id);

      $order_data = $order->get_data();
      $customer_email = $order_data['billing']['email'];
      $from_email = apply_filters('woocommerce_email_from_address', get_option('woocommerce_email_from_address'), $this);

      $email_html = __('Please find your Order shipment details below, you can click on the Tracking Number to track your order.', self::BASE_SHORT);
      $email_html .= '<h3>' . __('Shipping Tracking', self::BASE_SHORT) . '</h3>';

      $days_type = $estimated_days_type === 'calendar_days' ? __('Calendar days', self::BASE_SHORT) : __('Workdays', self::BASE_SHORT);

      $email_html .= '<table cellpadding="20">
                  <tr style="background-color:#c6c6c6;">
                    <th>' . __('Provider Name', self::BASE_SHORT) . '</th>
                    <th>' . __('Tracking Number', self::BASE_SHORT) . '</th>
                    <th>' . __('Date Shipped', self::BASE_SHORT) . '</th>
                    <th>' . __('Estimated Delivery', self::BASE_SHORT) . '</th>                    
                  </tr>
                  <tr style="background-color:#0000000f;">
                    <td style="border-bottom: 1px solid #eded;">' . __($provider_name, self::BASE_SHORT) . '</td>
                    <td style="border-bottom: 1px solid #eded;"><a href="' . $tracking_url . '" target="_blank">' . $tracking_number . '</a></td>
                    <td style="border-bottom: 1px solid #eded;">' . $shipped_at . '</td>
                    <td style="border-bottom: 1px solid #eded;">' . $estimated_days . ' ' . $days_type . '</td>
                  </tr>
                  </table>
                  ';
      $to = $customer_email;
      $subject = get_bloginfo('name') . ': ' . __('Order Tracking', self::BASE_SHORT);
      $headers = array('Content-Type: text/html; charset=UTF-8', 'From: ' . get_bloginfo('name') . ' &lt;' . $from_email);

      // todo switch to woocomerce email template

      wc_mail($to, $subject, $email_html, $headers);

      $note = __('Email with shipping tracking information was sent to %s', self::BASE_SHORT);
      $order->add_order_note(sprintf($note, $to));
      $order->save();

      if ($order_status) {
        $order->update_status($order_status);
      }
    }

    public function trafikito_woocomerce_shipment_email_order_view_metabox($post)
    {
      $baseShort = self::BASE_SHORT;
      $providers = $this->getProviders(false);

      $provider_id = get_post_meta($post->ID, self::BASE_FULL . '_provider_id', true);
      $tracking_number = get_post_meta($post->ID, self::BASE_FULL . '_tracking_number', true);
      $shipped_at = get_post_meta($post->ID, self::BASE_FULL . '_shipped_at', true);
      $estimated_days = get_post_meta($post->ID, self::BASE_FULL . '_estimated_days', true);
      $estimated_days_type = get_post_meta($post->ID, self::BASE_FULL . '_estimated_days_type', true);
      $orderStatuses = wc_get_order_statuses();

      wp_nonce_field(self::BASE_SHORT . 'send', self::BASE_SHORT . 'n');
      include_once dirname(__FILE__) . '/views/html-metabox.php';
    }

    // META BOX AT ORDER end
  }

  $twse = Trafikito_woocomerce_shipment_email::get_instance();
}
