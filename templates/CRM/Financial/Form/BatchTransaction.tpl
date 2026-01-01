{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{* this template is used for batch transaction screen, assign/remove transactions to batch  *}
{if in_array($batchStatus, array('Open', 'Reopened'))}
<div class="crm-form-block crm-search-form-block">
  <details class="crm-accordion-light crm-batch_transaction_search-accordion">
    <summary>
      {ts}Edit Search Criteria{/ts}
    </summary>
    <div class="crm-accordion-body">
      <div id="searchForm" class="crm-block crm-form-block crm-contact-custom-search-activity-search-form-block">
        <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="top"}</div>
        <table class="form-layout-compressed">
          <tr>
            <td colspan="2">
              {$form.sort_name.label}<br>
              {$form.sort_name.html|crmAddClass:'twenty'}
            </td>
          </tr>
          <tr>
          {if !empty($form.contact_tags)}
            <td>
              <label>{ts}Contributor Tag(s){/ts}</label><br>
              {$form.contact_tags.html}
            </td>
            {else}
            <td>&nbsp;</td>
          {/if}
          {if !empty($form.group)}
            <td><label>{ts}Contributor Group(s){/ts}</label><br>
              {$form.group.html}
            </td>
            {else}
            <td>&nbsp;</td>
          {/if}
          </tr>
          {include file="CRM/Contribute/Form/Search/Common.tpl"}
        </table>
	<div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
      </div>
    </div>
  </details>
</div>
{if in_array($batchStatus, array('Open', 'Reopened'))}
<div class="form-layout-compressed">{$form.trans_assign.html}&nbsp;{$form.submit.html}</div><br/>
{/if}
<div id="ltype">
  <p></p>
  <div class="form-item">
  {strip}
    <table id="crm-transaction-selector-assign-{$entityID}" cellpadding="0" cellspacing="0" border="0">
      <thead>
      <tr>
        <th class="crm-transaction-checkbox">{if in_array($batchStatus, array('Open', 'Reopened'))}{$form.toggleSelect.html}{/if}</th>
        <th class="crm-contact-type"></th>
        <th class="crm-contact-name">{ts}Name{/ts}</th>
        <th class="crm-amount">{ts}Amount{/ts}</th>
        <th class="crm-trxnID">{ts}Trxn ID{/ts}</th>
        <th class="crm-trxn_date">{ts}Payment/Transaction Date{/ts}</th>
        <th class="crm-received">{ts}Contribution Date{/ts}</th>
        <th class="crm-payment-method">{ts}Pay Method{/ts}</th>
        <th class="crm-status">{ts}Status{/ts}</th>
        <th class="crm-type">{ts}Financial Type{/ts}</th>
        <th class="crm-transaction-links"></th>
      </tr>
      </thead>
    </table>
  {/strip}
  </div>
</div>
{/if}

