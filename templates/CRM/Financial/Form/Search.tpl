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

{* Financial search component. *}
<div id="enableDisableStatusMsg" class="crm-container" style="display:none"></div>
<div class="crm-submit-buttons">
  <a accesskey="N" href="{crmURL p='civicrm/financial/batch' q="reset=1&action=add&context=$batchStatus"}" id="newBatch" class="button"><span><div class="icon add-icon"></div>{ts}New Accounting Batch{/ts}</span></a>
</div>
<div class="crm-form-block crm-search-form-block">
  <div class="crm-accordion-wrapper crm-activity_search-accordion">
    <div class="crm-accordion-header">
      {ts}Filter Results{/ts}
    </div>
    <div class="crm-accordion-body">
      <div id="financial-search-form" class="crm-block crm-form-block">
        <table class="form-layout-compressed">
          {* Loop through all defined search criteria fields (defined in the buildForm() function). *}
          {foreach from=$elements item=element}
            <tr class="crm-financial-search-form-block-{$element}">
              <td class="label">{$form.$element.label}</td>
              <td>{$form.$element.html}</td>
            </tr>
          {/foreach}
        </table>
      </div>
    </div>
  </div>
</div>
<div class="form-layout-compressed">{$form.batch_update.html}&nbsp;{$form.submit.html}</div><br/>
<table id="crm-batch-selector" class="row-highlight">
  <thead>
    <tr>
      <th class="crm-batch-checkbox">{$form.toggleSelect.html}</th>
      <th class="crm-batch-name">{ts}Batch Name{/ts}</th>
      <th class="crm-batch-payment_instrument">{ts}Payment Instrument{/ts}</th>
      <th class="crm-batch-item_count">{ts}Item Count{/ts}</th>
      <th class="crm-batch-total">{ts}Total Amount{/ts}</th>
      <th class="crm-batch-status">{ts}Status{/ts}</th>
      <th class="crm-batch-created_by">{ts}Created By{/ts}</th>
      <th></th>
    </tr>
  </thead>
