{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
<div class="messages status float-right no-popup">
  {ts}Total Recipients:{/ts} <strong>{$count|crmNumberFormat}</strong>
</div>
{if $action eq 256 & $ssid eq null}
  <div class="status float-right">
    <div id="popupContainer">
      <table id="selectedRecords" class="display crm-copy-fields">
        <thead>
          <tr class="columnheader">
            <th class="contact_details">Name</th>
          </tr>
        </thead>

        <tbody>
          {foreach from=$value item='row'}
          <tr class="{cycle values="odd-row,even-row"}">
                 <td class="name">{$row}</td>
              {/foreach}
          </tr>
        </tbody>
      </table>
    </div>
     <a href="#" id="button" title="{ts}Contacts selected in the Find Contacts page{/ts}"> {ts}View Selected Contacts{/ts}</a>
  </div>
{literal}
<script type="text/javascript">

  CRM.$(function($) {
    $("#popupContainer").hide();
    $("#button").click(function() {
      $("#popupContainer").dialog({
        title: {/literal}"{ts escape='js'}Selected Contacts{/ts}"{literal},
        width:700,
        height:500,
        modal: true
      });
    });
    var count = 0; var columns=''; var sortColumn = '';

    $('#selectedRecords th').each( function( ) {
      if ( $(this).attr('class') == 'contact_details' ) {
        sortColumn += '[' + count + ', "asc" ],';
        columns += '{"sClass": "contact_details"},';
      } else {
        columns += '{ "bSortable": false },';
      }
      count++;
    });

    columns    = columns.substring(0, columns.length - 1 );
    sortColumn = sortColumn.substring(0, sortColumn.length - 1 );
    eval('sortColumn =[' + sortColumn + ']');
    eval('columns =[' + columns + ']');

    //load jQuery data table.
    $('#selectedRecords').dataTable( {
      "sPaginationType": "full_numbers",
      "bJQueryUI"  : true,
      "aaSorting"  : sortColumn,
      "aoColumns"  : columns,
      "bFilter"    : false
    });

  });

</script>
{/literal}
{/if}
