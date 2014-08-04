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
{capture assign='reqMark'}<span class="marker"  title="{ts}This field is required.{/ts}">*</span>{/capture}
<div class="crm-block crm-form-block crm-contribution-contributionpage-thankyou-form-block">
<div id="help">
    <p>{ts}Use this form to configure the thank-you message and receipting options. Contributors will see a confirmation and thank-you page after whenever an online contribution is successfully processed. You provide the content and layout of the thank-you section below. You also control whether an electronic receipt is automatically emailed to each contributor - and can add a custom message to that receipt.{/ts}</p>
</div>
    <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="top"}</div>
    <table class="form-layout-compressed">
    <tr class="crm-contribution-contributionpage-thankyou-form-block-thankyou_title">
       <td class="label">{$form.thankyou_title.label}{if $action == 2}{include file='CRM/Core/I18n/Dialog.tpl' table='civicrm_contribution_page' field='thankyou_title' id=$contributionPageID}{/if}</td>
       <td class="html-adjust">{$form.thankyou_title.html}<br />
           <span class="description">{ts}This title will be displayed at the top of the thank-you / transaction confirmation page.{/ts}</span>
       </td>
    </tr>
    <tr class="crm-contribution-contributionpage-thankyou-form-block-thankyou_text">
       <td class="label">{$form.thankyou_text.label}{if $action == 2}{include file='CRM/Core/I18n/Dialog.tpl' table='civicrm_contribution_page' field='thankyou_text' id=$contributionPageID}{/if}</td>
       <td class="html-adjust">{$form.thankyou_text.html}<br />
            <span class="description">{ts}Enter text (and optional HTML layout tags) for the thank-you message that will appear at the top of the confirmation page.{/ts}</span>
       </td>
    </tr>
    <tr class="crm-contribution-contributionpage-thankyou-form-block-thankyou_footer">
       <td class="label">{$form.thankyou_footer.label}{if $action == 2}{include file='CRM/Core/I18n/Dialog.tpl' table='civicrm_contribution_page' field='thankyou_footer' id=$contributionPageID}{/if}</td>
       <td class="html-adjust">{$form.thankyou_footer.html}<br />
            <span class="description">{ts}Enter link(s) and/or text that you want to appear at the bottom of the thank-you page. You can use this content area to encourage contributors to visit a tell-a-friend page or take some other action.{/ts}</span>
       </td>
    </tr>
    <tr class="crm-contribution-contributionpage-thankyou-form-block-is_email_receipt">
       <td class="label"></td>
       <td class="html-adjust">{$form.is_email_receipt.html}{$form.is_email_receipt.label}<br />
            <span class="description">{ts}Check this box if you want an electronic receipt to be sent automatically.{/ts}</span>
       </td>
    </tr>
    </table>
    <table id="receiptDetails" class="form-layout-compressed">
    <tr class="crm-contribution-contributionpage-thankyou-form-block-receipt_from_name">
  <td class="label">{$form.receipt_from_name.label}{if $action == 2}{include file='CRM/Core/I18n/Dialog.tpl' table='civicrm_contribution_page' field='receipt_from_name' id=$contributionPageID}{/if}
      </td>
      <td class="html-adjust">{$form.receipt_from_name.html}<br />
          <span class="description">{ts}Enter the FROM name to be used when receipts are emailed to contributors.{/ts}</span>
  </td>
    </tr>
    <tr class="crm-contribution-contributionpage-thankyou-form-block-receipt_from_email">
  <td class="label">{$form.receipt_from_email.label}{$reqMark}
  </td>
  <td class="html-adjust">{$form.receipt_from_email.html}<br />
      <span class="description">{ts}Enter the FROM email address to be used when receipts are emailed to contributors.{/ts}</span>
  </td>
    </tr>
    <tr class="crm-contribution-contributionpage-thankyou-form-block-receipt_text">
  <td class="label">{$form.receipt_text.label}{if $action == 2}{include file='CRM/Core/I18n/Dialog.tpl' table='civicrm_contribution_page' field='receipt_text' id=$contributionPageID}{/if}
  </td>
  <td class="html-adjust">{$form.receipt_text.html}<br />
      <span class="description">{ts}Enter a message you want included at the beginning of emailed receipts. NOTE: This text will be included in both TEXT and HTML versions of receipt emails so we do not recommend including HTML tags / formatting here.{/ts}<br /></span>
  </td>
    </tr>
    <tr class="crm-contribution-contributionpage-thankyou-form-block-cc_receipt">
  <td class="label">{$form.cc_receipt.label}
      </td>
  <td class="html-adjust">{$form.cc_receipt.html}<br />
      <span  class="description">{ts}If you want member(s) of your organization to receive a carbon copy of each emailed receipt, enter one or more email addresses here. Multiple email addresses should be separated by a comma (e.g. jane@example.org, paula@example.org).{/ts}
      </span>
  </td>
    </tr>
    <tr class="crm-contribution-contributionpage-thankyou-form-block-bcc_receipt">
  <td class="label">{$form.bcc_receipt.label}
  </td>
  <td class="html-adjust">{$form.bcc_receipt.html}<br />
      <span class="description">{ts}If you want member(s) of your organization to receive a BLIND carbon copy of each emailed receipt, enter one or more email addresses here. Multiple email addresses should be separated by a comma (e.g. jane@example.org, paula@example.org).{/ts}</span>
  </td>
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
