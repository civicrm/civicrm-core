{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2016                                |
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
           <em>{$evSummary}{if $ev.summary|count_characters:true GT 80}  (<a href="{$ev.url}">{ts}more{/ts}...</a>){/if}</em>
           </p>
         {/foreach}
     {else}
   <p>{ts}There are no upcoming events.{/ts}</p>
     {/if}
</div>
