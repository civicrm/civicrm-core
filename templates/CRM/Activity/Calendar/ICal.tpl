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
PRODID:-//CiviCRM//NONSGML CiviCRM iCal//EN
X-WR-TIMEZONE:{$timezone}
METHOD:REQUEST
BEGIN:VEVENT
UID:CIVICRMACTIVITY{$activity->id}
SUMMARY:{$activity->subject|crmICalText}
CALSCALE:GREGORIAN
DTSTAMP;TZID={$timezone}:{$smarty.now|crmDate:'%Y-%m-%d %H:%M:%S'|crmICalDate}
DTSTART;TZID={$timezone}:{$activity->activity_date_time|crmICalDate}
DURATION:PT{$activity->duration}M
{if $activity->location}
LOCATION:{$activity->location|crmICalText}
{/if}
{if $organizer}
ORGANIZER:MAILTO:{$organizer|crmICalText}
{/if}
{foreach from=$contacts item=contact}
ATTENDEE;CN="{$contact.display_name|crmICalText}":MAILTO:{$contact.email|crmICalText}
{/foreach}
END:VEVENT
END:VCALENDAR
