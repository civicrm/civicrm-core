{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{if !empty($participantData)}
  <div class="messages status no-popup">
    <i class="crm-i fa-exclamation-triangle" aria-hidden="true"></i>
    {ts}There are participants registered for repeating events being removed from the set. Those with participants will be converted to standalone events, and those without registration will be deleted.{/ts}
  </div>
  <table class="display">
    <thead><tr>
      <th>{ts}Event ID{/ts}</th>
      <th>{ts}Event{/ts}</th>
      <th>{ts}Participant Count{/ts}</th>
    </tr><thead>
    <tbody>
      {foreach from=$participantData item="row" key="id"}
        {foreach from=$row item="count" key="data"}
          <tr class="{cycle values="odd-row,even-row"}">
            <td>{$id}</td>
            <td><a href="{crmURL p="civicrm/event/manage/settings" q="reset=1&action=update&id=$id"}">{$data}</a></td>
            <td><a href="{crmURL p='civicrm/event/search' q="reset=1&force=1&status=true&event=$id"}">{$count}</a></td>
          </tr>
        {/foreach}
      {/foreach}
    </tbody>
  </table>
{/if}

<h3>
  {ts}A repeating set will be created with the following dates.{/ts}
</h3>
<table class="display row-highlight">
  <thead><tr>
    <th>#</th>
    <th>{ts}Start date{/ts}</th>
    {if $endDates}<th>{ts}End date{/ts}</th>{/if}
  </tr><thead>
  <tbody>
    {foreach from=$dates item="row" key="count"}
      <tr class="{cycle values="odd-row,even-row"}">
        <td>{if $count}{$count+1}{else}{ts}Original{/ts}{/if}</td>
        <td>{$row.start_date}</td>
        {if $endDates}<td>{$row.end_date}</td>{/if}
      </tr>
    {/foreach}
  </tbody>
</table>
