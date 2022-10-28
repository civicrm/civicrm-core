{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{* template for assigning the current case to another client*}
<div class="crm-block crm-form-block crm-case-editclient-form-block">
  <div class="messages status no-popup">
    {icon icon="fa-info-circle"}{/icon} {ts 1=$currentClientName|escape 2=$id}Remove Client %1 from case %2{/ts}
  </div>
  <div class="crm-form-block">
    <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
  </div>
</div>
