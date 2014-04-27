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
<feed xmlns='http://www.w3.org/2005/Atom'
    xmlns:gd='http://schemas.google.com/g/2005'>
  <id>{crmURL p='civicrm/admin/event' q="reset=1&list=1&gData=1"}</id>
  <title type='text'>{ts}CiviEvent Public Calendar{/ts}</title>
  <subtitle type='text'>{ts}Listing of current and upcoming public events.{/ts}</subtitle>
  <generator>CiviCRM</generator>
{foreach from=$events key=uid item=event}
<entry xmlns='http://www.w3.org/2005/Atom'
    xmlns:gd='http://schemas.google.com/g/2005'>
  <category scheme='http://schemas.google.com/g/2005#kind'
    term='http://schemas.google.com/g/2005#event'></category>
  <title type='text'>{$event.title}</title>
{if $event.description}
  <content type='text'>{$event.description}</content>
{/if}
{if $event.contact_email}
  <author>
    <email>{$event.contact_email}</email>
  </author>
{/if}
  <gd:transparency
    value='http://schemas.google.com/g/2005#event.opaque'>
  </gd:transparency>
  <gd:eventStatus
    value='http://schemas.google.com/g/2005#event.confirmed'>
  </gd:eventStatus>
{if $event.is_show_location EQ 1 && $event.location}
  <gd:where valueString='{$event.location}'></gd:where>
{/if}
{if $event.start_date}
  <gd:when startTime='{$event.start_date|crmICalDate:1}'
    endTime='{$event.end_date|crmICalDate:1}'></gd:when>
{/if}
</entry>
{/foreach}
</feed>
