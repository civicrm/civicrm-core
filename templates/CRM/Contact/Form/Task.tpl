{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{ts 1=$totalSelectedContacts}Number of selected contacts: %1{/ts}

{if $isSelectedContacts}
<div id="popupContainer">
  <div class="crm-block crm-form-block crm-search-form-block">
    <table id="selectedRecords-" class="display crm-copy-fields crm-sortable">
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
  </div>
</div><br />
<a href="#" id="popup-button" title="{ts}View Selected Contacts{/ts}">{ts}View Selected Contacts{/ts}</a>
{/if}

{if $isSelectedContacts}
{literal}
<script type="text/javascript">
  CRM.$(function($) {
    $("#popupContainer").css({
      "background-color":"#E0E0E0",
      'display':'none'
    });

    $("#popup-button").click(function() {
      $("#popupContainer").dialog({
        title: {/literal}"{ts escape='js'}Selected Contacts{/ts}"{literal},
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
    $('#selectedRecords- th').each(function() {
      if ($(this).attr('class') === 'contact_details') {
        sortColumn += '[' + count + ', "asc" ],';
        columns += '{"sClass": "contact_details"},';
      }
      else {
        columns += '{ "bSortable": false },';
      }
      count++;
    });

  });

</script>
{/literal}
{/if}

{if !empty($rows)}
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
