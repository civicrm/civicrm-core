{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
<div class="crm-block crm-activity_task_print-form-block">
<p>
{if $rows}
<div class="crm-submit-buttons element-right">
  {include file="CRM/common/formButtons.tpl" location="top"}
</div>
<div class="spacer"></div>
<br />
<p>
<table>
  <tr class="columnheader">
    <td>{ts}Type{/ts}</td>
    <td>{ts}Subject{/ts}</td>
    <td>{ts}Added by{/ts}</td>
    <td>{ts}With{/ts}</td>
    <td>{ts}Assigned to{/ts}</td>
    <td>{ts}Date{/ts}</td>
    <td>{ts}Status{/ts}</td>
  </tr>

{foreach from=$rows item=row}
    <tr class="{cycle values="odd-row,even-row"}">
        <td>{$row.activity_type}</td>
        <td>{$row.activity_subject}</td>
        <td>{$row.source_contact_name}</td>
        <td>
          {if !$row.target_contact_name}
             <em>n/a</em>
          {elseif $row.target_contact_name}
             {assign var="showTarget" value=0}
             {foreach from=$row.target_contact_name item=targetName key=targetID}
                {if $showTarget < 5}
                   {if $showTarget};&nbsp;{/if}<a href="{crmURL p='civicrm/contact/view' q="reset=1&cid=`$targetID`"}" title="{ts escape='htmlattribute'}View contact{/ts}">{$targetName}</a>
                     {assign var="showTarget" value=$showTarget+1}
                {/if}
             {/foreach}
          {/if}
        </td>
        <td>
          {if !$row.assignee_contact_name}
             <em>n/a</em>
          {elseif $row.assignee_contact_name}
             {assign var="showAssignee" value=0}
             {foreach from=$row.assignee_contact_name item=assigneeName key=assigneeID}
                {if $showAssignee < 5}
                   {if $showAssignee};&nbsp;{/if}<a href="{crmURL p='civicrm/contact/view' q="reset=1&cid=`$assigneeID`"}" title="{ts escape='htmlattribute'}View contact{/ts}">{$assigneeName}</a>
                     {assign var="showAssignee" value=$showAssignee+1}
                {/if}
             {/foreach}
          {/if}
        </td>
        <td>{$row.activity_date_time}</td>
        <td>{$row.activity_status}</td>
    </tr>
{/foreach}
</table>
<div class="crm-submit-buttons element-right">
  {include file="CRM/common/formButtons.tpl" location="bottom"}
{else}
   <div class="messages status no-popup">
       {icon icon="fa-info-circle"}{/icon}
         {ts}There are no records selected for Print.{/ts}
    </div>
{/if}
</div>
