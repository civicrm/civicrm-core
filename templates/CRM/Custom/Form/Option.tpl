{*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2018                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
*}
<div class="crm-block {if $action eq 4}crm-content-block {else}crm-form-block {/if}crm-custom_option-form-block">
<h3>{if $action eq 4 }{ts}View Option{/ts}{elseif $action eq 2}{ts}Edit Option{/ts}{elseif $action eq 8}{ts 1=$label}Delete Option "%1"{/ts}{else}{ts}Add Option{/ts}{/if}</h3>
    {if $action ne 4 AND $action ne 8}
        <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="top"}</div>
    {/if} {* $action ne view *}
    {if $action eq 8}
      <div class="messages status no-popup">
          <div class="icon inform-icon"></div>
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
    {/if} {* $action ne view *}
</div>