</table>
{include file="CRM/Form/validate.tpl"}
{literal}
<script type="text/javascript">
cj(function($) {
  var batchSelector;
  buildBatchSelector();
  $("#batch_update").removeAttr('disabled');

  $('#financial-search-form :input').change(function() {
    if (!$(this).hasClass('crm-inline-error')) {
      batchSelector.fnDraw();
    }
  });

  $('#financial-search-form :input').keypress(function(event) {
    if (event.which == 13) {
      event.preventDefault();
      $(this).change();
      return false;
    }
  });

  var checkedRows = [];
  function buildBatchSelector() {
    var ZeroRecordText = {/literal}'<div class="status messages">{ts escape="js"}No Accounting Batches match your search criteria.{/ts}</div>'{literal};
    var sourceUrl = {/literal}'{crmURL p="civicrm/ajax/batchlist" h=0 q="snippet=4&context=financialBatch"}'{literal};

    batchSelector = $('#crm-batch-selector').dataTable({
      "bFilter" : false,
      "bAutoWidth" : false,
      "aaSorting" : [],
      "aoColumns" : [
        {sClass:'crm-batch-checkbox', bSortable:false},
        {sClass:'crm-batch-name'},
        {sClass:'crm-batch-payment_instrument'},
        {sClass:'crm-batch-item_count right'},
        {sClass:'crm-batch-total right'},
        {sClass:'crm-batch-status'},
        {sClass:'crm-batch-created_by'},
        {sClass:'crm-batch-links', bSortable:false},
       ],
      "bProcessing": true,
      "asStripClasses" : ["odd-row", "even-row"],
      "sPaginationType": "full_numbers",
      "sDom" : '<"crm-datatable-pager-top"lfp>rt<"crm-datatable-pager-bottom"ip>',
      "bServerSide": true,
      "bJQueryUI": true,
      "sAjaxSource": sourceUrl,
      "iDisplayLength": 25,
      "oLanguage": {
        "sZeroRecords": ZeroRecordText,
        "sProcessing": {/literal}"{ts escape='js'}Processing...{/ts}"{literal},
        "sLengthMenu": {/literal}"{ts escape='js'}Show _MENU_ entries{/ts}"{literal},
        "sInfo": {/literal}"{ts escape='js'}Showing _START_ to _END_ of _TOTAL_ entries{/ts}"{literal},
        "sInfoEmpty": {/literal}"{ts escape='js'}Showing 0 to 0 of 0 entries{/ts}"{literal},
        "sInfoFiltered": {/literal}"{ts escape='js'}(filtered from _MAX_ total entries) {/ts}"{literal},
        "sSearch": {/literal}"{ts escape='js'}Search:{/ts}"{literal},
        "oPaginate": {
          "sFirst": {/literal}"{ts escape='js'}First{/ts}"{literal},
          "sPrevious": {/literal}"{ts escape='js'}Previous{/ts}"{literal},
          "sNext": {/literal}"{ts escape='js'}Next{/ts}"{literal},
          "sLast": {/literal}"{ts escape='js'}Last{/ts}"{literal}
        }
      },
      "fnServerParams": function (aoData) {
        $('#financial-search-form :input').each(function() {
          if ($(this).val()) {
            aoData.push(
              {name:$(this).attr('id'), value: $(this).val()}
            );
          }
        });
        checkedRows = [];
        $("#crm-batch-selector input.select-row:checked").each(function() {
          checkedRows.push('#' + $(this).attr('id'));
        });
      },
      "fnRowCallback": function(nRow, aData, iDisplayIndex, iDisplayIndexFull) {
        var box = $(aData[0]);
        var id = box.attr('id').replace('check_', '');
        $(nRow).addClass('crm-entity').attr('data-entity', 'batch').attr('data-id', id).attr('data-status_id', box.attr('data-status_id'));
        $('td:eq(1)', nRow).wrapInner('<div class="crm-editable crmf-title" />');
        return nRow;
      },
      "fnDrawCallback": function(oSettings) {
        $('.crm-editable', '#crm-batch-selector').crmEditable();
        $("#toggleSelect").prop('checked', false);
        if (checkedRows.length) {
          $(checkedRows.join(',')).prop('checked', true).change();
        }
      }
    });
  }

  function editRecords(records, op) {
    records = validateOp(records, op);
    if (records.length) {
      $("#enableDisableStatusMsg").dialog({
        title: {/literal}'{ts escape="js"}Confirm Changes{/ts}'{literal},
        modal: true,
        bgiframe: true,
        position: "center",
        overlay: {
          opacity: 0.5,
          background: "black"
        },
        open:function() {
          switch (op) {{/literal}
            case 'reopen':
              var msg = '<h3>{ts escape="js"}Are you sure you want to re-open:{/ts}</h3>';
              break;
            case 'delete':
              var msg = '<h3>{ts escape="js"}Are you sure you want to delete:{/ts}</h3>';
              break;
            case 'close':
              var msg = '<h3>{ts escape="js"}Are you sure you want to close:{/ts}</h3>';
              break;
            case 'export':
              var msg = '<h3>{ts escape="js"}Export:{/ts}</h3>\
              <div>\
                <label>{ts escape="js"}Format:{/ts}</label>\
                <select class="export-format">\
                  <option value="IIF">IIF</option>\
                  <option value="CSV">CSV</option>\
                </select>\
              </div>';
              break;
          {literal}}
          msg += listRecords(records, op == 'close' || op == 'export');
          $('#enableDisableStatusMsg').show().html(msg);
        },
        buttons: {
          {/literal}"{ts escape='js'}Cancel{/ts}"{literal}: function() {
            $(this).dialog("close");
          },
          {/literal}"{ts escape='js'}OK{/ts}{literal}": function() {
            saveRecords(records, op);
            $(this).dialog("close");
          }
        }
      });
    }
  }

  function listRecords(records, compareValues) {
    var txt = '<ul>',
    mismatch = false;
    for (var i in records) {
      var $tr = $('tr[data-id=' + records[i] + ']');
      txt += '<li>' + $('.crmf-title', $tr).text();
      if (compareValues) {
        $('.actual-value.crm-error', $tr).each(function() {
          mismatch = true;
          var $th = $tr.closest('table').find('th').eq($(this).closest('td').index());
          var $expected = $(this).siblings('.expected-value');
          var label = $th.text();
          var actual = $(this).text();
          var expected = $expected.text();
          txt += {/literal}'<div class="messages crm-error"><strong>' +
          label + ' {ts escape="js"}mismatch.{/ts}</strong><br />{ts escape="js"}Expected{/ts}: ' + expected + '<br />{ts escape="js"}Current Total{/ts}: ' + actual + '</div>'{literal};
        });
      }
      txt += '</li>';
    }
    txt += '</ul>';
    if (mismatch) {
      txt += {/literal}'<div class="messages status">{ts escape="js"}Click OK to override and update expected values.{/ts}</div>'{literal}
    }
    return txt;
  }

  function saveRecords(records, op) {
    if (op == 'export') {
      return exportRecords(records);
    }
    var postUrl = CRM.url('civicrm/ajax/rest', 'className=CRM_Financial_Page_AJAX&fnName=assignRemove');
    //post request and get response
    $.post(postUrl, {records: records, recordBAO: 'CRM_Batch_BAO_Batch', op: op, key: {/literal}"{crmKey name='civicrm/ajax/ar'}"{literal}},
      function(response) {
        //this is custom status set when record update success.
        if (response.status == 'record-updated-success') {
          CRM.alert(listRecords(records), op == 'delete' ? {/literal}'{ts escape="js"}Deleted{/ts}' : '{ts escape="js"}Updated{/ts}'{literal}, 'success');
          batchSelector.fnDraw();
        }
        else {
          CRM.alert({/literal}'{ts escape="js"}An error occurred while processing your request.{/ts}', $("#batch_update option[value=" + op + "]").text() + ' {ts escape="js"}Error{/ts}'{literal}, 'error');
        }
      },
      'json').error(serverError);
  }

  function exportRecords(records) {
    var query = {'batch_id': records, 'export_format': $('select.export-format').val()};
    var exportUrl = CRM.url('civicrm/financial/batch/export', 'reset=1');
    // jQuery redirect expects all query args as an object, so extract them from crm url
    var urlParts = exportUrl.split('?');
    $.each(urlParts[1].split('&'), function(key, val) {
      var q = val.split('=');
      query[q[0]] = q[1];
    });
    $().redirect(urlParts[0], query, 'GET');
    setTimeout(function() {batchSelector.fnDraw();}, 4000);
  }

  function validateOp(records, op) {
    switch (op) {
      case 'reopen':
        var notAllowed = [1, 5];
        break;
      case 'close':
        var notAllowed = [2, 5];
        break;
      case 'export':
        var notAllowed = [5];
        break;
      default:
        return records;
    }
    var len = records.length;
    var invalid = {};
    var i = 0;
    while (i < len) {
      var status = $('tr[data-id='+records[i]+']').data('status_id');
      if ($.inArray(status, notAllowed) >= 0) {
        $('#check_' + records[i] + ':checked').prop('checked', false).change();
        invalid[status] = invalid[status] || [];
        invalid[status].push(records[i]);
        records.splice(i, 1);
        --len;
      }
      else {
        i++;
      }
    }
    for (status in invalid) {
      i = invalid[status];
      var msg = (i.length == 1 ? {/literal}'{ts escape="js"}This record already has the status{/ts}' : '{ts escape="js"}The following records already have the status{/ts}'{literal}) + ' ' + $('tr[data-id='+i[0]+'] .crm-batch-status').text() + ':' + listRecords(i);
      CRM.alert(msg, {/literal}'{ts escape="js"}Cannot{/ts} '{literal} + $("#batch_update option[value=" + op + "]").text());
    }
    return records;
  }

  function serverError() {
     CRM.alert({/literal}'{ts escape="js"}No response from the server. Check your internet connection and try reloading the page.{/ts}', '{ts escape="js"}Network Error{/ts}'{literal}, 'error');
  }

  $('#Go').click(function() {
    var op = $("#batch_update").val();
    if (op == "") {
       CRM.alert({/literal}'{ts escape="js"}Please select an action from the menu.{/ts}', '{ts escape="js"}No Action Selected{/ts}'{literal});
    }
    else if (!$("input.select-row:checked").length) {
       CRM.alert({/literal}'{ts escape="js"}Please select one or more batches for this action.{/ts}', '{ts escape="js"}No Batches Selected{/ts}'{literal});
    }
    else {
      records = [];
      $("input.select-row:checked").each(function() {
        records.push($(this).attr('id').replace('check_', ''));
      });
      editRecords(records, op);
    }
    return false;
  });

  $('#crm-container').on('click', 'a.action-item[href="#"]', function(event) {
    event.stopImmediatePropagation();
    editRecords([$(this).closest('tr').attr('data-id')], $(this).attr('rel'));
    return false;
  });

});

</script>
{/literal}
