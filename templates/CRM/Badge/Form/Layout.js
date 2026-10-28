// http://civicrm.org/licensing
/* global Image */
CRM.$(function($) {
  function openKCFinder() {
    var field = $(this);
    window.KCFinder = {
      callBack: function(url) {
        field.val(url).change();
        // calculate the image default width, height
        // and assign to respective fields
        const img = new Image();
        img.onload = function() {
          var widthId = 'width_' + field.attr('id');
          var heightId = 'height_' + field.attr('id');
          var f = 300 / 25.4;
          $('#' + widthId).val((img.width / f).toFixed(0));
          $('#' + heightId).val((img.height / f).toFixed(0));
        };
        img.src = url;
        window.KCFinder = null;
      }
    };

    window.open(CRM.url('civicrm/kcfinder/browse?type=images'), 'kcfinder_textbox',
      'status=0, toolbar=0, location=0, menubar=0, directories=0, ' +
        'resizable=1, scrollbars=0, width=800, height=600'
    );
  }

  $('input[id^="image_"]')
    .click(openKCFinder)
    .change(function() {
      $(this).siblings('.clear-image').css({visibility: $(this).val() ? '' : 'hidden'});
    })
    .change();

  $('.clear-image').click(function() {
    $(this).closest('tr').find('input[type=text]').val('').change();
    return false;
  });
});
