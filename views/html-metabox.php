<?php

if (!defined('ABSPATH')) {
  exit;
}

$id_provider = "{$baseShort}_provider";
$id_tracking_number = "{$baseShort}_tracking_number";
$id_url = "{$baseShort}_url";
$id_shipped_at = "{$baseShort}_shipped_at";
$id_estimated_days = "{$baseShort}_estimated_days";
$id_estimated_days_type = "{$baseShort}_estimated_days_type";
$id_btn_submit = "{$baseShort}_submit";
$id_order_status = "{$baseShort}_order_status";

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
    <div>
      <label
          style="font-weight: bold"
          for="<?php echo $id_provider ?>"
      >
        <?php echo __('Provider:', $baseShort) ?>
      </label>
    </div>
    <select id="<?php echo $id_provider ?>" name="<?php echo $id_provider ?>" style="width: 100%;">
      <?php foreach ($providers as $provider): ?>
        <option
            value="<?php echo $provider['provider_id'] ?>"><?php echo __($provider['provider'], $baseShort) ?></option>
      <?php endforeach; ?>
    </select>
  </div>

  <label
      style="font-weight: bold"
      for="<?php echo $id_tracking_number ?>"
  >
    <?php echo __('Tracking number', $baseShort) ?>:
  </label>
  <input
      type="text"
      id="<?php echo $id_tracking_number ?>"
      name="<?php echo $id_tracking_number ?>"
      style="width: 100%;"
  />

  <div
      style="display: none; color: #FF9800; font-weight: bold"
      id="<?php echo $baseShort ?>_test_url_error"
  >
    <?php echo __('Insert tracking number', $baseShort) ?>
  </div>

  <input type="hidden" name="<?php echo $id_url ?>" id="<?php echo $id_url ?>" value="">

  <a
      style="display: none;"
      target="_blank"
      href="#"
      id="<?php echo $baseShort ?>_test_url"
  >
    <?php echo __('Test tracking URL', $baseShort) ?>
  </a>

  <div style="margin: 12px 0">
    <label style="font-weight: bold" for="<?php echo $id_shipped_at ?>">
      <?php echo __('When shipped', $baseShort) ?>
      <span style="font-size: .7em">(<?php echo __('YYYY-MM-DD', $baseShort) ?>)</span>
    </label>
    <input
        type="text"
        id="<?php echo $id_shipped_at ?>"
        name="<?php echo $id_shipped_at ?>"
        style="width: 100%;"
    />
  </div>

  <label
      style="font-weight: bold"
      for="<?php echo $id_estimated_days ?>"
  >
    <?php echo __('Estimated delivery', $baseShort) ?>
  </label>
  <div>
    <select id="<?php echo $id_estimated_days ?>" name="<?php echo $id_estimated_days ?>">
      <?php for ($i = 0; $i <= 100; $i++): ?>
        <option value="<?php echo $i ?>"><?php echo $i ?></option>
      <?php endfor; ?>
    </select>
    <select id="<?php echo $id_estimated_days_type ?>" name="<?php echo $id_estimated_days_type ?>">
      <option value="workdays"><?php echo __('Workdays', $baseShort) ?></option>
      <option value="calendar_days"><?php echo __('Calendar days', $baseShort) ?></option>
    </select>
  </div>

  <div style="margin: 12px 0 0 0">
    <label
        style="font-weight: bold"
        for="<?php echo $id_order_status ?>"
    >
      <?php echo __('Order status after sent', $baseShort) ?>
    </label>
    <div>
      <select id="<?php echo $id_order_status ?>" name="<?php echo $id_order_status ?>">
        <option value=""><?php echo __("Don't change the status", $baseShort); ?></option>
        <option disabled/>
        <?php foreach ($orderStatuses as $status_id => $status): ?>
          <option value="<?php echo $status_id ?>"><?php echo $status ?></option>
        <?php endforeach; ?>
      </select>
    </div>
  </div>

  <hr style="margin: 12px -12px"/>
  <div style="display: flex; justify-content: space-between; align-items: center;">
    <a href="<?php echo $url_settings ?>"><?php echo __('Settings', self::BASE_SHORT) ?></a>
    <button class="button button-primary " id="<?php echo $id_btn_submit ?>">
      <?php echo __('Save and Send', self::BASE_SHORT) ?>
    </button>
  </div>
</div>