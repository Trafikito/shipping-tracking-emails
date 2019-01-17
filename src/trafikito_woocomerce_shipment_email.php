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

      add_action('admin_enqueue_scripts', array($this, 'register_scripts'));
//      add_action('wp_ajax_' . self::BASE_FULL . '_send_tracking', array($this, 'send_tracking'));
//      add_action('wp_ajax_' . self::BASE_FULL . '_add_provider', array($this, 'add_provider'));
//      add_action('wp_ajax_' . self::BASE_FULL . '_validate_provider_name', array($this, 'validate_provider_name'));
//      add_action('wp_ajax_' . self::BASE_FULL . '_update_provider', array($this, 'update_provider'));
//      add_action('wp_ajax_' . self::BASE_FULL . '_delete_provider', array($this, 'delete_provider'));
//      add_action('wp_ajax_' . self::BASE_FULL . '_get_info_by_id', array($this, 'get_info_by_id'));
//      add_action('wp_ajax_' . self::BASE_FULL . '_update_order_provider', array($this, 'update_order_provider'));

      add_filter('woocommerce_get_sections_email', array($this, 'settings_section'));
      add_action('woocommerce_settings_tabs_email', array($this, 'settings_tab'));
    }

    public function settings_section($settings)
    {
      $settings['shipping_tracking_email'] = __('Shipping tracking', self::BASE_SHORT);
      return $settings;
    }

    public function plugin_action_links($links)
    {
      $mylinks = array(
        '<a href="' . admin_url('admin.php?page=wc-settings&tab=email&section=shipping_tracking_email') . '">Settings</a>',
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

    public function register_scripts()
    {
      wp_enqueue_script('jquery-ui-datepicker');
      wp_enqueue_script(self::BASE_SHORT . '-settings', $this->url . 'js/settings.js', array('jquery'), rand(0, 99999), true);
//      wp_enqueue_style(self::BASE_SHORT . '-style', $this->url . 'css/style.css', array());
      wp_localize_script(
        self::BASE_SHORT . '-settings',
        self::BASE_SHORT,
        array(
          'ajaxurl' => admin_url('admin-ajax.php'),
          'form_validation_error' => __('Please Fill all the fields', self::BASE_SHORT),
          'Off' => __('Off', self::BASE_SHORT),
          'On' => __('On', self::BASE_SHORT),
          'tracking_sent' => __('Order tracking sent.', self::BASE_SHORT)
        ));
    }

    // PLUGIN install-uninstall end

    public function admin_notice_error()
    {
      $message = __('WooCommerce: email with shipment link requires WooCommerce to be activated', self::BASE_SHORT);
      printf(
        '
        <div class="notice notice-warning">
          <p><strong>%s</strong> <a href="./plugins.php">view plugins</a></p>
        </div>
     ',
        $message);
    }

    private function show_settings_provider_edit()
    {
      echo 'edit one!';

    }

    private function show_settings_providers_table()
    {
      $providers = self::getProviders(true);
      $baseShort = self::BASE_SHORT;
      $orderStatuses = wc_get_order_statuses();
      wp_nonce_field(self::BASE_SHORT . 'update_providers', self::BASE_SHORT . 'n');
      include_once dirname(__FILE__) . '/views/html-admin-page-email-shipping-email.php';
    }

    public function settings_tab()
    {
      echo json_encode($_POST);

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

        // save all providers
        foreach ($providerIds as $providerId) {
          // todo build array of providers and save it to db
          echo "<div> providerId >>>> $providerId</div>";
        }
      }

      $this->show_settings_providers_table();
    }


    //
    //
    //
    //
    //
    //
    //
    //
    //
    //
    //
    //
    //
    //
    //
    //
    //
    //d


    // META BOX at order view start

    public function adding_meta_boxes()
    {
      add_meta_box(
        self::BASE_SHORT . '-shipment-tracking',
        __('Send shipping email', self::BASE_SHORT),
        array($this, self::BASE_FULL . '_order_box_callback'),
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

      if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
      if (!current_user_can('edit_post', $post_id)) {
        add_action('admin_notices', array($this, 'admin_notice_no_permission_to_edit'));
        return;
      };

      $provider_id = $_POST[self::BASE_FULL . '_provider_id'];
      $tracking_number = $_POST[self::BASE_FULL . '_tracking_number'];
      $timestamp_shipped = $_POST[self::BASE_FULL . '_timestamp_shipped'];
      $delivery_days = $_POST[self::BASE_FULL . '_delivery_days'];
      $delivery_days_type = $_POST[self::BASE_FULL . '_delivery_days_type'];

      if (isset($provider_id)) {
        update_post_meta($post_id, self::BASE_FULL . '_provider_id', sanitize_text_field($provider_id));
      }

      if (isset($tracking_number)) {
        update_post_meta($post_id, self::BASE_FULL . '_tracking_number', sanitize_text_field($tracking_number));
      }

      if (isset($timestamp_shipped)) {
        update_post_meta($post_id, self::BASE_FULL . '_timestamp_shipped', sanitize_text_field($timestamp_shipped));
      }

      if (isset($delivery_days)) {
        update_post_meta($post_id, self::BASE_FULL . '_delivery_days', sanitize_text_field($delivery_days));
      }

      if (isset($delivery_days_type)) {
        update_post_meta($post_id, self::BASE_FULL . '_delivery_days_type', sanitize_text_field($delivery_days_type));
      }

      $return['msg'] = '';
      $errors = false;

      if ($provider_id === '') {
        $errors = true;
        $return['msg'] .= __('Please select a provider', self::BASE_SHORT) . '\n';
      }
      if ($tracking_number === '') {
        $errors = true;
        $return['msg'] .= __('Please add tracking number', self::BASE_SHORT) . '\n';
      }
      if ($timestamp_shipped === '') {
        $errors = true;
        $return['msg'] .= __('Please set date when it was shipped', self::BASE_SHORT) . '\n';
      }
      if ($delivery_days === '') {
        $errors = true;
        $return['msg'] .= __('Please set how long delivery will take', self::BASE_SHORT) . '\n';
      }
      if ($delivery_days_type === '') {
        $errors = true;
        $return['msg'] .= __('Please select type of days delivery will take', self::BASE_SHORT) . '\n';
      }

      if ($errors === false) {
        $order = new WC_Order($post_id);
        $order_data = $order->get_data();

        $customer_email = $order_data['billing']['email'];
        $provider = $this->providers[array_search($provider_id, array_column($this->providers, 'id'))];
        $provider_name = $provider['provider'];
        $tracking_url = '<a target="_blank" href="' . $provider['tracking_url'] . '">' . $tracking_number . '</a>';
        $tracking_url = str_replace('{{TRACKING_NUMBER}}', $tracking_url);
        $date_shipped = $provider['delivery_days'];

        // $order->update_status( $this->order_status );
        $email_html = 'Please find your Order shipment details below, you can click on the Tracking Number to track your order.';
        $email_html .= '<h3>Shipping Tracking</h3>';

        $email_html .= '<table cellpadding="20">
                  <tr style="background-color:#c6c6c6;">
                    <th>Provider Name</th>
                    <th>Tracking Number</th>
                    <th>Date Shipped</th>
                    <th>Estimated Delivery</th>                    
                  </tr>
                  <tr style="background-color:#0000000f;">
                    <td style="border-bottom: 1px solid #eded;">' . __($provider_name, self::BASE_SHORT) . '</td>
                    <td style="border-bottom: 1px solid #eded;">' . $tracking_url . '</td>
                    <td style="border-bottom: 1px solid #eded;">' . $date_shipped . '</td>
                  </tr>
                  </table>
                  ';
        $url = network_site_url('/');
        $end_email = preg_replace('#^https?://#', '', $url);
        $from_email = 'info@' . $end_email;
        $to = $customer_email;
        $subject = get_bloginfo('name') . ' Order Shippment Tracking';
        $headers = array('Content-Type: text/html; charset=UTF-8', 'From: ' . get_bloginfo('name') . ' &lt;' . $from_email);
        $success_true = wp_mail($to, $subject, $email_html, $headers);

        if ($success_true):
//          $note = __('Email with shipping tracking information was sent. ' . $formatted_track);
//          $order->add_order_note($note);
//          $order->save();
        endif;
      }
    }

    public function trafikito_woocomerce_shipment_email_order_box_callback($post)
    {
      wp_nonce_field('trafikito_shipment_link_shipment_tracking_data', 'trafikito_shipment_link_shipment_tracking_nonce');

      $provider_id = get_post_meta($post->ID, self::BASE_FULL . '_provider_id', true);
      $tracking_number = get_post_meta($post->ID, self::BASE_FULL . '_tracking_number', true);
      $timestamp_shipped = get_post_meta($post->ID, self::BASE_FULL . '_timestamp_shipped', true);
      $delivery_days = get_post_meta($post->ID, self::BASE_FULL . '_delivery_days', true);
      $delivery_days_type = get_post_meta($post->ID, self::BASE_FULL . '_delivery_days_type', true);

      $date_shipped = ''; // todo convert timestamp to $date_shipped YYYY-MM-DD
      $debug_providers = [];
      ?>
      <div id="<?= self::BASE_FULL ?>_metabox">
        <p>
        <div>$provider_id:: <?= $provider_id ?></div>
        <div>$tracking_number:: <?= $tracking_number ?></div>
        <div>$timestamp_shipped:: <?= $timestamp_shipped ?></div>
        <div>$delivery_days:: <?= $delivery_days ?></div>
        <div>$delivery_days_type:: <?= $delivery_days_type ?></div>

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
          <label for="<?= self::BASE_FULL . '_delivery_days' ?>">
            <strong><?php _e('Estimated Delivery', self::BASE_SHORT) ?>:</strong>
          </label>
          <br/>
          <select name="<?= self::BASE_FULL . '_delivery_days' ?>" id="<?= self::BASE_FULL . '_delivery_days' ?>">
            <?php for ($i = 1; $i <= 100; $i++): ?>
              <option value="<?= $i ?>" <?php selected($delivery_days, $i); ?>>
                <?= $i; ?>
              </option>
            <?php endfor; ?>
          </select>
          <select name="<?= self::BASE_FULL . '_delivery_days_type' ?>" id="calender-work-days">
            <option value="calendar_days" <?php selected($delivery_days_type, 'calendar_days'); ?>>
              <?php _e('Calendar days', self::BASE_SHORT); ?>
            </option>
            <option value="workdays" <?php selected($delivery_days_type, 'workdays'); ?>>
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
        || get_post_meta($order_id, self::BASE_FULL . '_delivery_days', true) == ''
        || get_post_meta($order_id, self::BASE_FULL . '_delivery_days_type', true) == ''
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

    /*
    * AJAX handler
    *
    * Function Adding new Provider to the existing provider list
    */

    public function add_provider()
    {
      $shipping_provider = sanitize_text_field($_POST["shipping_provider"]);
      $status = sanitize_text_field($_POST["status"]);
      $tracking_url = sanitize_text_field($_POST["shipping_tracking_url"]);
      $estimated_delivery_days = sanitize_text_field($_POST["estimated_delivery_days"]);
      $calendar_work_days = sanitize_text_field($_POST["calender_work_days"]);
      $new_order_status = sanitize_text_field($_POST["new_order_status"]);

      if (!empty($this->providers)) :
        end($this->providers);
        $key = key($this->providers);

        foreach ($this->providers as $current_key => $provider) :
          if ($key == $current_key):
            $last_provider_id = $provider['id'];
          else:
            $last_provider_id = 0;
          endif;
        endforeach;

        $key = $key + 1;
      else:
        $key = 0;
      endif;

      $this->providers[$key]['id'] = $last_provider_id + 1;
      $this->providers[$key]['provider'] = $shipping_provider;
      $this->providers[$key]['status'] = $status;
      $this->providers[$key]['tracking_url'] = $tracking_url;
      $this->providers[$key]['estimated_delivery_days'] = $estimated_delivery_days;
      $this->providers[$key]['estimated_delivery_days_type'] = $calendar_work_days;
      $this->providers[$key]['tracking_number'] = '';
      $this->providers[$key]['new_order_status'] = $new_order_status;

      update_option(self::BASE_FULL . '_providers', $this->providers);
      die;
    }


    /*
    * AJAX handler
    *
    * delete shipping provider ajax call function
    */
    public function delete_provider()
    {
      $key = sanitize_text_field($_POST["key"]);
      unset($this->providers[$key]);

      update_option(self::BASE_FULL . '_providers', array_values($this->providers));
      die();
    }

    /*
    * AJAX handler
    *
    * Update shipping provider based on the key passed
    */
    public function update_provider()
    {
      $shipping_provider = sanitize_text_field($_POST["shipping_provider"]);
      $status = sanitize_text_field($_POST["status"]);
      $tracking_url = sanitize_text_field($_POST["shipping_tracking_url"]);
      $estimated_delivery_days = sanitize_text_field($_POST["estimated_delivery_days"]);
      $calendar_work_days = sanitize_text_field($_POST["calender_work_days"]);
      $new_order_status = sanitize_text_field($_POST["new_order_status"]);
      $key = sanitize_text_field($_POST["key"]);

      $estimated_delivery = $estimated_delivery_days . ' ' . $calendar_work_days;

      $this->providers[$key]['provider'] = $shipping_provider;
      $this->providers[$key]['status'] = $status;
      $this->providers[$key]['tracking_url'] = $tracking_url;
      $this->providers[$key]['estimated_delivery'] = $estimated_delivery;
      $this->providers[$key]['new_order_status'] = $new_order_status;

      update_option(self::BASE_FULL . '_providers', $this->providers);
      die();
    }

    /*
    public function edit_shipping_provider()
    {
      ?>
      <div class="wrap <?= self::BASE_SHORT ?>-shippment-block edit-shipping-provider-block">
        <?php
        if (!empty($this->providers)):
          $current_provider = array();
          foreach ($this->providers as $key => $provider):
            if ($provider['id'] == $_GET['id']):
              $current_provider = $provider;
              $current_key = $key;
              continue;
            endif;
          endforeach;
        endif;
        ?>
        <h1 class="wp-heading-inline">
          <?php _e('Edit Provider:', self::BASE_SHORT) ?>
          <?php echo $current_provider['provider']; ?>
        </h1>

        <div class="components-notice-list delete-success" style="display:none">
          <div class="components-notice is-success is-dismissible">
            <div class="components-notice__content">
              <?php _e('Provider has been deleted successfully! ', self::BASE_SHORT); ?>
              <a href="<?php echo $this->manage_providers; ?>">View all providers
              </a>
            </div>
          </div>
        </div>
        <div class="components-notice-list update-success" style="display:none">
          <div class="components-notice is-success is-dismissible">
            <div class="components-notice__content">
              <?php _e('Provider has been updated successfully! ', self::BASE_SHORT); ?>
              <a href="<?php echo $this->manage_providers; ?>">View all providers
              </a>
            </div>
          </div>
        </div>
        <form action="" method="post" class="add-new-provider-form">
          <table class="<?= self::BASE_SHORT ?>form-table">
            <tbody>
            <tr class="form-field form-required">
              <td scope="row">
                <label for="user_login">
                  <?php _e('Provider', self::BASE_SHORT); ?>
                </label>
                <br/>
                <input name="shipping_provider" type="text" id="shipping-provider"
                       value="<?php _e($current_provider['provider'], self::BASE_SHORT); ?>"
                       aria-required="true"
                       autocapitalize="none" autocorrect="off" maxlength="80">
                <p class="<?= self::BASE_SHORT ?>-error-message empty-provider">
                  <?php _e('Shipping Provider cannot be empty!', self::BASE_SHORT); ?>
                </p>
                <p class="error-message-gb-plugin duplicate-error">
                  <?php _e('Shipping Provider already Exist!', self::BASE_SHORT); ?>
                </p>
              </td>
            </tr>
            <tr class="form-field form-required">
              <td scope="row">
                <?php _e('Status', self::BASE_SHORT); ?>
                <span
                    class="state-switch" <?php echo (strtolower($current_provider['status']) == 'on') ? 'style="color:green"' : 'style="color:red"' ?>>
                  <?php (strtolower($current_provider['status']) == 'on') ? _e('On', self::BASE_SHORT) : _e('Off', self::BASE_SHORT); ?>
                </span>
                <br/>
                <label class="switch">
                  <input type="checkbox" name="status"
                         value="<?php echo (strtolower($current_provider['status']) == 'on') ? 'on' : 'off'; ?>"
                         id="status-toggle"
                    <?php if (strtolower($current_provider['status']) == 'on') echo 'class="checked" checked'; ?>>
                  <span class="slider">
                  </span>
                </label>
              </td>
            </tr>
            <tr class="form-field">
              <td scope="row">
                <label for="shipping_tracking_url">
                  <?php _e('Tracking Url (Add {{TRACKING_NUMBER}} and it will be replaced by submitted tracking number)', self::BASE_SHORT); ?>
                </label><br>
                <i>
                  <?php _e('DHL example http://dhl.com/en/express/tracking.html?AWB={{TRACKING_NUMBER}}&brand=DHL', self::BASE_SHORT); ?>
                  <i><br/>
                    <input name="shipping_tracking_url" id="shipping-tracking-url" type="text"
                           value="<?php _e($current_provider['tracking_url'], self::BASE_SHORT); ?>"
                           type="text"
                           value="" aria-required="true" autocapitalize="none" autocorrect="off"
                           maxlength="80">
                    <p class="tracking-num-notify">
                      <?php _e('{{TRACKING_NUMBER}} is not used! Url will not contain tracking number and user will have to enter it manually.', self::BASE_SHORT); ?>
                    </p>
                    <p class="error-message-gb-plugin tracking_url_error">
                      <?php _e('Field cannot be cannot be empty!', self::BASE_SHORT); ?>
                    </p>
              </td>
            </tr>
            <tr class="form-field">
              <td scope="row">
                <label for="days">
                  <?php _e('Estimated Delivery', self::BASE_SHORT); ?>
                </label>
                <br/>
                <?php
                $estimate_br = explode(' ', $current_provider['estimated_delivery']);
                if (strpos($current_provider['estimated_delivery'], 'workdays')):
                  $Workdays_true = 'selected';
                else:
                  $Calendar_true = 'selected';
                endif;
                ?>
                <select name="estimated_delivery_days">
                  <?php for ($i = 1; $i <= 30; $i++): ?>
                    <option value="<?php echo $i; ?>"
                      <?php if ($estimate_br[0] == $i) echo 'selected'; ?>>
                      <?php echo $i; ?>
                    </option>
                  <?php endfor; ?>
                </select>
                <select name="calender_work_days" id="calender-work-days">
                  <option value="workdays" <?php echo $Workdays_true; ?>>
                    <?php _e('Workdays', self::BASE_SHORT); ?>
                  </option>
                  <option value="calendar_days" <?php echo $Calendar_true; ?>>
                    <?php _e('Calendar days', self::BASE_SHORT); ?>
                  </option>
                </select>
                <!--    <p class="error-message-gb-plugin cal-error">
                  <?php //_e('Please select Calendar or Workdays',self::BASE_SHORT);
                ?>
                </p> -->
              </td>
            </tr>
            <tr class="form-field">
              <td scope="row">
                <label for="order_status">
                  <?php _e('New order status after email is sent', self::BASE_SHORT); ?>
                </label><br>
                <?php
                $new_order_status = str_replace('_', ' ', strtolower($current_provider['new_order_status']));

                $order_status = array(__('Pending payment', self::BASE_SHORT), __('Processing', self::BASE_SHORT), __('On hold', self::BASE_SHORT), __('Completed', self::BASE_SHORT), __('Cancelled', self::BASE_SHORT), __('Failed', self::BASE_SHORT)); ?>

                <select name="new_order_status" id="new-order-status">
                  <option value='No change'>*
                    <?php _e('Do not change order status automatically', self::BASE_SHORT); ?>*
                  </option>
                  <?php
                  $new_order_status = str_replace(' ', '_', strtolower($current_provider['new_order_status']));
                  $order_status = array(__('Pending payment', self::BASE_SHORT), __('Processing', self::BASE_SHORT), __('On hold', self::BASE_SHORT), __('Completed', self::BASE_SHORT), __('Cancelled', self::BASE_SHORT), __('Failed', self::BASE_SHORT)); ?>

                  <?php
                  foreach ($order_status as $status):
                    $status_value = str_replace(' ', '_', strtolower($status));
                    if (strcmp(strtolower($status_value), strtolower($new_order_status)) == 0): ?>
                      <option value="<?php echo $status_value; ?>" selected>
                        <?php _e($status, self::BASE_SHORT); ?>
                      </option>
                    <?php else: ?>
                      <option value="<?php echo $status_value; ?>">
                        <?php _e($status, self::BASE_SHORT); ?>
                      </option>
                    <?php
                    endif;
                  endforeach; ?>
                </select>
              </td>
            </tr>
            <tr>
              <td>
                <input type="hidden" class="list-key" name="key" value="<?php echo $current_key; ?>">
                <a href="javascript:void()" class="action-button update-shipping-provider">
                  <?php _e('Update', self::BASE_SHORT); ?>
                </a>
                <a class="delete-shipping-provider action-button" href="javascript:void()">
                  <?php _e('Delete Permanently', self::BASE_SHORT); ?>
                </a>
                <span class="spinner update-spinner">
                </span>
              </td>
            </tr>
            </tbody>
          </table>
        </form>
      </div>
      <?php
    }
    */

  }

  Trafikito_woocomerce_shipment_email::get_instance();
}
