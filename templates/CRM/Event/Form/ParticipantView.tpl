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
         {crmPermission has='edit event participants'}
         {assign var='editUrlParams' value="reset=1&id=$participantId&cid=$contactId&action=update&context=$context&selectedChild=event"}
         {if ( $context eq 'fulltext' || $context eq 'search' ) && $searchKey}
         {assign var='editUrlParams' value="reset=1&id=$participantId&cid=$contactId&action=update&context=$context&selectedChild=event&key=$searchKey"}
         {/if}
             <a class="button" href="{crmURL p='civicrm/contact/view/participant' q=$editUrlParams}" accesskey="e"><span><i class="crm-i fa-pencil" aria-hidden="true"></i> {ts}Edit{/ts}</span></a>
          {/crmPermission}
          {crmPermission has='delete in CiviEvent'}
            <a class="button" href="{crmURL p='civicrm/participant/delete' q="reset=1&id=$participantId"}"><span><i class="crm-i fa-trash" aria-hidden="true"></i> {ts}Delete{/ts}</span></a>
          {/crmPermission}

        </div>
    </div>
    <table class="crm-info-panel">
    <tr class="crm-event-participantview-form-block-displayName">
      <td class="label">{ts}Participant Name{/ts}</td>
      <td>
        <strong><a href="{crmURL p='civicrm/contact/view' q="reset=1&cid=$contactId"}" title="{ts escape='htmlattribute'}View contact record{/ts}">{$displayName|escape}</a></strong>
        <div>
            <a class="action-item crm-hover-button" href="{crmURL p='civicrm/event/badge' q="reset=1&context=view&id=$participantId&cid=$contactId"}"><i class="crm-i fa-print" aria-hidden="true"></i> {ts}Print Name Badge{/ts}</a>
            <a class="action-item crm-hover-button" href="{crmURL p='civicrm/event/participant/print' q="reset=1&context=view&id=$participantId&cid=$contactId"}"><i class="crm-i fa-print" aria-hidden="true"></i> {ts}PDF letter - print for participant{/ts}</a>
        </div>
      </td>
  </tr>
  {if $participant_registered_by_id} {* Display primary participant *}
      <tr class="crm-event-participantview-form-block-registeredBy">
          <td class="label">{ts}Registered By{/ts}</td>
          <td><a href="{crmURL p='civicrm/contact/view/participant' q="reset=1&id=$participant_registered_by_id&cid=$registered_by_contact_id&action=view"}" title="{ts escape='htmlattribute'}view primary participant{/ts}">{$registered_by_display_name|escape}</a></td>
      </tr>
  {/if}
  {if $additionalParticipants} {* Display others registered by this participant *}
        <tr class="crm-event-participantview-form-block-additionalParticipants">
            <td class="label">{ts}Also Registered by this Participant{/ts}</td>
            <td>
                {foreach from=$additionalParticipants key=participantName item=participantURL}
                    <a href="{$participantURL}" title="{ts escape='htmlattribute'}view additional participant{/ts}">{$participantName|escape}</a><br />
                {/foreach}
            </td>
        </tr>
  {/if}
    <tr class="crm-event-participantview-form-block-event">
      <td class="label">{ts}Event{/ts}</td><td>
        <a href="{crmURL p='civicrm/event/manage/settings' q="action=update&reset=1&id=$event_id"}" title="{ts escape='htmlattribute'}Configure this event{/ts}">{$event|escape}</a>
      </td>
  </tr>

    {if $campaign}
    <tr class="crm-event-participantview-form-block-campaign">
      <td class="label">{ts}Campaign{/ts}</td>
      <td>{$campaign|escape}</td>
    </tr>
    {/if}

    <tr class="crm-event-participantview-form-block-role">
      <td class="label">{ts}Participant Role{/ts}</td>
      <td>{$role|escape}</td></tr>
        <tr class="crm-event-participantview-form-block-register_date">
      <td class="label">{ts}Registration Date{/ts}</td>
      <td>{$register_date|crmDate}&nbsp;</td>
  </tr>
    <tr class="crm-event-participantview-form-block-status">
      <td class="label">{ts}Status{/ts}</td><td>{$status|escape}&nbsp;
      {if $transferName}
        {ts}(Transferred to <a href="{crmURL p='civicrm/contact/view/participant' q="action=view&reset=1&id=$pid&cid=$transferId"}" title="{ts escape='htmlattribute'}View this Participant{/ts}">{$transferName|escape}</a>){/ts}
      {/if}
      </td>
  </tr>
    {if $source}
        <tr class="crm-event-participantview-form-block-event_source">
        <td class="label">{ts}Participant Source{/ts}</td><td>{$source|escape}&nbsp;</td>
      </tr>
    {/if}
    {if $fee_level}
      {if $lineItem}
        {include file="CRM/Price/Page/LineItem.tpl" context="Event" displayLineItemFinancialType=false getTaxDetails=$totalTaxAmount hookDiscount=false}
        {crmPermission has='edit event participants'}
          {if $hasPayment or $parentHasPayment}
            <a class="action-item crm-hover-button" href='{crmURL p="civicrm/event/participant/feeselection" q="reset=1&id=`$participantId`&cid=`$contactId`&action=update"}'><i class="crm-i fa-pencil" aria-hidden="true"></i> {ts}Change Selections{/ts}</a>
          {else}
            <a class="action-item crm-hover-button" href='{crmURL p="civicrm/contact/view/participant" q=$editUrlParams}'><i class="crm-i fa-pencil" aria-hidden="true"></i> {ts}Change Selections{/ts}</a>
          {/if}
          {if $transferOrCancelLink}
            <a class="action-item crm-hover-button" href={$transferOrCancelLink}><i class="crm-i fa-times" aria-hidden="true"></i> {ts}Transfer or Cancel{/ts}</a>
          {/if}
        {/crmPermission}
        <div class="clear"></div>
        <br>
      {else}
        <td class="label">{ts}Event Level{/ts}</td>
        <td>{$fee_level|escape}&nbsp;{if $fee_amount}- {$fee_amount|crmMoney:$currency}{/if}</td>
      {/if}
    {/if}
  </table>
  {foreach from=$note item="rec"}
    {if $rec}
      <div class="messages status no-popup">{icon icon="fa-info-circle"}{/icon} {ts}Note:{/ts} {$rec|escape|nl2br}</div>
    {/if}
  {/foreach}
    {if $participantId and $hasPayment}
      <div id="payment-info"></div>
      {include file="CRM/Contribute/Page/PaymentInfo.tpl" show='payments'}
    {/if}
    {include file="CRM/Custom/Page/CustomDataView.tpl"}
    {if $accessContribution and array_key_exists(0, $rows) and $rows.0.contribution_id}
        {include file="CRM/Contribute/Form/Selector.tpl" context="Search" single=true}
    {/if}
    <div class="crm-submit-buttons">
      {crmPermission has='edit event participants'}
        <a class="button" href="{crmURL p='civicrm/contact/view/participant' q=$editUrlParams}" accesskey="e"><span><i class="crm-i fa-pencil" aria-hidden="true"></i> {ts}Edit{/ts}</span></a>
      {/crmPermission}
      {crmPermission has='delete in CiviEvent'}
        <a class="button" href="{crmURL p='civicrm/participant/delete' q="reset=1&id=$participantId"}"><span><i class="crm-i fa-trash" aria-hidden="true"></i> {ts}Delete{/ts}</span></a>
      {/crmPermission}
      {include file="CRM/common/formButtons.tpl" location="bottom"}
    </div>
</div>
