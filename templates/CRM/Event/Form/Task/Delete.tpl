{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{* Confirmation of participation deletes  *}
<div class="crm-block crm-form-block crm-event-delete-form-block">
<div class="messages status no-popup">
  <div class="icon inform-icon"></div>
  <div>
    <p>{ts}Are you sure you want to delete the selected participations? This delete operation cannot be undone and will delete all transactions and activity associated with these participations.{/ts}</p>
        <p>{include file="CRM/Event/Form/Task.tpl"}</p>
  </div>
</div>
{if $additionalParticipants}
    {$form.delete_participant.html}
{/if}
<p>
<div class="crm-submit-buttons">
 {include file="CRM/common/formButtons.tpl" location="bottom"}
</div>
</div>
