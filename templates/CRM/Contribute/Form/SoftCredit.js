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
        success : function(html){
          htmlText = html.split( '|' , 2);
          $('#soft_credit_contact_' + rowCnt).val(htmlText[0]);
          rowCnt++;
        }
      });
    }
  });

});
