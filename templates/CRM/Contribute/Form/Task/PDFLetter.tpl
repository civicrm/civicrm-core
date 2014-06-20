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
<div class="crm-form-block crm-block crm-contact-task-pdf-form-block">
<h3>{ts}Thank-you Letter for Contributions (PDF){/ts}</h3>
{if $single eq false}
    <div class="messages status no-popup">{include file="CRM/Contribute/Form/Task.tpl"}</div>
{/if}

<div class="crm-accordion-wrapper crm-html_email-accordion ">
  <div class="crm-accordion-header">
    {$form.more_options_header.html}
  </div><!-- /.crm-accordion-header -->
  <div class="crm-accordion-body">
    <table class="form-layout-compressed">
      <tr><td class="label-left">{$form.thankyou_update.html} {$form.thankyou_update.label}</td><td></td></tr>
      <tr><td class="label-left">{$form.receipt_update.html} {$form.receipt_update.label}</td><td></td></tr>
      <tr>
        <td class="label-left">{$form.group_by.label} {help id="id-contribution-grouping"}</td>
        <td>{$form.group_by.html}</td>
      </tr>
      <tr>
        <td class="label-left">{$form.group_by_separator.label}</td>
        <td>{$form.group_by_separator.html}</td>
      </tr>
      <tr>
        <td class="label-left">{$form.email_options.label} {help id="id-contribution-email-print"}</td>
        <td>{$form.email_options.html}</td>
      </tr>
    </table>
  </div><!-- /.crm-accordion-body -->
</div><!-- /.crm-accordion-wrapper -->

{include file="CRM/Contact/Form/Task/PDFLetterCommon.tpl"}

<div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
</div>
