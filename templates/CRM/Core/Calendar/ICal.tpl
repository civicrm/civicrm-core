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
{foreach from=$events key=uid item=event}
BEGIN:VEVENT
UID:{$event.uid}
SUMMARY:{$event.title|crmICalText}
{if $event.description}
DESCRIPTION:{$event.description|crmICalText}
{/if}
{if $event.event_type}
CATEGORIES:{$event.event_type|crmICalText}
{/if}
CALSCALE:GREGORIAN
{if $event.start_date}
DTSTAMP;VALUE=DATE-TIME:{$event.start_date|crmICalDate}
DTSTART;VALUE=DATE-TIME:{$event.start_date|crmICalDate}
{else}
DTSTAMP;VALUE=DATE-TIME:{$smarty.now|date_format:'%Y-%m-%d %H:%M:%S'|crmICalDate}
{/if}
{if $event.end_date}
DTEND;VALUE=DATE-TIME:{$event.end_date|crmICalDate}
{else}
DTEND;VALUE=DATE-TIME:{$event.start_date|crmICalDate}
{/if}
{if $event.is_show_location EQ 1 && $event.location}
LOCATION:{$event.location|crmICalText}
{/if}
{if $event.contact_email}
ORGANIZER:MAILTO:{$event.contact_email|crmICalText}
{/if}
{if $event.url}
URL:{$event.url}
{/if}
END:VEVENT
{/foreach}
END:VCALENDAR
