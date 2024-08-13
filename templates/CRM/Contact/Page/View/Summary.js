// http://civicrm.org/licensing
(function($, _) {
  // FIXME: Much of this code is redundant with CRM.loadForm

  var ajaxFormParams = {
    dataType:'json',
    beforeSubmit: function(arr, $form, options) {
      $form.block();
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
      var width = o.width();
      $('.crm-edit-ready').removeClass('crm-edit-ready');
      o.block().addClass('form').css('width', '' + width + 'px');
      $.getJSON(CRM.url('civicrm/ajax/inline', data))
        .fail(errorHandler)
        .done(function(response) {
          o.unblock();
          o.css('overflow', 'hidden').wrapInner('<div class="inline-edit-hidden-content" style="display:none" />').append(response.content);
          // Needed to accurately measure box width
          $('.crm-container-snippet', o).css('display', 'inline-block');
          // Smooth resizing
          var newHeight = $('.crm-container-snippet', o).height(),
            speed = newHeight - parseInt(o.css('height'), 10),
            animation = {height: '' + newHeight + 'px'};
          // Animation speed is set relative to how much the box needs to grow
          if (speed < 0) {
            speed = 0 - speed;
          }
          // Horizontal growth
          var newWidth = $('.crm-container-snippet', o).width();
          if (newWidth > width) {
            animation.width = '' + newWidth + 'px';
            // Slow down animation if we have lots of horizontal growth to do
            if (newWidth - width > speed) {
              speed = newWidth - width;
            }
          } else {
            newWidth = width;
          }
          $('.crm-container-snippet', o).css('display', '');
          o.animate(animation, speed, function() {
            o.css({height: '', width: '', minWidth: '' + newWidth + 'px'});
          });
          $('form', o).validate(CRM.validate.params);
          ajaxFormParams.data = data;
          $('form', o).ajaxForm(ajaxFormParams);
          o.trigger('crmLoad').trigger('crmFormLoad');
        });
    }
  }

  function reloadBlock(el) {
    return $(el).each(function() {
      var data = $(this).data('edit-params');
      if (data) {
        data.snippet = data.reset = 1;
        data.class_name = data.class_name.replace('Form', 'Page');
        data.type = 'page';
        $(this).closest('.crm-summary-block').load(CRM.url('civicrm/ajax/inline', data), function() {
          $(this).trigger('crmLoad');
        });
      }
    });
  }

  function requestHandler(response) {
    var o = $('div.crm-inline-edit.form');
    $('form', o).ajaxFormUnbind();

    if (response.status == 'success' || response.status == 'cancel') {
      o.trigger('crmFormSuccess', [response]).removeAttr('style');
      $('.crm-inline-edit-container').addClass('crm-edit-ready');
      var data = o.data('edit-params');
      var dependent = $((o.data('dependent-fields') || []).join(','));
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
        dependent = dependent.add($('.crm-inline-edit', clone));
      }
      $('a.ui-notify-close', '#crm-notification-container').click();
      // Delete an address
      if (o.hasClass('address') && !o.hasClass('add-new') && !response.addressId) {
        o.parent().remove();
        CRM.status(ts('Address Deleted'));
      }
      else {
        // Reload this block plus all dependent blocks
        reloadBlock(dependent.add(o));
        CRM.status(ts('Saved'));
      }
    }
    else {
      // Handle formRule error
      $('.crm-container-snippet', o).replaceWith(response.content);
      $('form', o).validate(CRM.validate.params);
      $('form', o).ajaxForm(ajaxFormParams);
      o.trigger('crmFormError', [response]).trigger('crmFormLoad').trigger('crmLoad');
    }
  }

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
          .addClass('crm-lock-button css_right')
          .appendTo(errorTag);

        var buttonContainer = o.find('.crm-lock-button');
        $('<button>')
          .addClass('crm-button')
          .text(options.saveAnywayLabel)
          .click(function() {
            $(form).find('input[name=oplock_ts]').val(errorTag.attr('data:update_oplock_ts'));
            errorTag.parent().hide();
            $(this).closest('form').find('.crm-form-submit.default').first().click();
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
    $('.crm-inline-edit.form form').unblock();
  }

  $(function() {
    // don't perform inline edit during print mode
    if (CRM.summaryPrint.mode) {
      $('div').removeClass('crm-inline-edit');
      $('.crm-inline-block-content > div.crm-edit-help').remove();
      $('div.crm-inline-block-content').removeAttr('title');
    }
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
      document.title = document.title.replace(oldName, contactName);
      oldName = contactName;
    }
    $('#contactname-block').on('load', refreshTitle);
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
        $('form', container).ajaxFormUnbind();
        $('.inline-edit-hidden-content', container).nextAll().remove();
        $('.inline-edit-hidden-content > *:first-child', container).unwrap();
        container.removeClass('form').removeAttr('style');
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
      // Note this is no longer required for the email block
      // & similar changes to phone, address, im, openid would allow removal from them as well.
      .on('change', '[class$=is_primary] input', function() {
        if ($(this).is(':checked')) {
          $('[class$=is_primary] input', $(this).closest('form')).not(this).prop('checked', false);
        }
      })
      // handle delete link within blocks
      .on('click', '.crm-delete-inline', function(e) {
        var row = $(this).closest('tr');
        var form = $(this).closest('form');
        row.hide();
        var blockNumber = row.data('block-number');
        if (blockNumber) {
          $('.crm-block-entity-' + row.data('entity') + '-' + blockNumber).addClass('hiddenElement');
          $('input', '.crm-block-entity-' + row.data('entity') + '-' + blockNumber).val('');
        }
        $('input', row).val('');
        //if the primary is checked for deleted block
        //unset and set first as primary
        if ($('[class$=is_primary] input:checked', row).length > 0) {
          $('[class$=is_primary] input', row).prop('checked', false);
          $('[class$=is_primary] input:visible:first', form).prop('checked', true );
        }
        $('.add-more-inline', form).show();
        if ($('[class$=is_primary] input:visible', form).length == 0) {
          $('.add-more-inline', form).click();
          $('[class$=is_primary] input:visible:first', form).prop('checked', true );
        }

        e.preventDefault();
      })
      // Delete an address
      .on('click', '.crm-inline-edit.address .delete-button', function(e) {
         var $block = $(this).closest('.crm-inline-edit.address');
         CRM.confirm({message: ts('Are you sure you want to delete this address?')})
           .on('crmConfirm:yes', function() {
            CRM.api3('address', 'delete', {id: $block.data('edit-params').aid}, true)
              .done(function(data) {
                $('.crm-inline-edit-container').addClass('crm-edit-ready');
                $block.closest('.crm-address-block').remove();
                reloadBlock('.crm-inline-edit.address:not(.add-new)');
              });
            });
        e.preventDefault();
      })
      // add more and set focus to new row
      .on('click', '.add-more-inline', function(e) {
        var form = $(this).closest('form');
        var row = $('tr[class="hiddenElement"]:first', form);
        row.removeClass('hiddenElement');
        var blockNumber = row.data('block-number');
        if (blockNumber) {
          $('.crm-block-entity-' + row.data('entity') + '-' + blockNumber).removeClass('hiddenElement');
        }

        $('input:focus', form).blur();
        $('input:first', row).focus();
        if ($('tr[class="hiddenElement"]').length < 1) {
          $(this).hide();
        }
        e.preventDefault();
      });
    // Trigger cancel button on esc keypress
    $(document).keydown(function(key) {
      if (key.which == 27) {
        $('.crm-inline-edit.form :submit[name$=cancel]').click();
      }
    });
    $('#crm-container')
      // Switch tabs when clicking log link
      .on('click', '#crm-record-log a.crm-log-view', function() {
        $('#tab_log a').click();
        return false;
      })
      // Handle action links in popup
      .on('click', '.crm-contact_actions-list a, .crm-contact_activities-list a', function(e) {
        $('#crm-contact-actions-list').hide();
        if ($(this).attr('href') === '#') {
          var $tab = $('#tab_' + ($(this).data('tab') || 'summary'));
          CRM.tabHeader.focus($tab);
          e.preventDefault();
        } else {
          CRM.popup.call(this, e);
        }
      })
      .on('crmPopupFormSuccess',  '.crm-contact_actions-list a, .crm-contact_activities-list a', function() {
        var $tab = $('#tab_' + ($(this).data('tab') || 'summary'));
        CRM.tabHeader.resetTab($tab);
        CRM.tabHeader.focus($tab);
      });
    $(document)
      // Actions menu
      .on('click', function(e) {
        if ($(e.target).is('#crm-contact-actions-link, #crm-contact-actions-link *')) {
          $('#crm-contact-actions-list').show();
          return false;
        }
        $('#crm-contact-actions-list').hide();
      })
      .on('crmFormSuccess crmLoad', function(e, data) {
        // Update changelog tab and contact footer
        if (data && data.changeLog) {
          if (data.changeLog.count) {
            CRM.tabHeader.updateCount('#tab_log', data.changeLog.count);
          }
          if (data.changeLog.markup) {
            $("#crm-record-log").replaceWith(data.changeLog.markup);
          }
        }
      })
      .on('crmFormSuccess', function(e, data) {
        // Advanced logging... just increment the changelog tab count to avoid the overhead of reloading the data
        if (CRM.incrementChangeLogTab) {
          CRM.incrementChangeLogTab();
        }
        // Refresh changelog tab next time it is opened
        CRM.tabHeader.resetTab('#tab_log');
        // Refresh dependent blocks
        if (data && data.reloadBlocks) {
          reloadBlock(data.reloadBlocks.join(','));
        }
      });

    /**
     * Make contact summary fit in small screens
     */
    function onResize() {
      var contactwidth = $('#crm-container #mainTabContainer').width();
      if (contactwidth < 600) {
        $('#crm-container #mainTabContainer').addClass('narrowpage');
        $('#crm-container #mainTabContainer.narrowpage #contactTopBar td').each(function (index) {
          if (index > 1) {
            if (index % 2 === 0) {
              $(this).parent().after('<tr class="narrowadded"></tr>');
            }
            var item = $(this);
            $(this).parent().next().append(item);
          }
        });
      }
      else {
        $('#crm-container #mainTabContainer.narrowpage').removeClass('narrowpage');
        $('#crm-container #mainTabContainer #contactTopBar tr.narrowadded td').each(function () {
          var nitem = $(this);
          var parent = $(this).parent();
          $(this).parent().prev().append(nitem);
          if (parent.children().size() === 0) {
            parent.remove();
          }
        });
        $('#crm-container #mainTabContainer.narrowpage #contactTopBar tr.added').detach();
      }
      var cformwidth = $('#crm-container #Contact .contact_basic_information-section').width();

      if (cformwidth < 720) {
        $('#crm-container .contact_basic_information-section').addClass('narrowform');
        $('#crm-container .contact_basic_information-section table.form-layout-compressed td .helpicon').parent().addClass('hashelpicon');
        if (cformwidth < 480) {
          $('#crm-container .contact_basic_information-section').addClass('xnarrowform');
        }
        else {
          $('#crm-container .contact_basic_information-section.xnarrowform').removeClass('xnarrowform');
        }
      }
      else {
        $('#crm-container .contact_basic_information-section.narrowform').removeClass('narrowform');
        $('#crm-container .contact_basic_information-section.xnarrowform').removeClass('xnarrowform');
      }
    }

    // Changing relationships may affect related members and contributions. Ensure they are refreshed.
    $('#contact-rel').on('crmPopupFormSuccess', function() {
      CRM.tabHeader.resetTab('#tab_contribute');
      CRM.tabHeader.resetTab('#tab_member');
    });

    onResize();
    $(window).resize(onResize);
  });
})(CRM.$, CRM._);
