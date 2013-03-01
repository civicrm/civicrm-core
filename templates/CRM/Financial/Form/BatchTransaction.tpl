{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.3                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2013                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
*}
{* this template is used for batch transaction screen, assign/remove transactions to batch  *}
{if $statusID eq 1}
<div class="crm-form-block crm-search-form-block">
  <div class="crm-accordion-wrapper crm-batch_transaction_search-accordion collapsed">
    <div class="crm-accordion-header crm-master-accordion-header">
      {ts}Edit Search Criteria{/ts}
    </div>
    <div class="crm-accordion-body">
      <div id="searchForm" class="crm-block crm-form-block crm-contact-custom-search-activity-search-form-block">
        <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="top"}</div>
        <table class="form-layout-compressed">
          <tr>
            <td class="font-size12pt" colspan="2">{$form.sort_name.label}&nbsp;&nbsp;{$form.sort_name.html|crmAddClass:'twenty'}</td>
          </tr>
          <tr>
          {if $form.contact_tags}
            <td><label>{ts}Contributor Tag(s){/ts}</label>
              {$form.contact_tags.html}
              {literal}
                <script type="text/javascript">
                  cj("select#contact_tags").crmasmSelect({
                    addItemTarget: 'bottom',
                    animate: false,
                    highlight: true,
                    sortable: true,
                    respectParents: true
                  });
                </script>
              {/literal}
            </td>
            {else}
            <td>&nbsp;</td>
          {/if}
          {if $form.group}
            <td><label>{ts}Contributor Group(s){/ts}</label>
              {$form.group.html}
              {literal}
                <script type="text/javascript">
                  cj("select#group").crmasmSelect({
                    addItemTarget: 'bottom',
                    animate: false,
                    highlight: true,
                    sortable: true,
                    respectParents: true
                  });

                </script>
              {/literal}
            </td>
            {else}
            <td>&nbsp;</td>
          {/if}
          {include file="CRM/Contribute/Form/Search/Common.tpl"}
        </table>
        <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="botttom"}</div>
      </div>
    </div>
  </div>
</div>
{if $statusID eq 1}
<div class="form-layout-compressed">{$form.trans_assign.html}&nbsp;{$form.submit.html}</div><br/>
{/if}
<div id="ltype">
  <p></p>
  <div class="form-item">
  {strip}
    <table id="crm-transaction-selector-assign" cellpadding="0" cellspacing="0" border="0">
      <thead>
      <tr>
        <th class="crm-transaction-checkbox">{if $statusID eq 1}{$form.toggleSelect.html}{/if}</th>
        <th class="crm-contact-type"></th>
        <th class="crm-contact-name">{ts}Name{/ts}</th>
        <th class="crm-amount">{ts}Amount{/ts}</th>
	      <th class="crm-trxnID">{ts}Trxn ID{/ts}</th>
        <th class="crm-received">{ts}Received{/ts}</th>
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
cj( function() {
  cj().crmAccordions();
  cj('#_qf_BatchTransaction_submit-top, #_qf_BatchTransaction_submit-botttom').click(function() {
    cj('.crm-batch_transaction_search-accordion:not(.collapsed)').crmAccordionToggle();
  });
  var batchStatus = {/literal}{$statusID}{literal};
  // build transaction listing only for open batches
  if (batchStatus == 1) {
    var paymentInstrumentID = {/literal}{if $paymentInstrumentID neq null}{$paymentInstrumentID}{else}'null'{/if}{literal};
    if (paymentInstrumentID != 'null') {
      buildTransactionSelectorAssign( true );
    }
    else {
      buildTransactionSelectorAssign( false );
    }
    buildTransactionSelectorRemove();
    cj('#_qf_BatchTransaction_submit-botttom, #_qf_BatchTransaction_submit-top').click( function() {
      buildTransactionSelectorAssign( true );
      return false;
    });

    cj("#trans_assign").attr('disabled',true);
    cj("#trans_remove").attr('disabled',true);
    cj('#crm-transaction-selector-assign #toggleSelect').click( function() {
      enableActions('x');
    });
    cj('#crm-transaction-selector-remove #toggleSelects').click( function() {
      enableActions('y');
    });
    cj('#Go').click( function() {
      return selectAction("trans_assign","toggleSelect", "crm-transaction-selector-assign input[id^='mark_x_']");
    });
    cj('#GoRemove').click( function() {
      return selectAction("trans_remove","toggleSelects", "crm-transaction-selector-remove input[id^='mark_y_']");
    });
    cj('#Go').click( function() {
      if (cj("#trans_assign" ).val() != "" && cj("input[id^='mark_x_']").is(':checked')) {
        bulkAssignRemove('Assign');
      }
      return false;
    });
    cj('#GoRemove').click( function() {
      if (cj("#trans_remove" ).val() != "" && cj("input[id^='mark_y_']").is(':checked')) {
        bulkAssignRemove('Remove');
      }
      return false;
    });
    cj("#crm-transaction-selector-assign input[id^='mark_x_']").click( function() {
      enableActions('x');
    });
    cj("#crm-transaction-selector-remove input[id^='mark_y_']").click( function() {
      enableActions('y');
    });

    cj("#crm-transaction-selector-assign #toggleSelect").click( function() {
      if (cj("#crm-transaction-selector-assign #toggleSelect").is(':checked')) {
        cj("#crm-transaction-selector-assign input[id^='mark_x_']").prop('checked',true);
      }
      else {
        cj("#crm-transaction-selector-assign input[id^='mark_x_']").prop('checked',false);
      }
    });
    cj("#crm-transaction-selector-remove #toggleSelects").click( function() {
      if (cj("#crm-transaction-selector-remove #toggleSelects").is(':checked')) {
        cj("#crm-transaction-selector-remove input[id^='mark_y_']").prop('checked',true);
      }
      else {
        cj("#crm-transaction-selector-remove input[id^='mark_y_']").prop('checked',false);
      }
    });
  }
  else {
    buildTransactionSelectorRemove();
  }
});

