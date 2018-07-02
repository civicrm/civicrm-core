{*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2018                                |
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
{if !empty($participantData)}
  <div class="messages status no-popup">
    <i class="crm-i fa-exclamation-triangle"></i>
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
