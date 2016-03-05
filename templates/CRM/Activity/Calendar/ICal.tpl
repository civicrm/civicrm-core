{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2015                                |
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
PRODID:-//CiviCRM//NONSGML CiviCRM iCal//EN
X-WR-TIMEZONE:{$timezone}
METHOD:REQUEST
BEGIN:VEVENT
UID:CIVICRMACTIVITY{$activity->id}
SUMMARY:{$activity->subject|crmICalText}
CALSCALE:GREGORIAN
DTSTAMP;VALUE=DATE-TIME:{$smarty.now|date_format:'%Y-%m-%d %H:%M:%S'|crmICalDate}
DTSTART;VALUE=DATE-TIME:{$activity->activity_date_time|crmICalDate}
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
