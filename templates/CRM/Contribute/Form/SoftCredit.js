// http://civicrm.org/licensing
cj(function($) {
  $('.crm-contribution-pcp-block').hide();
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
      cj( "#pcp_made_through_id" ).val( data[1] );
    });
});
