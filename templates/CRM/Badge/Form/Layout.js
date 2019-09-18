// http://civicrm.org/licensing
CRM.$(function($) {
  function openKCFinder(field) {
    var field = $(this);
    window.KCFinder = {
      callBack: function(url) {
        field.val(url).change();
        // calculate the image default width, height
        // and assign to respective fields
         var ajaxUrl = CRM.url('civicrm/ajax/rest', 'className=CRM_Badge_Page_AJAX&fnName=getImageProp&json=1&img=' + url);
         $.getJSON(ajaxUrl).done(function ( response ) {
            var widthId = 'width_' + field.attr('id');
            var heightId = 'height_' + field.attr('id');
            $('#' + widthId).val(response.width.toFixed(0));
            $('#' + heightId).val(response.height.toFixed(0));
        });
        window.KCFinder = null;
      }
    };

    window.open(CRM.kcfinderPath + 'kcfinder/browse.php?cms=civicrm&type=images', 'kcfinder_textbox',
      'status=0, toolbar=0, location=0, menubar=0, directories=0, ' +
        'resizable=1, scrollbars=0, width=800, height=600'
    );
  }

  //toggle image inputs based on whether and where participant image is used
  toggleImages();
  $('#show_participant_image').change( function(){
    toggleImages();
  });
  $('#alignment_participant_image').change( function(){
    toggleImages();
  } );

  function toggleImages() {
    var align = $('#alignment_participant_image').val();

    $('.crm-badge-layout-form-block-image_1').show();
    $('.crm-badge-layout-form-block-image_2').show();
    if( $('#show_participant_image').is(':checked') ) {
      if (align == "L") {
        $('.crm-badge-layout-form-block-image_1').hide();
      }
      else if (align == "R") {
        $('.crm-badge-layout-form-block-image_2').hide();
      }
    }
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
