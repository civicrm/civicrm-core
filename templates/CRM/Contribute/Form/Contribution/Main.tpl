{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{* Callback snippet: On-behalf profile *}
{if $snippet and !empty($isOnBehalfCallback) and !$isPaymentOnExistingContribution}
  <div class="crm-public-form-item crm-section">
    {include file="CRM/Contribute/Form/Contribution/OnBehalfOf.tpl" context="front-end"}
  </div>
{else}
{literal}
  <script type="text/javascript">

    // Putting these functions directly in template for historical reasons.
    function useAmountOther(mainPriceFieldName) {
      var currentFocus = CRM.$(':focus');
      CRM.$('input[name=' + mainPriceFieldName + ']:radio:unchecked').each(
        function () {
          if (CRM.$(this).data('is-null-option') !== undefined) {
            // Triggering this click here because over in Calculate.tpl
            // a blur action is attached
            CRM.$(this).prop('checked', true).trigger('click');
          }
        }
      );
      // Copied from `updatePriceSetHighlight()` below which isn't available here.
      // @todo - consider adding this to the actions assigned in Calculate.tpl
      CRM.$('#priceset .price-set-row span').removeClass('highlight');
      CRM.$('#priceset .price-set-row input:checked').parent().addClass('highlight');
      // Return the focus we blurred earlier.
      currentFocus.trigger('focus');

    }

    function clearAmountOther(otherPriceFieldName) {
      cj('#' + otherPriceFieldName).val('').trigger('blur');
    }

  </script>
{/literal}

  {if ($action & 1024) or $dummyTitle}
    {include file="CRM/Contribute/Form/Contribution/PreviewHeader.tpl"}
  {/if}

  {crmPermission has='administer CiviCRM'}
    {capture assign="buttonTitle"}{ts}Configure Contribution Page{/ts}{/capture}
    {crmButton target="_blank" p="civicrm/admin/contribute/settings" q="reset=1&action=update&id=`$contributionPageID`" fb=1 title="$buttonTitle" icon="fa-wrench"}{ts}Configure{/ts}{/crmButton}
    <div class='clear'></div>
  {/crmPermission}

  <div class="crm-contribution-page-id-{$contributionPageID} crm-block crm-contribution-main-form-block" data-page-id="{$contributionPageID}" data-page-template="main">

    {crmRegion name='contribution-main-not-you-block'}
    {if $contact_id && !$isPaymentOnExistingContribution}
      <div class="messages status no-popup crm-not-you-message">
        {ts 1=$display_name}Welcome %1{/ts}. (<a href="{crmURL p='civicrm/contribute/transact' q="cid=0&reset=1&id=`$contributionPageID`"}" title="{ts}Click here to do this for a different person.{/ts}">{ts 1=$display_name}Not %1, or want to do this for a different person{/ts}</a>?)
      </div>
    {/if}
    {/crmRegion}

    <div id="intro_text" class="crm-public-form-item crm-section intro_text-section">
      {$intro_text|purify}
    </div>
    {include file="CRM/common/cidzero.tpl"}

    {if $isShowMembershipBlock && $hasExistingLifetimeMembership}
      <div class="help">{ts}You have a current Lifetime Membership which does not need to be renewed.{/ts}</div>
    {/if}

    {if $isShowMembershipBlock && !$isPaymentOnExistingContribution}
      <div class="crm-public-form-item crm-section">
        {include file="CRM/Contribute/Form/Contribution/MainMembershipBlock.tpl"}
      </div>
    {elseif $isPaymentOnExistingContribution}
      {if $lineItem && $priceSetID && !$is_quick_config}
        <div class="header-dark">
          {ts}Contribution Information{/ts}{if $display_name} &ndash; {$display_name}{/if}
        </div>
        {assign var="totalAmount" value=$pendingAmount}
        {include file="CRM/Price/Page/LineItem.tpl" context="Contribution"}
      {else}
        <div class="display-block">
          <td class="label">{$form.total_amount.label}</td>
          <td><span>{$form.total_amount.html}&nbsp;&nbsp;{if $taxAmount}{ts 1=$taxTerm 2=$taxAmount|crmMoney}(includes %1 of %2){/ts}{/if}</span></td>
        </div>
      {/if}
    {else}
      <div id="priceset-div">
        {include file="CRM/Price/Form/PriceSet.tpl" extends="Contribution" hideTotal=$quickConfig}
      </div>
    {/if}

    {if !$isPaymentOnExistingContribution}
      {crmRegion name='contribution-main-pledge-block'}
      {if $pledgeBlock}
        {if array_key_exists('pledge_amount', $form)}
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
              {if array_key_exists('pledge_frequency_interval', $form)}
                {$form.pledge_frequency_interval.html}&nbsp;
              {/if}
              {$form.pledge_frequency_unit.html}<span id="pledge_installments_num">&nbsp;{ts}for{/ts}&nbsp;{$form.pledge_installments.html}&nbsp;{ts}installments.{/ts}</span>
            </div>
            <div class="clear"></div>
            {if array_key_exists('start_date', $form) && $start_date_editable}
              {if $is_date}
                <div class="label">{$form.start_date.label}</div><div class="content">{$form.start_date.html}</div>
              {else}
                <div class="label">{$form.start_date.label}</div><div class="content">{$form.start_date.html}</div>
              {/if}
            {elseif array_key_exists('start_date', $form)}
              <div class="label">{$form.start_date.label}</div>
              <div class="content">{$start_date_display|crmDate:'%b %e, %Y'}</div>
            {/if}
            <div class="clear"></div>
          </div>
        {/if}
      {/if}
      {/crmRegion}

      {if !empty($form.is_recur)}
        <div class="crm-public-form-item crm-section {$form.is_recur.name}-section">
          <div class="label">&nbsp;</div>
          <div class="content">
            {$form.is_recur.html} {$form.is_recur.label}
            {if $is_recur_interval}
              {$form.frequency_interval.html}
            {/if}
            {if !$all_text_recur}
              {if $one_frequency_unit}
                {$form.frequency_interval.label}
              {else}
                {$form.frequency_unit.html}
              {/if}
            {/if}
            {if $is_recur_installments}
              <span id="recur_installments_num">
          {ts}for{/ts} {$form.installments.html} {$form.installments.label}
          </span>
            {/if}
            <div id="recurHelp" class="description">
              {$recurringHelpText}
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
      {if $showMainEmail}
        {assign var=n value="email-`$bltID`"}
        <div class="crm-public-form-item crm-section {$form.$n.name}-section">
          <div class="label">{$form.$n.label}</div>
          <div class="content">
            {$form.$n.html}
          </div>
          <div class="clear"></div>
        </div>
      {/if}

      <div id='onBehalfOfOrg' class="crm-public-form-item crm-section">
        {include file="CRM/Contribute/Form/Contribution/OnBehalfOf.tpl"}
      </div>

      {* User account registration option. Displays if enabled for one of the profiles on this page. *}
      <div class="crm-public-form-item crm-section cms_user-section">
        {include file="CRM/common/CMSUser.tpl"}
      </div>
      <div class="crm-public-form-item crm-section premium_block-section">
        {include file="CRM/Contribute/Form/Contribution/PremiumBlock.tpl" context="makeContribution" preview=false showPremiumSelectionFields=true}
      </div>

      {if $honoreeProfileFields && $honoreeProfileFields|@count}
        <fieldset class="crm-public-form-item crm-group honor_block-group">
          {crmRegion name="contribution-soft-credit-block"}
            <legend>{$honor_block_title}</legend>
            <div class="crm-public-form-item crm-section honor_block_text-section">
              {$honor_block_text}
            </div>
          {if !empty($form.soft_credit_type_id.html)}
            <div class="crm-public-form-item crm-section {$form.soft_credit_type_id.name}-section">
              <div class="content" >
                {$form.soft_credit_type_id.html}
                <div class="description">{ts}Select an option to reveal honoree information fields.{/ts}</div>
              </div>
            </div>
          {/if}
          {/crmRegion}
          <div id="honorType" class="honoree-name-email-section">
            {include file="CRM/UF/Form/Block.tpl" fields=$honoreeProfileFields mode=8 prefix='honor' hideFieldset=true}
          </div>
        </fieldset>
      {/if}

      <div class="crm-public-form-item crm-group custom_pre_profile-group">
        {include file="CRM/UF/Form/Block.tpl" fields=$customPre prefix=false hideFieldset=false}
      </div>

      {if array_key_exists('pcp_display_in_roll', $form)}
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

    {if !empty($form.payment_processor_id.label)}
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

    {include file="CRM/Core/BillingBlockWrapper.tpl"}

    <div class="crm-public-form-item crm-group custom_post_profile-group">
      {include file="CRM/UF/Form/Block.tpl" fields=$customPost prefix=false hideFieldset=false}
    </div>

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
    {if array_key_exists('pcp_display_in_roll', $form)}
      pcpAnonymous();
    {/if}

    {literal}

    cj('input[name="soft_credit_type_id"]').on('change', function() {
      enableHonorType();
    });

    function enableHonorType() {
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

    function toggleRecur() {
      var isRecur = cj('input[id="is_recur"]:checked');

      var quickConfig = {/literal}'{$quickConfig}'{literal};
      if (cj("#auto_renew").length && quickConfig) {
        showHideAutoRenew(null);
      }

      var frequencyUnit = cj('#frequency_unit');
      var frequencyInerval = cj('#frequency_interval');
      var installments = cj('#installments');
      isDisabled = false;

      if (isRecur.val() > 0) {
        cj('#recurHelp').show();
        frequencyUnit.prop('disabled', false).addClass('required');
        frequencyInerval.prop('disabled', false).addClass('required');
        installments.prop('disabled', false);
        cj('#amount_sum_label').text('{/literal}{ts escape='js'}Regular Amount{/ts}{literal}');
      }
      else {
        cj('#recurHelp').hide();
        frequencyUnit.prop('disabled', true).removeClass('required');
        frequencyInerval.prop('disabled', true).removeClass('required');
        installments.prop('disabled', true);
        cj('#amount_sum_label').text('{/literal}{ts escape='js'}Total Amount{/ts}{literal}');
      }
    }

    function pcpAnonymous() {
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
{include file="CRM/Form/validate.tpl"}
