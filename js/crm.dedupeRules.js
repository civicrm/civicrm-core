// https://civicrm.org/licensing

CRM.$(function($) {
  function updateDisplay() {
    var used = $('[name=used]').val();
    var inputParent = $('[name=usedDialog][value=' + used + ']').closest('div');
    var title = inputParent.find('.dedupe-rules-dialog-title').text();
    var desc = inputParent.find('.dedupe-rules-dialog-desc').text();
    $('.js-dedupe-rules-current').text(title);
    $('.js-dedupe-rules-desc').text(desc);
  }
  function setInitial() {
    var used = $('[name=used]').val();
    $('[name=usedDialog][value=' + used + ']').prop('checked', true);
    updateDisplay();
  }
  function setSaveValue() {
    var dialogVal = $('[name=usedDialog]:checked').val();
    $('[name=used]').val(dialogVal);
    updateDisplay();
  }
  function openDialog() {
    var dialog = $('.dedupe-rules-dialog');
    dialog.dialog({
      title: dialog.attr('data-title'),
      width: 800,
      buttons: [
        {
          text: dialog.attr('data-button-close'),
          icon: 'fa-close',
          click: function() {
            dialog.dialog('close');
          }
        },
        {
          text: dialog.attr('data-button-update'),
          icon: 'fa-check',
          click: function() {
            setSaveValue();
            dialog.dialog('close');
          }
        }
      ]
    });
  }
  setInitial();
  $('.js-dedupe-rules-change').on('click', openDialog);
});
