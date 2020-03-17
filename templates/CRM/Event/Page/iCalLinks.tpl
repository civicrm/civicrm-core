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
    <a href="{$icalFile}" title="{ts}Download iCalendar entry for this event.{/ts}"><img src="{$config->resourceBase}i/office-calendar.png" alt="{ts}Download iCalendar entry for this event.{/ts}"></a>&nbsp;&nbsp;<a href="{$icalFeed}" title="{ts}iCalendar feed for this event.{/ts}"><img src="{$config->resourceBase}i/ical_feed.gif" alt="{ts}iCalendar feed for this event.{/ts}" style="margin-bottom: 4px;" /></a>
</div>
