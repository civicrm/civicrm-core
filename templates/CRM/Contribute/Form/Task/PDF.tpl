{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
<div class="messages status no-popup">
  {icon icon="fa-info-circle"}{/icon}
      {include file="CRM/Contribute/Form/Task.tpl"}
</div>
<div class="help">
    {ts}You may choose to email receipts to contributors OR download a PDF file containing one receipt per page to your local computer by clicking <strong>Process Receipt(s)</strong>. Your browser may display the file for you automatically, or you may need to open it for printing using any PDF reader (such as Adobe&reg; Reader).{/ts}
</div>

<table class="form-layout-compressed">
  <tr>
    <td>{$form.output.email_receipt.html}</td>
  </tr>
  <tr id="selectEmailFrom" style="display: none" class="crm-contactEmail-form-block-fromEmailAddress crm-email-element">
    <td class="label">{$form.from_email_address.label}</td>
    <td>{$form.from_email_address.html}  {help id="from_email_address" file="CRM/Contact/Form/Task/Help/Email/id-from_email.hlp"}</td>
  </tr>
  <tr>
    <td>{$form.output.pdf_receipt.html}</td>
  </tr>
  <tr id="selectPdfFormat" style="display: none;">
    <td>{$form.pdf_format_id.html} {$form.pdf_format_id.label} {help id="pdf_format_id" file="CRM/Contact/Form/Task/PDFLetterCommon.hlp"}</td>
  </tr>
  <tr>
    <td>{$form.receipt_update.html} {$form.receipt_update.label}</td>
  </tr>
  <tr>
    <td>{$form.override_privacy.html} {$form.override_privacy.label}</td>
  </tr>
</table>

<div class="spacer"></div>
<div class="form-item">
 {$form.buttons.html}
</div>
