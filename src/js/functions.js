/**
 * gb
 *
 *
 */
(function (window, document, $) {

  let input_date_shipped = $('#trafikito_shipment_link_date_shipped');
  let trafikito_shipment_link_tracking_provider_id = '#trafikito_shipment_link_tracking_provider_id';
  let status_toggle = 'input#status-toggle';
  let shipping_provider = '#shipping-provider';
  let shipping_tracking_url = '#shipping-tracking-url';
  let delete_false = '.delete-false';
  let update_provider = '.update-shipping-provider';
  let delete_shipping_provider = '.delete-shipping-provider';
  let action_delete = '.action-delete';
  let add_shipping_provider = '.add-shipping-provider';

  $(document).on('change', trafikito_shipment_link_tracking_provider_id, function (e) {
    let pr_id = $('#trafikito_shipment_link_tracking_provider_id').val();
    $.ajax({
      type: 'POST',
      url: gb.ajaxurl,
      data: 'pr_id=' + pr_id + '&action=trafikito_shipment_link_get_info_by_id',
      success: function (data) {
        if (data['date'] !== '') {
          $('#trafikito_shipment_link_ship_date').val(data['date']);
          $('#calender-work-days').val(data['day']);
        }
      },
    });
    $('#trafikito_shipment_link_tracking_number').val('');
    $('#trafikito_shipment_link_date_shipped').val('');
    $('#side-sortables p, #side-sortables div').removeClass('trafikito_shipment_link_hidden_fields');
  });

  if ($('#trafikito_shipment_link_tracking_number').val() !== '') {
    $('#side-sortables p, #side-sortables div').removeClass('trafikito_shipment_link_hidden_fields');
  }

  $(document).on('focusin', input_date_shipped, function (e) {
    input_date_shipped.datepicker({
      showButtonPanel: true,
    });
  });

  $(document).on('focusout', shipping_provider, function (e) {
    let element = $(shipping_provider);
    if (element.val() === '') {
      element.removeClass('validate-success');
      $('.empty-provider').fadeIn('slow');
    }
  });

  $(document).on('click', update_provider, function (e) {
    e.preventDefault();
    validate_tracking_url();
    if ($(shipping_tracking_url).hasClass('validate-success')) {
      let data = '';
      $('input, select,checkbox').each(
        function (index) {
          let input = $(this);
          data += input.attr('name') + '=' + input.val();
          data += '&';
        },
      );
      $.ajax({
        type: 'POST',
        url: gb.ajaxurl,
        data: data + 'action=trafikito_shipment_link_update_provider',
        success: function (data) {
          $('.update-success').fadeIn();
        },
      });
    } else {
      alert(gb.form_validation_error);
    }

  });


  $(document).on('keypress', shipping_provider, function (e) {
    setTimeout(function () {
      let element = $(shipping_provider);
      let Y = '';
      let N = '';
      $('.empty-provider').hide('slow');
      $.ajax({
        type: 'POST',
        url: gb.ajaxurl,
        data: 'name=' + element.val() + '&action=validate_provider_name',
        success: function (data) {
          if (data === 'Y') {
            element.removeClass('validate-success');
            $('.duplicate-error').fadeIn();
          } else {
            $('.duplicate-error').fadeOut();
            element.addClass('validate-success');
          }
        },
      });
    }, 1000);
  });


  $(document).on('focusout', shipping_tracking_url, function (e) {
    validate_tracking_url();
  });


  $(document).on('click', add_shipping_provider, function (e) {
    let data = '';
    if ($(shipping_provider).hasClass('validate-success') && $(shipping_tracking_url).hasClass('validate-success')) {
      $('input, select,checkbox').each(
        function (index) {
          let input = $(this);
          data += input.attr('name') + '=' + input.val();
          data += '&';
        },
      );
      $.ajax({
        type: 'POST',
        url: gb.ajaxurl,
        data: data + 'action=add_shipping_provider',
        success: function (data) {
          $('.new-subscriber-success').fadeIn();
        },
      });
    } else {
      alert(gb.form_validation_error);
    }
  });

  $(document).on('click', status_toggle, function (e) {
    if ($(this).hasClass('checked')) {
      $(this).removeClass('checked');
      $(this).val('off');
      $('span.state-switch').css('color', 'red');
      $('span.state-switch').text(gb.Off);
    } else {
      $(this).addClass('checked');
      $(this).val('on');
      $('span.state-switch').css('color', 'green');
      $('span.state-switch').text(gb.On);

    }
  });


  $(document).on('click', action_delete, function (e) {
    if (jQuery(this).parent('td').find('.confirm-delete').length !== 1) {
      $('.confirm-delete').fadeOut('slow');
      jQuery(this).parent('td').prepend('<div class="confirm-delete"><p class="del-confirm">Are you sure?</p><a href="javascript:void()" class="yes delete-shipping-provider">Yes</a><a class="no delete-false" href="javascript:void()">No</a></div>');
    }
  });


  $(document).on('click', delete_false, function (e) {
    if (jQuery(this).parent('td').find('.confirm-delete').length !== 1) {
      $('.confirm-delete').fadeOut('slow').remove();
    }
  });


  $(document).on('click', delete_shipping_provider, function (e) {
    e.preventDefault();
    if ($('.confirm-delete').length !== 0) {
      let parent_div = $(this).parent('.confirm-delete');
      let parent_td = parent_div.parent('td');
      let parent_tr = parent_td.parent('tr');
      let input_value = parent_td.find('input.list-key');
    } else {
      let parent_td = $(this).parent('td');
      let parent_tr = parent_td.parent('tr');
      let input_value = parent_td.find('input.list-key');
    }

    parent_td.find('.spinner').addClass('is-active');
    $.ajax({
      type: 'POST',
      url: gb.ajaxurl,
      data: 'key=' + input_value.val() + '&action=trafikito_shipment_link_delete_provider',
      success: function (data) {
        if (window.location.href.indexOf('&section=edit_provider') > 0) {
          $('.delete-success').show();
        } else {
          $('.tr_key_' + input_value.val()).fadeOut('slow');
        }
        $('.delete-success').show();
        parent_td.find('.spinner').removeClass('is-active');
      },
    });
  });

  function validate_tracking_url() {
    let element = $(shipping_tracking_url);
    if (element.val() === '') {
      $('.tracking_url_error').fadeIn('slow');
    } else {
      $('.tracking_url_error').hide();
      if (element.val().indexOf('{{TRACKING_NUMBER}}') === -1) {
        $('.tracking-num-notify').fadeIn();
        element.removeClass('validate-success');
      } else {
        $('.tracking-num-notify').fadeOut();
        element.addClass('validate-success');
      }
    }
  }

}(window, document, jQuery));