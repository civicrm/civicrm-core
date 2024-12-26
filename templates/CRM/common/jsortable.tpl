{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{literal}
<script type="text/javascript">
  CRM.$(function($) {

    function getElementClass(element) {
      return $(element).attr('class') || '';
    }

    // fetch the occurrence of element
    function getRowId(row, str) {
      var optionId;
      $.each(row, function(i, n) {
        if (str === $(n).attr('class')) {
          optionId = i;
        }
      });
      return optionId;
    }

    // for date sorting see http://wiki.civicrm.org/confluence/display/CRMDOC/Sorting+Date+Fields+in+dataTables+Widget
    var useAjax = {/literal}{$useAjax}{literal},
      sourceUrl = '',
      useClass = 'display',
      tcount = 1,
      tableId = [];

    if (useAjax) {
      {/literal}{if $sourceUrl}sourceUrl = "{$sourceUrl}";{/if}{literal}
      useClass = 'pagerDisplay';
      tcount = 5;
    }

    CRM.dataTableCount = CRM.dataTableCount || 1;

    // FIXME: Rewriting DOM ids is probably a bad idea, and could be avoided
    $('table.' + useClass).not('.dataTable').each(function() {
      $(this).attr('id', 'option' + tcount + CRM.dataTableCount);
      tableId.push(CRM.dataTableCount);
      CRM.dataTableCount++;
    });

    $.each(tableId, function(i, n) {
      var tabId = '#option' + tcount + n;
      // get the object of first tr data row.
      var tdObject = $(tabId + ' tr:nth(1) td');
      var id = -1; var count = 0; var columns = ''; var sortColumn = '';
      // build columns array for sorting or not sorting
      $(tabId + ' th').each(function() {
        var option = $(this).prop('id').split("_");
        option = (option.length > 1) ? option[1] : option[0];
        var stype = 'numeric';
        switch (option) {
          case 'sortable':
            sortColumn += '[' + count + ', "{/literal}{$defaultOrderByDirection}{literal}" ],';
            columns += '{"sClass": "' + getElementClass(this) + '"},';
            break;
          case 'date':
            stype = 'date';
          case 'order':
            if ($(this).attr('class') == 'sortable') {
              sortColumn += '[' + count + ', "{/literal}{$defaultOrderByDirection}{literal}" ],';
            }
            var sortId = getRowId(tdObject, $(this).attr('id') + ' hiddenElement');
            columns += '{ "render": function ( data, type, row ) { return "<div style=\'display:none\'>"+ data +"</div>" + row[sortId] ; }, "targets": sortColumn,"bUseRendered": false},';
            break;
          case 'nosort':
            columns += '{ "bSortable": false, "sClass": "' + getElementClass(this) + '"},';
            break;
          case 'currency':
            columns += '{ "sType": "currency" },';
            break;
          case 'link':
            columns += '{"sType": "html"},';
            break;
          default:
            if ($(this).text()) {
              columns += '{"sClass": "' + getElementClass(this) + '"},';
            } else {
              columns += '{ "bSortable": false },';
            }
            break;
        }
        count++;
      });
      columns = columns.substring(0, columns.length - 1);
      sortColumn = sortColumn.substring(0, sortColumn.length - 1);
      columns = JSON.parse('[' + columns + ']');
      sortColumn = JSON.parse('[' + sortColumn + ']');

      var noRecordFoundMsg = {/literal}'{ts escape="js"}None found.{/ts}'{literal};

      var oTable;
      if (useAjax) {
        oTable = $(tabId).dataTable({
          "iDisplayLength": 25,
          "bFilter": false,
          "bAutoWidth": false,
          "aaSorting": sortColumn,
          "aoColumns": columns,
          "bProcessing": true,
          "bJQueryUI": true,
          "asStripClasses": ["odd-row", "even-row"],
          "sPaginationType": "full_numbers",
          "sDom": '<"crm-datatable-pager-top"lfp>rt<"crm-datatable-pager-bottom"ip>',
          "bServerSide": true,
          "sAjaxSource": sourceUrl,
          "oLanguage": {
            "sEmptyTable": noRecordFoundMsg,
            "sZeroRecords": noRecordFoundMsg
          },
          "fnServerData": function (sSource, aoData, fnCallback) {
            $.ajax({
              "dataType": 'json',
              "type": "POST",
              "url": sSource,
              "data": aoData,
              "success": fnCallback
            });
          }
        });
      } else {
        oTable = $(tabId).dataTable({
          "aaSorting": sortColumn,
          "bPaginate": false,
          "bLengthChange": true,
          "bFilter": false,
          "bInfo": false,
          "asStripClasses": ["odd-row", "even-row"],
          "bAutoWidth": false,
          "aoColumns": columns,
          "bSort": true,
          "sDom": 'ti',
          "oLanguage": {
            "sEmptyTable": noRecordFoundMsg,
            "sZeroRecords": noRecordFoundMsg
          }
        });
      }
    });
  });

  // plugin to sort on currency
  cj.fn.dataTableExt.oSort['currency-asc'] = function(a, b) {
    var symbol = "{/literal}{$defaultCurrencySymbol}{literal}";
    var x = (a == "-") ? 0 : a.replace( symbol, "" );
    var y = (b == "-") ? 0 : b.replace( symbol, "" );
    x = parseFloat(x);
    y = parseFloat(y);
    return ((x < y) ? -1 : ((x > y) ? 1 : 0));
  };

  cj.fn.dataTableExt.oSort['currency-desc'] = function(a, b) {
    var symbol = "{/literal}{$defaultCurrencySymbol}{literal}";
    var x = (a == "-") ? 0 : a.replace( symbol, "" );
    var y = (b == "-") ? 0 : b.replace( symbol, "" );
    x = parseFloat(x);
    y = parseFloat(y);
    return ((x < y) ? 1 : ((x > y) ? -1 : 0));
  };
</script>
{/literal}
