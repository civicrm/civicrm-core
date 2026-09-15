{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{crmRegion name="contribute-form-contributionpage-thankyou-main"}
<div class="crm-block crm-form-block crm-contribution-contributionpage-thankyou-form-block">
<div class="help">
    <p>{ts}Use this form to configure the thank-you page and receipting options.{/ts} {help id="id_thank"}</p>
</div>
    <h3>{ts}Thank You Page{/ts}</h3>
    <table class="form-layout">
    <tr class="crm-contribution-contributionpage-thankyou-form-block-thankyou_mode">
       <td class="label">{$form.thankyou_mode.label}{if $action == 2}{include file='CRM/Core/I18n/Dialog.tpl' table='civicrm_contribution_page' field='thankyou_mode' id=$contributionPageID}{/if} {help id="thankyou_mode"}</td>
       <td class="html-adjust">{$form.thankyou_mode.html}
       </td>
    </tr>
    </table>
    <table data-thankyou-type="page" class="form-layout">
    <tr class="crm-contribution-contributionpage-thankyou-form-block-thankyou_title">
       <td class="label">{$form.thankyou_title.label}{if $action == 2}{include file='CRM/Core/I18n/Dialog.tpl' table='civicrm_contribution_page' field='thankyou_title' id=$contributionPageID}{/if} {help id="thankyou_title"}</td>
       <td class="html-adjust">{$form.thankyou_title.html}
       </td>
    </tr>
    <tr class="crm-contribution-contributionpage-thankyou-form-block-thankyou_text">
       <td class="label">{$form.thankyou_text.label}{if $action == 2}{include file='CRM/Core/I18n/Dialog.tpl' table='civicrm_contribution_page' field='thankyou_text' id=$contributionPageID}{/if}<br />{help id="thankyou_text"}</td>
       <td class="html-adjust">{$form.thankyou_text.html}<br /></td>
    </tr>
    <tr class="crm-contribution-contributionpage-thankyou-form-block-thankyou_footer">
       <td class="label">{$form.thankyou_footer.label}{if $action == 2}{include file='CRM/Core/I18n/Dialog.tpl' table='civicrm_contribution_page' field='thankyou_footer' id=$contributionPageID}{/if} {help id="thankyou_footer"}</td>
       <td class="html-adjust">{$form.thankyou_footer.html}<br /></td>
    </tr>
    </table>
    <table data-thankyou-type="redirect" class="form-layout">
    <tr class="crm-contribution-contributionpage-thankyou-form-block-thankyou_redirect_url">
       <td class="label">{$form.thankyou_redirect_url.label}{if $action == 2}{include file='CRM/Core/I18n/Dialog.tpl' table='civicrm_contribution_page' field='thankyou_redirect_url' id=$contributionPageID}{/if} {help id="thankyou_redirect_url"}</td>
       <td class="html-adjust">{$form.thankyou_redirect_url.html}
       </td>
    </tr>
    </table>

    <h3>{ts}Email Receipt{/ts}</h3>
    <table class="form-layout">
    <tr class="crm-contribution-contributionpage-thankyou-form-block-is_email_receipt">
       <td class="label">{help id="is_email_receipt"}</td>
       <td class="html-adjust">{$form.is_email_receipt.html}{$form.is_email_receipt.label}</td>
    </tr>
    </table>
    <table id="receiptDetails" class="form-layout">
    <tr class="crm-contribution-contributionpage-thankyou-form-block-receipt_from_name">
      <td class="label">{$form.receipt_from_name.label}{if $action == 2}{include file='CRM/Core/I18n/Dialog.tpl' table='civicrm_contribution_page' field='receipt_from_name' id=$contributionPageID}{/if} {help id="receipt_from_name"}
      </td>
      <td class="html-adjust">{$form.receipt_from_name.html}
  </td>
    </tr>
    <tr class="crm-contribution-contributionpage-thankyou-form-block-receipt_from_email">
      <td class="label">{$form.receipt_from_email.label} <span class="crm-marker" title="{ts escape='htmlattribute'}This field is required.{/ts}">*</span> {help id="receipt_from_email"}</td>
      <td class="html-adjust">{$form.receipt_from_email.html}</td>
    </tr>
    <tr class="crm-contribution-contributionpage-thankyou-form-block-receipt_text">
      <td class="label">{$form.receipt_text.label}{if $action == 2}{include file='CRM/Core/I18n/Dialog.tpl' table='civicrm_contribution_page' field='receipt_text' id=$contributionPageID}{/if}  {help id="receipt_text"}</td>
      <td class="html-adjust">{$form.receipt_text.html}<br /></td>
    </tr>
    <tr class="crm-contribution-contributionpage-thankyou-form-block-cc_receipt">
      <td class="label">{$form.cc_receipt.label} {help id="cc_receipt"}</td>
      <td class="html-adjust">{$form.cc_receipt.html}</td>
    </tr>
    <tr class="crm-contribution-contributionpage-thankyou-form-block-bcc_receipt">
      <td class="label">{$form.bcc_receipt.label} {help id="bcc_receipt"}</td>
      <td class="html-adjust">{$form.bcc_receipt.html}</td>
    </tr>
    </table>
    <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
</div>

<script type="text/javascript">
 {literal}
     function showReceipt() {
        const checkbox = document.getElementsByName("is_email_receipt");
        if (checkbox[0].checked) {
            document.getElementById("receiptDetails").style.display = null;
        } else {
            document.getElementById("receiptDetails").style.display = "none";
        }
     }
    showReceipt();

     (function () {
      const radios = document.querySelectorAll('[name=thankyou_mode]');
      const sections = document.querySelectorAll('[data-thankyou-type]');

      const showThankYouModeFields = () => {
        const selected = document.querySelector('[name=thankyou_mode]:checked').value;
        sections.forEach((section) => {
          const isSelected = (selected === section.dataset.thankyouType);
          // show/hide section
          section.style.display = isSelected ? null : 'none';
          // require/dont require section inputs
          section.querySelectorAll('input').forEach((input) => input.required = isSelected);
        })
      };

      // show based on loaded value
      showThankYouModeFields();
      // update on change
      radios.forEach((el) => el.addEventListener('click', showThankYouModeFields));
     })();

 {/literal}
</script>
{/crmRegion}
{crmRegion name="contribute-form-contributionpage-thankyou-post"}
{/crmRegion}
