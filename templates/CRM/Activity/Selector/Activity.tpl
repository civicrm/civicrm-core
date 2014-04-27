{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.5                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2014                                |
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
{* Displays Activities. *}

<div>
  {if empty($noFieldSet)}
  <h3 class="crm-table-title">{ts}Activities{/ts}</h3>
  {/if}
{if $rows}
  <form title="activity_pager" action="{crmURL}" method="post">
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
      <tr class="{cycle values="odd-row,even-row"} {$row.class} crm-activity crm-activity_status-{$row.activity_status_id} crm-activity-type_{$row.activity_type_id}" id="crm-activity_{$row.activity_id}">
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
        <td>{$row.action|replace:'xx':$row.id}</td>
      </tr>
      {/foreach}

    </table>
  {/strip}

  {include file="CRM/common/pager.tpl" location="bottom"}

  {include file="CRM/Case/Form/ActivityToCase.tpl" contactID=$contactId}
  </form>
{else}

  <div class="messages status no-popup">
    {if isset($caseview) and $caseview}
      {ts}There are no Activities attached to this case record.{/ts}{if $permission EQ 'edit'} {ts}You can go to the Activities tab to create or attach activity records.{/ts}{/if}
    {elseif $context eq 'home'}
      {ts}There are no Activities to display.{/ts}
    {else}
      {ts}There are no Activites to display.{/ts}{if $permission EQ 'edit'} {ts}You can use the links above to schedule or record an activity.{/ts}{/if}
    {/if}
  </div>

{/if}
{if !$noFieldSet}
{/if}
</div>

