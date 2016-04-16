{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2016                                |
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
    {capture assign=schedRemindersDocLink}{docURL page="user/current/email/scheduled-reminders/"}{/capture}
    <div class="help">
      {ts}Scheduled reminders allow you to automatically send messages to contacts regarding their memberships, participation in events, or other activities.{/ts} {$schedRemindersDocLink}
    </div>
  {/if}
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
{/if}
{/if}
