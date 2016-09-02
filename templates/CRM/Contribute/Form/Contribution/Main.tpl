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
{* Callback snippet: On-behalf profile *}
{if $snippet and !empty($isOnBehalfCallback) and !$ccid}
  <div class="crm-public-form-item crm-section">
    {include file="CRM/Contribute/Form/Contribution/OnBehalfOf.tpl" context="front-end"}
  </div>
{else}
  {literal}
  <script type="text/javascript">

  // Putting these functions directly in template so they are available for standalone forms
  function useAmountOther() {
    var priceset = {/literal}{if $contriPriceset}'{$contriPriceset}'{else}0{/if}{literal};

    for( i=0; i < document.Main.elements.length; i++ ) {
      element = document.Main.elements[i];
      if ( element.type == 'radio' && element.name == priceset ) {
        if (element.value == '0' ) {
          element.click();
        }
        else {
          element.checked = false;
        }
      }
    }
  }

  function clearAmountOther() {
    var priceset = {/literal}{if $priceset}'#{$priceset}'{else}0{/if}{literal}
    if( priceset ){
      cj(priceset).val('');
      cj(priceset).blur();
    }
    if (document.Main.amount_other == null) return; // other_amt field not present; do nothing
    document.Main.amount_other.value = "";
  }

  </script>
  {/literal}

  {if $action & 1024}
  {include file="CRM/Contribute/Form/Contribution/PreviewHeader.tpl"}
  {/if}

  {include file="CRM/common/TrackingFields.tpl"}

  <div class="crm-contribution-page-id-{$contributionPageID} crm-block crm-contribution-main-form-block">

  {if $contact_id && !$ccid}
    <div class="messages status no-popup crm-not-you-message">
      {ts 1=$display_name}Welcome %1{/ts}. (<a href="{crmURL p='civicrm/contribute/transact' q="cid=0&reset=1&id=`$contributionPageID`"}" title="{ts}Click here to do this for a different person.{/ts}">{ts 1=$display_name}Not %1, or want to do this for a different person{/ts}</a>?)
    </div>
  {/if}

  <div id="intro_text" class="crm-public-form-item crm-section intro_text-section">
    {$intro_text}
  </div>
  {include file="CRM/common/cidzero.tpl"}
  {if $islifetime or $ispricelifetime }
  <div class="help">{ts}You have a current Lifetime Membership which does not need to be renewed.{/ts}</div>
  {/if}

  {if !empty($useForMember) && !$ccid}
    <div class="crm-public-form-item crm-section">
      {include file="CRM/Contribute/Form/Contribution/MembershipBlock.tpl" context="makeContribution"}
    </div>
  {elseif !empty($ccid)}
    {if $lineItem && $priceSetID && !$is_quick_config}
      <div class="header-dark">
        {ts}Contribution Information{/ts}
      </div>
      {assign var="totalAmount" value=$pendingAmount}
      {include file="CRM/Price/Page/LineItem.tpl" context="Contribution"}
    {else}
      <div class="display-block">
        <td class="label">{$form.total_amount.label}</td>
        <td><span>{$form.total_amount.html|crmMoney}</span></td>
      </div>
    {/if}
  {else}
    <div id="priceset-div">
    {include file="CRM/Price/Form/PriceSet.tpl" extends="Contribution"}
    </div>
  {/if}

  {if !$ccid}
    {crmRegion name='contribution-main-pledge-block'}
    {if $pledgeBlock}
      {if $is_pledge_payment}
      <div class="crm-public-form-item crm-section {$form.pledge_amount.name}-section">
        <div class="label">{$form.pledge_amount.label}&nbsp;<span class="crm-marker">*</span></div>
        <div class="content">{$form.pledge_amount.html}</div>
        <div class="clear"></div>
      </div>
      {else}
        <div class="crm-public-form-item crm-section {$form.is_pledge.name}-section">
          <div class="label">&nbsp;</div>
          <div class="content">
            {$form.is_pledge.html}&nbsp;
            {if $is_pledge_interval}
              {$form.pledge_frequency_interval.html}&nbsp;
            {/if}
            {$form.pledge_frequency_unit.html}<span id="pledge_installments_num">&nbsp;{ts}for{/ts}&nbsp;{$form.pledge_installments.html}&nbsp;{ts}installments.{/ts}</span>
          </div>
          <div class="clear"></div>
          {if $start_date_editable}
            {if $is_date}
              <div class="label">{$form.start_date.label}</div><div class="content">{include file="CRM/common/jcalendar.tpl" elementName=start_date}</div>
            {else}
              <div class="label">{$form.start_date.label}</div><div class="content">{$form.start_date.html}</div>
            {/if}
          {else}
            <div class="label">{$form.start_date.label}</div>
            <div class="content">{$start_date_display|date_format}</div>
          {/if}
        <div class="clear"></div>
        </div>
      {/if}
    {/if}
    {/crmRegion}

    {if $form.is_recur}
    <div class="crm-public-form-item crm-section {$form.is_recur.name}-section">
      <div class="label">&nbsp;</div>
      <div class="content">
        {$form.is_recur.html} {$form.is_recur.label} {ts}every{/ts}
        {if $is_recur_interval}
          {$form.frequency_interval.html}
        {/if}
        {if $one_frequency_unit}
          {$frequency_unit}
          {else}
          {$form.frequency_unit.html}
        {/if}
        {if $is_recur_installments}
          <span id="recur_installments_num">
          {ts}for{/ts} {$form.installments.html} {$form.installments.label}
          </span>
        {/if}
        <div id="recurHelp" class="description">
          {ts}Your recurring contribution will be processed automatically.{/ts}
          {if $is_recur_installments}
            {ts}You can specify the number of installments, or you can leave the number of installments blank if you want to make an open-ended commitment. In either case, you can choose to cancel at any time.{/ts}
          {/if}
          {if $is_email_receipt}
            {ts}You will receive an email receipt for each recurring contribution.{/ts}
          {/if}
        </div>
      </div>
      <div class="clear"></div>
    </div>
    {/if}
    {if $pcpSupporterText}
    <div class="crm-public-form-item crm-section pcpSupporterText-section">
      <div class="label">&nbsp;</div>
      <div class="content">{$pcpSupporterText}</div>
      <div class="clear"></div>
    </div>
    {/if}
    {assign var=n value=email-$bltID}
    <div class="crm-public-form-item crm-section {$form.$n.name}-section">
      <div class="label">{$form.$n.label}</div>
      <div class="content">
        {$form.$n.html}
      </div>
      <div class="clear"></div>
    </div>

    <div class="crm-public-form-item crm-section">
      {include file="CRM/Contribute/Form/Contribution/OnBehalfOf.tpl"}
    </div>

    {* User account registration option. Displays if enabled for one of the profiles on this page. *}
    <div class="crm-public-form-item crm-section cms_user-section">
      {include file="CRM/common/CMSUser.tpl"}
    </div>
    <div class="crm-public-form-item crm-section premium_block-section">
      {include file="CRM/Contribute/Form/Contribution/PremiumBlock.tpl" context="makeContribution"}
    </div>

    {if $honoreeProfileFields|@count}
      <fieldset class="crm-public-form-item crm-group honor_block-group">
        {crmRegion name="contribution-soft-credit-block"}
          <legend>{$honor_block_title}</legend>
          <div class="crm-public-form-item crm-section honor_block_text-section">
            {$honor_block_text}
          </div>
          {if $form.soft_credit_type_id.html}
            <div class="crm-public-form-item crm-section {$form.soft_credit_type_id.name}-section">
              <div class="content" >
                {$form.soft_credit_type_id.html}
                <div class="description">{ts}Select an option to reveal honoree information fields.{/ts}</div>
              </div>
            </div>
          {/if}
        {/crmRegion}
        <div id="honorType" class="honoree-name-email-section">
          {include file="CRM/UF/Form/Block.tpl" fields=$honoreeProfileFields mode=8 prefix='honor'}
        </div>
      </fieldset>
    {/if}

    <div class="crm-public-form-item crm-group custom_pre_profile-group">
    {include file="CRM/UF/Form/Block.tpl" fields=$customPre}
    </div>

    {if $isHonor}
    <fieldset class="crm-public-form-item crm-group pcp-group">
      <div class="crm-public-form-item crm-section pcp-section">
        <div class="crm-public-form-item crm-section display_in_roll-section">
          <div class="content">
            {$form.pcp_display_in_roll.html} &nbsp;
            {$form.pcp_display_in_roll.label}
          </div>
          <div class="clear"></div>
        </div>
        <div id="nameID" class="crm-public-form-item crm-section is_anonymous-section">
          <div class="content">
            {$form.pcp_is_anonymous.html}
          </div>
          <div class="clear"></div>
        </div>
        <div id="nickID" class="crm-public-form-item crm-section pcp_roll_nickname-section">
          <div class="label">{$form.pcp_roll_nickname.label}</div>
          <div class="content">{$form.pcp_roll_nickname.html}
            <div class="description">{ts}Enter the name you want listed with this contribution. You can use a nick name like 'The Jones Family' or 'Sarah and Sam'.{/ts}</div>
          </div>
          <div class="clear"></div>
        </div>
        <div id="personalNoteID" class="crm-public-form-item crm-section pcp_personal_note-section">
          <div class="label">{$form.pcp_personal_note.label}</div>
          <div class="content">
            {$form.pcp_personal_note.html}
            <div class="description">{ts}Enter a message to accompany this contribution.{/ts}</div>
          </div>
          <div class="clear"></div>
        </div>
      </div>
    </fieldset>
    {/if}

  {* end of ccid loop *}
  {/if}

  {if $form.payment_processor_id.label}
  {* PP selection only works with JS enabled, so we hide it initially *}
  <fieldset class="crm-public-form-item crm-group payment_options-group" style="display:none;">
    <legend>{ts}Payment Options{/ts}</legend>
    <div class="crm-public-form-item crm-section payment_processor-section">
      <div class="label">{$form.payment_processor_id.label}</div>
      <div class="content">{$form.payment_processor_id.html}</div>
      <div class="clear"></div>
    </div>
  </fieldset>
  {/if}

  {if $is_pay_later}
  <fieldset class="crm-public-form-item crm-group pay_later-group">
    <legend>{ts}Payment Options{/ts}</legend>
    <div class="crm-public-form-item crm-section pay_later_receipt-section">
      <div class="label">&nbsp;</div>
      <div class="content">
        [x] {$pay_later_text}
      </div>
      <div class="clear"></div>
    </div>
  </fieldset>
  {/if}

  <div id="billing-payment-block">
    {include file="CRM/Financial/Form/Payment.tpl" snippet=4}
  </div>
  {include file="CRM/common/paymentBlock.tpl"}

  <div class="crm-public-form-item crm-group custom_post_profile-group">
  {include file="CRM/UF/Form/Block.tpl" fields=$customPost}
  </div>

  {if $is_monetary and $form.bank_account_number}
  <div id="payment_notice">
    <fieldset class="crm-public-form-item crm-group payment_notice-group">
      <legend>{ts}Agreement{/ts}</legend>
      {ts}Your account data will be used to charge your bank account via direct debit. While submitting this form you agree to the charging of your bank account via direct debit.{/ts}
    </fieldset>
  </div>
  {/if}

  {if $isCaptcha}
    {include file='CRM/common/ReCAPTCHA.tpl'}
  {/if}
  <div id="crm-submit-buttons" class="crm-submit-buttons">
  {include file="CRM/common/formButtons.tpl" location="bottom"}
  </div>
  {if $footer_text}
  <div id="footer_text" class="crm-public-form-item crm-section contribution_footer_text-section">
    <p>{$footer_text}</p>
  </div>
  {/if}
