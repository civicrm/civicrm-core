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
<?xml version="1.0" encoding="UTF-8"?>
<rss version="2.0">
<channel>
<title>{ts}CiviEvent Public Calendar{/ts}</title>
<link>{$config->userFrameworkBaseURL}</link>
<description>{ts}Listing of current and upcoming public events.{/ts}</description>
<language>{$rssLang}</language>
<generator>CiviCRM</generator>
<docs>http://blogs.law.harvard.edu/tech/rss</docs>
{foreach from=$events key=uid item=event}
<item>
<title>{$event.title|escape:'html'}</title>
<link>{crmURL p='civicrm/event/info' q="reset=1&id=`$event.event_id`" fe=1 a=1}</link>
<description>
{if $event.summary}{$event.summary|escape:'html'}
{/if}
{if $event.description}{$event.description|escape:'html'}
{/if}
{if $event.start_date}{ts}When{/ts}: {$event.start_date|crmDate}{if $event.end_date} {ts}through{/ts} {strip}
        {* Only show end time if end date = start date *}
        {if $event.end_date|date_format:"%Y%m%d" == $event.start_date|date_format:"%Y%m%d"}
            {$event.end_date|date_format:"%I:%M %p"}
        {else}
            {$event.end_date|crmDate}
        {/if}{/strip}
    {/if}
{/if}
{if $event.is_show_location EQ 1 && $event.location}{ts}Where{/ts}: {$event.location|escape:'html'}
{/if}
</description>
{if $event.event_type}<category>{$event.event_type|escape:'html'}</category>
{/if}
{if $event.contact_email}<author>{$event.contact_email}</author>
{/if}
<guid isPermaLink="false">{$event.uid}</guid>
</item>
{/foreach}
</channel>
</rss>