function enableActions( type ) {
  if (type == 'x') {
    cj("#trans_assign").attr('disabled',false);
  }
  else {
    cj("#trans_remove").attr('disabled',false);
  }
}

function buildTransactionSelectorAssign(filterSearch) {
  var columns = '';
  var sourceUrl = {/literal}'{crmURL p="civicrm/ajax/rest" h=0 q="className=CRM_Financial_Page_AJAX&fnName=getFinancialTransactionsList&snippet=4&context=financialBatch&entityID=$entityID&notPresent=1&statusID=$statusID"}'{literal};
  if ( filterSearch ) {
    sourceUrl = sourceUrl+"&search=1";
    var ZeroRecordText = '<div class="status messages">{/literal}{ts escape="js"}No Contributions found for your search criteria.{/ts}{literal}</li></ul></div>';
  }

  crmBatchSelector1 = cj('#crm-transaction-selector-assign').dataTable({
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
      cj('#searchForm :input').each(function() {
        if (cj(this).val()) {
          aoData.push(
            {name:cj(this).attr('id'), value: cj(this).val()}
          );
          cj(':radio, :checkbox').each(function() {
            if (cj(this).is(':checked')) {
              aoData.push( { name: cj(this).attr('name'), value: cj(this).val() } );
            }
          });
        }
      });
    }
    cj.ajax({
      "dataType": 'json',
      "type": "POST",
      "url": sSource,
      "data": aoData,
      "success": fnCallback
    });
  }
});
}

function buildTransactionSelectorRemove( ) {
  var columns = '';
  var sourceUrl = {/literal}'{crmURL p="civicrm/ajax/rest" h=0 q="className=CRM_Financial_Page_AJAX&fnName=getFinancialTransactionsList&snippet=4&context=financialBatch&entityID=$entityID&statusID=$statusID"}'{literal};

  crmBatchSelector = cj('#crm-transaction-selector-remove').dataTable({
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
    cj.ajax({
      "dataType": 'json',
      "type": "POST",
      "url": sSource,
      "data": aoData,
      "success": fnCallback
    });
  }
});
}

function selectAction( id, toggleSelectId, checkId ) {
  if (cj("#"+ id ).is(':disabled')) {
    return false;
  }
  else if (!cj("#" + toggleSelectId).is(':checked') && !cj("#" + checkId).is(':checked') && cj("#" + id).val() != "") {
    CRM.alert ({/literal}'{ts escape="js"}Please select one or more contributions for this action.{/ts}'{literal});
    return false;
  }
  else if (cj("#" + id).val() == "") {
    CRM.alert ({/literal}'{ts escape="js"}Please select an action from the drop-down menu.{/ts}'{literal});
    return false;
  }
}

function bulkAssignRemove( action ) {
  var postUrl = {/literal}"{crmURL p='civicrm/ajax/rest' h=0 q="className=CRM_Financial_Page_AJAX&fnName=bulkAssignRemove&entityID=$entityID" }"{literal};
  var fids = [];
  if (action == 'Assign') {
    cj("input[id^='mark_x_']:checked").each( function () {
      var a = cj(this).attr('id');
      fids.push(a);
    });
  }
  if (action == 'Remove') {
    cj("input[id^='mark_y_']:checked").each( function () {
      var a = cj(this).attr('id');
      fids.push(a);
    });
  }
  cj.post(postUrl, { ID: fids, action:action }, function(data) {
    //this is custom status set when record update success.
    if (data.status == 'record-updated-success') {
      buildTransactionSelectorAssign( true );
      buildTransactionSelectorRemove();
      batchSummary({/literal}{$entityID}{literal});
    }
    else {
      CRM.alert(data.status);
    } 
  }, 'json');
}
</script>
{/literal}
