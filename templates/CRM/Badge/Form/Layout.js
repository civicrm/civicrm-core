// http://civicrm.org/licensing
cj(function ($) {
  function openKCFinder(field) {
    window.KCFinder = {
      callBack: function(url) {
        field.val(url);
        window.KCFinder = null;
      }
    };

    window.open(CRM.kcfinderPath + 'kcfinder/browse.php?cms=civicrm&type=images', 'kcfinder_textbox',
      'status=0, toolbar=0, location=0, menubar=0, directories=0, ' +
        'resizable=1, scrollbars=0, width=800, height=600'
    );
  }

  $('input[id^="image_"]').click(function(){
    openKCFinder($(this));
  });

  $('.clear-image').click(function(){
    $('#' + $(this).attr('imgname')).val('');
    return false;
  });
});
