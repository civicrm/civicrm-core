{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2015                                |
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
<div class="crm-block crm-activity_task_print-form-block">
<p>
{if $rows }
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
    <td>{ts}Added By{/ts}</td>
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
                   {if $showTarget};&nbsp;{/if}<a href="{crmURL p='civicrm/contact/view' q="reset=1&cid=`$targetID`"}" title="{ts}View contact{/ts}">{$targetName}</a>
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
                   {if $showAssignee};&nbsp;{/if}<a href="{crmURL p='civicrm/contact/view' q="reset=1&cid=`$assigneeID`"}" title="{ts}View contact{/ts}">{$assigneeName}</a>
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
       <div class="icon inform-icon"></div>
         {ts}There are no records selected for Print.{/ts}
    </div>
{/if}
</div>
