// https://civicrm.org/licensing
/**
 * By default this simply loads tabs via ajax CRM.loadPage method
 * Tabs with class 'ajaxForm' will use CRM.loadForm instead, suitable for most forms
 * Tabs with class 'livePage' will get popup action links, suitable for crud tables
 */
CRM.$(function($) {
  var tabSettings = CRM.tabSettings || {};
  tabSettings.active = tabSettings.active ? $('#tab_' + tabSettings.active).prevAll().length : 0;
  $("#mainTabContainer")
    .on('tabsbeforeactivate', function(e, ui) {
      // CRM-14353 - Warn of unsaved changes for all forms except those which have opted out
      if (CRM.utils.initialValueChanged($('form:not([data-warn-changes=false])', ui.oldPanel))) {
        CRM.alert(ts('Your changes in the <em>%1</em> tab have not been saved.', {1: ui.oldTab.text()}), ts('Unsaved Changes'), 'warning');
      }
    })
    .on('tabsbeforeload', function(e, ui) {
      // Use civicrm ajax wrappers rather than the default $.load
      if (!ui.panel.data("civiCrmSnippet")) {
        var method = ui.tab.hasClass('ajaxForm') ? 'loadForm' : 'loadPage';
        var params = {target: ui.panel};
        if (method === 'loadForm') {
          params.autoClose = params.openInline = params.cancelButton = params.refreshAction = false;
          ui.panel.on('crmFormLoad', function() {
            // Hack: "Save and done" and "Cancel" buttons submit without ajax
            $('.cancel.crm-form-submit, input[name$=upload_done]', this).on('click', function(e) {
              $(this).closest('form').ajaxFormUnbind();
            });
          });
        }
        if (ui.tab.hasClass('livePage') && CRM.config.ajaxPopupsEnabled) {
          ui.panel
            .off('click.crmLivePage')
            .on('click.crmLivePage', 'a.button, a.action-item', CRM.popup)
            .on('crmPopupFormSuccess.crmLivePage', 'a.button, a.action-item:not(.crm-enable-disable)', CRM.refreshParent);
        }
        ui.panel
          .off('.tabInfo')
          .on('crmLoad.tabInfo crmFormSuccess.tabInfo', function(e, data) {
            if (data) {
              if (typeof(data.tabCount) !== 'undefined') {
                CRM.tabHeader.updateCount(ui.tab, data.tabCount);
              }
              if (typeof(data.tabValid) !== 'undefined') {
                var method = data.tabValid ? 'removeClass' : 'addClass';
                ui.tab[method]('disabled');
              }
            }
          });
        CRM[method]($('a', ui.tab).attr('href'), params);
      }
      e.preventDefault();
    })
    .tabs(tabSettings);
  // Any load/submit event could potentially call for tabs to refresh.
  $(document).on('crmLoad.tabInfo crmFormSuccess.tabInfo', function(e, data) {
    if (data && $.isPlainObject(data.updateTabs)) {
      $.each(data.updateTabs, CRM.tabHeader.updateCount);
      $.each(data.updateTabs, CRM.tabHeader.resetTab);
    }
  });
});
(function($) {
  // Utility functions
  CRM.tabHeader = CRM.tabHeader || {};

  /**
   * Return active tab
   */
  CRM.tabHeader.getActiveTab = function() {
    return $('.ui-tabs-active', '#mainTabContainer');
  };

  /**
   * Make a given tab the active one
   * @param tab jQuery selector
   */
  CRM.tabHeader.focus = function(tab) {
    $('#mainTabContainer').tabs('option', 'active', $(tab).prevAll().length);
  };

  /**
   * @param tab jQuery selector
   * @returns panel jQuery object
   */
  CRM.tabHeader.getTabPanel = function(tab) {
    var selector = $(tab).attr('aria-controls');
    return selector ? $('#' + selector) : $();
  };

  /**
   * @param tab jQuery selector
   * @returns {string|null}
   */
  function getCountClass(tab) {
    var $tab = $(tab),
      css = $tab.attr('class') || '',
      val = css.match(/(crm-count-\d+)/);
    return val && val.length ? val[0] : null;
  }

  /**
   * @param tab jQuery selector
   * @returns {Number|null}
   */
  CRM.tabHeader.getCount = function(tab) {
    var cssClass = getCountClass(tab);
    return cssClass ? parseInt(cssClass.slice(10), 10) : null;
  };

  /**
   * Update the counter in a tab
   * @param tab jQuery selector
   * @param count {Number}
   */
  CRM.tabHeader.updateCount = function(tab, count) {
    var oldClass = getCountClass(tab);
    if (oldClass) {
      $(tab).removeClass(oldClass);
    }
    $(tab)
      .addClass('crm-count-' + count)
      .find('a em').html('' + count);
  };

  /**
   * Refresh tab immediately if it is active (or force=true)
   * otherwise ensure it will be refreshed next time the user clicks on it
   *
   * @param tab
   * @param force
   */
  CRM.tabHeader.resetTab = function(tab, force) {
    var $panel = CRM.tabHeader.getTabPanel(tab);
    if ($(tab).hasClass('ui-tabs-active')) {
      $panel.crmSnippet('refresh');
    }
    else if (force) {
      if ($panel.data("civiCrmSnippet")) {
        $panel.crmSnippet('refresh');
      } else {
        $("#mainTabContainer").trigger('tabsbeforeload', [{panel: $panel, tab: $(tab)}]);
      }
    }
    else if ($panel.data("civiCrmSnippet")) {
      $panel.crmSnippet('destroy');
    }
  };
})(CRM.$);
