{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
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
<div class="messages status no-popup">
  <div class="icon inform-icon"></div>
      {include file="CRM/Contribute/Form/Task.tpl"}
</div>
<div class="help">
    {ts}You may choose to email receipts to contributors OR download a PDF file containing one receipt per page to your local computer by clicking <strong>Process Receipt(s)</strong>. Your browser may display the file for you automatically, or you may need to open it for printing using any PDF reader (such as Adobe&reg; Reader).{/ts}
</div>

<table class="form-layout-compressed">
  <tr>
    <td>{$form.output.email_receipt.html}</td>
  </tr>
  <tr>
    <td>{$form.output.pdf_receipt.html}</td>
  </tr>
  <tr id="selectPdfFormat" style="display: none;">
    <td>{$form.pdf_format_id.html} {$form.pdf_format_id.label} {help id="id-contribution-receipt" file="CRM/Contact/Form/Task/PDFLetterCommon.hlp"}</td>
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
