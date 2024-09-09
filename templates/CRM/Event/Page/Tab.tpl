{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{if $action eq 1 or $action eq 2 or $action eq 8} {* add, update or delete *}
    {include file="CRM/Event/Form/Participant.tpl"}
{elseif $action eq 4}
    {include file="CRM/Event/Form/ParticipantView.tpl"}
{else}
    {if $permission EQ 'edit'}{capture assign=newEventURL}{crmURL p="civicrm/contact/view/participant" q="reset=1&action=add&cid=`$contactId`&context=participant"}{/capture}
    {/if}

    <div class="help">
        <p>{ts 1=$displayName}This page lists all event registrations for %1 since inception.{/ts}
        {capture assign="link"}class="action-item" href="{$newEventURL nofilter}"{/capture}
        {if $permission EQ 'edit'}{ts 1=$link}Click <a %1>Add Event Registration</a> to register this contact for an event.{/ts}{/if}
        {if $accessContribution and $newCredit}
            {capture assign=newCreditURL}{crmURL p="civicrm/contact/view/participant" q="reset=1&action=add&cid=`$contactId`&context=participant&mode=live"}{/capture}
            {capture assign="link"}class="action-item" href="{$newCreditURL|smarty:nodefaults}"{/capture}
            {ts 1=$link|smarty:nodefaults}Click <a %1>Submit Credit Card Event Registration</a> to process a new New Registration on behalf of the participant using their credit card.{/ts}
        {/if}
        </p>
    </div>
    {if $action eq 16 and $permission EQ 'edit'}
       <div class="action-link">
           <a accesskey="N" href="{$newEventURL|smarty:nodefaults}" class="button"><span><i class="crm-i fa-plus-circle" aria-hidden="true"></i> {ts}Add Event Registration{/ts}</span></a>
            {if $accessContribution and $newCredit}
                <a accesskey="N" href="{$newCreditURL|smarty:nodefaults}" class="button"><span><i class="crm-i fa-credit-card" aria-hidden="true"></i> {ts}Submit Credit Card Event Registration{/ts}</a></span>
            {/if}
            <br/><br/>
       </div>
   {/if}

    {if $rows}
      {include file="CRM/common/pager.tpl" location="top"}
        {include file="CRM/Event/Form/Selector.tpl"}
  {include file="CRM/common/pager.tpl" location="bottom"}
    {else}
       <div class="messages status no-popup">
           <table class="form-layout">
             <tr>{icon icon="fa-info-circle"}{/icon}
                   {ts}No event registrations have been recorded for this contact.{/ts}
             </tr>
           </table>
       </div>
    {/if}
{/if}
