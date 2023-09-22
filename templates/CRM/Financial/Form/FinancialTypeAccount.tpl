{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{* this template is used for adding/editing/deleting financial type  *}
<div class="crm-block crm-form-block crm-financial_type-form-block">
  {if $action eq 8}
    <div class="messages status">
      {icon icon="fa-info-circle"}{/icon}
      {ts}WARNING: You cannot delete a financial type if it is currently used by any Contributions, Contribution Pages or Membership Types. Consider disabling this option instead.{/ts} {ts}Deleting a financial type cannot be undone. Unbalanced transactions may be created if you delete this account.{/ts} {ts}Do you want to continue?{/ts}
      </div>
  {else}
    <table class="form-layout">
      <tr class="crm-contribution-form-block-account_relationship">
      <td class="label">{$form.account_relationship.label}</td>
  <td class="html-adjust">{$form.account_relationship.html}</td>
      </tr>
      <tr class="crm-contribution-form-block-financial_account_id">
      <td class="label">{$form.financial_account_id.label}</td>
  <td class="html-adjust">{$form.financial_account_id.html}</td>
      </tr>
    </table>
   {/if}
   <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="botttom"}</div>
</div>

<script language="JavaScript" type="text/javascript">
{literal}
CRM.$(function($) {
  $("#financial_account_id").change(function() {
    {/literal}
      relationID = "#account_relationship"
      financialAccountID = "#financial_account_id"
      callbackURL = "{crmURL p='civicrm/ajax/rest' h=0 q='className=CRM_Financial_Page_AJAX&fnName=jqFinancialRelation'}"
    {literal}
    var financialId = $("#financial_account_id").val();
    var check = $(relationID).val();
    if (check == 'select' || financialId == 'select') {
      callbackURL = callbackURL+"&_value=" + financialId;
      $.ajax({
        url: callbackURL,
      	context: document.body,
      	success: function(data, textStatus) {
          $(relationID).html("");//clear old options
  	  data = eval(data);//get json array
          if (data != null) {
    	    for (i = 0; i < data.length; i++) {
      	      if (data[i].selected == 'Selected') {
                var idf = data[i].value;
              }
              $(relationID).get(0).add(new Option(data[i].name, data[i].value), document.all ? i : null);
    	    }
          }
  	  if (idf != null) {
      	    $(relationID).val(idf);
          }
        }
      });
    }
    if (financialId == 'select') {
      {/literal}
        callbackURLs = "{crmURL p='civicrm/ajax/rest' h=0 q='className=CRM_Financial_Page_AJAX&fnName=jqFinancial'}"
      {literal}
      callbackURLs = callbackURLs + "&_value=select";
      $.ajax({
        url: callbackURLs,
        context: document.body,
        success: function(data, textStatus) {
          $(financialAccountID).html("");//clear old options
    	  data = eval(data);//get json array
          if (data != null) {
      	    for (i = 0; i < data.length; i++) {
              $(financialAccountID).get(0).add(new Option(data[i].name, data[i].value), document.all ? i : null);
            }
          }
        }
      });
    }
  });
  $("#account_relationship").change(function() {
    {/literal}
      relationID = "#account_relationship"
      financialAccountID = "#financial_account_id"
      callbackURLs = "{crmURL p='civicrm/ajax/rest' h=0 q='className=CRM_Financial_Page_AJAX&fnName=jqFinancial'}"
    {literal}
    var financialId = $("#account_relationship").val();
    var check = $(financialAccountID).val();
    callbackURLs = callbackURLs+"&_value="+financialId;
    $.ajax({
      url: callbackURLs,
      context: document.body,
      success: function(data, textStatus) {
        $(financialAccountID).html("");//clear old options
        data = eval(data);//get json array
        if (data != null) {
          for (i = 0; i < data.length; i++) {
       	    if (data[i].selected == 'Selected') {
              var idf = data[i].value;
            }
	    $(financialAccountID).get(0).add(new Option(data[i].name, data[i].value), document.all ? i : null);
          }
        }
  	if (idf != null) {
    	  $(financialAccountID).val(idf);
        }
      }
    });
    if (financialId == 'select') {
      {/literal}
        callbackURL = "{crmURL p='civicrm/ajax/rest' h=0 q='className=CRM_Financial_Page_AJAX&fnName=jqFinancialRelation'}"
      {literal}
      callbackURL = callbackURL+"&_value=select";
      $.ajax({
        url: callbackURL,
        context: document.body,
        success: function(data, textStatus) {
          $(relationID).html("");//clear old options
          data = eval(data);//get json array
          if (data != null) {
            for (i = 0; i < data.length; i++) {
              if (data[i].selected == 'Selected') {
                var idf = data[i].value;
              }
              $(relationID).get(0).add(new Option(data[i].name, data[i].value), document.all ? i : null);
            }
          }
          if (idf != null) {
            $(relationID).val(idf);
          }
        }
      });
    }
  });
});
{/literal}
</script>
