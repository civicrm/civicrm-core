// https://civicrm.org/licensing
(function($, CRM, _, undefined) {

  CRM.initiator = function initiator(params) {
    const initiatorUrl = params.url;
    const unsavedChanges = CRM.utils.initialValueChanged($('form[data-warn-changes=true]:visible'));

    if (unsavedChanges) {
      CRM.alert(
        '<p>' + ts('Please save changes first.') + '</p>',
        ts('Unsaved Changes')
      );
    }
    else {
      $.get(initiatorUrl).then(function onInitiate(resp) {
        var region = $('.initiator-body');
        if (region.empty()) {
          region = $('body').append('<div class="initiator-body" style="display: none;">');
        }
        region.append(resp);
      });
    }

    return false;
  };

}(CRM.$, CRM, CRM._));
