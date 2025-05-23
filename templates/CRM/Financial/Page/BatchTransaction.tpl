{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}

<div id="enableDisableStatusMsg" class="crm-container" style="display:none;"></div>
<table id="batch-summary" cellpadding="0" cellspacing="0" border="0" class="report crm-batch_summary">
  <thead class="sticky">
    <tr>
     {foreach from=$columnHeaders item=head}
       <th>{$head}</th>
     {/foreach}
    </tr>
  </thead>
  <tbody>
    <tr>
      {foreach from=$columnHeaders item=head key=rowKey}
        <td id="row_{$rowKey}" class="even-row"></td>
      {/foreach}
    </tr>
  </tbody>
</table>

<div class="crm-submit-buttons">{if array_key_exists('close_batch', $form)}{$form.close_batch.html}{/if} {if array_key_exists('close_batch', $form)}{$form.export_batch.html}{/if}</div>

{if in_array($batchStatus, array('Open', 'Reopened'))} {* Add / remove transactions only allowed for Open/Reopened batches *}
  <br /><div class="form-layout-compressed">{$form.trans_remove.html}&nbsp;{$form.rSubmit.html}</div><br/>
{/if}

<div id="ltype">
  <p></p>
  <div class="form-item">
  {strip}
    <table id="crm-transaction-selector-remove-{$entityID}" cellpadding="0" cellspacing="0" border="0">
      <thead>
        <tr>
          <th class="crm-transaction-checkbox">{if in_array($batchStatus, array('Open', 'Reopened'))}{$form.toggleSelects.html}{/if}</th>
          <th class="crm-contact-type"></th>
          <th class="crm-contact-name">{ts}Name{/ts}</th>
          <th class="crm-amount">{ts}Amount{/ts}</th>
          <th class="crm-trxnID">{ts}Trxn ID{/ts}</th>
          <th class="crm-trxn_date">{ts}Payment/Transaction Date{/ts}</th>
          <th class="crm-received">{ts}Contribution Date{/ts}</th>
          <th class="crm-payment-method">{ts}Pay Method{/ts}</th>
          <th class="crm-status">{ts}Status{/ts}</th>
          <th class="crm-type">{ts}Type{/ts}</th>
          <th class="crm-transaction-links"></th>
        </tr>
      </thead>
    </table>
  {/strip}
  </div>
</div>
<br/>
{include file="CRM/Financial/Form/BatchTransaction.tpl"}

{literal}
<script type="text/javascript">
CRM.$(function($) {
  var entityID = {/literal}{$entityID}{literal};
  batchSummary(entityID);
  CRM.$('#close_batch').click( function() {
    assignRemove(entityID, 'close');
    return false;
  });
  CRM.$('#export_batch').click( function() {
    assignRemove(entityID, 'export');
    return false;
  });
});
function assignRemove(recordID, op) {
  var recordBAO = 'CRM_Batch_BAO_Batch';
  if (op === 'assign' || op === 'remove') {
    recordBAO = 'CRM_Batch_BAO_EntityBatch';
  }
  var entityID = {/literal}"{$entityID}"{literal};
  if (op === 'close' || op === 'export') {
    var mismatch = checkMismatch();
  }
  else {
    CRM.$('#mark_x_' + recordID).closest('tr').block({message: {/literal}'{ts escape="js"}Updating{/ts}'{literal}});
  }
  if (op === 'close' || (op === 'export' && mismatch.length)) {
    CRM.$("#enableDisableStatusMsg").dialog({
      title: {/literal}'{ts escape="js"}Close Batch{/ts}'{literal},
      modal: true,
      open:function() {
        if (op == 'close') {
          var msg = {/literal}'{ts escape="js"}Are you sure you want to close this batch?{/ts}'{literal};
        }
        else {
          var msg = {/literal}'{ts escape="js"}Are you sure you want to close and export this batch?{/ts}'{literal};
        }
        CRM.$('#enableDisableStatusMsg').show().html(msg + mismatch);
      },
      buttons: {
        {/literal}"{ts escape='js'}Cancel{/ts}"{literal}: function() {
          CRM.$(this).dialog("close");
        },
        {/literal}"{ts escape='js'}OK{/ts}"{literal}: function() {
          CRM.$(this).dialog("close");
          saveRecord(recordID, op, recordBAO, entityID);
        }
      }
    });
  }
  else {
    saveRecord(recordID, op, recordBAO, entityID);
  }
}

