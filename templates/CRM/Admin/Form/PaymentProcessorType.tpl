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
{* this template is used for adding/editing available Payment Processors  *}
<div class="crm-block crm-form-block crm-paymentProcessor-type-form-block">
<fieldset><legend>{if $action eq 1}{ts}New Payment Procesor Type{/ts}{elseif $action eq 2}{ts}Edit Payment Procesor Type{/ts}{else}{ts}Delete Payment Procesor Type{/ts}{/if}</legend>
 <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="top"}</div>
{if $action eq 8}
  <div class="messages status no-popup">
      <div class="icon inform-icon"></div>
      {ts}Do you want to continue?{/ts}
  </div>
{else}

  <table class="form-layout-compressed">
    <tr class="crm-paymentProcessor-type-form-block-title">
        <td class="label">{$form.title.label}</td>
        <td>{$form.title.html}</td>
    </tr>
    <tr class="crm-paymentProcessor-type-form-block-name">
        <td class="label">{$form.name.label}</td>
        <td>{$form.name.html}</td>
    </tr>
    <tr class="crm-paymentProcessor-type-form-block-description">
        <td class="label">{$form.description.label}</td>
        <td>{$form.description.html}</td>
    </tr>
    <tr class="crm-paymentProcessor-type-form-block-billing_mode">
        <td class="label">{$form.billing_mode.label}</td>
        <td>{$form.billing_mode.html}</td>
    </tr>
    <tr class="crm-paymentProcessor-type-form-block-class_name">
        <td class="label">{$form.class_name.label}</td>
        <td>{$form.class_name.html}</td>
    </tr>
    <tr class="crm-paymentProcessor-type-form-block-is_active">
        <td class="label"></td><td>{$form.is_active.html} {$form.is_active.label}</td>
    </tr>
    <tr class="crm-paymentProcessor-type-form-block-is_default">
        <td class="label"></td><td>{$form.is_default.html} {$form.is_default.label}</td>
    </tr>
    <tr class="crm-paymentProcessor-type-form-block-is_recur">
        <td class="label"></td><td>{$form.is_recur.html} {$form.is_recur.label}</td>
    </tr>
    <tr class="crm-paymentProcessor-type-form-block-user_name_label">
        <td class="label">{$form.user_name_label.label}</td>
        <td>{$form.user_name_label.html}</td>
    </tr>
    <tr class="crm-paymentProcessor-type-form-block-password_label">
        <td class="label">{$form.password_label.label}</td>
        <td>{$form.password_label.html}</td>
    </tr>
    <tr class="crm-paymentProcessor-type-form-block-signature_label">
        <td class="label">{$form.signature_label.label}</td>
        <td>{$form.signature_label.html}</td>
    </tr>
    <tr class="crm-paymentProcessor-type-form-block-subject_label">
        <td class="label">{$form.subject_label.label}</td>
        <td>{$form.subject_label.html}</td>
    </tr>
    <tr class="crm-paymentProcessor-type-form-block-url_site_default">
        <td class="label">{$form.url_site_default.label}</td>
        <td>{$form.url_site_default.html}</td>
    </tr>
    <tr class="crm-paymentProcessor-type-form-block-url_api_default">
        <td class="label">{$form.url_api_default.label}</td>
        <td>{$form.url_api_default.html}</td>
    </tr>
    <tr class="crm-paymentProcessor-type-form-block-url_recur_default">
        <td class="label">{$form.url_recur_default.label}</td>
        <td>{$form.url_recur_default.html}</td>
    </tr>
    <tr class="crm-paymentProcessor-type-form-block-url_button_default">
        <td class="label">{$form.url_button_default.label}</td>
        <td>{$form.url_button_default.html}</td>
    </tr>
    <tr class="crm-paymentProcessor-type-form-block-url_site_test_default">
        <td class="label">{$form.url_site_test_default.label}</td>
        <td>{$form.url_site_test_default.html}</td>
    </tr>
    <tr class="crm-paymentProcessor-type-form-block-url_api_test_default">
        <td class="label">{$form.url_api_test_default.label}</td>
        <td>{$form.url_api_test_default.html}</td>
    </tr>
    <tr class="crm-paymentProcessor-type-form-block-url_recur_test_default">
        <td class="label">{$form.url_recur_test_default.label}</td>
        <td>{$form.url_recur_test_default.html}</td>
    </tr>
    <tr class="crm-paymentProcessor-type-form-block-url_button_test_default">
        <td class="label">{$form.url_button_test_default.label}</td>
        <td>{$form.url_button_test_default.html}</td>
    </tr>
</table>
{/if}
</fieldset>
<div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
</div>
