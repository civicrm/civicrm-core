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
{if $setTab eq 1}
  {if $component eq 'event'}
     {include file="CRM/Event/Form/ManageEvent/Tab.tpl"}
  {/if}
{else}
{if $action eq 1 or $action eq 2 or $action eq 8 or $action eq 16384}
   {include file="CRM/Admin/Form/ScheduleReminders.tpl"}
{else}
  {if !$component}
    {capture assign=schedRemindersDocLink}{docURL page="user/email/scheduled-reminders/"}{/capture}
    <div class="help">
      {ts}Scheduled reminders allow you to automatically send messages to contacts regarding their memberships, participation in events, or other activities.{/ts} {$schedRemindersDocLink}
    </div>
  {/if}
  <div class="crm-content-block crm-block">
  {if $rows}
    <div id="reminder">
      {include file="CRM/Admin/Page/Reminders.tpl"}
    </div>
  {else}
    <div class="messages status no-popup">
      <div class="icon inform-icon"></div>
      {ts}None found.{/ts}
    </div>
  {/if}
  <div class="action-link">
    {assign var='link' value="civicrm/admin/scheduleReminders"}
    {if $component}
      {assign var='urlParams' value="action=add&context=$component&compId=$id&reset=1"}
    {else}
      {assign var='urlParams' value="action=add&reset=1"}
    {/if}
    {crmButton p=$link q=$urlParams id="newScheduleReminder"  icon="plus-circle"}{ts}Add Reminder{/ts}{/crmButton}
  </div>
  </div>
{/if}
{/if}
