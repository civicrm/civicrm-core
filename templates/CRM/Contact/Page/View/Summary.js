// http://civicrm.org/licensing
(function($) {

  var ajaxFormParams = {
    dataType:'json',
    beforeSubmit: function(arr, $form, options) {
      addCiviOverlay($form);
    },
    success: requestHandler,
    error: errorHandler
  };

  function crmFormInline(o) {
    var data = o.data('edit-params');
    if (o.is('.crm-edit-ready .crm-inline-edit') && data) {
      o.animate({height: '+=50px'}, 200);
      data.snippet = 6;
      data.reset = 1;
      o.addClass('form');
      $('.crm-edit-ready').removeClass('crm-edit-ready');
      addCiviOverlay(o);
      $.getJSON(CRM.url('civicrm/ajax/inline', data))
        .fail(errorHandler)
        .done(function(response) {
          removeCiviOverlay(o);
          o.css('overflow', 'hidden').wrapInner('<div class="inline-edit-hidden-content" style="display:none" />').append(response.content);
          // Smooth resizing
          var newHeight = $('.crm-container-snippet', o).height();
          var diff = newHeight - parseInt(o.css('height'), 10);
          if (diff < 0) {
            diff = 0 - diff;
          }
          o.animate({height: '' + newHeight + 'px'}, diff * 2, function() {
            o.removeAttr('style');
          });
          $('form', o).validate(CRM.validate.params);
          ajaxFormParams.data = data;
          $('form', o).ajaxForm(ajaxFormParams);
          o.trigger('crmFormLoad');
        });
    }
  };

  function requestHandler(response) {
    var o = $('div.crm-inline-edit.form');

    if (response.status == 'save' || response.status == 'cancel') {
      o.trigger('crmFormSuccess', [response]);
      $('.crm-inline-edit-container').addClass('crm-edit-ready');
      var data = o.data('edit-params');
      var dependent = o.data('dependent-fields') || [];
      // Clone the add-new link if replacing it, and queue the clone to be refreshed as a dependent block
      if (o.hasClass('add-new') && response.addressId) {
        data.aid = response.addressId;
        var clone = o.closest('.crm-summary-block').clone();
        o.data('edit-params', data);
        $('form', clone).remove();
        if (clone.hasClass('contactCardLeft')) {
          clone.removeClass('contactCardLeft').addClass('contactCardRight');
        }
        else if (clone.hasClass('contactCardRight')) {
          clone.removeClass('contactCardRight').addClass('contactCardLeft');
        }
        var cl = $('.crm-inline-edit', clone);
        var clData = cl.data('edit-params');
        var locNo = clData.locno++;
        cl.attr('id', cl.attr('id').replace(locNo, clData.locno)).removeClass('form');
        o.closest('.crm-summary-block').after(clone);
        $.merge(dependent, $('.crm-inline-edit', clone));
      }
      $('a.ui-notify-close', '#crm-notification-container').click();
      // Delete an address
      if (o.hasClass('address') && !o.hasClass('add-new') && !response.addressId) {
        o.parent().remove();
        CRM.alert('', ts('Address Deleted'), 'success');
      }
      else {
        // Reload this block plus all dependent blocks
        var update = $.merge([o], dependent);
        for (var i in update) {
          $(update[i]).each(function() {
            var data = $(this).data('edit-params');
            data.snippet = data.reset = 1;
            data.class_name = data.class_name.replace('Form', 'Page');
            data.type = 'page';
            $(this).closest('.crm-summary-block').load(CRM.url('civicrm/ajax/inline', data), function() {$(this).trigger('load');});
          });
        }
        CRM.alert('', ts('Saved'), 'success');
      }
      // Update changelog tab and contact footer
      if (response.changeLog.count) {
        $("#tab_log a em").html(response.changeLog.count);
      }
      $("#crm-record-log").replaceWith(response.changeLog.markup);
      if ($('#Change_Log div').length) {
        $('#Change_Log').load($("#tab_log a").attr('href'));
      }
    }
    else {
      // Handle formRule error
      $('form', o).ajaxForm('destroy');
      $('.crm-container-snippet', o).replaceWith(response.content);
      $('form', o).validate(CRM.validate.params);
      $('form', o).ajaxForm(ajaxFormParams);
      o.trigger('crmFormError', [response]).trigger('crmFormLoad');
    }
  };

  /**
   * Configure optimistic locking mechanism for inplace editing
   *
   * options.ignoreLabel: string, text for a button
   * options.reloadLabel: string, text for a button
   */
  $.fn.crmFormContactLock = function(options) {
    var form = this;
    // AFTER ERROR: Render any "Ignore" and "Restart" buttons
    return this.on('crmFormError', function(event, obj, status) {
      var o = $(event.target);
      var data = o.data('edit-params');
      var errorTag = o.find('.update_oplock_ts');
      if (errorTag.length > 0) {
        $('<span>')
          .addClass('crm-lock-button')
          .appendTo(errorTag);

        var buttonContainer = o.find('.crm-lock-button');
        $('<button>')
          .addClass('crm-button')
          .text(options.saveAnywayLabel)
          .click(function() {
            $(form).find('input[name=oplock_ts]').val(errorTag.attr('data:update_oplock_ts'));
            errorTag.parent().hide();
            $(this).closest('form').find('.form-submit.default').first().click();
            return false;
          })
          .appendTo(buttonContainer)
          ;
        $('<button>')
          .addClass('crm-button')
          .text(options.reloadLabel)
          .click(function() {
            window.location.reload();
            return false;
          })
          .appendTo(buttonContainer)
          ;
      }
    });
  };

  function errorHandler(response) {
    CRM.alert(ts('Unable to reach the server. Please refresh this page in your browser and try again.'), ts('Network Error'), 'error');
    removeCiviOverlay($('.crm-inline-edit.form form'));
  }

  $('document').ready(function() {
    // Set page title
    var oldName = 'CiviCRM';
    var nameTitle = $('#crm-remove-title');
    if (nameTitle.length > 0) {
      oldName = nameTitle.text();
      nameTitle.parent('h1').remove();
    }
    else {
      $('h1').each(function() {
        if ($(this).text() == oldName) {
          $(this).remove();
        }
      });
    }
    function refreshTitle() {
      var contactName = $('.crm-summary-display_name').text();
      contactName = $.trim(contactName);
      var title = $('title').html().replace(oldName, contactName);
      document.title = title;
      oldName = contactName;
    }
    $('#contactname-block').load(refreshTitle);
    refreshTitle();

    var clicking;
    $('.crm-inline-edit-container')
      .addClass('crm-edit-ready')
      // Allow links inside edit blocks to be clicked without triggering edit
      .on('mousedown', '.crm-inline-edit:not(.form) a, .crm-inline-edit:not(.form) .crm-accordion-header, .crm-inline-edit:not(.form) .collapsible-title', function(event) {
        if (event.which == 1) {
          event.stopPropagation();
          return false;
        }
      })
      // Respond to a click (not drag, not right-click) of crm-inline-edit blocks
      .on('mousedown', '.crm-inline-edit:not(.form)', function(button) {
        if (button.which == 1) {
          clicking = this;
          setTimeout(function() {clicking = null;}, 500);
        }
      })
      .on('mouseup', '.crm-inline-edit:not(.form)', function(button) {
        if (clicking === this && button.which == 1) {
          crmFormInline($(this));
        }
      })
      // Inline edit form cancel button
      .on('click', '.crm-inline-edit :submit[name$=cancel]', function() {
        var container = $(this).closest('.crm-inline-edit.form');
        $('.inline-edit-hidden-content', container).nextAll().remove();
        $('.inline-edit-hidden-content > *:first-child', container).unwrap();
        container.removeClass('form');
        $('.crm-inline-edit-container').addClass('crm-edit-ready');
        $('a.ui-notify-close', '#crm-notification-container').click();
        return false;
      })
      // Switch tabs when clicking tag link
      .on('click', '#tagLink a', function() {
        $('#tab_tag a').click();
        return false;
      })
      // make sure only one is_primary radio is checked
      .on('change', '[class$=is_primary] input', function() {
        if ($(this).is(':checked')) {
          $('[class$=is_primary] input', $(this).closest('form')).not(this).prop('checked', false);
        }
      })
      // make sure only one builk_mail radio is checked
      .on('change', '.crm-email-bulkmail input', function(){
        if ($(this).is(':checked')) {
          $('.crm-email-bulkmail input').not(this).prop('checked', false);
        }
      })
      // handle delete link within blocks
      .on('click', '.crm-delete-inline', function() {
        var row = $(this).closest('tr');
        var form = $(this).closest('form');
        row.addClass('hiddenElement');
        $('input', row).val('');
        //if the primary is checked for deleted block
        //unset and set first as primary
        if ($('[class$=is_primary] input:checked', row).length > 0) {
          $('[class$=is_primary] input', row).prop('checked', false);
          $('[class$=is_primary] input:first', form).prop('checked', true );
        }
        $('.add-more-inline', form).show();
      })
      // Delete an address
      .on('click', '.crm-inline-edit.address .delete-button', function() {
         var $block = $(this).closest('.crm-inline-edit.address');
         CRM.confirm(function() {
            CRM.api('address', 'delete', {id: $block.data('edit-params').aid}, {success:
              function(data) {
                CRM.alert('', ts('Address Deleted'), 'success');
                $('.crm-inline-edit-container').addClass('crm-edit-ready');
                $block.remove();
              }
            });
          },
          {
          message: ts('Are you sure you want to delete this address?')
          }
        );
        return false;
      })
      // add more and set focus to new row
      .on('click', '.add-more-inline', function() {
        var form = $(this).closest('form');
        var row = $('tr[class="hiddenElement"]:first', form);
        row.removeClass('hiddenElement');
        $('input:focus', form).blur();
        $('input:first', row).focus();
        if ($('tr[class="hiddenElement"]').length < 1) {
          $(this).hide();
        }
      });
    // Trigger cancel button on esc keypress
    $(document).keydown(function(key) {
      if (key.which == 27) {
        $('.crm-inline-edit.form :submit[name$=cancel]').click();
      }
    });
    // Switch tabs when clicking log link
    $('#crm-container').on('click', '#crm-record-log a.crm-log-view', function() {
      $('#tab_log a').click();
      return false;
    });
  });
})(cj);
