{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
BEGIN:VCALENDAR
VERSION:2.0
PRODID:-//CiviCRM//NONSGML CiviEvent iCal//EN
X-WR-TIMEZONE:{$timezone}
METHOD:PUBLISH
{foreach from=$timezones item=tzItem}
BEGIN:VTIMEZONE
TZID:{$tzItem.id}
{foreach from=$tzItem.transitions item=tzTr}
BEGIN:{$tzTr.type}
TZOFFSETFROM:{$tzTr.offset_from}
TZOFFSETTO:{$tzTr.offset_to}
TZNAME:{$tzTr.abbr}
{if $tzTr.dtstart}
DTSTART:{$tzTr.dtstart|crmICalDate}
{/if}
END:{$tzTr.type}
{/foreach}
END:VTIMEZONE
{/foreach}
{foreach from=$events key=uid item=event}
BEGIN:VEVENT
UID:{$event.uid}
SUMMARY:{$event.title|crmICalText}
{if $event.description}
X-ALT-DESC;FMTTYPE=text/html:{$event.description|crmICalText:true:29}
DESCRIPTION:{$event.description|crmICalText}
{/if}
{if $event.event_type}
CATEGORIES:{$event.event_type|crmICalText}
{/if}
CALSCALE:GREGORIAN
{if $event.start_date}
DTSTAMP;TZID={$timezone}:{$event.start_date|crmICalDate}
DTSTART;TZID={$timezone}:{$event.start_date|crmICalDate}
{else}
DTSTAMP;TZID={$timezone}:{$smarty.now|crmDate:'%Y-%m-%d %H:%M:%S'|crmICalDate}
{/if}
{if $event.end_date}
DTEND;TZID={$timezone}:{$event.end_date|crmICalDate}
{else}
DTEND;TZID={$timezone}:{$event.start_date|crmICalDate}
{/if}
{if $event.is_show_location EQ 1 && $event.location}
LOCATION:{$event.location|crmICalText}
{/if}
{if $event.url}
URL:{$event.url}
{/if}
END:VEVENT
{/foreach}
END:VCALENDAR
