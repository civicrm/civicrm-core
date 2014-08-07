{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.5                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2014                                |
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
<div class="crm-block crm-form-block crm-batch-search-form-block">
  <h3>{ts}Data Entry Batches{/ts}</h3>
  <table class="form-layout-compressed">
    <tr>
      <td>
      {$form.title.html}<br />
        <span class="description font-italic">
        {ts}Complete OR partial batch name.{/ts}
        </span>
      </td>
      <td>{$form.buttons.html}</td>
    </tr>
  </table>
</div>
<div class="crm-submit-buttons">
  <a accesskey="N" href="{crmURL p='civicrm/batch/add' q='reset=1&action=add'}" id="newBatch" class="button"><span><div class="icon add-icon"></div>{ts}New Data Entry Batch{/ts}</span></a><br/>
</div>
<table class="crm-batch-selector">
  <thead>
  <tr>
    <th class="crm-batch-name">{ts}Batch Name{/ts}</th>
    <th class="crm-batch-type">{ts}Type{/ts}</th>
    <th class="crm-batch-item_count">{ts}Item Count{/ts}</th>
    <th class="crm-batch-total_amount">{ts}Total Amount{/ts}</th>
    <th class="crm-batch-status">{ts}Status{/ts}</th>
    <th class="crm-batch-created_by">{ts}Created By{/ts}</th>
    <th></th>
  </tr>
  </thead>
</table>

{literal}
<script type="text/javascript">
CRM.$(function($) {
  buildBatchSelector( false );
  $('#_qf_Search_refresh').click( function() {
    buildBatchSelector(true);
  });

  function buildBatchSelector( filterSearch ) {
    var status = {/literal}{$status}{literal};
    if (filterSearch) {
      crmBatchSelector.fnDestroy();
      var ZeroRecordText = '<div class="status messages">{/literal}{ts escape="js"}No matching Data Entry Batches found for your search criteria.{/ts}{literal}</li></ul></div>';
    }
    else if (status == 1) {
      var ZeroRecordText = {/literal}'<div class="status messages">{ts escape="js"}You do not have any Open Data Entry Batches.{/ts}</div>'{literal};
    }
    else {
      var ZeroRecordText = {/literal}'<div class="status messages">{ts escape="js"}No Data Entry Batches have been created for this site.{/ts}</div>'{literal};
    }

    var columns = '';
    var sourceUrl = {/literal}'{crmURL p="civicrm/ajax/batchlist" h=0 q="snippet=4"}'{literal};
    var $context = $('#crm-main-content-wrapper');

    crmBatchSelector = $('table.crm-batch-selector', $context).dataTable({
      "bFilter"    : false,
      "bAutoWidth" : false,
      "aaSorting"  : [],
      "aoColumns"  : [
        {sClass:'crm-batch-name'},
        {sClass:'crm-batch-type'},
        {sClass:'crm-batch-item_count right'},
        {sClass:'crm-batch-total_amount right'},
        {sClass:'crm-batch-status'},
        {sClass:'crm-batch-created_by'},
        {sClass:'crm-batch-links', bSortable:false}
      ],
      "bProcessing": true,
      "asStripClasses" : [ "odd-row", "even-row" ],
      "sPaginationType": "full_numbers",
      "sDom"       : '<"crm-datatable-pager-top"lfp>rt<"crm-datatable-pager-bottom"ip>',
      "bServerSide": true,
      "bJQueryUI": true,
      "sAjaxSource": sourceUrl,
      "iDisplayLength": 25,
      "oLanguage": { "sZeroRecords":  ZeroRecordText,
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
        if (filterSearch) {
          aoData.push(
            {name:'title', value: $('.crm-batch-search-form-block #title').val()}
          );
        }
        $.ajax({
          "dataType": 'json',
          "type": "POST",
          "url": sSource,
          "data": aoData,
          "success": fnCallback
        });
      }
    });
  }
});

</script>
{/literal}
