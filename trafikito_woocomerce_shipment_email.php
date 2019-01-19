<?php
/*
* Plugin Name: WooCommerce: email with shipment link
*
* Description: WooCommerce plugin to send emails with shipping tracking information.
*
* Author: <a href="https://trafikito.com/?utm_source=wp_shipping_track_plugin&utm_medium=author&utm_campaign=desc_brand">Trafikito.com</a> - get free notifications when your server is going out of resources. <a href="https://trafikito.com/?utm_source=wp_shipping_track_plugin&utm_medium=author&utm_campaign=desc_install">Install now</a> for free.
*
* Text Domain: trafikito_woocomerce_shipment_email_
*/

if (!defined('ABSPATH')) {
  exit;
}

if (!class_exists('Trafikito_woocomerce_shipment_email')) {

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

      // basename: trafikito_woocomerce_shipment_email
      // url: http://mamis.local/wp-content/plugins/trafikito_woocomerce_shipment_email/
      // path: /var/www/mamis.lt/wp-content/plugins/trafikito_woocomerce_shipment_email/

//      $this->add_new_provider_path = admin_url('admin.php?page=wc-settings&tab=' . self::BASE_FULL . '&section=add_new_provider');
//      $this->edit_provider = admin_url('admin.php?page=wc-settings&tab=' . self::BASE_FULL . '&section=edit_provider');
//      $this->manage_providers = admin_url('admin.php?page=wc-settings&tab=' . self::BASE_FULL);

      add_action('plugins_loaded', array($this, 'add_hooks'));
      load_plugin_textdomain(self::BASE_SHORT, false, $this->basename . '/languages/');
      register_activation_hook(__FILE__, array($this, 'plugin_activate'));
      register_deactivation_hook(__FILE__, array($this, 'plugin_deactivate'));
//      register_uninstall_hook(__FILE__, self::plugin_uninstall());
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

//      add_action('wp_ajax_' . self::BASE_FULL . '_send_tracking', array($this, 'send_tracking'));
//      add_action('wp_ajax_' . self::BASE_FULL . '_add_provider', array($this, 'add_provider'));
//      add_action('wp_ajax_' . self::BASE_FULL . '_validate_provider_name', array($this, 'validate_provider_name'));
//      add_action('wp_ajax_' . self::BASE_FULL . '_update_provider', array($this, 'update_provider'));
//      add_action('wp_ajax_' . self::BASE_FULL . '_delete_provider', array($this, 'delete_provider'));
//      add_action('wp_ajax_' . self::BASE_FULL . '_get_info_by_id', array($this, 'get_info_by_id'));
//      add_action('wp_ajax_' . self::BASE_FULL . '_update_order_provider', array($this, 'update_order_provider'));


//      do_action( 'admin_enqueue_scripts', string $hook_suffix )

      add_action('admin_enqueue_scripts', array($this, 'register_script'));

//      Notice: wp_enqueue_script was called incorrectly. Scripts and styles should not be registered or enqueued until the wp_enqueue_scripts, admin_enqueue_scripts, or login_enqueue_scripts hooks.
//    Please see Debugging in WordPress for more information. (This message was added in version 3.3.0.) in /var/www/mamis.lt/wp-includes/functions.php on line 4231

      add_filter('woocommerce_get_sections_email', array($this, 'settings_section'));
      add_action('woocommerce_settings_tabs_email', array($this, 'settings_tab'));
    }

    public function register_script()
    {

      if (isset($_GET['section']) && $_GET['section'] === 'shipping_tracking_email' && isset($_GET['tab']) && $_GET['tab'] === 'email') {
        wp_enqueue_script(self::BASE_SHORT . '-settings', $this->url . 'js/settings.js', array('jquery'), rand(0, 99999), true);
      }
      if (isset($_GET['post']) && isset($_GET['action']) && $_GET['action'] === 'edit') {
        wp_enqueue_script('jquery-ui-datepicker');
        wp_enqueue_script(self::BASE_SHORT . '-metabox', $this->url . 'js/metabox.js', array('jquery'), rand(0, 99999), true);
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

    public function plugin_activate()
    {
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

//    public static function plugin_uninstall()
//    {
//      delete_option(self::BASE_FULL . '_providers');
//    }

    public function plugin_deactivate()
    {
      // todo remove this
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

      if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
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

      $email_html = 'Please find your Order shipment details below, you can click on the Tracking Number to track your order.';
      $email_html .= '<h3>Shipping Tracking</h3>';

      $days_type = $estimated_days_type === 'calendar_days' ? __('Calendar days', self::BASE_SHORT) : __('Workdays', self::BASE_SHORT);

      $email_html .= '<table cellpadding="20">
                  <tr style="background-color:#c6c6c6;">
                    <th>Provider Name</th>
                    <th>Tracking Number</th>
                    <th>Date Shipped</th>
                    <th>Estimated Delivery</th>                    
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

      // todo switch to woocomerce email

      $success_true = wp_mail($to, $subject, $email_html, $headers);

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

      /*
            $date_shipped = ''; // todo convert timestamp to $date_shipped YYYY-MM-DD
            $debug_providers = [];
            ?>
            <div id="<?= self::BASE_FULL ?>_metabox">
              <p>
              <div>$provider_id:: <?= $provider_id ?></div>
              <div>$tracking_number:: <?= $tracking_number ?></div>
              <div>$timestamp_shipped:: <?= $timestamp_shipped ?></div>
              <div>$estimated_days:: <?= $estimated_days ?></div>
              <div>$estimated_days_type:: <?= $estimated_days_type ?></div>

              <label
                  for="<?= self::BASE_FULL . '_provider_id' ?>"
                  class="input-text"
              >
                <strong><?= _e('Provider', self::BASE_SHORT) ?>:</strong>
              </label>
              <select
                  name="<?= self::BASE_FULL . '_provider_id' ?>"
                  id="<?= self::BASE_FULL . '_provider_id' ?>"
                  class="gb-field"
              >
                <?php if (!empty($this->providers)) :
                  foreach ($this->providers as $key => $provider) : ?>
                    <?php array_push($debug_providers, $provider) ?>
                    <?php if ($provider['status'] !== 'off'): ?>
                      <option
                          value="<?= $provider['id'] ?>" <?php selected(isset($provider_id) ? $provider_id : '', $provider['id']); ?>>
                        <?= $provider['provider'] ?>
                      </option>
                    <?php endif; ?>
                  <?php endforeach;
                endif ?>
              </select>
              </p>

              <div>$debug_providers:: <?= json_encode($debug_providers) ?></div>

              <p class="trafikito_shipment_link_hidden_fields">
                <label for="<?= self::BASE_FULL . '_tracking_number' ?>">
                  <strong><?php _e('Tracking number', self::BASE_SHORT) ?>:</strong>
                </label>
                <input
                    type="text"
                    class="gb-field"
                    name="<?= self::BASE_FULL . '_tracking_number' ?>"
                    id="<?= self::BASE_FULL . '_tracking_number' ?>"
                    value="<?php if (isset($tracking_number)) echo $tracking_number; ?>"
                />
              </p>
              <p class="tracking-link"><?php echo $this->tracking_link($post->ID); ?></p>

              <label for="<?= self::BASE_FULL . '_timestamp_shipped' ?>" class="input-text">
                <strong><?= _e('When shipped', self::BASE_SHORT) ?>:</strong>
              </label>

              <p class="trafikito_shipment_link_hidden_fields">
                <input
                    type="text"
                    class="gb-field"
                    autocomplete="off"
                    placeholder="<?= _e('When shipped', self::BASE_SHORT) ?>"
                    name="<?= self::BASE_FULL . '_timestamp_shipped' ?>"
                    id="<?= self::BASE_FULL . '_timestamp_shipped' ?>"
                    value="<?php echo($date_shipped ? $date_shipped : date('Y-m-d')) ?>"
                />
              </p>

              <p class="trafikito_shipment_link_hidden_fields">
                <label for="<?= self::BASE_FULL . '_estimated_days' ?>">
                  <strong><?php _e('Estimated Delivery', self::BASE_SHORT) ?>:</strong>
                </label>
                <br/>
                <select name="<?= self::BASE_FULL . '_estimated_days' ?>" id="<?= self::BASE_FULL . '_estimated_days' ?>">
                  <?php for ($i = 1; $i <= 100; $i++): ?>
                    <option value="<?= $i ?>" <?php selected($estimated_days, $i); ?>>
                      <?= $i; ?>
                    </option>
                  <?php endfor; ?>
                </select>
                <select name="<?= self::BASE_FULL . '_estimated_days_type' ?>" id="calender-work-days">
                  <option value="calendar_days" <?php selected($estimated_days_type, 'calendar_days'); ?>>
                    <?php _e('Calendar days', self::BASE_SHORT); ?>
                  </option>
                  <option value="workdays" <?php selected($estimated_days_type, 'workdays'); ?>>
                    <?php _e('Workdays', self::BASE_SHORT); ?>
                  </option>
                </select>
              </p>

              <input type="hidden" class="gb-field" name="<?= self::BASE_FULL . '_ID' ?>"
                     value="<?php echo $post->ID ?>"/>

              <div class="control-actions">
                <a class="metabox-shipping-track" href="<?php echo $this->manage_providers; ?>">
                  <?php _e('Settings', self::BASE_SHORT) ?>
                </a>
                <div class="alignright trafikito_shipment_link_hidden_fields">
                  <button class="button button-primary right " id="save_send">
                    <?php echo($this->validate($post->ID) ? __('Save', self::BASE_SHORT) : __('Save and Send', self::BASE_SHORT)); ?>
                  </button>
                  <span class="spinner"></span>
                </div>
                <br class="clear">
              </div>
            </div>
            <?php
      */
    }

    // META BOX AT ORDER end

    public function email_tracking_link($order_id, $provider_id)
    {

      if (!$this->validate($order_id)) return false;
      $tracking_number = get_post_meta($order_id, 'trafikito_shipment_link_tracking_number', true);
      $key = array_search($provider_id, array_column($this->providers, 'id'));
      return $tracking_url;
    }

    public function tracking_link($order_id)
    {

      if (!$this->validate($order_id)) return false;

      $tracking_provider_id = get_post_meta($order_id, 'trafikito_shipment_link_tracking_provider_id', true);
      $tracking_number = get_post_meta($order_id, 'trafikito_shipment_link_tracking_number', true);
      $key = array_search($tracking_provider_id, array_column($this->providers, 'id'));
      $tracking_url = str_replace("{{TRACKING_NUMBER}}", $tracking_number, $this->providers[$key]['tracking_url']);


      return sprintf(
        '<a href="%s%s" target="_blank">%s</a>',
        esc_url($tracking_url),
        ($this->providers[$key]['add_tracking_url'] == 1 ? $tracking_number : ""),
        __('Check the link', self::BASE_SHORT)
      );

    }

    public function validate($order_id)
    {
      if (get_post_meta($order_id, self::BASE_FULL . '_provider_id', true) == ''
        || get_post_meta($order_id, self::BASE_FULL . '_tracking_number', true) == ''
        || get_post_meta($order_id, self::BASE_FULL . '_timestamp_shipped', true) == ''
        || get_post_meta($order_id, self::BASE_FULL . '_estimated_days', true) == ''
        || get_post_meta($order_id, self::BASE_FULL . '_estimated_days_type', true) == ''
      ) {
        return false;
      } else {
        return true;
      }
    }

    /*
    * AJAX handler
    *
    * Validate if provider already exists.
    */
    public function validate_provider_name()
    {
      $name = sanitize_text_field($_POST["name"]);

      foreach ($this->providers as $provider_array):
        $comparison_result = strcmp($name, $provider_array['provider']);
        if ($comparison_result == 0):
          echo $message = 'Y';
          die;
        endif;
      endforeach;

      if (empty($message)):
        echo $message = 'N';
      endif;
      die;
    }
  }

  Trafikito_woocomerce_shipment_email::get_instance();
}
