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
{crmRegion name="contribute-form-contributionpage-thankyou-main"}
<div class="crm-block crm-form-block crm-contribution-contributionpage-thankyou-form-block">
<div class="help">
    <p>{ts}Use this form to configure the thank-you message and receipting options.{/ts} {help id="id_thank"}</p>
</div>
    <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="top"}</div>
    <table class="form-layout">
    <tr class="crm-contribution-contributionpage-thankyou-form-block-thankyou_title">
       <td class="label">{$form.thankyou_title.label}{if $action == 2}{include file='CRM/Core/I18n/Dialog.tpl' table='civicrm_contribution_page' field='thankyou_title' id=$contributionPageID}{/if} {help id="id_thankyou-title"}</td>
       <td class="html-adjust">{$form.thankyou_title.html}
       </td>
    </tr>
    <tr class="crm-contribution-contributionpage-thankyou-form-block-thankyou_text">
       <td class="label">{$form.thankyou_text.label}{if $action == 2}{include file='CRM/Core/I18n/Dialog.tpl' table='civicrm_contribution_page' field='thankyou_text' id=$contributionPageID}{/if}<br />{help id="id_thankyou-text"}</td>
       <td class="html-adjust">{$form.thankyou_text.html}<br /></td>
    </tr>
    <tr class="crm-contribution-contributionpage-thankyou-form-block-thankyou_footer">
       <td class="label">{$form.thankyou_footer.label}{if $action == 2}{include file='CRM/Core/I18n/Dialog.tpl' table='civicrm_contribution_page' field='thankyou_footer' id=$contributionPageID}{/if} {help id="id_footer-text"}</td>
       <td class="html-adjust">{$form.thankyou_footer.html}<br /></td>
    </tr>
    <tr class="crm-contribution-contributionpage-thankyou-form-block-is_email_receipt">
       <td class="label"></td>
       <td class="html-adjust">{$form.is_email_receipt.html}{$form.is_email_receipt.label} {help id="id_is-email-receipt"}
       </td>
    </tr>
    </table>
    <table id="receiptDetails" class="form-layout">
    <tr class="crm-contribution-contributionpage-thankyou-form-block-receipt_from_name">
      <td class="label">{$form.receipt_from_name.label}{if $action == 2}{include file='CRM/Core/I18n/Dialog.tpl' table='civicrm_contribution_page' field='receipt_from_name' id=$contributionPageID}{/if} {help id="id_receipt-from-name"}
      </td>
      <td class="html-adjust">{$form.receipt_from_name.html}
  </td>
    </tr>
    <tr class="crm-contribution-contributionpage-thankyou-form-block-receipt_from_email">
      <td class="label">{$form.receipt_from_email.label} <span class="crm-marker" title="{ts}This field is required.{/ts}">*</span> {help id="id_receipt-from-email"}</td>
      <td class="html-adjust">{$form.receipt_from_email.html}</td>
    </tr>
    <tr class="crm-contribution-contributionpage-thankyou-form-block-receipt_text">
      <td class="label">{$form.receipt_text.label}{if $action == 2}{include file='CRM/Core/I18n/Dialog.tpl' table='civicrm_contribution_page' field='receipt_text' id=$contributionPageID}{/if}  {help id="id_receipt-text"}</td>
      <td class="html-adjust">{$form.receipt_text.html}<br /></td>
    </tr>
    <tr class="crm-contribution-contributionpage-thankyou-form-block-cc_receipt">
      <td class="label">{$form.cc_receipt.label} {help id="id_receipt-cc"}</td>
      <td class="html-adjust">{$form.cc_receipt.html}</td>
    </tr>
    <tr class="crm-contribution-contributionpage-thankyou-form-block-bcc_receipt">
      <td class="label">{$form.bcc_receipt.label} {help id="id_receipt-bcc"}</td>
      <td class="html-adjust">{$form.bcc_receipt.html}</td>
    </tr>
    </table>
    <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
</div>

<script type="text/javascript">
 showReceipt();
 {literal}
     function showReceipt() {
        var checkbox = document.getElementsByName("is_email_receipt");
        if (checkbox[0].checked) {
            document.getElementById("receiptDetails").style.display = "block";
        } else {
            document.getElementById("receiptDetails").style.display = "none";
        }
     }
 {/literal}
</script>
{/crmRegion}
{crmRegion name="contribute-form-contributionpage-thankyou-post}
{/crmRegion}
