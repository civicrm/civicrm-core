// http://civicrm.org/licensing
cj(function($) {
  $('#showPCP, #showSoftCredit').click(function(){
    return showHideSoftCreditAndPCP();
  });

  function showHideSoftCreditAndPCP() {
    $('.crm-contribution-pcp-block').toggle();
    $('.crm-contribution-pcp-block-link').toggle();
    $('.crm-contribution-form-block-soft_credit_to').toggle();
    return false;
  }

  $('#addMoreSoftCredit').click(function(){
    $('.crm-soft-credit-block tr.hiddenElement :first').show().removeClass('hiddenElement');
    if ( $('.crm-soft-credit-block tr.hiddenElement').length < 1 ) {
      $('#addMoreSoftCredit').hide();
    }
    return false;
  });

  var pcpURL = CRM.url('civicrm/ajax/rest',
    'className=CRM_Contact_Page_AJAX&fnName=getPCPList&json=1&context=contact&reset=1');
  $('#pcp_made_through').autocomplete(pcpURL,
    { width : 360, selectFirst : false, matchContains: true
  }).result( function(event, data, formatted) {
      $("#pcp_made_through_id" ).val( data[1]);
    });

  var rowCnt = 1;
  $('input[name^="soft_credit_contact_select_id["]').each(function(){
    if ($(this).val()){
      var dataUrl = CRM.url('civicrm/ajax/rest',
        'className=CRM_Contact_Page_AJAX&fnName=getContactList&json=1&context=contact&id=' + $(this).val());
      $.ajax({
        url     : dataUrl,
        async   : false,
        success : function(html){
          htmlText = html.split( '|' , 2);
          $('#soft_credit_contact_' + rowCnt).val(htmlText[0]);
          rowCnt++;
        }
      });
    }
  });

  $('.crm-soft-credit-block tr span').each(function () {
    if ($(this).hasClass('crm-error')) {
      $(this).parents('tr').show();
    }
  });

  $('.delete-link').click(function(){
    var row = $(this).attr('row-no');
    $('#soft-credit-row-' + row).hide().find('input').val('');
    $('input[name="soft_credit_contact_select_id['+row+']"]').val('');
    return false;
  });

  $('input[name^="soft_credit_contact["]').change(function(){
    var rowNum = $(this).attr('id').replace('soft_credit_contact_','');
    var totalAmount = $('#total_amount').val();
    //assign total amount as default soft credit amount
    $('#soft_credit_amount_'+ rowNum).val(totalAmount);
    var thousandMarker = CRM.monetaryThousandSeparator;
    totalAmount = Number(totalAmount.replace(thousandMarker,''));
    if (rowNum > 1) {
      var scAmount = Number($('#soft_credit_amount_'+ (rowNum - 1)).val().replace(thousandMarker,''));
      if (scAmount < totalAmount) {
     //if user enters less than the total amount and adds another soft credit row, 
     //the soft credit amount default will be left empty 
        $('#soft_credit_amount_'+ rowNum).val(''); 
      }
    }
  });

});
