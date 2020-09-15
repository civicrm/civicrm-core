{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{* No matches for submitted search request or viewing an empty group. *}
<div class="messages status no-popup">
  {icon icon="fa-info-circle"}{/icon}
        {if $context EQ 'smog'}
            {capture assign=crmURL}{crmURL q="context=amtg&amtgID=`$group.id`&reset=1"}{/capture}{ts 1=$group.title 2=$crmURL}%1 has no contacts which match your search criteria. You can <a href='%2'>add contacts here.</a>{/ts}
        {else}
            {if $qill}{ts}No matches found for:{/ts}
                {include file="CRM/common/displaySearchCriteria.tpl"}
                <br />
            {else}
            {ts}None found.{/ts}
            {/if}
            {ts}Suggestions:{/ts}
            <ul>
            <li>{ts}check your spelling{/ts}</li>
            <li>{ts}try a different spelling or use fewer letters{/ts}</li>
            <li>{ts}if you are searching within a Group or for Tagged contacts, try 'any group' or 'any tag'{/ts}</li>
            {if $context NEQ 'Profile'}
            {capture assign=crmURLI}{crmURL p='civicrm/contact/add' q='ct=Individual&reset=1'}{/capture}
            {capture assign=crmURLO}{crmURL p='civicrm/contact/add' q='ct=Organization&reset=1'}{/capture}
            {capture assign=crmURLH}{crmURL p='civicrm/contact/add' q='ct=Household&reset=1'}{/capture}
            <li>{ts 1=$crmURLI 2=$crmURLO 3=$crmURLH}add a <a href='%1'>New Individual</a>, <a href='%2'>Organization</a> or <a href='%3'>Household</a>{/ts}</li>
            <li>{ts}make sure you have enough privileges in the access control system{/ts}</li>
            {/if}
            </ul>
        {/if}
</div>