{literal}
<script type="text/javascript">
CRM.$(function($) {
  CRM.$('#_qf_BatchTransaction_submit-top, #_qf_BatchTransaction_submit-bottom').click(function() {
    CRM.$('.crm-batch_transaction_search-accordion[open]').prop('open', false);
  });
  var batchStatus = {/literal}{$statusID}{literal};
  {/literal}{if $validStatus}{literal}
    // build transaction listing only for open/reopened batches
    var paymentInstrumentID = {/literal}{if $paymentInstrumentID neq null}{$paymentInstrumentID}{else}'null'{/if}{literal};
    if (paymentInstrumentID != 'null') {
      buildTransactionSelectorAssign( true );
    }
    else {
      buildTransactionSelectorAssign( false );
    }
    buildTransactionSelectorRemove();
    CRM.$('#_qf_BatchTransaction_submit-bottom, #_qf_BatchTransaction_submit-top').click( function() {
      buildTransactionSelectorAssign( true );
      return false;
    });

    CRM.$("#trans_assign").prop('disabled',true);
    CRM.$("#trans_remove").prop('disabled',true);
    CRM.$('#crm-transaction-selector-assign-{/literal}{$entityID}{literal} #toggleSelect').click( function() {
      enableActions('x');
    });
    CRM.$('#crm-transaction-selector-remove-{/literal}{$entityID}{literal} #toggleSelects').click( function() {
      enableActions('y');
    });
    CRM.$('#Go').click( function() {
      return selectAction("trans_assign","toggleSelect", "crm-transaction-selector-assign-{/literal}{$entityID}{literal} input[id^='mark_x_']");
    });
    CRM.$('#GoRemove').click( function() {
      return selectAction("trans_remove","toggleSelects", "crm-transaction-selector-remove-{/literal}{$entityID}{literal} input[id^='mark_y_']");
    });
    CRM.$('#Go').click( function() {
      if (CRM.$("#trans_assign" ).val() != "" && CRM.$("input[id^='mark_x_']").is(':checked')) {
        bulkAssignRemove('Assign');
      }
      return false;
    });
    CRM.$('#GoRemove').click( function() {
      if (CRM.$("#trans_remove" ).val() != "" && CRM.$("input[id^='mark_y_']").is(':checked')) {
        bulkAssignRemove('Remove');
      }
      return false;
    });
    CRM.$("#crm-transaction-selector-assign-{/literal}{$entityID}{literal} input[id^='mark_x_']").click( function() {
      enableActions('x');
    });
    CRM.$("#crm-transaction-selector-remove-{/literal}{$entityID}{literal} input[id^='mark_y_']").click( function() {
      enableActions('y');
    });

    CRM.$("#crm-transaction-selector-assign-{/literal}{$entityID}{literal} #toggleSelect").click( function() {
      toggleFinancialSelections('#toggleSelect', 'assign');
    });
    CRM.$("#crm-transaction-selector-remove-{/literal}{$entityID}{literal} #toggleSelects").click( function() {
      toggleFinancialSelections('#toggleSelects', 'remove');
    });
  {/literal}{else}{literal}
    buildTransactionSelectorRemove();
  {/literal}{/if}{literal}
});

function enableActions( type ) {
  if (type == 'x') {
    CRM.$("#trans_assign").prop('disabled',false);
  }
  else {
    CRM.$("#trans_remove").prop('disabled',false);
  }
}

function toggleFinancialSelections(toggleID, toggleClass) {
  var mark = 'x';
  if (toggleClass == 'remove') {
    mark = 'y';
  }
  if (CRM.$("#crm-transaction-selector-" + toggleClass + "-{/literal}{$entityID}{literal} " +	toggleID).is(':checked')) {
    CRM.$("#crm-transaction-selector-" + toggleClass + "-{/literal}{$entityID}{literal} input[id^='mark_" + mark + "_']").prop('checked',true);
  }
  else {
    CRM.$("#crm-transaction-selector-" + toggleClass + "-{/literal}{$entityID}{literal} input[id^='mark_" + mark + "_']").prop('checked',false);
  }
}

