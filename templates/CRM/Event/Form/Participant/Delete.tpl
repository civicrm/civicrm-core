{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{* This template is used for deleting offline Event Registrations *}

<div class="crm-participant-form-block-delete messages status no-popup">
  <div class="crm-content">
      {icon icon="fa-info-circle"}{/icon}
      {ts}WARNING: Deleting this registration will result in the loss of related payment records (if any).{/ts} {ts}Do you want to continue?{/ts}
  </div>
    {if $additionalParticipant}
      <div class="crm-content">
          {ts 1=$additionalParticipant} There are %1 more Participant(s) registered by this participant.{/ts}
      </div>
    {/if}
</div>
{if $additionalParticipant}
    {$form.delete_participant.html}
{/if}
<div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
