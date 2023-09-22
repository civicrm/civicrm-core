{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{* Confirmation of Cancel Registration *}
<div class="crm-block crm-form-block crm-event-cancel-form-block">
<div class="messages status no-popup">
  {icon icon="fa-info-circle"}{/icon}
  <div>
      <p>{ts}Are you sure you want to set status to Cancelled for the selected participants?{/ts}</p>
      <p>{include file="CRM/Event/Form/Task.tpl"}</p>
  </div>
</div>

<div class="crm-submit-buttons">
  {include file="CRM/common/formButtons.tpl" location="bottom"}
</div>
</div>
