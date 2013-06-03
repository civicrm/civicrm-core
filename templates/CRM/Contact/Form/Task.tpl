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
{ts 1=$totalSelectedContacts}Number of selected contacts: %1{/ts}

{if $searchtype eq 'ts_sel'}
<div id="popupContainer">
  <table id="selectedRecords" class="display crm-copy-fields">
    <thead>
    <tr class="columnheader">
      <th class="contact_details">{ts}Name{/ts}</th>
    </tr>
    </thead>

    <tbody>
      {foreach from=$value item='row'}
      <tr class="{cycle values="odd-row,even-row"}">
        <td class="name">{$row}</td>
      </tr>
      {/foreach}
    </tbody>
  </table>

</div><br />
<a href="#" id="popup-button" title="{ts}View Selected Contacts{/ts}">{ts}View Selected Contacts{/ts}</a>
{/if}

{if $searchtype eq 'ts_sel'}
{literal}
<script type="text/javascript">
  cj(function($) {
    $("#popupContainer").css({
      "background-color":"#E0E0E0",
      'display':'none',
    });

    $("#popup-button").click(function() {
      $("#popupContainer").dialog({
        title: "Selected Contacts",
        width:700,
        height:500,
        modal: true,
        overlay: {
          opacity: 0.5,
          background: "black"
        }
      });
      return false;
    });

    var count = 0; var columns = ''; var sortColumn = '';
    $('#selectedRecords th').each(function() {
      if ($(this).attr('class') == 'contact_details') {
        sortColumn += '[' + count + ', "asc" ],';
        columns += '{"sClass": "contact_details"},';
      }
      else {
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

{if $rows}
<div class="form-item">
  <table width="30%">
    <tr class="columnheader">
      <th>{ts}Name{/ts}</th>
    </tr>
    {foreach from=$rows item=row}
      <tr class="{cycle values="odd-row,even-row"}">
        <td>{$row.displayName}</td>
      </tr>
    {/foreach}
  </table>
</div>
{/if}
