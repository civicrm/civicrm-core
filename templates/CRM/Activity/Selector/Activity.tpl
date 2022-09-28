{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{* Displays Activities. *}

<div>
  {if empty($noFieldSet)}
  <h3 class="crm-table-title">{ts}Activities{/ts}</h3>
  {/if}
{if $rows}
  <form action="{crmURL}" method="post">
  {include file="CRM/common/pager.tpl" location="top"}

  {strip}
    <table class="selector row-highlight">
      <tr class="columnheader">
      {foreach from=$columnHeaders item=header}
        <th scope="col">
        {if $header.sort}
          {assign var='key' value=$header.sort}
          {$sort->_response.$key.link}
        {else}
          {$header.name}
        {/if}
        </th>
      {/foreach}
      </tr>

      {counter start=0 skip=1 print=false}
      {foreach from=$rows item=row}
      <tr class="{cycle values="odd-row,even-row"}{if !empty($row.class)} {$row.class}{/if} crm-activity crm-activity_status-{$row.activity_status_id} crm-activity-type_{$row.activity_type_id}" id="crm-activity_{$row.activity_id}">
        <td class="crm-activity-type crm-activity-type_{$row.activity_type_id}">{$row.activity_type}</td>
        <td class="crm-activity-subject">{$row.subject}</td>
        <td class="crm-activity-source_contact_name">
        {if $contactId == $row.source_contact_id}
          {$row.source_contact_name}
        {elseif $row.source_contact_id}
          <a href="{crmURL p='civicrm/contact/view' q="reset=1&cid=`$row.source_contact_id`"}" title="{ts}View contact{/ts}">{$row.source_contact_name}</a>
        {else}
          <em>n/a</em>
        {/if}
        </td>

        <td class="crm-activity-target_contact_name">
        {if $row.mailingId}
          <a href="{$row.mailingId}" title="{ts}View Mailing Report{/ts}">{$row.recipients}</a>
        {elseif $row.recipients}
          {$row.recipients}
        {elseif !$row.target_contact_name}
          <em>n/a</em>
        {elseif $row.target_contact_name}
            {assign var="showTarget" value=0}
            {foreach from=$row.target_contact_name item=targetName key=targetID}
                {if $showTarget < 5}
                    {if $showTarget};&nbsp;{/if}<a href="{crmURL p='civicrm/contact/view' q="reset=1&cid=`$targetID`"}" title="{ts}View contact{/ts}">{$targetName}</a>
                    {assign var="showTarget" value=$showTarget+1}
                {/if}
            {/foreach}
            {if count($row.target_contact_name) > 5} ({ts}more{/ts}){/if}
        {/if}
        </td>

        <td class="crm-activity-assignee_contact_name">
        {if !$row.assignee_contact_name}
            <em>n/a</em>
        {elseif $row.assignee_contact_name}
            {assign var="showAssignee" value=0}
            {foreach from=$row.assignee_contact_name item=assigneeName key=assigneeID}
                {if $showAssignee < 5}
                    {if $showAssignee};&nbsp;{/if}<a href="{crmURL p='civicrm/contact/view' q="reset=1&cid=`$assigneeID`"}" title="{ts}View contact{/ts}">{$assigneeName}</a>
                    {assign var="showAssignee" value=$showAssignee+1}
                {/if}
            {/foreach}
            {if count($row.assignee_contact_name) > 5}({ts}more{/ts}){/if}
        {/if}
        </td>

        <td class="crm-activity-date_time">{$row.activity_date_time|crmDate}</td>
        <td class="crm-activity-status crm-activity-status_{$row.status_id}">{$row.status}</td>
        <td>{$row.action|smarty:nodefaults|replace:'xx':$row.id}</td>
      </tr>
      {/foreach}

    </table>
  {/strip}

  {include file="CRM/common/pager.tpl" location="bottom"}

  {include file="CRM/Case/Form/ActivityToCase.tpl" contactID=$contactId}
  </form>
{else}

  <div class="messages status no-popup">
    {if $context eq 'home'}
      {ts}There are no Activities to display.{/ts}
    {else}
      {ts}There are no Activities to display.{/ts}{if $permission EQ 'edit'} {ts}You can use the links above to schedule or record an activity.{/ts}{/if}
    {/if}
  </div>

{/if}
{if !$noFieldSet}
{/if}
</div>

