{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
<div class="view-content">
{if $action eq 1 or $action eq 2 or $action eq 8} {* add, update or view *}
    {include file="CRM/Pledge/Form/Pledge.tpl"}
{elseif $action eq 4}
    {include file="CRM/Pledge/Form/PledgeView.tpl"}
{else}
<div class="help">
    {ts 1=$displayName}Pledges received from %1 since inception.{/ts}
    {if $permission EQ 'edit'}
     {capture assign=newContribURL}{crmURL p="civicrm/contact/view/pledge" q="reset=1&action=add&cid=`$contactId`&context=pledge"}{/capture}
     {ts 1=$link}Click <a class="action-item" href="{$newContribURL|smarty:nodefaults}">Add Pledge</a> to record a new pledge received from this contact.{/ts}
    {/if}
</div>

{if $action eq 16 and $permission EQ 'edit'}
    <div class="action-link">
       <a accesskey="N" href="{$newContribURL|smarty:nodefaults}" class="button"><span><i class="crm-i fa-plus-circle" aria-hidden="true"></i> {ts}Add Pledge{/ts}</a></span>
       <br/><br/>
    </div>
{/if}


{if $rows}
    <p> </p>
    {include file="CRM/Pledge/Form/Selector.tpl"}

{else}
   <div class="messages status no-popup">
       {icon icon="fa-info-circle"}{/icon}
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
