{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{* this template is used for adding/editing/deleting Payment-Method  *}
<div class="crm-block crm-form-block crm-contribution-payment_instrument-form-block">
<fieldset><legend>{if $action eq 1}{ts}New Payment Method{/ts}{elseif $action eq 2}{ts}Edit Payment Method{/ts}{else}{ts}Delete Payment Method{/ts}{/if}</legend>
  <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="top"}</div>
   {if $action eq 8}
      <div class="messages status no-popup">
           <div class="icon inform-icon"></div>
          {ts}WARNING: Deleting this option will result in the loss of all contribution records which use this option.{/ts} {ts}This may mean the loss of a substantial amount of data, and the action cannot be undone.{/ts} {ts}Do you want to continue?{/ts}
      </div>
     {else}
     <table>
   <tr class="crm-contribution-form-block-name"><td class="label">{$form.name.label}</td><td class="html-adjust">{$form.name.html}</td></tr>
      <tr class="crm-contribution-form-block-description"><td class="label">{$form.description.label}</td><td class="html-adjust">{$form.description.html}</td></tr>
        <tr class="crm-contribution-form-block-is_active"><td class="label">{$form.is_active.label}</td><td class="html-adjust">{$form.is_active.html}</td></tr>
     </table>
     {/if}
    <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
</fieldset>
</div>
