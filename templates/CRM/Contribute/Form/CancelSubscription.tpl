{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}

<div class="crm-block crm-form-block crm-auto-renew-membership-cancellation">
<div class="help">
  {icon icon="fa-info-circle"}{/icon}
  {$cancelRecurDetailText}
  {if $cancelRecurNotSupportedText}
    <div class="status-warning">{$cancelRecurNotSupportedText}</div>
  {/if}
</div>
  {include file="CRM/Core/Form/EntityForm.tpl"}
</div>
