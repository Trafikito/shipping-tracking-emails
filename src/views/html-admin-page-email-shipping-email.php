<?php
/**
 * Shipping classes admin
 *
 * @package WooCommerce/Admin/Shipping
 */

if (!defined('ABSPATH')) {
  exit;
}
?>

<h2>
  <?php esc_html_e('Shipping tracking providers', $baseShort); ?>
  <?php echo wc_help_tip(__('Providers from this table are available at WooCommerce order view when selecting order provider to send email with shipping details.', $baseShort)); ?>
</h2>

<script type="text/javascript">
  window.<?php echo $baseShort?>_settings = {
    providers: <?php echo json_encode($providers) ?>,
    BASE_SHORT: "<?php echo $baseShort ?>",
  };
</script>

<table class="widefat" style="padding: 12px 0;">
  <thead>
  <tr>
    <th>
      <strong><?php echo __("Status", $baseShort); ?></strong>
    </th>
    <th><strong><?php echo __("Provider", $baseShort); ?></strong></th>
    <th>
      <strong><?php echo __("Tracking URL", $baseShort); ?></strong>
      <?php echo wc_help_tip(__('If tracking URL includes {{TRACKING_NUMBER}} placeholder, it will be replaced with submitted tracking number. You can submit tracking number at order edit view.', $baseShort)); ?>
    </th>
    <th><strong><?php echo __("Estimated delivery", $baseShort); ?></strong></th>
    <th>
      <strong><?php echo __("Order status after sent", $baseShort); ?></strong>
      <?php echo wc_help_tip(__('New order status after sending email with shipping information.', $baseShort)); ?>
    </th>
    <th></th>
  </tr>
  </thead>
  <tfoot>
  <tr>
    <td colspan="6">
      <button
          type="submit"
          name="save"
          class="button button-primary"
          id="<?php echo $baseShort ?>_providers_save"
          value="<?php esc_attr_e('Save providers', $baseShort); ?>"
      >
        <?php esc_html_e('Save providers', $baseShort); ?>
      </button>
      <span style="margin: 6px;"></span>
      <a class="button button-secondary" href="#" id="<?php echo $baseShort ?>_provider_add">
        <?php esc_html_e('Add provider', $baseShort); ?>
      </a>
    </td>
  </tr>
  </tfoot>
  <tbody id="<?php echo $baseShort ?>_providers_rows"></tbody>
</table>

<script type="text/html" id="tmpl-<?php echo $baseShort ?>_providers_row_edit">
  <tr data-id="{{provider_id}}" id="{{provider_id}}_row" class="<?php echo $baseShort ?>_provider_edit">
    <td>
      <input type="hidden" name="row__{{provider_id}}" value="{{provider_id}}">
      <select id="{{provider_id}}_status" name="{{provider_id}}_status">
        <option value="on">On</option>
        <option value="off">Off</option>
      </select>
    </td>
    <td><input type="text" name="provider" id="{{provider_id}}_provider" name="{{provider_id}}_provider"></td>
    <td>
      <input
          data-id="{{provider_id}}"
          type="text"
          name="{{provider_id}}_tracking_url"
          class="<?php echo $baseShort ?>_tracking_url"
          id="{{provider_id}}_url"
      >
      <p class="help" id="{{provider_id}}_url_help" style="max-width: 220px">
        <?php
        echo __('Use {{TRACKING_NUMBER}} as a placeholder for the tracking number', $baseShort)
        ?>
      </p>
    </td>
    <td>
      <div style="display: flex; align-items: center;">
        <select id="{{provider_id}}_days" name="{{provider_id}}_days">
          <?php for ($n = 0; $n <= 100; $n++): ?>
            <option <?php echo $n === 7 ? 'selected="selected"' : '' ?> value="<?php echo $n ?>">
              <?php echo $n ?>
            </option>
          <?php endfor; ?>
        </select>
        <div style="margin: 0 3px;"/>
        <select id="{{provider_id}}_days_type" name="{{provider_id}}_days_type">
          <option selected="selected" value="workdays"><?php echo __('Workdays', $baseShort) ?></option>
          <option value="calendar_days"><?php echo __('Calendar days', $baseShort) ?></option>
        </select>
      </div>
    </td>
    <td>
      <select id="{{provider_id}}_order_status" name="{{provider_id}}_order_status">
        <option value=""><?php echo __("Don't change the status", $baseShort); ?></option>
        <option disabled/>
        <?php foreach ($orderStatuses as $status => $title): ?>
          <option value="<?php echo $status ?>" <?php echo $status === 'wc-completed' ? 'selected="selected"' : '' ?>>
            <?php echo $title ?>
          </option>
        <?php endforeach; ?>
      </select>
    </td>
    <td>
      <div class="<?php echo $baseShort ?>_btn_remove">
        <a data-id="{{provider_id}}" href="#" class="<?php echo $baseShort ?>_provider_remove">Remove</a>
      </div>
    </td>
  </tr>
</script>
