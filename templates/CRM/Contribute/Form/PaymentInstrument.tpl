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
