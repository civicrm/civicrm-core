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
{* No matches for submitted search request or viewing an empty group. *}
<div class="messages status no-popup">
  <div class="icon inform-icon"></div>&nbsp;
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
