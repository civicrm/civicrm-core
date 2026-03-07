{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{* Confirm Activity deletion *}
<div class="crm-block crm-form-block crm-activity_delete-form-block">
  <div class="messages status no-popup">
    {icon icon="fa-info-circle"}{/icon}
    {ts}Are you sure you want to delete the selected Activities?{/ts}
    <p>{include file="CRM/Activity/Form/Task.tpl"}</p>
  </div>
  <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
</div>
