(function (window, document, $) {
  $('.woocommerce-save-button').hide();

  const BASE_SHORT = window.twse__settings.BASE_SHORT;

  const btn_addProvider = $(`#${BASE_SHORT}_provider_add`);
  const btn_saveProviders = $(`#${BASE_SHORT}_providers_save`);
  const tmpl_rowEdit = $(`#tmpl-${BASE_SHORT}_providers_row_edit`);
  const tbody = $(`#${BASE_SHORT}_providers_rows`);

  let maxCurrentId = 0;

  let otherEnabledButtons = null;
  $('input, select').live('focus', () => {
    otherEnabledButtons = $('#mainform').find('button:enabled').not(`.${BASE_SHORT}_settings_btn`);
    otherEnabledButtons && otherEnabledButtons.attr('disabled', true);
  }).live('blur', () => {
    otherEnabledButtons && otherEnabledButtons.attr('disabled', false);
  });

  $(`.${BASE_SHORT}_providers_rows`).children().live('keyup', (e) => {
    if (e.key === 'Enter') {
      e.stopImmediatePropagation();
      btn_saveProviders.trigger('click');
    }
  });

  // print all current rows && set maxCurrentId

  if (window.twse__settings.providers && window.twse__settings.providers.length > 0) {
    window.twse__settings.providers.forEach((provider) => {
      if (provider && provider.provider_id > maxCurrentId) {
        maxCurrentId = provider.provider_id;
      }

      const id = `${BASE_SHORT}_provider_${provider.provider_id}`;
      const html = tmpl_rowEdit.html().split('{{provider_id}}').join(id);
      tbody.append(html);

      // set setting values
      $(`#${BASE_SHORT}_provider_${provider.provider_id}_status`).val(provider.status);
      $(`#${BASE_SHORT}_provider_${provider.provider_id}_provider`).val(provider.provider);
      $(`#${BASE_SHORT}_provider_${provider.provider_id}_url`).val(provider.tracking_url);
      $(`#${BASE_SHORT}_provider_${provider.provider_id}_days`).val(provider.estimated_delivery_days);
      $(`#${BASE_SHORT}_provider_${provider.provider_id}_days_type`).val(provider.estimated_delivery_days_type);
      $(`#${BASE_SHORT}_provider_${provider.provider_id}_order_status`).val(provider.order_status_after_email);

      if (provider.tracking_url.indexOf('{{TRACKING_NUMBER}}') === -1) {
        $(`#${BASE_SHORT}_provider_${provider.provider_id}_url_help`).show();
      } else {
        $(`#${BASE_SHORT}_provider_${provider.provider_id}_url_help`).hide();
      }
    });
  }

  // helper functions - END

  btn_addProvider.click(() => {
    const id = `${BASE_SHORT}_provider_${++maxCurrentId}`;
    const html = tmpl_rowEdit.html().split('{{provider_id}}').join(id);
    tbody.append(html);
  });

  $(`.${BASE_SHORT}_provider_remove`).live('click', (e) => {
    const id = $(e.target).data('id');
    $(`#${id}_row`).remove();
  });

  $(`.${BASE_SHORT}_tracking_url`).live('keyup', (e) => {
    const target = $(e.target);
    const id = target.data('id');

    if (target.val().indexOf('{{TRACKING_NUMBER}}') === -1) {
      $(`#${id}_url_help`).fadeIn();
    } else {
      $(`#${id}_url_help`).fadeOut();
    }
  });

}(window, document, jQuery));