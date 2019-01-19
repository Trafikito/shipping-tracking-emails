(function (window, document, $) {
  const BASE_SHORT = 'twse_';

  const providers = window[`${BASE_SHORT}_data`] && window[`${BASE_SHORT}_data`].providers || null;
  const select_provider = $(`#${BASE_SHORT}_provider`);
  const input_tracking_number = $(`#${BASE_SHORT}_tracking_number`);
  const input_shipped_at = $(`#${BASE_SHORT}_shipped_at`);
  const input_estimated_days = $(`#${BASE_SHORT}_estimated_days`);
  const input_estimated_days_type = $(`#${BASE_SHORT}_estimated_days_type`);
  const input_url = $(`#${BASE_SHORT}_url`);
  const select_order_status = $(`#${BASE_SHORT}_order_status`);
  const link_test_url = $(`#${BASE_SHORT}_test_url`);
  const div_test_url_error = $(`#${BASE_SHORT}_test_url_error`);
  const btn_submit = $(`#${BASE_SHORT}_submit`);

  let user_changed_days = false;
  let user_changed_order_status_after_email = false;

  input_estimated_days.on('change', () => {
    user_changed_days = true;
  });

  input_estimated_days_type.on('change', () => {
    user_changed_days = true;
  });

  select_order_status.on('change', () => {
    user_changed_order_status_after_email = true;
  });

  function setTestUrl({provider}) {
    if (!provider) {
      const provider_id = select_provider.val();
      provider = providers && providers.find(
        (row) => parseInt(row.provider_id, 10) === parseInt(provider_id, 10),
      );
    }

    if (provider && provider.tracking_url) {
      let href = provider.tracking_url;
      if (href.indexOf('{{TRACKING_NUMBER}}') !== -1) {
        const tracking_number = input_tracking_number.val();
        if (tracking_number) {
          href = href.split('{{TRACKING_NUMBER}}').join(tracking_number);
          link_test_url.show();
          div_test_url_error.hide();
          btn_submit.prop('disabled', false);
        } else {
          link_test_url.hide();
          div_test_url_error.show();
          btn_submit.prop('disabled', true);
        }
      } else {
        link_test_url.show();
        div_test_url_error.hide();
        btn_submit.prop('disabled', false);
      }

      link_test_url.attr('href', `${href}`);
      input_url.val(href);
    }
  }

  function setValues() {
    const provider_id = select_provider.val();
    const provider = providers && providers.find(
      (row) => parseInt(row.provider_id, 10) === parseInt(provider_id, 10),
    );

    if (provider) {
      if (user_changed_days === false) {
        input_estimated_days.val(provider.estimated_delivery_days);
        input_estimated_days_type.val(provider.estimated_delivery_days_type);
      }
      if (user_changed_order_status_after_email === false) {
        select_order_status.val(provider.order_status_after_email);
      }
      setTestUrl({provider});
    }
  }

  // init
  setValues();

  const now = new Date();
  const datestring = `${now.getFullYear()}-${('0' + (now.getMonth() + 1)).slice(-2)}-${('0' + now.getDate()).slice(-2)}`;

  $(input_shipped_at).val(datestring);
  $(input_shipped_at).datepicker({
    dateFormat: 'yy-mm-dd',
  });

  input_tracking_number.on('keyup', setTestUrl);

  if (providers && providers.length > 0) {
    select_provider.on('change', setValues);
  }

}(window, document, jQuery));