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
{if $priceSetOnly == 1}
  {assign var=financial_type_id value=$form['financial_type_id']['value'][0]}
  {include file="CRM/Price/Form/PriceSet.tpl" context="standalone" extends="Membership"}
  <script>
    cj('#financial_type_id').val({$form.financial_type_id.value[0]});
    {if $price_set_has_new_orgs}
      cj('#join_date_desc').show();
    {else}
      cj('#join_date_desc').hide();
    {/if}
  </script>
{else}

{* this template is used for renewing memberships for a contact  *}
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
    <div class="help">
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
        <td class="html-adjust">
          <table>
            <tr>
            <td>
                {$orgName}&nbsp;&nbsp;-&nbsp;&nbsp;{$memType}
                      {if $member_is_test} {ts}(test){/ts}{/if}
            </td>
            <td>
                  <a id="changeMembershipOrgType" href='#' onclick='adjustMembershipOrgType(); return false;'>{ts}change membership type{/ts}</a>
            </td>
            </tr>
          </table>
        </td>
      </tr>
      <tr id="membershipOrgType" class="crm-member-membershiprenew-form-block-renew_org_name hiddenElement">
        <td class="label">{$form.membership_type_id.label}</td>
        <td>{$form.membership_type_id.html}
          {if $member_is_test} {ts}(test){/ts}{/if}
          <br/>
          <span class="description">{ts}Select Membership Organization and then Membership Type.{/ts}</span>
        </td>
      </tr>
      {if $priceSetId}
        {** the price set drop-down and section **}
        <tr id="membershipPriceSet" class="crm-member-membershiprenew-form-block-renew_priceset">
          <td class="label">{$form.price_set_id.label}</td>
          <td>
            <span id='membershipPriceSetBlockPriceSet' class='hiddenElement'>
              <span id='selectPriceSet'>{$form.price_set_id.html}</span>
                &nbsp;<a id="hidePriceSet" href='#' onclick='hidePriceSet(); return false;'>{ts}switch to single membership view{/ts}</a>
                {* if there is a price set, then we will have one to start, so no need to show a hidden block *}
                <div id="priceset"><br/>{include file="CRM/Price/Form/PriceSet.tpl" extends="Membership"}</div>
              </span>
            </span>
            <span id='membershipPriceSetBlockSingleMembership'>
              {ts}This membership does not appear to have been created using a price set.{/ts}<br/>
              {ts}You may still renew it using a price set by{/ts}
                <a id="showPriceSet" href='#' onclick='showPriceSet(); return false;'>{ts}clicking here{/ts}</a>.
            </span>
          </td>
        <tr/>
      {/if}
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
        <td>{include file="CRM/common/jcalendar.tpl" elementName=renewal_date}
        {if $priceSetId}
        <br />
        <span class="description hiddenElement" id="join_date_desc">{ts}If you choose a membership type for an organization for which this contact is not member of, then this date will also be used as the 'Member Since' date{/ts}</span>
        {/if}
        </td>
      </tr>
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
      {/if}
      {include file="CRM/Member/Form/MembershipCommon.tpl"}
      {if $emailExists and $outBound_option != 2}
      <table class="form-layout">
        <tr class="crm-{$formClass}-form-block-send_receipt">
          <td class="label">{$form.send_receipt.label}</td>
          <td>{$form.send_receipt.html}<br/>
            <span class="description">{ts 1=$emailExists}Automatically email a membership confirmation and receipt to %1?{/ts}</span>
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
    CRM.$(function($) {
      $('#membershipOrgType').hide();
      $('#changeNumTerms').hide();
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
      cj('#showPriceSet').hide();
    }

    {/literal}
    {if $priceSet}
      var priceSet = {$priceSet};
      {literal}
      var membershipTypeOrg = cj('#membership_type_id_0').val();
      var membershipTypeId = cj('#membership_type_id_1').val();

      function showPriceSet() {
      {/literal}
        {if $price_set_has_new_orgs}{literal}
          cj('#join_date_desc').show();
        {/literal}{/if}
        {literal}
        cj('#price_set_id').val(priceSet);
        // unset membershiptype and org, so to avoid triggering processing on those, since we're using a price set
        membershipTypeOrg = cj('#membership_type_id_0').val();
        membershipTypeId = cj('#membership_type_id_1').val();
        cj('#membership_type_id_0').val(null);
        cj('#membership_type_id_1').val(null);

        // We're using a price set.  total_amount is read only *}
        cj('#total_amount').prop('readonly', 'true');
        // join date, which is pretty well synonyous to renewal_date, but allowed separately for more flexibility
        cj('#membershipJoinDate').show();

        cj('#changeMembershipOrgType').hide();
        cj('#membershipPriceSetBlockSingleMembership').hide();
        cj('#membershipPriceSetBlockPriceSet').show();
      }

      function hidePriceSet() {
        // unset price set so that we don't submit it
        priceSet = cj('#price_set_id').val();
        cj('#price_set_id').val(null);

        //set back membership org & type
        cj('#membership_type_id_0').val(membershipTypeOrg);
        cj('#membership_type_id_1').val(membershipTypeId);

        // make total_amount editable again
        cj('#total_amount').removeProp('readonly');

        cj('#changeMembershipOrgType').show();
        cj('#membershipPriceSetBlockSingleMembership').show();
        cj('#membershipPriceSetBlockPriceSet').hide();
      }
      {/literal}
    {/if}

  {literal}

    function changeNumTerms() {
      cj('#changeNumTerms').show();
      cj('#defaultNumTerms').hide();
    }

    CRM.$(function($) {
      cj('#record_contribution').click(function () {
        if (cj(this).prop('checked')) {
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
          cj('#record_contribution').prop('checked', true);
          cj('#recordContribution').show();
        }
        else {
          cj('#record_contribution').prop('checked', false);
          cj('#recordContribution').hide();
        }
      }

      var term = cj("#num_terms").val();
      if (term) {
        var renewTotal = allMemberships[memType]['total_amount_numeric'] * term;
        cj("#total_amount").val(CRM.formatMoney(renewTotal, true));
      }
      else {
        cj("#total_amount").val(allMemberships[memType]['total_amount']);
      }

      cj('.totaltaxAmount').html(allMemberships[memType]['tax_message']);
    }

    // show/hide different contact section
    setDifferentContactBlock();
    cj('#is_different_contribution_contact').change(function () {
      setDifferentContactBlock();
    });

    function setDifferentContactBlock() {
      //get the
      if (cj('#is_different_contribution_contact').prop('checked')) {
        cj('#record-different-contact').show();
      }
      else {
        cj('#record-different-contact').hide();
      }
    }

    function buildAmount( ) {
        priceSetId = cj("#price_set_id").val( );
        var fname = '#priceset';

        cj('#total_amount').val( '' );
        cj('#total_amount').attr("readonly", true);

        var dataUrl = {/literal}"{crmURL h=0 q='snippet=4'}"{literal} + '&priceSetOnly=1&priceSetId=' + priceSetId;

        var response = cj.ajax({
          url: dataUrl,
          async: false
        }).responseText;

        cj( fname ).show().html( response );
        // freeze total amount text field.

        cj( "#totalAmountORPriceSet" ).hide( );
        cj( "#mem_type_id" ).hide( );
        var manual = "{/literal}{ts}Manual membership and price{/ts}{literal}";
        cj("#price_set_id option[value='']").html( manual );
        cj( "#num_terms_row" ).hide( );
        cj(".crm-membership-form-block-financial_type_id-mode").hide();
    }

  {/literal}
  {if $show_price_set}
      showPriceSet();
  {/if}

  </script>

{/if}