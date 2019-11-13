{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{* Confirmation of Activity deletes  *}
<div class="crm-block crm-form-block crm-activity_delete-form-block">
<div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="top"}</div>
<div class="messages status no-popup">
     <div class="icon inform-icon"></div>
          <p>{ts}Are you sure you want to delete the selected Activities?{/ts}</p>
        <p>{include file="CRM/Activity/Form/Task.tpl"}</p>
    </div>
<p>
<div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
</div>
