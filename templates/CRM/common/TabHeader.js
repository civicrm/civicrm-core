// https://civicrm.org/licensing
cj(function($) {
  var tabSettings = CRM.tabSettings || {};
  tabSettings.active = tabSettings.active ? $('#tab_' + tabSettings.active).prevAll().length : 0;
  $("#mainTabContainer")
    .on('tabsbeforeactivate', function(e, ui) {
      // Warn of unsaved changes - requires formNavigate.tpl to be included in each tab
      if (!global_formNavigate) {
        CRM.alert(ts('Your changes in the <em>%1</em> tab have not been saved.', {1: ui.oldTab.text()}), ts('Unsaved Changes'), 'warning');
        global_formNavigate = true;
      }
    })
    .on('tabsbeforeload', function(e, ui) {
      // Use civicrm ajax wrappers rather than the default $.load
      if (!ui.panel.data("civicrmCrmSnippet")) {
        var method = ui.tab.hasClass('ajaxForm') ? 'loadForm' : 'loadPage';
        var params = {target: ui.panel};
        if (method === 'loadForm') {
          params.autoClose = params.openInline = params.cancelButton = params.refreshAction = false;
          ui.panel.on('crmFormLoad', function() {
            // Hack: "Save and done" and "Cancel" buttons submit without ajax
            $('.cancel.form-submit, input[name$=upload_done]', this).on('click', function(e) {
              $(this).closest('form').ajaxFormUnbind();
            })
          });
        }
        CRM[method]($('a', ui.tab).attr('href'), params);
      }
      e.preventDefault();
    })
    .tabs(tabSettings);
});
(function($) {
  CRM.updateTabCount = function(tab, count) {
    $(tab)
      .removeClass($(tab).attr('class').match(/(crm-count-\d+)/)[0])
      .addClass('crm-count-' + count)
      .find('a em').html('' + count);
  }
})(cj);
