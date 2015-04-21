{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.6                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2015                                |
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
{* this template is used for adding/editing relative date filters *}
<div class="crm-block crm-form-block crm-admin-options-form-block">
<div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="top"}</div>
  {if $action eq 8}
      <div class="messages status no-popup">
        <div class="icon inform-icon"></div>
             {ts}WARNING: Deleting this date filter will break any Smart Groups or reports which use this date filter.{/ts} {ts}This action cannot be undone.  Consider disabling the date filter instead.{/ts} {ts}Do you want to continue?{/ts}
      </div>
  {else}
    <table class="form-layout-compressed">
      <tr class="crm-admin-options-form-block-label">
        <td class="label">{$form.label.label} {if $action == 2}{include file='CRM/Core/I18n/Dialog.tpl' table='civicrm_option_value' field='label' id=$id}{/if}</td>
        <td class="html-adjust">{$form.label.html}<br />
          <span class="description">{ts}The option label is displayed to users.{/ts}</span>
        </td>
      </tr>
      <tr class="crm-admin-options-form-block-relative-terms">
        <td class="label">{$form.relative_terms.label}</td>
        <td>{$form.relative_terms.html}</td>
      </tr>
      <tr class="crm-admin-options-form-block-units">
        <td class="label">{$form.units.label}</td>
        <td>{$form.units.html}</td>
      </tr>
      <tr class="crm-admin-options-form-block-preview">
        <td class="label">Preview (doesn't work yet)</td>
        <td>When run on (date selector here, defaults to today), this filter will run from (FROM) to (TO).</td>
      </tr>
      <tr class="crm-admin-options-form-block-value">
        <td class="label">{$form.value.label} (For debugging purposes only)</td>
        <td>{$form.value.html}<br />
           <span class="description"><div class="icon ui-icon-alert"></div>{ts}Changing the date filter will break Smart Groups and reports which use this filter. This change can not be undone except by restoring the previous value.{/ts}</span>
        </td>
      </tr>
      <tr class="crm-admin-options-form-block-description">
        <td class="label">{$form.description.label}</td>
        <td>{$form.description.html}<br />
        </td>
      </tr>
      <tr class="crm-admin-options-form-block-weight">
        <td class="label">{$form.weight.label}</td>
        <td>{$form.weight.html}</td>
      </tr>
      <tr class="crm-admin-options-form-block-is_active">
        <td class="label">{$form.is_active.label}</td>
        <td>{$form.is_active.html}</td>
      </tr>
    </table>
  {/if}
<div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
 </fieldset>
</div>
