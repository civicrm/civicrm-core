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
<div class="view-content">
{if $action eq 1 or $action eq 2 or $action eq 8} {* add, update or view *}
    {include file="CRM/Pledge/Form/Pledge.tpl"}
{elseif $action eq 4}
    {include file="CRM/Pledge/Form/PledgeView.tpl"}
{else}
<div id="help">
    {ts 1=$displayName}Pledges received from %1 since inception.{/ts}
    {if $permission EQ 'edit'}
     {capture assign=newContribURL}{crmURL p="civicrm/contact/view/pledge" q="reset=1&action=add&cid=`$contactId`&context=pledge"}{/capture}
     {capture assign=link}class="action-item" href="{$newContribURL}"{/capture}
     {ts 1=$link}Click <a %1>Add Pledge</a> to record a new pledge received from this contact.{/ts}
    {/if}
</div>

{if $action eq 16 and $permission EQ 'edit'}
    <div class="action-link">
       <a accesskey="N" href="{$newContribURL}" class="button"><span><div class="icon add-icon"></div>{ts}Add Pledge{/ts}</a></span>
       <br/><br/>
    </div>
{/if}


{if $rows}
    <p> </p>
    {include file="CRM/Pledge/Form/Selector.tpl"}

{else}
   <div class="messages status no-popup">
       <div class="icon inform-icon"></div>
            {ts}No pledges have been recorded from this contact.{/ts}
       </div>
{/if}

{if $honor}
    <div class="solid-border-top">
        <br /><label>{ts 1=$displayName}Contributions made in honor of %1{/ts}</label>
    </div>
    {include file="CRM/Contribute/Page/ContributionHonor.tpl"}
{/if}

{/if}
</div>