function buildTransactionSelectorAssign(filterSearch) {
  var columns = '';
  var sourceUrl = {/literal}'{crmURL p="civicrm/ajax/rest" h=0 q="className=CRM_Financial_Page_AJAX&fnName=getFinancialTransactionsList&snippet=4&context=financialBatch&entityID=$entityID&notPresent=1&statusID=$statusID"}'{literal};
  if ( filterSearch ) {
    sourceUrl = sourceUrl+"&search=1";
    var ZeroRecordText = '<div class="status messages">{/literal}{ts escape="js"}None found.{/ts}{literal}</li></ul></div>';
  }

  crmBatchSelector1 = CRM.$('#crm-transaction-selector-assign-{/literal}{$entityID}{literal}').dataTable({
  "bDestroy"   : true,
  "bFilter"    : false,
  "bAutoWidth" : false,
  "lengthMenu": [ 10, 25, 50, 100, 250, 500, 1000, 2000 ],
  "aaSorting"  : [[5, 'desc']],
  "aoColumns"  : [
    {sClass:'crm-transaction-checkbox', bSortable:false},
    {sClass:'crm-contact-type', bSortable:false},
    {sClass:'crm-contact-name'},
    {sClass:'crm-amount'},
    {sClass:'crm-trxnID'},
    {sClass:'crm-trxn_date'},
    {sClass:'crm-received'},
    {sClass:'crm-payment-method'},
    {sClass:'crm-status'},
    {sClass:'crm-type'},
    {sClass:'crm-transaction-links', bSortable:false}
  ],
  "bProcessing": true,
  "asStripClasses" : [ "odd-row", "even-row" ],
  "sPaginationType": "full_numbers",
  "sDom"       : '<"crm-datatable-pager-top"lfp>rt<"crm-datatable-pager-bottom"ip>',
  "bServerSide": true,
  "bJQueryUI": true,
  "sAjaxSource": sourceUrl,
  "iDisplayLength": 25,
  "oLanguage": {
    "sZeroRecords":  ZeroRecordText,
    "sProcessing":    {/literal}"{ts escape='js'}Processing...{/ts}"{literal},
    "sLengthMenu":    {/literal}"{ts escape='js'}Show _MENU_ entries{/ts}"{literal},
    "sInfo":          {/literal}"{ts escape='js'}Showing _START_ to _END_ of _TOTAL_ entries{/ts}"{literal},
    "sInfoEmpty":     {/literal}"{ts escape='js'}Showing 0 to 0 of 0 entries{/ts}"{literal},
    "sInfoFiltered":  {/literal}"{ts escape='js'}(filtered from _MAX_ total entries){/ts}"{literal},
    "sSearch":        {/literal}"{ts escape='js'}Search:{/ts}"{literal},
    "oPaginate": {
      "sFirst":    {/literal}"{ts escape='js'}First{/ts}"{literal},
      "sPrevious": {/literal}"{ts escape='js'}Previous{/ts}"{literal},
      "sNext":     {/literal}"{ts escape='js'}Next{/ts}"{literal},
      "sLast":     {/literal}"{ts escape='js'}Last{/ts}"{literal}
    }
  },
  "fnServerData": function ( sSource, aoData, fnCallback ) {
    if ( filterSearch ) {
      CRM.$('#searchForm :input').each(function() {
        if (CRM.$(this).val()) {
          aoData.push(
            {name:CRM.$(this).attr('id'), value: CRM.$(this).val()}
          );
          CRM.$(':radio, :checkbox').each(function() {
            if (CRM.$(this).is(':checked')) {
              aoData.push( { name: CRM.$(this).attr('name'), value: CRM.$(this).val() } );
            }
          });
        }
      });
    }
    CRM.$.ajax({
      "dataType": 'json',
      "type": "POST",
      "url": sSource,
      "data": aoData,
      "success": function(b) {
        fnCallback(b);
        toggleFinancialSelections('#toggleSelect', 'assign');
      }
    });
  }
});

}

