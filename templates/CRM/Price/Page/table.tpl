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
{foreach from=$contexts item=context}
{if $context EQ "Event"}
    {if $action eq 8}
        {ts}If you no longer want to use this price set, click the event title below, and modify the fees for that event.{/ts}
    {else}
        {ts}This price set is used by the event(s) listed below. Click the event title to change or remove the price set.{/ts}
    {/if}
    <br /><br />
<table class="report">
      <thead class="sticky">
            <th scope="col">{ts}Event{/ts}</th>
           <th scope="col">{ts}Type{/ts}</th>
           <th scope="col">{ts}Public{/ts}</th>
           <th scope="col">{ts}Date(s){/ts}</th>
      </thead>

      {foreach from=$usedBy.civicrm_event item=event key=id}
           <tr>
               <td><a href="{crmURL p="civicrm/event/manage/fee" q="action=update&reset=1&id=`$id`"}" title="{ts}Change or remove the price set used for this event.{/ts}">{$event.title}</a></td>
               <td>{$event.eventType}</td>
               <td>{if $event.isPublic}{ts}Yes{/ts}{else}{ts}No{/ts}{/if}</td>
               <td>{$event.startDate|crmDate}{if $event.endDate}&nbsp;to&nbsp;{$event.endDate|crmDate}{/if}</td>
           </tr>
      {/foreach}
</table>
{/if}
{if $context EQ "Contribution"}
    {if $action eq 8}
        {ts}If you no longer want to use this price set, click the contribution page title below, and modify the Amounts or Membership tab configuration.{/ts}
    {else}
        {ts}This price set is used by the contribution page(s) listed below. Click the contribution page title to change or remove the price set.{/ts}
    {/if}
    <br /><br />
<table class="report">
      <thead class="sticky">
            <th scope="col">{ts}Contribution Page{/ts}</th>
           <th scope="col">{ts}Type{/ts}</th>
           <th scope="col">{ts}Date(s){/ts}</th>
      </thead>

      {foreach from=$usedBy.civicrm_contribution_page item=contributionPage key=id}
           <tr>
               <td><a href="{crmURL p="civicrm/admin/contribute/settings" q="action=update&reset=1&id=`$id`"}" title="{ts}Change or remove the price set used for this contribution page.{/ts}">{$contributionPage.title}</a></td>
               <td>{$contributionPage.type}</td>
               <td>{$contributionPage.startDate|truncate:10:''|crmDate}{if $contributionPage.endDate}&nbsp;to&nbsp;{$contributionPage.endDate|truncate:10:''|crmDate}{/if}</td>
           </tr>
      {/foreach}
</table>
{/if}
{if $context EQ "EventTemplate"}
  {if $action eq 8}
    {ts}If you no longer want to use this price set, click the event template title below, and modify the fees for that event.{/ts}
  {else}
    {ts}This price set is used by the event template(s) listed below. Click the event template title to change or remove the price set.{/ts}
  {/if}
  <br /><br />
<table class="report">
  <thead class="sticky">
    <th scope="col">{ts}Event Template Name{/ts}</th>
    <th scope="col">{ts}Type{/ts}</th>
    <th scope="col">{ts}Public{/ts}</th>
  </thead>
  {foreach from=$usedBy.civicrm_event_template item=eventTemplate key=id}
    <tr>
      <td><a href="{crmURL p="civicrm/event/manage/fee" q="action=update&reset=1&id=`$id`"}" title="{ts}Change or remove the price set used for this event template.{/ts}">{$eventTemplate.title}</a></td>
      <td>{$eventTemplate.eventType}</td>
      <td>{if $eventTemplate.isPublic}{ts}Yes{/ts}{else}{ts}No{/ts}{/if}</td>
    </tr>
  {/foreach}
</table>
{/if}
{/foreach}