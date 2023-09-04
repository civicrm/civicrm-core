{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{* The price field can be used somewhere but not necessarily in a page/event. In that case we still want to display some message. *}
{assign var='showGenericMessage' value=true}
{foreach from=$contexts item=context}
{if $context EQ "Event"}
  {assign var='showGenericMessage' value=false}
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
  {assign var='showGenericMessage' value=false}
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
  {assign var='showGenericMessage' value=false}
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
{if $showGenericMessage}
  {if $action neq 8}
    {* We don't have to do anything for delete action because the calling tpl already displays something. *}
    {ts}This price set is used by at least one contribution, but is not used by any active events or contribution pages or event templates.{/ts}
  {/if}
{/if}
