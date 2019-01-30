CRM.$(function($) {
  $('input[name=inheritLocale]').click(function () {
    showHideUiLanguages();
  });

  function showHideUiLanguages() {
    var val =  $('input[name=inheritLocale]:checked').val();
    if(val == 0) {
      $('.crm-localization-form-block-uiLanguages').show();
    } else {
      $('.crm-localization-form-block-uiLanguages').hide();
    }
  }

  showHideUiLanguages();
});
