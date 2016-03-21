{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2016                                |
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
{* this template is used for adding/editing a mailing component  *}
<div class="crm-block crm-form-block crm-mailing-component-form-block">
<fieldset><legend>{if $action eq 1}{ts}New Mailing Component{/ts}{else}{ts}Edit Mailing Component{/ts}{/if}</legend>
  <table class="form-layout">
    <tr class="crm-mailing-component-form-block-name"><td class="label">{$form.name.label}</td><td>{$form.name.html}</td>
    <tr class="crm-mailing-component-form-block-component_type"><td class="label">{$form.component_type.label}</td><td>{$form.component_type.html}</td>
    <tr class="crm-mailing-component-form-block-subject"><td class="label">{$form.subject.label}</td><td>{$form.subject.html}</td>
    <tr class="crm-mailing-component-form-block-body_text"><td class="label">{$form.body_text.label}</td><td>{$form.body_text.html}</td>
    <tr class="crm-mailing-component-form-block-body_html"><td class="label">{$form.body_html.label}</td><td>{$form.body_html.html}</td>
    <tr class="crm-mailing-component-form-block-is_default"><td class="label">{$form.is_default.label}</td><td>{$form.is_default.html}</td>
    <tr class="crm-mailing-component-form-block-is_active"><td class="label">{$form.is_active.label}</td><td>{$form.is_active.html}</td>
  </table>
</fieldset>
<div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl"}</div>
</div>
