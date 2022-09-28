{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{* Block to display upcoming events. *}
{* You can add the following additional event elements to this tpl as needed: $ev.end_date, $ev.location, $ev.description, $ev.contact_email *}
{* Change truncate:80 to a larger or smaller value to show more or less of the summary. Remove it to show complete summary. *}
<div id="crm-event-block" class="crm-container">
     {if $eventBlock}
        {foreach from=$eventBlock item=ev}
            <p>
           <a href="{$ev.url}">{$ev.title}</a><br />
           {$ev.start_date|truncate:10:""|crmDate}<br />
           {assign var=evSummary value=$ev.summary|truncate:80:""}
           <em>{$evSummary}{if $ev.summary|crmCountCharacters:true GT 80}  (<a href="{$ev.url}">{ts}more{/ts}...</a>){/if}</em>
           </p>
         {/foreach}
     {else}
   <p>{ts}There are no upcoming events.{/ts}</p>
     {/if}
</div>
