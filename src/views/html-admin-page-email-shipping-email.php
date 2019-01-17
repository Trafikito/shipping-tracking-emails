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
  <?php esc_html_e('Shipping classes', $baseShort); ?>
  <?php echo wc_help_tip(__('Providers from this table are available at WooCommerce order view when selecting order provider to send email with shipping details.', $baseShort)); ?>
</h2>

<div>$providers: <?= json_encode($providers) ?></div>

<table class="widefat" style="padding: 12px 0;">
  <thead>
  <tr>
    <th>
      <strong><?php echo __("Status", $baseShort); ?></strong>
      <?php echo wc_help_tip(__('At order edit view only active providers will be visible.', $baseShort)); ?>
    </th>
    <th><strong><?php echo __("Provider", $baseShort); ?></strong></th>
    <th>
      <strong><?php echo __("Tracking URL", $baseShort); ?></strong>
      <?php echo wc_help_tip(__('If tracking URL includes {{TRACKING_NUMBER}} placeholder, it will be replaced with submitted tracking number. You can submit tracking number at order edit view.', $baseShort)); ?>
    </th>
    <th><strong><?php echo __("Estimated delivery", $baseShort); ?></strong></th>
    <th>
      <strong><?php echo __("New order status", $baseShort); ?></strong>
      <?php echo wc_help_tip(__('New order status after sending email with shipping information.', $baseShort)); ?>
    </th>
  </tr>
  </thead>
  <tfoot>
  <tr>
    <td colspan="5">
      <button
          type="submit"
          name="save"
          class="button button-primary <?php echo $baseShort ?>_save_providers"
          value="<?php esc_attr_e('Save providers', $baseShort); ?>"
          disabled
      >
        <?php esc_html_e('Save providers', $baseShort); ?>
      </button>
      <span style="margin: 6px;"></span>
      <a class="button button-secondary <?php echo $baseShort ?>_provider_add" href="#">
        <?php esc_html_e('Add provider', $baseShort); ?>
      </a>
    </td>
  </tr>
  </tfoot>
  <tbody class="<?php echo $baseShort ?>_providers_rows"></tbody>
</table>

<script type="text/html" id="tmpl-<?php echo $baseShort ?>_providers_rows_blank">
  <tr>
    <td colspan="5">
      <p><?php esc_html_e('No providers have been created.', $baseShort); ?></p></td>
  </tr>
</script>

<script type="text/html" id="tmpl-<?php echo $baseShort ?>_providers_row_view">
  <tr id="<?php echo $baseShort ?>_provider_{{provider_id}}_view">
    <td>{{provider_status}}</td>
    <td>{{provider_name}}</td>
    <td>{{tracking_url}}</td>
    <td>{{estimated_delivery_days}} {{estimated_delivery_days_type}}</td>
    <td>{{order_status_after_email}}</td>
  </tr>
</script>

<script type="text/html" id="tmpl-<?php echo $baseShort ?>_providers_row_edit">
  <tr id="<?php echo $baseShort ?>_provider_{{provider_id}}_edit">
    <td>
      <select id="<?php echo $baseShort ?>_provider_{{provider_id}}_status" name="status">
        <option value="on">On</option>
        <option value="off">Off</option>
      </select>
    </td>
    <td><input type="text" name="provider" id="<?php echo $baseShort ?>_provider_{{provider_id}}_provider"></td>
    <td><input type="text" name="url" id="<?php echo $baseShort ?>_provider_{{provider_id}}_url"></td>
    <td>
      <div style="display: flex; align-items: center;">
        <select id="<?php echo $baseShort ?>_provider_{{provider_id}}_days" name="status">
          <?php for ($n = 0; $n <= 100; $n++): ?>
            <option value="<?php echo $n ?>"><?php echo $n ?></option>
          <?php endfor; ?>
        </select>
        <div style="margin: 0 3px;"/>
        <select id="<?php echo $baseShort ?>_provider_{{provider_id}}_days_type" name="status">
          <option value="workdays"><?php echo __('Workdays', $baseShort) ?></option>
          <option value="calendar_days"><?php echo __('Calendar days', $baseShort) ?></option>
        </select>
      </div>
    </td>
    <td>
      <select id="<?php echo $baseShort ?>_provider_{{provider_id}}_days" name="status">
        <option value=""><?php echo __("Don't change the status", $baseShort); ?></option>
        <option disabled/>
        <?php foreach ($orderStatuses as $status => $title): ?>
          <option value="<?php echo $status ?>"><?php echo $title ?></option>
        <?php endforeach; ?>
      </select>
    </td>
  </tr>
</script>


<script type="text/html" id="SAMPLE_TODO_REMOVE______________________________________________REMOVE________THIS___">
  <?php
  foreach ($shipping_class_columns as $class => $heading) {
    echo '<td class="' . esc_attr($class) . '">';
    switch ($class) {
      case 'wc-shipping-class-name':
        ?>
        <div class="view">
          {{ data.name }}
          <div class="row-actions">
            <a class="wc-shipping-class-edit" href="#"><?php esc_html_e('Edit', 'woocommerce'); ?></a> | <a href="#"
                                                                                                            class="wc-shipping-class-delete"><?php esc_html_e('Remove', 'woocommerce'); ?></a>
          </div>
        </div>
        <div class="edit">
          <input type="text" name="name[{{ data.term_id }}]" data-attribute="name" value="{{ data.name }}"
                 placeholder="<?php esc_attr_e('Shipping class name', 'woocommerce'); ?>"/>
          <div class="row-actions">
            <a class="wc-shipping-class-cancel-edit"
               href="#"><?php esc_html_e('Cancel changes', 'woocommerce'); ?></a>
          </div>
        </div>
        <?php
        break;
      case 'wc-shipping-class-slug':
        ?>
        <div class="view">{{ data.slug }}</div>
        <div class="edit"><input type="text" name="slug[{{ data.term_id }}]" data-attribute="slug"
                                 value="{{ data.slug }}" placeholder="<?php esc_attr_e('Slug', 'woocommerce'); ?>"/>
        </div>
        <?php
        break;
      case 'wc-shipping-class-description':
        ?>
        <div class="view">{{ data.description }}</div>
        <div class="edit"><input type="text" name="description[{{ data.term_id }}]" data-attribute="description"
                                 value="{{ data.description }}"
                                 placeholder="<?php esc_attr_e('Description for your reference', 'woocommerce'); ?>"/>
        </div>
        <?php
        break;
      case 'wc-shipping-class-count':
        ?>
        <a href="<?php echo esc_url(admin_url('edit.php?post_type=product&product_shipping_class=')); ?>{{data.slug}}">{{
          data.count }}</a>
        <?php
        break;
      default:
        do_action('woocommerce_shipping_classes_column_' . $class);
        break;
    }
    echo '</td>';
  }
  ?>
  </tr>
</script>
