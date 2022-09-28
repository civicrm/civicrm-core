{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
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
{* pubDate must follow RFC822 format *}
<pubDate>{$event.start_date|crmRSSPubDate}</pubDate>
<link>{crmURL p='civicrm/event/info' q="reset=1&id=`$event.event_id`" fe=1 a=1}</link>
<description>
{if $event.summary}{$event.summary|escape:'html'}
{/if}
{if $event.description}{$event.description|escape:'html'}
{/if}
{if $event.start_date}{ts}When{/ts}: {$event.start_date|crmDate}{if $event.end_date} {ts}through{/ts} {strip}
        {* Only show end time if end date = start date *}
        {if $event.end_date|crmDate:"%Y%m%d" == $event.start_date|crmDate:"%Y%m%d"}
            {$event.end_date|crmDate:"%I:%M %p"}
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
