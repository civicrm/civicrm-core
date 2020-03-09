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
  <div class="icon inform-icon"></div>&nbsp;
  {$cancelRecurDetailText}
  {if !$cancelSupported}
    <div class="status-warning">{$cancelRecurNotSupportedText}</div>
  {/if}
</div>
  {include file="CRM/Core/Form/EntityForm.tpl"}
</div>
