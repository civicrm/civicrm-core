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
  <a href="{$iCalItem.url}" {if $isShowICalIconsInline} class="crm-event-feed-link"{/if}>
    <span class="fa-stack" aria-hidden="true"><i class="crm-i fa-calendar-o fa-stack-2x" role="img" aria-hidden="true"></i><i style="top: 15%;" class="crm-i {$iCalItem.icon} fa-stack-1x" role="img" aria-hidden="true"></i></span>
    <span class="label">{$iCalItem.text}</span>
  </a>
  {/foreach}
