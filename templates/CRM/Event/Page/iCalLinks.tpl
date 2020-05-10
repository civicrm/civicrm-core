{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{* Display icons / links for ical download and feed for EventInfo.tpl and ThankYou.tpl *}
{capture assign=icalFile}{crmURL p='civicrm/event/ical' q="reset=1&id=`$event.id`" fe=1 a=1}{/capture}
{capture assign=icalFeed}{crmURL p='civicrm/event/ical' q="reset=1&list=1&id=`$event.id`" fe=1 a=1}{/capture}
<div class="action-link section iCal_links-section">
  <a href="{$icalFile}" title="{ts}Download iCalendar entry for this event.{/ts}">
    <span class="fa-stack"><i class="crm-i fa-calendar-o fa-stack-2x"></i><i style="top: 15%;" class="crm-i fa-download fa-stack-1x"></i></span>
    <span class="sr-only">{ts}Download iCalendar entry for this event.{/ts}</span>
  </a>
  <a href="{$icalFeed}" title="{ts}iCalendar feed for this event.{/ts}">
    <span class="fa-stack"><i class="crm-i fa-calendar-o fa-stack-2x"></i><i style="top: 15%;" class="crm-i fa-link fa-stack-1x"></i></span>
    <span class="sr-only">{ts}iCalendar feed for this event.{/ts}</span>
  </a>
</div>
