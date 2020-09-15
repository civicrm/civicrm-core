{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{* Confirmation of membership deletes  *}
<div class="crm-block crm-form-block crm-member-task-delete-form-block">
  <div class="messages status no-popup">
     {icon icon="fa-info-circle"}{/icon}
        <span>{ts}Are you sure you want to delete the selected memberships? This delete operation cannot be undone and will delete all transactions and activity associated with these memberships.{/ts}</span>
        <p>{include file="CRM/Member/Form/Task.tpl"}</p>
  </div>
  <div class="crm-submit-buttons">
    {include file="CRM/common/formButtons.tpl" location="bottom"}
  </div>
</div>
