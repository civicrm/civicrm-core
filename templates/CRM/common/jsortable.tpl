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
{literal}
<script type="text/javascript">
cj( function( ) {
// for date sorting see http://wiki.civicrm.org/confluence/display/CRMDOC42/Sorting+Date+Fields+in+dataTables+Widget
var useAjax = {/literal}{if $useAjax}1{else}0{/if}{literal};

var sourceUrl = '';
var useClass  = 'display';

var tcount =1;
if ( useAjax ) {
 {/literal}{if isset($sourceUrl)}sourceUrl = "{$sourceUrl}";{/if}{literal}
 useClass = 'pagerDisplay';
 tcount =5;
}

var tableId = '';
var count   = 1;

//rename id of table with sequence
//and create the object for navigation
cj('table.' + useClass).each(function(){
    cj(this).attr('id','option' + tcount + count);
    tableId += count + ',';
    count++;
});

//remove last comma
tableId = tableId.substring(0, tableId.length - 1 );
eval('tableId =[' + tableId + ']');

  cj.each(tableId, function(i,n){
    tabId = '#option' + tcount + n;
    //get the object of first tr data row.
    tdObject = cj(tabId + ' tr:nth(1) td');
    var id = -1; var count = 0; var columns=''; var sortColumn = '';
    //build columns array for sorting or not sorting
    cj(tabId + ' th').each( function( ) {
        var option = cj(this).prop('id').split("_");
        option  = ( option.length > 1 ) ? option[1] : option[0];
        stype   = 'numeric';
        switch( option ) {
            case 'sortable':
                sortColumn += '[' + count + ', "asc" ],';
                columns += '{"sClass": "'+ getElementClass( this ) +'"},';
            break;
            case 'date':
                stype = 'date';
            case 'order':
                if ( cj(this).attr('class') == 'sortable' ){
                    sortColumn += '[' + count + ', "asc" ],';
                }
                sortId   = getRowId(tdObject, cj(this).attr('id') +' hiddenElement' );
                columns += '{ "sType": \'' + stype + '\', "fnRender": function (oObj) { return oObj.aData[' + sortId + ']; },"bUseRendered": false},';
            break;
            case 'nosort':
                columns += '{ "bSortable": false, "sClass": "'+ getElementClass( this ) +'"},';
            break;
            case 'currency':
                columns += '{ "sType": "currency" },';
            break;
            case 'link':
                columns += '{"sType": "html"},';
            break;
            default:
                if ( cj(this).text() ) {
                    columns += '{"sClass": "'+ getElementClass( this ) +'"},';
                } else {
                    columns += '{ "bSortable": false },';
                }
            break;
        }
        count++;
  });
  columns    = columns.substring(0, columns.length - 1 );
  sortColumn = sortColumn.substring(0, sortColumn.length - 1 );
  eval('sortColumn =[' + sortColumn + ']');
  eval('columns =[' + columns + ']');

        var currTable = cj(tabId);
        if (currTable) {
            // contains the dataTables master records
            var s = cj(document).dataTableSettings;
            if (s != 'undefined') {
                var len = s.length;
                for (var i=0; i < len; i++) {
                    // if already exists, remove from the array
                    if (s[i].sInstance = tabId) {
                        s.splice(i,1);
              }
              }
        }
  }

    var noRecordFoundMsg  = {/literal}'{ts escape="js"}There are no records.{/ts}'{literal};

    oTable = null;
    if ( useAjax ) {
      oTable = cj(tabId).dataTable({
              "bFilter"    : false,
              "bAutoWidth" : false,
              "aaSorting"  : sortColumn,
              "aoColumns"  : columns,
              "bProcessing": true,
              "bJQueryUI"  : true,
              "asStripClasses" : [ "odd-row", "even-row" ],
              "sPaginationType": "full_numbers",
              "sDom"       : '<"crm-datatable-pager-top"lfp>rt<"crm-datatable-pager-bottom"ip>',
              "bServerSide": true,
              "sAjaxSource": sourceUrl,
              "oLanguage":{"sEmptyTable"  : noRecordFoundMsg,
              "sZeroRecords" : noRecordFoundMsg },

              {/literal}{if !empty($callBack)}{literal}
              "fnDrawCallback": function() { checkSelected(); },
              {/literal}{/if}{literal}

              "fnServerData": function ( sSource, aoData, fnCallback ) {
                cj.ajax( {
                  "dataType": 'json',
                  "type": "POST",
                  "url": sSource,
                  "data": aoData,
                  "success": fnCallback
                });
              }
         });
    } else {
      oTable = cj(tabId).dataTable({
                "aaSorting"    : sortColumn,
                "bPaginate"    : false,
                "bLengthChange": true,
                "bFilter"      : false,
                "bInfo"        : false,
                "asStripClasses" : [ "odd-row", "even-row" ],
                "bAutoWidth"   : false,
                "aoColumns"   : columns,
            "oLanguage":{"sEmptyTable"  : noRecordFoundMsg,
                         "sZeroRecords" : noRecordFoundMsg }
              });
    }
    var object;

    if ( !useAjax ) {
    cj('a.action-item').click( function(){
        object = cj(this);
        cj('table.display').one( 'mouseover', function() {
            var nNodes     = oTable.fnGetNodes( );
            var tdSelected = cj(object).closest('td');
            var closestEle = cj(object).closest('tr').attr('id');
            cj.each( nNodes, function(i,n) {
                //operation on selected row element.
                if ( closestEle == n.id ){
                    var col = 0;
                    cj('tr#' + closestEle + ' td:not(.hiddenElement)').each( function() {
                        if ( tdSelected.get(0) !== cj(this).get(0)  ){
                            oTable.fnUpdate( cj(this).html() , i, col );
                        }
                        col++;
                    });
                }
            });
        });
    });
    }

    });
});

function getElementClass( element ) {
if( cj(element).attr('class') )   return cj(element).attr('class');
return '';
}

//function to fetch the occurence of element
function getRowId(row,str){
 cj.each( row, function(i, n) {
    if( str === cj(n).attr('class') ) {
        optionId = i;
    }
 });
return optionId;
}

//plugin to sort on currency
var symbol = "{/literal}{$config->defaultCurrencySymbol($config->defaultSymbol)}{literal}";
cj.fn.dataTableExt.oSort['currency-asc']  = function(a,b) {
  var x = (a == "-") ? 0 : a.replace( symbol, "" );
  var y = (b == "-") ? 0 : b.replace( symbol, "" );
  x = parseFloat( x );
  y = parseFloat( y );
  return ((x < y) ? -1 : ((x > y) ?  1 : 0));
};

cj.fn.dataTableExt.oSort['currency-desc'] = function(a,b) {
  var x = (a == "-") ? 0 : a.replace( symbol, "" );
  var y = (b == "-") ? 0 : b.replace( symbol, "" );
  x = parseFloat( x );
  y = parseFloat( y );
  return ((x < y) ?  1 : ((x > y) ? -1 : 0));
};
</script>
{/literal}
