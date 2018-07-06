{*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2018                                |
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
{* Display icons / links for ical download and feed for EventInfo.tpl and ThankYou.tpl *}
{capture assign=icalFile}{crmURL p='civicrm/event/ical' q="reset=1&id=`$event.id`" fe=1 a=1}{/capture}
{capture assign=icalFeed}{crmURL p='civicrm/event/ical' q="reset=1&list=1&id=`$event.id`" fe=1 a=1}{/capture}
<div class="action-link section iCal_links-section">
    <a href="{$icalFile}" title="{ts}Download iCalendar entry for this event.{/ts}"><img src="{$config->resourceBase}i/office-calendar.png" alt="{ts}Download iCalendar entry for this event.{/ts}"></a>&nbsp;&nbsp;<a href="{$icalFeed}" title="{ts}iCalendar feed for this event.{/ts}"><img src="{$config->resourceBase}i/ical_feed.gif" alt="{ts}iCalendar feed for this event.{/ts}" style="margin-bottom: 4px;" /></a>
</div>
