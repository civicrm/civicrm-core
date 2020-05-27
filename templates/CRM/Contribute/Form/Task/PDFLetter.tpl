{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
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
      <tr>
        <td class="label-left">{$form.from_email_address.label} {help id="id-from_email" file="CRM/Contact/Form/Task/Email.hlp" isAdmin=$isAdmin}</td>
        <td>{$form.from_email_address.html}</td>
      </tr>
    </table>
  </div><!-- /.crm-accordion-body -->
</div><!-- /.crm-accordion-wrapper -->

{include file="CRM/Contact/Form/Task/PDFLetterCommon.tpl"}

<div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
</div>
