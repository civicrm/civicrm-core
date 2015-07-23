{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.6                                                |
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
{if $action eq 1 or $action eq 2 or $action eq 8} {* add, update or view *}
    {include file="CRM/Contribute/Form/Contribution.tpl"}
{elseif $action eq 4}
    {include file="CRM/Contribute/Form/ContributionView.tpl"}
{else}
    <div class="view-content">
        <div id="help">
            {if $permission EQ 'edit'}
              {capture assign=newContribRecurURL}{crmURL p="civicrm/contributionrecur/add" q="reset=1&action=add&cid=`$contactId`&context=contribution"}{/capture}
              {capture assign=link}class="action-item" href="{$newContribRecurURL}"{/capture}
              {ts 1=$link}Click <a %1>Set Up Recurring Contribution</a> to setup a new recurring contribution for this contact.{/ts}
            {else}
                {ts 1=$displayName}Contributions received from %1 since inception.{/ts}
            {/if}
        </div>

         {if $action eq 16 and $permission EQ 'edit'}
            <div class="action-link">
                <a accesskey="N" href="{$newContribRecurURL}" class="button"><span><div class="icon ui-icon-circle-plus"></div>{ts}Set Up Recurring Contribution{/ts}</span></a>
                <br /><br />
            </div>
      <div class='clear'> </div>
        {/if}

        {if $recur}
            <div>
                <br /><label>{ts 1=$displayName}Recurring Contributions{/ts}</label>
            </div>
            {include file="CRM/Contribute/Page/ContributionRecur.tpl"}
        {else}    
            <div class="messages status no-popup">
                <div class="icon inform-icon"></div>
                {ts}No recurring contributions have been recorded from this contact.{/ts}
            </div>
        {/if}
    </div>
{/if}
