{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.5                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2014                                |
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
