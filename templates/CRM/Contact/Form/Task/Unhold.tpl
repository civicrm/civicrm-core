{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
<div class="crm-block crm-form-block crm-unhold-form-block">
<div class="spacer"></div>
<div class="messages status no-popup">
    {icon icon="fa-info-circle"}{/icon}
          <p>{ts}Are you sure you want to unhold email of selected contact(s)?.{/ts} {ts}This action cannot be undone.{/ts}</p>
      <p>{include file="CRM/Contact/Form/Task.tpl"}</p>
    </div>
<div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location=''}</div>
</div>
