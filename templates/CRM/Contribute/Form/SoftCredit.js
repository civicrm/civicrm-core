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
});
/*
 var url = "{/literal}{$dataUrl}{literal}";

  cj('#soft_credit_to').autocomplete( url, { width : 180, selectFirst : false, matchContains: true
  }).result( function(event, data, formatted) {
      cj( "#soft_contact_id" ).val( data[1] );
  });
 {/literal}

// load form during form rule.
{if $buildPriceSet}{literal}buildAmount( );{/literal}{/if}

{if $siteHasPCPs}
  {literal}
  var pcpUrl = "{/literal}{$pcpDataUrl}{literal}";

  cj('#pcp_made_through').autocomplete( pcpUrl, { width : 360, selectFirst : false, matchContains: true
  }).result( function(event, data, formatted) {
      cj( "#pcp_made_through_id" ).val( data[1] );
  });
{/literal}

  {if $pcpLinked}
    {literal}hideSoftCredit( );{/literal}{* hide soft credit on load if we have PCP linkage *}
  {else}
    {literal}cj('#pcpID').hide();{/literal}{* hide PCP section *}
  {/if}

  {literal}
  function hideSoftCredit ( ){
    cj("#softCreditID").hide();
  }
  function showPCP( ) {
    cj('#pcpID').show();
    cj("#softCreditID").hide();
  }
  function showSoftCredit( ) {
    cj('#pcp_made_through_id').val('');
    cj('#pcp_made_through').val('');
    cj('#pcp_roll_nickname').val('');
    cj('#pcp_personal_note').val('');
    cj('#pcp_display_in_roll').attr('checked', false);
    cj("#pcpID").hide();
    cj('#softCreditID').show();
  }
  {/literal}
{/if}
</script>

*/