function buildTransactionSelectorRemove( ) {
  var columns = '';
  var sourceUrl = {/literal}'{crmURL p="civicrm/ajax/rest" h=0 q="className=CRM_Financial_Page_AJAX&fnName=getFinancialTransactionsList&snippet=4&context=financialBatch&entityID=$entityID&statusID=$statusID"}'{literal};

  crmBatchSelector = CRM.$('#crm-transaction-selector-remove-{/literal}{$entityID}{literal}').dataTable({
  "bDestroy"   : true,
  "bFilter"    : false,
  "bAutoWidth" : false,
  "aaSorting"  : [[5, 'desc']],
  "aoColumns"  : [
    {sClass:'crm-transaction-checkbox', bSortable:false},
    {sClass:'crm-contact-type', bSortable:false},
    {sClass:'crm-contact-name'},
    {sClass:'crm-amount'},
    {sClass:'crm-trxnID'},
    {sClass:'crm-trxn_date'},
    {sClass:'crm-received'},
    {sClass:'crm-payment-method'},
    {sClass:'crm-status'},
    {sClass:'crm-type'},
    {sClass:'crm-transaction-links', bSortable:false}
  ],
  "bProcessing": true,
  "asStripClasses" : [ "odd-row", "even-row" ],
  "sPaginationType": "full_numbers",
  "sDom"       : '<"crm-datatable-pager-top"lfp>rt<"crm-datatable-pager-bottom"ip>',
  "bServerSide": true,
  "bJQueryUI": true,
  "sAjaxSource": sourceUrl,
  "iDisplayLength": 25,
  "oLanguage": {
    "sProcessing":    {/literal}"{ts escape='js'}Processing...{/ts}"{literal},
    "sLengthMenu":    {/literal}"{ts escape='js'}Show _MENU_ entries{/ts}"{literal},
    "sInfo":          {/literal}"{ts escape='js'}Showing _START_ to _END_ of _TOTAL_ entries{/ts}"{literal},
    "sInfoEmpty":     {/literal}"{ts escape='js'}Showing 0 to 0 of 0 entries{/ts}"{literal},
    "sInfoFiltered":  {/literal}"{ts escape='js'}(filtered from _MAX_ total entries){/ts}"{literal},
    "sSearch":        {/literal}"{ts escape='js'}Search:{/ts}"{literal},
    "oPaginate": {
      "sFirst":    {/literal}"{ts escape='js'}First{/ts}"{literal},
      "sPrevious": {/literal}"{ts escape='js'}Previous{/ts}"{literal},
      "sNext":     {/literal}"{ts escape='js'}Next{/ts}"{literal},
      "sLast":     {/literal}"{ts escape='js'}Last{/ts}"{literal}
    }
  },
  "fnServerData": function (sSource, aoData, fnCallback) {
    CRM.$.ajax({
      "dataType": 'json',
      "type": "POST",
      "url": sSource,
      "data": aoData,
      "success": function(b) {
        fnCallback(b);
        toggleFinancialSelections('#toggleSelects', 'remove');
      }
    });
  }
});
}

function selectAction( id, toggleSelectId, checkId ) {
  if (CRM.$("#"+ id ).is(':disabled')) {
    return false;
  }
  else if (!CRM.$("#" + toggleSelectId).is(':checked') && !CRM.$("#" + checkId).is(':checked') && CRM.$("#" + id).val() != "") {
    CRM.alert ({/literal}'{ts escape="js"}Please select one or more contributions for this action.{/ts}'{literal});
    return false;
  }
  else if (CRM.$("#" + id).val() == "") {
    CRM.alert ({/literal}'{ts escape="js"}Please select an action from the drop-down menu.{/ts}'{literal});
    return false;
  }
}

function bulkAssignRemove( action ) {
  var postUrl = {/literal}"{crmURL p='civicrm/ajax/rest' h=0 q="className=CRM_Financial_Page_AJAX&fnName=bulkAssignRemove&entityID=$entityID"}"{literal};
  var fids = [];
  if (action == 'Assign') {
    CRM.$("input[id^='mark_x_']:checked").each( function () {
      var a = CRM.$(this).attr('id');
      fids.push(a);
    });
  }
  if (action == 'Remove') {
    CRM.$("input[id^='mark_y_']:checked").each( function () {
      var a = CRM.$(this).attr('id');
      fids.push(a);
    });
  }
  CRM.$.post(postUrl, { ID: fids, action:action }, function(data) {
    //this is custom status set when record update success.
    if (data.status == 'record-updated-success') {
      buildTransactionSelectorAssign( true );
      buildTransactionSelectorRemove();
      batchSummary({/literal}{$entityID}{literal});
    }
    else {
      CRM.alert(data.status);
    }
  }, 'json').fail(function() {
    CRM.alert('{/literal}{ts escape="js"}Unable to complete the request. The server returned an error or could not be reached.{/ts}{literal}', '{/literal}{ts escape="js"}Request Failed{/ts}{literal}', 'error');
  });
}
</script>
{/literal}