function removeFromBatch(financial_trxn_id) {
  var entityID = "{/literal}{$entityID}{literal}";
  if (financial_trxn_id && entityID) {
    CRM.api4("EntityBatch", "delete", {where: [
      ["entity_id", "=", financial_trxn_id],
      ["entity_table", "=", "civicrm_financial_trxn"],
      ["batch_id", "=", entityID]
    ]}).then(function(batch) {
      buildTransactionSelectorAssign(true);
      buildTransactionSelectorRemove();
      batchSummary(entityID);
    }, function(failure) {
      CRM.alert({/literal}'{ts escape="js"}Error removing from batch.{/ts}', '{ts escape="js"}Api Error{/ts}'{literal}, 'error');
    });
  }
}

function noServerResponse() {
  CRM.alert({/literal}'{ts escape="js"}No response from the server. Check your internet connection and try reloading the page.{/ts}', '{ts escape="js"}Network Error{/ts}'{literal}, 'error');
}

function saveRecord(recordID, op, recordBAO, entityID) {
  if (op == 'export') {
    window.location.href = CRM.url('civicrm/financial/batch/export', {reset: 1, id: recordID, status: 1});
    return;
  }
  var postUrl = "{/literal}{crmURL p='civicrm/ajax/rest' h=0 q="className=CRM_Financial_Page_AJAX&fnName=assignRemove"}&qfKey={$financialAJAXQFKey}{literal}";
  //post request and get response
  CRM.$.post( postUrl, { records: [recordID], recordBAO: recordBAO, op:op, entityID:entityID }, function( html ){
    //this is custom status set when record update success.
    if (html.status == 'record-updated-success') {
       if (op == 'close') {
         window.location.href = CRM.url('civicrm/financial/financialbatches', 'reset=1&batchStatus=2');
       }
       else {
         buildTransactionSelectorAssign( true );
         buildTransactionSelectorRemove();
         batchSummary(entityID);
       }
    }
    else {
      CRM.alert(html.status);
    }
  },
  'json').fail(noServerResponse);
}

function batchSummary(entityID) {
  var postUrl = {/literal}"{crmURL p='civicrm/ajax/rest' h=0 q='className=CRM_Financial_Page_AJAX&fnName=getBatchSummary'}"{literal};
  //post request and get response
  CRM.$.post( postUrl, {batchID: entityID}, function(html) {
    CRM.$.each(html, function(i, val) {
      CRM.$("#row_" + i).html(val);
    });
  },
  'json');
}

function checkMismatch() {
  var txt = '';
  var enteredItem = CRM.$("#row_item_count").text();
  var assignedItem = CRM.$("#row_assigned_item_count").text();
  var enteredTotal = CRM.$("#row_total").text();
  var assignedTotal = CRM.$("#row_assigned_total").text();
  if (enteredItem != "" && enteredItem != assignedItem) {
     txt = '{/literal}<div class="messages crm-error"><strong>Item Count mismatch:</strong><br/>{ts escape="js"}Expected{/ts}:' + enteredItem +'<br/>{ts escape="js"}Current Total{/ts}:' + assignedItem + '</div>{literal}';
  }
  if (enteredTotal != "" && enteredTotal != assignedTotal) {
     txt += '{/literal}<div class="messages crm-error"><strong>Total Amount mismatch</strong><br/>{ts escape="js"}Expected{/ts}:' + enteredTotal +'<br/>{ts escape="js"}Current Total{/ts}:' + assignedTotal + '</div>{literal}';
  }
  if (txt.length) {
    txt += {/literal}'<div class="messages status">{ts escape="js"}Click OK to override and update expected values.{/ts}</div>'{literal}
  }
  return txt;
}
</script>
{/literal}
