{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{* View existing event registration record. *}
<div class="crm-block crm-content-block crm-event-participant-view-form-block">
    <div class="action-link">
        <div class="crm-submit-buttons">
            {if call_user_func(array('CRM_Core_Permission','check'), 'edit event participants')}
         {assign var='urlParams' value="reset=1&id=$id&cid=$contact_id&action=update&context=$context&selectedChild=event"}
         {if ( $context eq 'fulltext' || $context eq 'search' ) && $searchKey}
         {assign var='urlParams' value="reset=1&id=$id&cid=$contact_id&action=update&context=$context&selectedChild=event&key=$searchKey"}
         {/if}
               <a class="button" href="{crmURL p='civicrm/contact/view/participant' q=$urlParams}" accesskey="e"><span><i class="crm-i fa-pencil" aria-hidden="true"></i> {ts}Edit{/ts}</span></a>
            {/if}
            {if call_user_func(array('CRM_Core_Permission','check'), 'delete in CiviEvent')}
                {assign var='urlParams' value="reset=1&id=$id&cid=$contact_id&action=delete&context=$context&selectedChild=event"}
          {if ( $context eq 'fulltext' || $context eq 'search' ) && $searchKey}
          {assign var='urlParams' value="reset=1&id=$id&cid=$contact_id&action=delete&context=$context&selectedChild=event&key=$searchKey"}
          {/if}
                <a class="button" href="{crmURL p='civicrm/contact/view/participant' q=$urlParams}"><span><i class="crm-i fa-trash" aria-hidden="true"></i> {ts}Delete{/ts}</span></a>
            {/if}
            {include file="CRM/common/formButtons.tpl" location="top"}
        </div>
    </div>
    <table class="crm-info-panel">
    <tr class="crm-event-participantview-form-block-displayName">
      <td class="label">{ts}Participant Name{/ts}</td>
      <td>
        <strong><a href="{crmURL p='civicrm/contact/view' q="reset=1&cid=$contact_id"}" title="{ts}View contact record{/ts}">{$displayName}</a></strong>
        <div>
            <a class="action-item crm-hover-button" href="{crmURL p='civicrm/event/badge' q="reset=1&context=view&id=$id&cid=$contact_id"}"><i class="crm-i fa-print" aria-hidden="true"></i> {ts}Print Name Badge{/ts}</a>
        </div>
      </td>
  </tr>
  {if $participant_registered_by_id} {* Display primary participant *}
      <tr class="crm-event-participantview-form-block-registeredBy">
          <td class="label">{ts}Registered By{/ts}</td>
          <td><a href="{crmURL p='civicrm/contact/view/participant' q="reset=1&id=$participant_registered_by_id&cid=$registered_by_contact_id&action=view"}" title="{ts}view primary participant{/ts}">{$registered_by_display_name}</a></td>
      </tr>
  {/if}
  {if $additionalParticipants} {* Display others registered by this participant *}
        <tr class="crm-event-participantview-form-block-additionalParticipants">
            <td class="label">{ts}Also Registered by this Participant{/ts}</td>
            <td>
                {foreach from=$additionalParticipants key=apName item=apURL}
                    <a href="{$apURL}" title="{ts}view additional participant{/ts}">{$apName}</a><br />
                {/foreach}
            </td>
        </tr>
  {/if}
    <tr class="crm-event-participantview-form-block-event">
      <td class="label">{ts}Event{/ts}</td><td>
        <a href="{crmURL p='civicrm/event/manage/settings' q="action=update&reset=1&id=$event_id"}" title="{ts}Configure this event{/ts}">{$event}</a>
      </td>
  </tr>

    {if $campaign}
    <tr class="crm-event-participantview-form-block-campaign">
      <td class="label">{ts}Campaign{/ts}</td>
      <td>{$campaign}</td>
    </tr>
    {/if}

    <tr class="crm-event-participantview-form-block-role">
      <td class="label">{ts}Participant Role{/ts}</td>
      <td>{$role}</td></tr>
        <tr class="crm-event-participantview-form-block-register_date">
      <td class="label">{ts}Registration Date and Time{/ts}</td>
      <td>{$register_date|crmDate}&nbsp;</td>
  </tr>
    <tr class="crm-event-participantview-form-block-status">
      <td class="label">{ts}Status{/ts}</td><td>{$status}&nbsp;
      {if $transferName}
        {ts}(Transferred to <a href="{crmURL p='civicrm/contact/view/participant' q="action=view&reset=1&id=$pid&cid=$transferId"}" title="{ts}View this Participant{/ts}">{$transferName}</a>){/ts}
      {/if}
      </td>
  </tr>
    {if $source}
        <tr class="crm-event-participantview-form-block-event_source">
        <td class="label">{ts}Event Source{/ts}</td><td>{$source}&nbsp;</td>
      </tr>
    {/if}
    {if $participantId and $hasPayment}
      <tr>
        <td class='label'>{ts}Fees{/ts}</td>
        <td id='payment-info'></td>
      </tr>
    {/if}
    {if $fee_level}
        <tr class="crm-event-participantview-form-block-fee_amount">
            {if $lineItem}
                <td class="label">{ts}Selections{/ts}</td>
                <td>{include file="CRM/Price/Page/LineItem.tpl" context="Event"}
                {if call_user_func(array('CRM_Core_Permission','check'), 'edit event participants')}
                    {if $hasPayment or $parentHasPayment}
                        <a class="action-item crm-hover-button" href='{crmURL p="civicrm/event/participant/feeselection" q="reset=1&id=`$participantId`&cid=`$contactId`&action=update"}'><i class="crm-i fa-pencil" aria-hidden="true"></i> {ts}Change Selections{/ts}</a>
                    {/if}
                    {if $transferOrCancelLink}
                      <a class="action-item crm-hover-button" href={$transferOrCancelLink}><i class="crm-i fa-times" aria-hidden="true"></i> {ts}Transfer or Cancel{/ts}</a>
                    {/if}
                {/if}
                </td>
            {else}
                <td class="label">{ts}Event Level{/ts}</td>
                <td>{$fee_level}&nbsp;{if $fee_amount}- {$fee_amount|crmMoney:$fee_currency}{/if}</td>
            {/if}
        </tr>
    {/if}
    {foreach from=$note item="rec"}
      {if $rec }
            <tr><td class="label">{ts}Note{/ts}</td><td>{$rec|nl2br}</td></tr>
      {/if}
    {/foreach}
    </table>
    {if $participantId and $hasPayment}
      {include file="CRM/Contribute/Page/PaymentInfo.tpl" show='payments'}
    {/if}
    {include file="CRM/Custom/Page/CustomDataView.tpl"}
    {if $accessContribution and $rows.0.contribution_id}
        {include file="CRM/Contribute/Form/Selector.tpl" context="Search"}
    {/if}
    <div class="crm-submit-buttons">
        {if call_user_func(array('CRM_Core_Permission','check'), 'edit event participants')}
    {assign var='urlParams' value="reset=1&id=$id&cid=$contact_id&action=update&context=$context&selectedChild=event"}
    {if ( $context eq 'fulltext' || $context eq 'search' ) && $searchKey}
    {assign var='urlParams' value="reset=1&id=$id&cid=$contact_id&action=update&context=$context&selectedChild=event&key=$searchKey"}
    {/if}

           <a class="button" href="{crmURL p='civicrm/contact/view/participant' q=$urlParams}" accesskey="e"><span><i class="crm-i fa-pencil" aria-hidden="true"></i> {ts}Edit{/ts}</span></a>
        {/if}
        {if call_user_func(array('CRM_Core_Permission','check'), 'delete in CiviEvent')}
    {assign var='urlParams' value="reset=1&id=$id&cid=$contact_id&action=delete&context=$context&selectedChild=event"}
    {if ( $context eq 'fulltext' || $context eq 'search' ) && $searchKey}
    {assign var='urlParams' value="reset=1&id=$id&cid=$contact_id&action=delete&context=$context&selectedChild=event&key=$searchKey"}
    {/if}
            <a class="button" href="{crmURL p='civicrm/contact/view/participant' q=$urlParams}"><span><i class="crm-i fa-trash" aria-hidden="true"></i> {ts}Delete{/ts}</span></a>
        {/if}
        {include file="CRM/common/formButtons.tpl" location="bottom"}
    </div>
</div>