</div>
<script type="text/javascript">
  {if $isHonor}
  pcpAnonymous();
  {/if}

  {literal}

  cj('input[name="soft_credit_type_id"]').on('change', function() {
    enableHonorType();
  });

  function enableHonorType( ) {
    var selectedValue = cj('input[name="soft_credit_type_id"]:checked');
    if ( selectedValue.val() > 0) {
      cj('#honorType').show();
    }
    else {
      cj('#honorType').hide();
    }
  }

  cj('input[id="is_recur"]').on('change', function() {
    toggleRecur();
  });

  function toggleRecur( ) {
    var isRecur = cj('input[id="is_recur"]:checked');
    var allowAutoRenew = {/literal}'{$allowAutoRenewMembership}'{literal};
    if ( allowAutoRenew && cj("#auto_renew") ) {
      showHideAutoRenew( null );
    }
    if (isRecur.val() > 0) {
      cj('#recurHelp').show();
      cj('#amount_sum_label').text(ts('Regular amount'));
    }
    else {
      cj('#recurHelp').hide();
      cj('#amount_sum_label').text(ts('Total amount'));
    }
  }

  function pcpAnonymous( ) {
    // clear nickname field if anonymous is true
    if (document.getElementsByName("pcp_is_anonymous")[1].checked) {
      document.getElementById('pcp_roll_nickname').value = '';
    }
    if (!document.getElementsByName("pcp_display_in_roll")[0].checked) {
      cj('#nickID').hide();
      cj('#nameID').hide();
      cj('#personalNoteID').hide();
    }
    else {
      if (document.getElementsByName("pcp_is_anonymous")[0].checked) {
        cj('#nameID').show();
        cj('#nickID').show();
        cj('#personalNoteID').show();
      }
      else {
        cj('#nameID').show();
        cj('#nickID').hide();
        cj('#personalNoteID').hide();
      }
    }
  }

  CRM.$(function($) {
    enableHonorType();
    toggleRecur();
    skipPaymentMethod();
  });

  CRM.$(function($) {
    // highlight price sets
    function updatePriceSetHighlight() {
      $('#priceset .price-set-row span').removeClass('highlight');
      $('#priceset .price-set-row input:checked').parent().addClass('highlight');
    }
    $('#priceset input[type="radio"]').change(updatePriceSetHighlight);
    updatePriceSetHighlight();

    // Update pledge contribution amount when pledge checkboxes change
    $("input[name^='pledge_amount']").on('change', function() {
      var total = 0;
      $("input[name^='pledge_amount']:checked").each(function() {
        total += Number($(this).attr('amount'));
      });
      $("input[name^='price_']").val(total.toFixed(2));
    });
  });
  {/literal}
</script>
{/if}

{* jQuery validate *}
{* disabled because more work needs to be done to conditionally require credit card fields *}
{*include file="CRM/Form/validate.tpl"*}
