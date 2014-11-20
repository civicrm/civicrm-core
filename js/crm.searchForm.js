// http://civicrm.org/licensing
(function($, _, undefined) {
  "use strict";
  var selected = 0,
    form = 'form.crm-search-form',
    active = 'a.button, a.action-item:not(.crm-enable-disable), a.crm-popup';

  function clearTaskMenu() {
    $('select#task', form).val('').select2('val', '').prop('disabled', true).select2('disable');
  }

  function enableTaskMenu() {
    if (selected || $('[name=radio_ts][value=ts_all]', form).is(':checked')) {
      $('select#task', form).prop('disabled', false).select2('enable');
    }
  }

  function displayCount() {
    $('label[for*=ts_sel] span', form).text(selected);
  }

  function countCheckboxes() {
    return $('input.select-row:checked', form).length;
  }

  function clearSelections(e) {
    if (selected) {
      var $form = $(this).closest('form');
      $('input.select-row, input.select-rows', $form).prop('checked', false).closest('tr').removeClass('crm-row-selected');
      if (usesAjax()) {
        phoneHome(false, $(this));
      } else {
        selected = 0;
        displayCount();
      }
    }
  }

  function usesAjax() {
    return $(form).hasClass('crm-ajax-selection-form');
  }

  // Use ajax to store selection server-side
  function phoneHome(single, $el, event) {
    var url = CRM.url('civicrm/ajax/markSelection');
    var params = {qfKey: 'civicrm search ' + $('input[name=qfKey]', form).val()};
    if (!$el.is(':checked') || $el.is('input[name=radio_ts][value=ts_all]')) {
      params.action = 'unselect';
      params.state = 'unchecked';
    }
    if (single) {
      params.name = $el.attr('id');
    } else {
      params.variableType = 'multiple';
      // "Reset all" button
      if ($el.is('a')) {
        event.preventDefault();
        $("input.select-row, input.select-rows", form).prop('checked', false).closest('tr').removeClass('crm-row-selected');
      }
      // Master checkbox
      else if ($el.hasClass('select-rows')) {
        params.name = $('input.select-row').map(function() {return $(this).attr('id')}).get().join('-');
      }
    }
    $.getJSON(url, params, function(data) {
      if (data && data.getCount !== undefined) {
        selected = data.getCount;
        displayCount();
        enableTaskMenu();
      }
    });
  }

  /**
   * Refresh the current page
   */
  function refresh() {
    // Clear cached search results using force=1 argument
    var location = $('#crm-main-content-wrapper').crmSnippet().crmSnippet('option', 'url');
    if (!(location.match(/[?&]force=1/))) {
      location += '&force=1';
    }
    $('#crm-main-content-wrapper').crmSnippet({url: location}).crmSnippet('refresh');
  }

  /**
   * When initially loading and reloading (paging) the results
   */
  function initForm() {
    clearTaskMenu();
    if (usesAjax()) {
      selected = parseInt($('label[for*=ts_sel] span', form).text(), 10);
    } else {
      selected = countCheckboxes();
      displayCount();
    }
    enableTaskMenu();
  }

  $(function() {
    initForm();
    // Handle user interactions with search results
    $('#crm-container')
      // When toggling between "all records" and "selected records only"
      .on('change', '[name=radio_ts]', function() {
        clearTaskMenu();
        enableTaskMenu();
      })
      .on('click', 'input[name=radio_ts][value=ts_all]', clearSelections)
      // When making a selection
      .on('click', 'input.select-row, input.select-rows, a.crm-selection-reset', function(event) {
        var $el = $(this),
          $form = $el.closest('form'),
          single = $el.is('input.select-row');
        clearTaskMenu();
        $('input[name=radio_ts][value=ts_sel]', $form).prop('checked', true);
        if (!usesAjax()) {
          if (single) {
            selected = countCheckboxes();
          } else {
            selected = $el.is(':checked') ? $('input.select-row', $form).length : 0;
          }
          displayCount();
          enableTaskMenu();
        } else {
          phoneHome(single, $el, event);
        }
      })
      // When selecting a task
      .on('change', 'select#task', function() {
        var $form = $(this).closest('form'),
          $go = $('input.crm-search-go-button', $form);
        if (1) {
          $go.click();
        }
        // The following code can load the task in a popup, however not all tasks function correctly with this
        // So it's disabled pending a per-task opt-in mechanism
        else {
          var data = $form.serialize() + '&' + $go.attr('name') + '=' + $go.attr('value');
          var url = $form.attr('action');
          url += (url.indexOf('?') < 0 ? '?' : '&') + 'snippet=json';
          clearTaskMenu();
          $.post(url, data, function(data) {
            CRM.loadForm(data.userContext).on('crmFormSuccess', refresh);
            enableTaskMenu();
          }, 'json');
        }
      });

    // Add a specialized version of livepage functionality
    $('#crm-main-content-wrapper')
      .on('crmLoad', function(e) {
        if ($(e.target).is(this)) {
          initForm();
        }
      })
      // Open action links in a popup
      .off('.crmLivePage')
      .on('click.crmLivePage', active, CRM.popup)
      .on('crmPopupFormSuccess.crmLivePage', active, refresh);
  });

})(CRM.$, CRM._);
