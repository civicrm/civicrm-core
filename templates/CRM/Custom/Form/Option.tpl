{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
<div class="crm-block {if $action eq 4}crm-content-block {else}crm-form-block {/if}crm-custom_option-form-block">
<h3>{if $action eq 4}{ts}View Option{/ts}{elseif $action eq 2}{ts}Edit Option{/ts}{elseif $action eq 8}{ts 1=$label}Delete Option "%1"{/ts}{else}{ts}Add Option{/ts}{/if}</h3>
    {if $action eq 8}
      <div class="messages status no-popup">
          {icon icon="fa-info-circle"}{/icon}
          {ts}WARNING: Deleting this custom field option will result in the loss of all related data.{/ts} {ts}This action cannot be undone.{/ts} {ts}Do you want to continue?{/ts}
      </div>
    {else}
  <table class="{if $action eq 4}crm-info-panel{else}form-layout{/if}">
        <tr class="crm-custom_option-form-block-label">
            <td class="label">{$form.label.label}{if $action == 2}{include file='CRM/Core/I18n/Dialog.tpl' table='civicrm_option_value' field='label' id=$id}{/if}</td>
            <td>{$form.label.html}</td>
        </tr>
        <tr class="crm-custom_option-form-block-value">
            <td class="label">{$form.value.label}</td>
            <td>{$form.value.html}</td>
        <tr class="crm-custom_option-form-block-desc">
            <td class="label">{$form.description.label}</td>
            <td>{$form.description.html}</td>
        <tr class="crm-custom_option-form-block-weight">
            <td class="label">{$form.weight.label}</td>
            <td>{$form.weight.html}</td>
        </tr>
        <tr class="crm-custom_option-form-block-is_active">
            <td class="label">{$form.is_active.label}</td>
            <td>{$form.is_active.html}</td>
        </tr>
      <tr class="crm-custom_option-form-block-default_value">
            <td class="label">{$form.default_value.label}</td>
            <td>{$form.default_value.html}<br />
            <span class="description">{ts}Make this option value 'selected' by default?{/ts}</span></td>
        </tr>
  </table>
    {/if}

    {if $action ne 4}
        <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
    {else}
        <div class="crm-submit-buttons">{$form.done.html}</div>
    {/if}
</div>
