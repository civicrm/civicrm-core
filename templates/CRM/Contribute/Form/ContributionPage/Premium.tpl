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
<div class="help">
  {ts}Edit <strong>Premiums Settings</strong> to customize the title and introductory message (e.g ...in appreciation of your support, you will be able to select from a number of exciting thank-you gifts...). You can optionally provide a contact email address and/or phone number for inquiries.{/ts}
  {ts}Then select and review the premiums that you want to offer on this contribution page.{/ts}
</div>
<div id="id_Premiums" class="crm-block crm-form-block crm-contribution-contributionpage-premium-form-block">
  <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="top"}</div>
  <table class="form-layout-compressed">
    <tr class="crm-contribution-contributionpage-premium-form-block-premiums_active">
      <td class="label">{$form.premiums_active.label}</td>
      <td class="html-adjust">{$form.premiums_active.html}<br/>
        <span class="description">{ts}Is the Premiums section enabled for this Online Contributions page?{/ts}</span>
      </td>
    </tr>
  </table>

  <div id="premiumSettings">
    <div class="crm-accordion-wrapper crm-premium-settings-accordion collapsed">
      <div class="crm-accordion-header">
        {ts}Premiums Settings{/ts}
      </div>
      <div class="crm-accordion-body">
        <table class="form-layout-compressed">
          <tr class="crm-contribution-contributionpage-premium-form-block-premiums_intro_title">
            <td class="label">
              {$form.premiums_intro_title.label}
              {if $action == 2}
                {include file='CRM/Core/I18n/Dialog.tpl' table='civicrm_premiums'
                field='premiums_intro_title' id=$contributionPageID}
              {/if}
            </td>
            <td class="html-adjust">{$form.premiums_intro_title.html}<br/>
              <span class="description">{ts}Title to appear at the top of the Premiums section.{/ts}</span>
            </td>
          </tr>
          <tr class="crm-contribution-contributionpage-premium-form-block-premiums_intro_text">
            <td class="label">
              {$form.premiums_intro_text.label}
              {if $action == 2}
                {include file='CRM/Core/I18n/Dialog.tpl' table='civicrm_premiums'
                field='premiums_intro_text' id=$contributionPageID}
              {/if}
            </td>
            <td class="html-adjust">{$form.premiums_intro_text.html}<br/>
            <span class="description">
              {ts}Enter content for the introductory message. This will be displayed below the Premiums section title. You may include HTML formatting tags. You can also include images, as long as they are already uploaded to a server - reference them using complete URLs.{/ts}
            </span>
            </td>
          </tr>
          <tr class="crm-contribution-contributionpage-premium-form-block-premiums_contact_email">
            <td class="label">
              {$form.premiums_contact_email.label}
            </td>
            <td class="html-adjust">{$form.premiums_contact_email.html}<br/>
            <span class="description">
              {ts}This email address is included in automated contribution receipts if the contributor has selected a premium. It should be an appropriate contact mailbox for inquiries about premium fulfillment/shipping.{/ts}
            </span>
            </td>
          </tr>
          <tr class="crm-contribution-contributionpage-premium-form-block-premiums_contact_phone">
            <td class="label">
              {$form.premiums_contact_phone.label}
            </td>
            <td class="html-adjust">{$form.premiums_contact_phone.html}<br/>
            <span class="description">
              {ts}This phone number is included in automated contribution receipts if the contributor has selected a premium. It should be an appropriate phone number for inquiries about premium fulfillment/shipping.{/ts}
            </span>
            </td>
          </tr>
          <tr class="crm-contribution-contributionpage-premium-form-block-premiums_display_min_contribution">
            <td class="label">
              {$form.premiums_display_min_contribution.label}
            </td>
            <td class="html-adjust">{$form.premiums_display_min_contribution.html}<br/>
            <span class="description">
              {ts}Should the minimum contribution amount be automatically displayed after each premium description?{/ts}
            </span>
            </td>
          </tr>
          <tr class="crm-contribution-contributionpage-premium-form-block-premiums_nothankyou_label">
            <td class="label">
              {$form.premiums_nothankyou_label.label}<span class="crm-marker"> *</span>
            </td>
            <td class="html-adjust">{$form.premiums_nothankyou_label.html}<br/>
              <span class="description">{ts}You can change the text for the 'No thank-you' radio button.{/ts}</span>
            </td>
          </tr>
          <tr class="crm-contribution-contributionpage-premium-form-block-premiums_nothankyou_position">
            <td class="label">
              {$form.premiums_nothankyou_position.label}
            </td>
            <td class="html-adjust">{$form.premiums_nothankyou_position.html}<br/>
            <span class="description">
              {ts}Place the 'No thank-you' radio button before OR after the list of premiums offered on this page.{/ts}
            </span>
            </td>
          </tr>
        </table>
      </div>
      <!-- /.crm-accordion-body -->
    </div>
    <!-- /.crm-accordion-wrapper -->

    {* include premium product templates *}
    {include file="CRM/Contribute/Page/Premium.tpl"}
  </div>

  <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
</div>

<script type="text/javascript">
  {literal}
  CRM.$(function($) {

    // bind click event to premiums_active checkbox
    $('#premiums_active').click(function () {
      premiumBlock();
    });

    // hide premium setting if premium block is not enabled
    if (!$('#premiums_active').prop('checked')) {
      $('#premiumSettings').hide();
    }
  });

  // function to show/hide premium settings
  function premiumBlock() {
    if (cj('#premiums_active').prop('checked')) {
      cj('#premiumSettings').show();
    }
    else {
      cj('#premiumSettings').hide();
    }
    return false;
  }

  {/literal}
</script>
