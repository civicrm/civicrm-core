{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{* No matches for submitted search request. *}
<div class="messages status">
    {icon icon="fa-info-circle"}{/icon}
        {if $qill}{ts}No matches found for:{/ts}
            {include file="CRM/common/displaySearchCriteria.tpl"}
        {else}
            {ts}None found.{/ts}
        {/if}
        <br />
        {ts}Suggestions:{/ts}
        <ul>
        <li>{ts}if you are searching by Contact name, check your spelling{/ts}</li>
        <li>{ts}try a different spelling or use fewer letters{/ts}</li>
        <li>{ts}if you are searching within a date range, try a wider range of values{/ts}</li>
        <li>{ts}make sure you have enough privileges in the access control system{/ts}</li>
        </ul>
</div>
