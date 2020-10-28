{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{* Display icons / links for ical download and feed for EventInfo.tpl, ThankYou.tpl, DashBoard.tpl, and ManageEvent.tpl *}
  {foreach from=$iCal item="iCalItem"}
  <a href="{$iCalItem.url}" title="{$iCalItem.text}"{if !$event} class="crm-event-feed-link"{/if}>
    <span class="fa-stack" aria-hidden="true"><i class="crm-i fa-calendar-o fa-stack-2x"></i><i style="top: 15%;" class="crm-i {$iCalItem.icon} fa-stack-1x"></i></span>
    <span class="sr-only">{$iCalItem.text}</span>
  </a>
  {/foreach}
