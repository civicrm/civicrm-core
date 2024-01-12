{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{* this template is for configuring Scheduled Reminders *}
{if $action eq 1 or $action eq 2 or $action eq 8 or $action eq 16384}
   {include file="CRM/Admin/Form/ScheduleReminders.tpl"}
{else}
  {capture assign=schedRemindersDocLink}{docURL page="user/email/scheduled-reminders/"}{/capture}
  <div class="help">
    {ts}Scheduled reminders allow you to automatically send messages to contacts regarding their memberships, participation in events, or other activities.{/ts} {$schedRemindersDocLink}
  </div>
  <div class="crm-content-block crm-block">
  {if $rows}
    <div id="reminder">
      {include file="CRM/Admin/Page/Reminders.tpl"}
    </div>
  {else}
    <div class="messages status no-popup">
      {icon icon="fa-info-circle"}{/icon}
      {ts}None found.{/ts}
    </div>
  {/if}
  <div class="action-link">
    {crmButton p=$addNewLink id="newScheduleReminder" icon="plus-circle"}{ts}Add Reminder{/ts}{/crmButton}
  </div>
  </div>
{/if}
