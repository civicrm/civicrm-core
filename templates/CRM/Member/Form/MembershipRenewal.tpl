{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.3                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2013                                |
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
{* this template is used for renewing memberships for a contact  *}
{if $cdType }
  {include file="CRM/Custom/Form/CustomData.tpl"}
{else}
  {if $membershipMode == 'test' }
    {assign var=registerMode value="TEST"}
  {elseif $membershipMode == 'live'}
    {assign var=registerMode value="LIVE"}
  {/if}
  {if !$email}
    <div class="messages status no-popup">
      <div class="icon inform-icon"></div>
      <p>{ts}You will not be able to send an automatic email receipt for this Renew Membership because there is no email address recorded for this contact. If you want a receipt to be sent when this Membership is recorded, click Cancel and then click Edit from the Summary tab to add an email address before Renewal the Membership.{/ts}</p>
    </div>
  {/if}
  {if $membershipMode}
    <div id="help">
      {ts 1=$displayName 2=$registerMode}Use this form to Renew Membership Record on behalf of %1.
        <strong>A %2 transaction will be submitted</strong>
        using the selected payment processor.{/ts}
    </div>
  {/if}
  {if $action eq 32768}
    {if $cancelAutoRenew}
      <div class="messages status no-popup">
        <div class="icon inform-icon"></div>
        <p>{ts 1=$cancelAutoRenew}This membership is set to renew automatically {if $renewalDate}on {$renewalDate|crmDate}{/if}. You will need to cancel the auto-renew option if you want to modify the Membership Type, End Date or Membership Status.
            <a href="%1">Click here</a>
            if you want to cancel the automatic renewal option.{/ts}</p>
      </div>
    {/if}
    <h3>{ts}Renew Membership{/ts}</h3>
  {/if}
  <div class="crm-block crm-form-block crm-member-membershiprenew-form-block">
    <div id="help" class="description">
      {ts}Renewing will add the normal membership period to the End Date of the previous period for members whose status is Current or Grace. For Expired memberships, renewing will create a membership period commencing from the 'Date Renewal Entered'. This date can be adjusted including being set to the day after the previous End Date - if continuous membership is required.{/ts}
    </div>
    <div>{include file="CRM/common/formButtons.tpl" location="top"}</div>
    <table class="form-layout">
      <tr class="crm-member-membershiprenew-form-block-payment_processor_id">
        <td class="label">{$form.payment_processor_id.label}</td>
        <td class="html-adjust">{$form.payment_processor_id.html}</td>
      </tr>
      <tr class="crm-member-membershiprenew-form-block-org_name">
        <td class="label">{ts}Membership Organization and Type{/ts}</td>
        <td class="html-adjust">{$orgName}&nbsp;&nbsp;-&nbsp;&nbsp;{$memType}
          {if $member_is_test} {ts}(test){/ts}{/if}
          &nbsp; <a id="changeMembershipOrgType" href='#'
                    onclick='adjustMembershipOrgType(); return false;'>{ts}change membership type{/ts}</a>
        </td>
      </tr>
      <tr id="membershipOrgType" class="crm-member-membershiprenew-form-block-renew_org_name">
        <td class="label">{$form.membership_type_id.label}</td>
        <td>{$form.membership_type_id.html}
          {if $member_is_test} {ts}(test){/ts}{/if}<br/>
          <span class="description">{ts}Select Membership Organization and then Membership Type.{/ts}</span>
        </td>
      </tr>
      <tr class="crm-member-membershiprenew-form-block-membership_status">
        <td class="label">{ts}Membership Status{/ts}</td>
        <td class="html-adjust">&nbsp;{$membershipStatus}<br/>
          <span class="description">{ts}Status of this membership.{/ts}</span></td>
      </tr>
      <tr class="crm-member-membershiprenew-form-block-end_date">
        <td class="label">{ts}Membership End Date{/ts}</td>
        <td class="html-adjust">&nbsp;{$endDate}</td>
      </tr>
      <tr class="crm-member-membershiprenew-form-block-renewal_date">
        <td class="label">{$form.renewal_date.label}</td>
        <td>{include file="CRM/common/jcalendar.tpl" elementName=renewal_date}</td>
      </tr>
      {if $membershipMode}
        <tr class="crm-member-membershiprenew-form-block-financial_type_id">
          <td class="label">{$form.financial_type_id.label}</td>
          <td>{$form.financial_type_id.html}<br/>
            <span class="description">{ts}Select the appropriate financial type for this payment.{/ts}</span></td>
        </tr>
      {/if}
      {if $accessContribution and ! $membershipMode}
        <tr class="crm-member-membershiprenew-form-block-record_contribution">
          <td class="label">{$form.record_contribution.label}</td>
          <td class="html-adjust">{$form.record_contribution.html}<br/>
            <span
              class="description">{ts}Check this box to enter payment information. You will also be able to generate a customized receipt.{/ts}</span>
          </td>
        </tr>
        <tr id="recordContribution" class="crm-member-membershiprenew-form-block-membership_renewal">
          <td colspan="2">
            <fieldset>
              <legend>{ts}Renewal Payment and Receipt{/ts}</legend>
              <table class="form-layout-compressed">
                <tr id="defaultNumTerms" class="crm-member-membershiprenew-form-block-default-num_terms">
                  <td colspan="2" class="description">
                    {ts}Renewal extends membership end date by one membership period{/ts}
                    &nbsp; <a id="changeTermsLink" href='#'
                              onclick='changeNumTerms(); return false;'>{ts}change{/ts}</a>
                  </td>
                </tr>
                <tr id="changeNumTerms" class="crm-member-membershiprenew-form-block-change-num_terms">
                  <td class="label">{$form.num_terms.label}</td>
                  <td>{$form.num_terms.html|crmAddClass:two} {ts}membership periods{/ts}<br/>
                    <span
                      class="description">{ts}Extend the membership end date by this many membership periods. Make sure the appropriate corresponding fee is entered below.{/ts}</span>
                  </td>
                </tr>
                {if $context neq 'standalone'}
                  <tr class="crm-membership-form-block-contribution-contact">
                    <td class="label">{$form.contribution_contact.label}</td>
                    <td>{$form.contribution_contact.html}&nbsp;&nbsp;{help id="id-contribution_contact"}</td>
                  </tr>
                  <tr id="record-different-contact">
                    <td>&nbsp;</td>
                    <td>
                      <table class="compressed">
                        <tr class="crm-membership-form-block-honor-type">
                          <td class="label">{$form.honor_type_id.label}</td>
                          <td>{$form.honor_type_id.html}</td>
                        </tr>
                        <tr id='contributionContact' class="crm-membership-form-block-contribution-type">
                          {include file="CRM/Contact/Form/NewContact.tpl"}
                        </tr>
                      </table>
                    </td>
                  </tr>
                {/if}
                <tr class="crm-member-membershiprenew-form-block-financial_type_id">
                  <td class="label">{$form.financial_type_id.label}</td>
                  <td>{$form.financial_type_id.html}<br/>
                    <span class="description">{ts}Select the appropriate financial type for this payment.{/ts}</span>
                  </td>
                </tr>
                <tr class="crm-member-membershiprenew-form-block-total_amount">
                  <td class="label">{$form.total_amount.label}</td>
                  <td>{$form.total_amount.html}<br/>
                    <span
                      class="description">{ts}Membership payment amount. A contribution record will be created for this amount.{/ts}</span>
                  </td>
                </tr>
                <tr class="crm-membershiprenew-form-block-receive_date">
                  <td class="label">{$form.receive_date.label}</td>
                  <td>{include file="CRM/common/jcalendar.tpl" elementName=receive_date}</td>
                </tr>
                <tr class="crm-member-membershiprenew-form-block-payment_instrument_id">
                  <td class="label">{$form.payment_instrument_id.label}</td>
                  <td>{$form.payment_instrument_id.html}</td>
                </tr>
                <tr id="checkNumber" class="crm-member-membershiprenew-form-block-check_number">
                  <td class="label">{$form.check_number.label}</td>
                  <td>{$form.check_number.html|crmAddClass:six}</td>
                </tr>
                <tr class="crm-member-membershiprenew-form-block-trxn_id">
                  <td class="label">{$form.trxn_id.label}</td>
                  <td>{$form.trxn_id.html}</td>
                </tr>
                <tr class="crm-member-membershiprenew-form-block-contribution_status_id">
                  <td class="label">{$form.contribution_status_id.label}</td>
                  <td>{$form.contribution_status_id.html}</td>
                </tr>
              </table>
            </fieldset>
          </td>
        </tr>
      {else}
        <tr class="crm-member-membershiprenew-form-block-total_amount">
          <td class="label">{$form.total_amount.label}</td>
          <td>{$form.total_amount.html}<br/>
            <span
              class="description">{ts}Membership payment amount. A contribution record will be created for this amount.{/ts}</span>
          </td>
        </tr>
      {/if}
    </table>
    {if $membershipMode}
      {if $context neq 'standalone'}
        <table class="form-layout-compressed">
          <tr class="crm-membership-form-block-contribution-contact">
            <td class="label">{$form.contribution_contact.label}</td>
            <td>{$form.contribution_contact.html}&nbsp;&nbsp;{help id="id-contribution_contact"}</td>
          </tr>
          <tr id="record-different-contact">
            <td>&nbsp;</td>
            <td>
              <table class="form-layout-compressed">
                <tr class="crm-membership-form-block-honor-type">
                  <td class="label">{$form.honor_type_id.label}</td>
                  <td>{$form.honor_type_id.html}</td>
                </tr>
                <tr id='contributionContact' class="crm-membership-form-block-contribution-type">
                  {include file="CRM/Contact/Form/NewContact.tpl"}
                </tr>
              </table>
            </td>
          </tr>
        </table>
      {/if}
      <div class="spacer"></div>
      {include file='CRM/Core/BillingBlock.tpl'}
    {/if}
    {if $email and $outBound_option != 2}
      <table class="form-layout">
        <tr class="crm-member-membershiprenew-form-block-send_receipt">
          <td class="label">{$form.send_receipt.label}</td>
          <td>{$form.send_receipt.html}<br/>
            <span
              class="description">{ts 1=$email}Automatically email a membership confirmation and receipt to %1?{/ts}</span>
          </td>
        </tr>
        <tr id="fromEmail">
          <td class="label">{$form.from_email_address.label}</td>
          <td>{$form.from_email_address.html}</td>
        </tr>
        <tr id="notice" class="crm-member-membershiprenew-form-block-receipt_text_renewal">
          <td class="label">{$form.receipt_text_renewal.label}</td>
          <td><span
              class="description">{ts}Enter a message you want included at the beginning of the emailed receipt. EXAMPLE: 'Thanks for supporting our organization with your membership.'{/ts}</span><br/>
            {$form.receipt_text_renewal.html|crmAddClass:huge}</td>
        </tr>
      </table>
    {/if}

    <div id="customData"></div>
    {*include custom data js file*}
    {include file="CRM/common/customData.tpl"}

    <div>{include file="CRM/common/formButtons.tpl" location="bottom"}</div>

    <div class="spacer"></div>
  </div>
  {if $accessContribution and ! $membershipMode}
    {include file="CRM/common/showHideByFieldValue.tpl"
    trigger_field_id    ="record_contribution"
    trigger_value       =""
    target_element_id   ="recordContribution"
    target_element_type ="table-row"
    field_type          ="radio"
    invert              = 0
    }
  {/if}

  {if $email and $outBound_option != 2}
    {include file="CRM/common/showHideByFieldValue.tpl"
    trigger_field_id    ="send_receipt"
    trigger_value       =""
    target_element_id   ="notice"
    target_element_type ="table-row"
    field_type          ="radio"
    invert              = 0
    }
    {include file="CRM/common/showHideByFieldValue.tpl"
    trigger_field_id    ="send_receipt"
    trigger_value       =""
    target_element_id   ="fromEmail"
    target_element_type ="table-row"
    field_type          ="radio"
    invert              = 0
    }
  {/if}

  {if !$membershipMode}
    {include file="CRM/common/showHideByFieldValue.tpl"
    trigger_field_id    ="payment_instrument_id"
    trigger_value       = '4'
    target_element_id   ="checkNumber"
    target_element_type ="table-row"
    field_type          ="select"
    invert              = 0
    }
    {include file="CRM/common/showHideByFieldValue.tpl"
    trigger_field_id    ="send_receipt"
    trigger_value       =""
    target_element_id   ="fromEmail"
    target_element_type ="table-row"
    field_type          ="radio"
    invert              = 0
    }
  {/if}
{literal}
  <script type="text/javascript">
    cj(function () {
      cj('#membershipOrgType').hide();
      cj('#changeNumTerms').hide();
      {/literal}
      CRM.buildCustomData('{$customDataType}');
      {if $customDataSubType}
      CRM.buildCustomData('{$customDataType}', {$customDataSubType});
      {/if}
      {literal}
    });

    function checkPayment() {
      showHideByValue('record_contribution', '', 'recordContribution', 'table-row', 'radio', false);
      {/literal}{if $email and $outBound_option != 2}{literal}
      var record_contribution = document.getElementsByName('record_contribution');
      if (record_contribution[0].checked) {
        document.getElementsByName('send_receipt')[0].checked = true;
        cj('#fromEmail').show();
      }
      else {
        document.getElementsByName('send_receipt')[0].checked = false;
      }
      showHideByValue('send_receipt', '', 'notice', 'table-row', 'radio', false);
      {/literal}{/if}{literal}
    }

    function adjustMembershipOrgType() {
      cj('#membershipOrgType').show();
      cj('#changeMembershipOrgType').hide();
    }

    function changeNumTerms() {
      cj('#changeNumTerms').show();
      cj('#defaultNumTerms').hide();
    }

    cj(function () {
      cj('#record_contribution').click(function () {
        if (cj(this).attr('checked')) {
          cj('#recordContribution').show();
          setPaymentBlock(true);
        }
        else {
          cj('#recordContribution').hide();
        }
      });

      cj('#membership_type_id_1').change(function () {
        setPaymentBlock();
      });
      setPaymentBlock();
    });

    function setPaymentBlock(checkboxEvent) {
      var memType = cj('#membership_type_id_1').val();

      if (!memType) {
        return;
      }

      var allMemberships = {/literal}{$allMembershipInfo}{literal};
      var mode = {/literal}'{$membershipMode}'{literal};

      if (!mode) {
        // skip this for test and live modes because contribution type is set automatically
        cj("#financial_type_id").val(allMemberships[memType]['financial_type_id']);
      }

      if (!checkboxEvent) {
        if (allMemberships[memType]['total_amount_numeric'] > 0) {
          cj('#record_contribution').attr('checked', 'checked');
          cj('#recordContribution').show();
        }
        else {
          cj('#record_contribution').removeAttr('checked');
          cj('#recordContribution').hide();
        }
      }

      var term = cj("#num_terms").val();
      if (term) {
        var renewTotal = allMemberships[memType]['total_amount_numeric'] * term;
        cj("#total_amount").val(renewTotal.toFixed(2));
      }
      else {
        cj("#total_amount").val(allMemberships[memType]['total_amount']);
      }
    }

    // show/hide different contact section
    setDifferentContactBlock();
    cj('#contribution_contact').change(function () {
      setDifferentContactBlock();
    });

    function setDifferentContactBlock() {
      //get the
      if (cj('#contribution_contact').attr('checked')) {
        cj('#record-different-contact').show();
      }
      else {
        cj('#record-different-contact').hide();
      }
    }
  </script>
{/literal}
{/if}{* closing of custom data if *}
