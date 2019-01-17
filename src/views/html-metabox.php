<?php
/**
 * Shipping classes admin
 *
 * @package WooCommerce/Admin/Shipping
 */

if (!defined('ABSPATH')) {
  exit;
}

$id_provider = "{$baseShort}_provider";
$id_tracking_number = "{$baseShort}_tracking_number";
$id_shipped_at = "{$baseShort}_shipped_at";
$id_estimated_days = "{$baseShort}_estimated_days";
$id_estimated_days_type = "{$baseShort}_estimated_days_type";

$url_settings = admin_url('admin.php?page=wc-settings&tab=email&section=shipping_tracking_email');
?>

<script type="text/javascript">
  window.<?php echo $baseShort?>_data = {
    providers: <?php echo json_encode($providers) ?>,
    BASE_SHORT: "<?php echo $baseShort ?>",
  };
</script>

<div>
  <div style="margin: 12px 0">
    <div><label for="<?php echo $id_provider ?>"><?php echo __('Provider:', $baseShort) ?></label></div>
    <select id="<?php echo $id_provider ?>" style="width: 100%;">
      <?php foreach ($providers as $provider): ?>
        <option value="<?php echo $provider['provider_id'] ?>"><?php echo $provider['provider'] ?></option>
      <?php endforeach; ?>
    </select>
  </div>

  <label for="<?php echo $id_tracking_number ?>"><?php echo __('Tracking number:', $baseShort) ?></label>
  <input type="text" id="<?php echo $id_tracking_number ?>" style="width: 100%;"/>

  <div style="margin: 12px 0">
    <label for="<?php echo $id_shipped_at ?>"><?php echo __('When shipped:', $baseShort) ?></label>
    <input type="text" id="<?php echo $id_shipped_at ?>" style="width: 100%;"/>
  </div>

  <label for="<?php echo $id_estimated_days ?>"><?php echo __('Estimated delivery', $baseShort) ?></label>
  <div>
    <select id="<?php echo $id_estimated_days ?>">
      <?php for ($i = 0; $i <= 100; $i++): ?>
        <option value="<?php echo $i ?>"><?php echo $i ?></option>
      <?php endfor; ?>
    </select>
    <select id="<?php echo $id_estimated_days_type ?>">
      <option value="workdays"><?php echo __('Workdays', $baseShort) ?></option>
      <option value="calendar_days"><?php echo __('Calendar days', $baseShort) ?></option>
    </select>
  </div>
  <hr style="margin: 12px -12px"/>
  <div style="display: flex; justify-content: space-between; align-items: center;">
    <a href="<?php echo $url_settings ?>"><?php echo __('Settings', self::BASE_SHORT) ?></a>
    <button class="button button-primary " id="save_send">
      <?php echo __('Save and Send', self::BASE_SHORT) ?>
    </button>
  </div>
</div>