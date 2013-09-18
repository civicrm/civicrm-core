// http://civicrm.org/licensing
cj(function ($) {
  function openKCFinder(field) {
    window.KCFinder = {
      callBack: function(url) {
        field.val(url);
        // calculate the image default width, height 
        // and assign to respective fields
         var ajaxUrl = CRM.url('civicrm/ajax/rest', 'className=CRM_Badge_Page_AJAX&fnName=getImageProp&json=1&img=' + url);
         $.ajax({
          url: ajaxUrl,
          async: false,
          global: false,
          dataType: "json",
          success: function ( response ) {
            var widthId = 'width_' + field.attr('id');
            var heightId = 'height_' + field.attr('id');
            $('#' + widthId).val(response.width.toFixed(0));
            $('#' + heightId).val(response.height.toFixed(0));
          }
        }); 
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
    var imgName = $(this).attr('imgname');
    $('#' + imgName).val('');
    $('#width_' + imgName).val('');
    $('#height_' + imgName).val('');
    return false;
  });
});
