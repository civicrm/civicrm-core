{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.5                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2014                                |
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
<h3>{if $action eq 1}{ts}New Option Group{/ts}{elseif $action eq 2}{ts}Edit Option Group{/ts}{else}{ts}Delete Option Group{/ts}{/if}</h3>
<div class="crm-block crm-form-block crm-admin-optiongroup-form-block">
  <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="top"}</div>
   {if $action eq 8}
      <div class="messages status no-popup">
          <div class="icon inform-icon"></div>
          {ts}WARNING: Deleting this option gruop will result in the loss of all records which use the option.{/ts} {ts}This may mean the loss of a substantial amount of data, and the action cannot be undone.{/ts} {ts}Do you want to continue?{/ts}
      </div>
     {else}
      <table class="form-layout-compressed">
          <tr class="crm-admin-optiongroup-form-block-name">
          <tr class="crm-admin-optiongroup-form-block-title">
              <td class="label">{$form.title.label}
            {if $action == 2}{include file='CRM/Core/I18n/Dialog.tpl' table='civicrm_option_group' field='title' id=$id}{/if}</td><td>{$form.title.html}</td>
          </tr>
          <tr class="crm-admin-optiongroup-form-block-description">
              <td class="label">{$form.description.label}
            {if $action == 2}{include file='CRM/Core/I18n/Dialog.tpl' table='civicrm_option_group' field='description' id=$id}{/if}</td><td>{$form.description.html}</td>
          </tr>
        <td class="label">{$form.name.label}</td>
              <td>{$form.name.html}</td></tr>
          <tr class="crm-admin-optiongroup-form-block-is_active">
              <td class="label">{$form.is_active.label}</td>
              <td>{$form.is_active.html}</td>
          </tr>
      </table>
     {/if}
     <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
</div>
