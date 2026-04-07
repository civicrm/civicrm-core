{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{* this template is used for adding/editing/deleting memberships for a contact  *}
{if $isRecur}
  <div class="messages status no-popup">
    {icon icon="fa-info-circle"}{/icon}
    <p>{ts}This membership is set to renew automatically {if $endDate}on {$endDate|crmDate}{/if}. Please be aware that any changes that you make here may not be reflected in the payment processor. Please ensure that you alter the related subscription at the payment processor.{/ts}</p>
    {if $cancelAutoRenew}<p>{ts 1=$cancelAutoRenew}To stop the automatic renewal:
      <a href="%1">Cancel auto-renew</a>
    {/ts}</p>{/if}
  </div>
{/if}
<div class="spacer"></div>
{if $priceSetId}
  {include file="CRM/Price/Form/PriceSet.tpl" isShowAdminVisibilityFields=true extends="Membership"  hideTotal=false}
  {literal}
  <script type="text/javascript">
  CRM.$(function($) {
    var membershipValues = [];
    {/literal}{foreach from=$optionsMembershipTypes item=memType key=opId}{literal}
      membershipValues[{/literal}{$opId}{literal}] = {/literal}{$memType}{literal};
    {/literal}{/foreach}{literal}
    processMembershipPriceset(membershipValues, {/literal}{$autoRenewOption}{literal}, 1);
    {/literal}{if !$membershipMode}{literal}
      enableAmountSection({/literal}{$contributionType}{literal});
    {/literal}{/if}{literal}
  });
  </script>
  {/literal}
{else}
  {if $membershipMode == 'test'}
    {assign var=registerMode value="TEST"}
    {elseif $membershipMode == 'live'}
    {assign var=registerMode value="LIVE"}
  {/if}
  {if !$emailExists and $action neq 8 and $context neq 'standalone'}
  <div class="messages status no-popup">
    {icon icon="fa-info-circle"}{/icon}
    <p>{ts}You will not be able to send an automatic email receipt for this Membership because there is no email address recorded for this contact. If you want a receipt to be sent when this Membership is recorded, click Cancel and then click Edit from the Summary tab to add an email address before recording the Membership.{/ts}</p>
  </div>
  {/if}
  {if $membershipMode}
  <div class="help">
    {ts 1=$displayName|escape 2=$registerMode}Use this form to submit Membership Record on behalf of %1. <strong>A %2 transaction will be submitted</strong> using the selected payment processor.{/ts}
  </div>
  {/if}
  <div class="crm-block crm-form-block crm-membership-form-block">
    {if $newCredit AND $action EQ 1 AND $membershipMode EQ null}
    <div class="action-link css_right crm-link-credit-card-mode">
      {if $contactId}
        {capture assign=ccModeLink}{crmURL p='civicrm/contact/view/membership' q="reset=1&action=add&cid=`$contactId`&context=`$context`&mode=live"}{/capture}
      {else}
        {capture assign=ccModeLink}{crmURL p='civicrm/contact/view/membership' q="reset=1&action=add&context=standalone&mode=live"}{/capture}
      {/if}
     <a class="open-inline-noreturn action-item crm-hover-button" href="{$ccModeLink}"><i class="crm-i fa-credit-card" role="img" aria-hidden="true"></i> {ts}submit credit card membership{/ts}</a>
    </div>
    {/if}
    {if $action eq 8}
    <div class="messages status no-popup">
      {icon icon="fa-info-circle"}{/icon}
      {$deleteMessage|smarty:nodefaults}
    </div>
    {else}
      <table class="form-layout-compressed">
        <tr class="crm-membership-form-contact-id">
           <td class="label">{$form.contact_id.label}</td>
           <td>{$form.contact_id.html}</td>
        </tr>
        <tr class="crm-membership-form-block-membership_type_id">
          <td class="label">{$form.membership_type_id.label}</td>
          <td id="mem_type_id-readonly">
            <span id="membership_type_id_0-readonly"></span> : <span id="membership_type_id_1-readonly"></span>
            <span id="mem-type-override">
              <a href="#" class="crm-hover-button action-item override-mem-type" id="show-mem-type">
                {ts}Override organization and type{/ts}
              </a>
              {help id="override_membership_type"}
            </span>
          </td>
          <td id="mem_type_id-editable"><span id='mem_type_id'>{$form.membership_type_id.html|smarty:nodefaults}</span>
            {if $hasPriceSets}
              <span id='totalAmountORPriceSet'> {ts}OR{/ts}</span>
              <span id='selectPriceSet'>{$form.price_set_id.html}</span>
              {if $buildPriceSet && $priceSet}
                <div id="priceset"><br/>{include file="CRM/Price/Form/PriceSet.tpl" extends="Membership" hideTotal=false isShowAdminVisibilityFields=true}</div>
                {else}
                <div id="priceset" class="hiddenElement"></div>
              {/if}
            {/if}
            {if $member_is_test} {ts}(test){/ts}{/if}<br />
            <span class="description">{ts}Select Membership Organization and then Membership Type.{/ts}{if $hasPriceSets} {ts}Alternatively, you can use a price set.{/ts}{/if}</span>
          </td>
        </tr>
        <tr id="maxRelated" class="crm-membership-form-block-max_related">
          <td class="label">{$form.max_related.label}</td>
          <td>{$form.max_related.html}<br />
            <span class="description">{ts}Maximum number of related memberships (leave blank for unlimited).{/ts} <span id="eligibleRelated"></span></span>
          </td>
        </tr>
        {if $action eq 1}
          <tr id="num_terms_row" class="crm-membership-form-block-num_terms">
            <td class="label">{$form.num_terms.label}</td>
            <td>&nbsp;{$form.num_terms.html}<br />
              <span class="description">{ts}Set the Membership Expiration Date this many membership periods from now. Make sure the appropriate corresponding fee is entered below.{/ts}</span>
            </td>
          </tr>
        {/if}
        <tr class="crm-membership-form-block-source">
          <td class="label">{$form.source.label}</td>
          <td>&nbsp;{$form.source.html}<br />
          <span class="description">{ts}Source of this membership. This value is searchable.{/ts}</span></td>
        </tr>

        {* CRM-7362 --add campaign to membership *}
        {include file="CRM/Campaign/Form/addCampaignToComponent.tpl"
        campaignTrClass="crm-membership-form-block-campaign_id"}

        <tr class="crm-membership-form-block-join_date"><td class="label">{$form.join_date.label}</td><td>{$form.join_date.html}
          <br />
          <span class="description">{ts}When did this contact first become a member?{/ts}</span></td></tr>
        <tr class="crm-membership-form-block-start_date"><td class="label">{$form.start_date.label}</td><td>{$form.start_date.html}
          <br />
          <span class="description">{ts}First day of current continuous membership period. Start Date will be automatically set based on Membership Type if you don't select a date.{/ts}</span></td></tr>
        <tr class="crm-membership-form-block-end_date"><td class="label">{$form.end_date.label}</td>
          <td id="end-date-readonly">
              {$endDate|crmDate}
              <a href="#" class="crm-hover-button action-item override-date" id="show-end-date">
                {ts}Override end date{/ts}
              </a>
              {help id="override_end_date"}
          </td>
          <td id="end-date-editable">
            {$form.end_date.html}
            <br />
            <span class="description">{ts}Latest membership period expiration date. End Date will be automatically set based on Membership Type if you don't select a date.{/ts}</span>
          </td>
        </tr>
        {if !$membershipMode}
          <tr>
            <td class="label">{$form.is_override.label} {help id="is_override"}</td>
            <td>
              <span id="is-override">{$form.is_override.html}</span>
              <span id="status-override-end-date">{$form.status_override_end_date.html}</span>
            </td>
          </tr>
          {* Show read-only Status block - when action is UPDATE and is_override is FALSE *}
          <tr id="memberStatus_show">
            {if $action eq 2}
              <td class="label">{$form.status_id.label}</td><td class="view-value">{$membershipStatus}</td>
            {/if}
          </tr>

          {* Show editable status field when is_override is TRUE *}
          <tr id="memberStatus"><td class="label">{$form.status_id.label}</td><td>{$form.status_id.html}<br />
            <span class="description">{ts}When <strong>Status Override</strong> is active, the selected status will remain in force (it will NOT be subject to membership status rules) until it is cancelled or become inactive.{/ts}</span></td></tr>
        {/if}

        {if $accessContribution and !$membershipMode AND ($action neq 2 or (!$rows.0.contribution_id AND !$softCredit))}
          <tr id="contri">
            <td class="label">{$form.record_contribution.label}</td>
            <td>{$form.record_contribution.html}<br />
              <span class="description">{ts}Check this box to enter or update payment information. You will also be able to generate a customized receipt.{/ts}</span></td>
          </tr>
          <tr class="crm-membership-form-block-record_contribution"><td colspan="2">
            <fieldset id="recordContribution"><legend>{ts}Membership Payment and Receipt{/ts}</legend>
        {/if}
        {include file="CRM/Member/Form/MembershipCommon.tpl"}
        {if $emailExists and $isEmailEnabledForSite}
          <tr id="send-receipt" class="crm-membership-form-block-send_receipt">
            <td class="label">{$form.send_receipt.label}</td>
            <td>
              {$form.send_receipt.html}
              <span class="description">
                {ts 1=$emailExists}Automatically email a membership confirmation and receipt to %1? OR if the payment is from a different contact, this email will only go to them.{/ts}
                <span class="auto-renew-text">{ts}For auto-renewing memberships the emails are sent when each payment is received{/ts}</span>
              </span>
            </td>
          </tr>
        {elseif $context eq 'standalone' and $isEmailEnabledForSite}
          <tr id="email-receipt" style="display:none;">
            <td class="label">{$form.send_receipt.label}</td>
            <td>
              {$form.send_receipt.html}
              <span class="description">
                {ts}Automatically email a membership confirmation and receipt to {/ts}<span id="email-address"></span>? {ts}OR if the payment is from a different contact, this email will only go to them.{/ts}
                <span class="auto-renew-text">{ts}For auto-renewing memberships the emails are sent when each payment is received{/ts}</span>
              </span>
            </td>
          </tr>
        {/if}
        <tr id="fromEmail" style="display: none" class="crm-contactEmail-form-block-fromEmailAddress crm-email-element">
          <td class="label">{$form.from_email_address.label}</td>
          <td>{$form.from_email_address.html}  {help id="from_email_address" file="CRM/Contact/Form/Task/Help/Email/id-from_email.hlp"}</td>
        </tr>
        <tr id='notice' style="display:none;">
          <td class="label">{$form.receipt_text.label}</td>
          <td class="html-adjust"><span class="description">{ts}If you need to include a special message for this member, enter it here. Otherwise, the confirmation email will include the standard receipt message configured under System Message Templates.{/ts}</span>
            {$form.receipt_text.html|crmAddClass:huge}</td>
        </tr>
      </table>
      {include file="CRM/common/customDataBlock.tpl" cid=false}
      {if $accessContribution and $action eq 2 and $rows.0.contribution_id}
        <details class="crm-accordion-bold" open>
          <summary>{ts}Related Contributions{/ts}</summary>
          <div class="crm-accordion-body">
            {include file="CRM/Contribute/Form/Selector.tpl" context="Search"}
            <script type="text/javascript">
              var membershipID = {$entityID};
              var contactID = {$contactId};
              {literal}
              CRM.$(function($) {
                CRM.loadPage(
                  CRM.url(
                    'civicrm/membership/recurring-contributions',
                    {
                      reset: 1,
                      membershipID: membershipID,
                      cid: contactID
                    },
                    'back'
                  ),
                  {
                    target : '#membership-recurring-contributions',
                    dialog : false
                  }
                );
              });
              {/literal}
            </script>
            <div id="membership-recurring-contributions"></div>
          </div>
        </details>
      {/if}
      {if $softCredit}
        <details class="crm-accordion-bold" open>
          <summary>{ts}Related Soft Contributions{/ts}</summary>
          <div class="crm-accordion-body">{include file="CRM/Contribute/Page/ContributionSoft.tpl" context="membership"}</div>
       </details>
      {/if}
    {/if}

    <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
  </div> <!-- end form-block -->

  {if $action neq 8} {* Jscript additions not need for Delete action *}
    {if $accessContribution and !$membershipMode AND ($action neq 2 or !$rows.0.contribution_id)}

    {include file="CRM/common/showHideByFieldValue.tpl"
    trigger_field_id    ="record_contribution"
    trigger_value       =""
    target_element_id   ="recordContribution"
    target_element_type ="table-row"
    field_type          ="radio"
    invert              = 0
    }
    {/if}

    {literal}
    <script type="text/javascript">
      function setPaymentBlock(mode, checkboxEvent) {
        if (cj('#price_set_id').length > 0 && cj('#price_set_id').val()) {
          return;
        }
        var membershipTypeID = parseInt(cj('#membership_type_id_1').val());
        if (!membershipTypeID) {
          return;
        }

        var allMemberships = {/literal}{$allMembershipInfo}{literal};
        membershipType = allMemberships[membershipTypeID];
        if (!mode) {
          //check the record_contribution checkbox if membership is a paid one
          {/literal}{if $action eq 1}{literal}
          if (!checkboxEvent) {
            if (membershipType['total_amount_numeric'] > 0) {
              cj('#record_contribution').prop('checked','checked');
              cj('#recordContribution').show();
            }
            else {
              cj('#record_contribution').prop('checked', false);
              cj('#recordContribution').hide();
            }
          }
          {/literal}{/if}{literal}
        }

        // skip this for test and live modes because financial type is set automatically
        cj("#financial_type_id").val(membershipType['financial_type_id']);
        // Get the number of terms from the form, default to 1 if no num_terms element.
        var term = cj('#num_terms').val() || 1;
        var taxTerm = {/literal}{$taxTerm|@json_encode}{literal};
        var currency = {/literal}{$currency_symbol|@json_encode}{literal};
        var taxExclusiveAmount = membershipType['total_amount_numeric'] * term;
        var taxAmount = (membershipType['tax_rate']/100)*taxExclusiveAmount;
        taxAmount = isNaN (taxAmount) ? 0:taxAmount;
        cj("#total_amount").val(CRM.formatMoney(taxExclusiveAmount + taxAmount, true));
        cj('.totaltaxAmount').html(allMemberships[membershipTypeID]['tax_message']);
      }


      CRM.$(function($) {
      var mode   = {/literal}'{$membershipMode}'{literal};
      if ( !mode ) {
        // Offline form (mode = false) has the record_contribution checkbox
        cj('#record_contribution').click( function( ) {
          if ( cj(this).prop('checked') ) {
            cj('#recordContribution').show( );
            setPaymentBlock( false, true);
          }
          else {
            cj('#recordContribution').hide( );
          }
        });
      }

      cj('#membership_type_id_1').change( function( ) {
        setPaymentBlock(mode);
      });
      cj('#num_terms').change( function( ) {
        setPaymentBlock(mode);
      });

      // show/hide different contact section
      setDifferentContactBlock();
      cj('#is_different_contribution_contact').change( function() {
        setDifferentContactBlock();
      });

      // give option to override membership type for auto-renew memberships - dev/core#1331
      {/literal}
      {if $isRecur}
        cj('#membership_type_id_0-readonly').text(cj('#membership_type_id_0 option:selected').text());
        cj('#membership_type_id_1-readonly').text(cj('#membership_type_id_1 option:selected').text());
        cj('#mem_type_id-readonly').show();
        cj('#mem_type_id-editable').hide();
      {else}
        cj('#mem_type_id-readonly').hide();
        cj('#mem_type_id-editable').show();
      {/if}
      {literal}

      cj('#show-mem-type').click( function( e ) {
        e.preventDefault();
        cj('#mem_type_id-readonly').hide();
        cj('#mem_type_id-editable').show();
      });

      // give option to override end-date for auto-renew memberships
      {/literal}
      {if $isRecur && $endDate}
        cj('#end-date-readonly').show();
        cj('#end-date-editable').hide();
      {else}
        cj('#end-date-readonly').hide();
        cj('#end-date-editable').show();
      {/if}
      {literal}

      cj('#show-end-date').click( function( e ) {
        e.preventDefault();
        cj('#end-date-readonly').hide();
        cj('#end-date-editable').show();
      });

    });

    function setDifferentContactBlock( ) {
      // show/hide different contact section
      if ( cj('#is_different_contribution_contact').prop('checked') ) {
        cj('#record-different-contact').show();
      }
      else {
        cj('#record-different-contact').hide();
      }
    }

    function showEmailOptions() {
      {/literal}
      // @todo emailExists refers to the primary contact for the page.
      // elsewhere some script determines if there is a paying contact the
      // email should go to instead (e.g gift membership). This should be checked for here
      // and that merged into that code as currently behaviour is inconsistent.
      var emailExists = {$emailExists|json_encode};
      var isStandalone = {if $context == 'standalone'}true{else}false{/if};
      var isEmailEnabledForSite = {if $isEmailEnabledForSite}true{else}false{/if};

      {literal}
      var isEmailable = (isEmailEnabledForSite && (emailExists || isStandalone));

      if (isEmailable && cj('#send_receipt').prop('checked') && !cj('#auto_renew').prop('checked')) {
        // Hide extra message and from email for recurring as they cannot be stored until use.
        cj('#notice').show();
        cj('#fromEmail').show();
      }
      else {
        cj('#notice').hide();
        cj('#fromEmail').hide();
      }
    }
    </script>
    <script type="text/javascript">

    {/literal}{if !$membershipMode}{literal}
    cj( "#is_override" ).change(function() {
      showHideMemberStatus();
    });

    showHideMemberStatus();
    function showHideMemberStatus() {
      var isOverride = cj( "#is_override" ).val();
      switch (isOverride) {
        case '0':
          cj('#memberStatus').hide();
          cj('#memberStatus_show').show();
          cj('#status-override-end-date').hide();
          cj('#status_id option[selected]').removeAttr('selected');
          break;
        case '1':
          cj('#memberStatus').show();
          cj('#memberStatus_show').hide();
          cj('#status-override-end-date').hide();
          break;
        case '2':
          cj('#memberStatus').show();
          cj('#memberStatus_show').hide();
          cj('#status-override-end-date').show();
          break;
        default :
          cj('#memberStatus').hide( );
          cj('#memberStatus_show').show( );
          cj('#status-override-end-date').hide();
          cj('#status_id option[selected]').removeAttr('selected');
          break;
      }
    }
    {/literal}{/if}

    {if $context eq 'standalone' and $isEmailEnabledForSite}
    {literal}
    CRM.$(function($) {
      var $form = $("form.{/literal}{$form.formClass}{literal}");
      $("#contact_id", $form).change(checkEmail);
      checkEmail( );

      function checkEmail( ) {
        var data = $("#contact_id", $form).select2('data');
        if (data && data.extra && data.extra.email && data.extra.email.length) {
          $("#email-receipt", $form).show();
          if ($("#send_receipt", $form).is(':checked')) {
            $("#notice", $form).show();
          }
          $("#email-address", $form).html(data.extra.email);
        }
        else {
          $("#email-receipt, #notice", $form).hide();
        }
      }
    });

    {/literal}
    {/if}

    {literal}
    //keep read only always checked.
    CRM.$(function($) {
      var $form = $("form.{/literal}{$form.formClass}{literal}");
      var allowAutoRenew   = {/literal}'{$allowAutoRenew}'{literal};
      var alreadyAutoRenew = {/literal}'{$alreadyAutoRenew}'{literal};
      if ( allowAutoRenew || alreadyAutoRenew ) {
        $( "#auto_renew" ).click(function( ) {
          if ( $(this).attr( 'readonly' ) ) {
            $(this).prop('checked', true );
          }
          showEmailOptions();
        });
      }

      {/literal}
      {if !empty($existingContactMemberships)}

      var alert, memberorgs = {$existingContactMemberships|@json_encode};

      {literal}
      $("select[name='membership_type_id[0]']").change(checkExistingMemOrg);



      function checkExistingMemOrg () {
        alert && alert.close && alert.close();
        var selectedorg = $("select[name='membership_type_id[0]']").val();
        if (selectedorg in memberorgs) {
          var andEndDate = '',
            endDate = memberorgs[selectedorg].membership_end_date,
            org = $('option:selected', "select[name='membership_type_id[0]']").text();
          if (endDate) {
            andEndDate = '{/literal}{ts escape='js' 1='%1'}and end date of %1{/ts}{literal}';
            andEndDate = ' ' + ts(andEndDate, {1:endDate});
          }

          alert = CRM.alert(
            // Mixing client-side variables with a translated string in smarty is awkward!
            ts({/literal}'{ts escape='js'}This contact has an existing %1 membership at %2 with %3 status%4.{/ts}'{literal}, {1:memberorgs[selectedorg].membership_type, 2: org, 3: memberorgs[selectedorg].membership_status, 4: andEndDate})
              + '<ul><li><a href="' + memberorgs[selectedorg].renewUrl + '">'
              + {/literal}'{ts escape='js'}Renew the existing membership instead{/ts}'
              + '</a></li><li><a href="' + memberorgs[selectedorg].membershipTab + '">'
              + '{ts escape='js'}View all existing and / or expired memberships for this contact{/ts}'{literal}
              + '</a></li></ul>',
            '{/literal}{ts escape='js'}Duplicate Membership?{/ts}{literal}', 'alert');
        }
      }
      checkExistingMemOrg();
      {/literal}
      {/if}

      {literal}

    });
    {/literal}

    {if $membershipMode or $action eq 2}
      buildAutoRenew( null, null, '{$membershipMode}');
    {/if}
    {literal}
    function buildAutoRenew(membershipTypeID, processorId, mode ) {
      var action = {/literal}'{$action}'{literal};

      //for update lets hide it when not already recurring.
      if (action == 2) {
        //user can't cancel auto renew by unchecking.
        if (cj("#auto_renew").prop('checked')) {
          cj("#auto_renew").attr('readonly', true);
        }
        else {
          cj("#autoRenew").hide( );
        }
      }

      //we should do all auto renew for cc memberships.
      if (!mode) {
        return;
      }

      //get the required values in case missing.
      if (!processorId) {
        processorId = cj( '#payment_processor_id' ).val( );
      }
      if (!membershipTypeID) {
        membershipTypeID = parseInt( cj('#membership_type_id_1').val( ) );
      }

      //we don't have both required values.
      if (!processorId || !membershipTypeID) {
        cj("#auto_renew").prop('checked', false);
        cj("#autoRenew").hide();
        showEmailOptions();
        return;
      }

      var recurProcessors  = {/literal}{$recurProcessor}{literal};
      var autoRenewOptions = {/literal}{$autoRenewOptions}{literal};
      var currentOption    = autoRenewOptions[membershipTypeID];

      if (!currentOption || !recurProcessors[processorId]) {
        cj("#auto_renew").prop('checked', false );
        cj("#autoRenew").hide();
        return;
      }

      if (currentOption == 1) {
        cj("#autoRenew").show();
        if (cj("#auto_renew").attr('readonly')) {
          cj("#auto_renew").prop('checked', false).removeAttr('readonly');
        }
      }
      else if ( currentOption == 2 ) {
        cj("#autoRenew").show();
        cj("#auto_renew").prop('checked', true).attr('readonly', true);
      }
      else {
        cj("#auto_renew").prop('checked', false);
        cj("#autoRenew").hide( );
      }
      showEmailOptions();
    }

    var customDataType = 'Membership';

    // load form during form rule.
    {/literal}{if $buildPriceSet}{literal}
      cj("#totalAmountORPriceSet, #mem_type_id, #num_terms_row, .crm-membership-form-block-financial_type_id-mode").hide();
      cj('#total_amount').attr("readonly", true);
    {/literal}{/if}{literal}

    function buildAmount( priceSetId ) {
      if (!priceSetId) {
        priceSetId = cj("#price_set_id").val();
      }

        if ( !priceSetId ) {
        cj('#membership_type_id_1').val(0);
        CRM.buildCustomData('Membership', null);

        // hide price set fields.
        cj('#priceset').empty();

        // show/hide price set amount and total amount.
        cj( "#mem_type_id").show( );

        var choose = "{/literal}{ts escape='js'}Choose price set{/ts}{literal}";
        cj("#price_set_id option[value='']").html( choose );
        cj( "#totalAmountORPriceSet" ).show( );
        cj('#total_amount').removeAttr("readonly");
        cj( "#num_terms_row").show( );
        cj(".crm-membership-form-block-financial_type_id-mode").show();

        {/literal}{if $allowAutoRenew}{literal}
          cj('#autoRenew').hide();
          cj("#auto_renew").removeAttr('readOnly').prop('checked', false );
        {/literal}{/if}{literal}
        return;
      }

      cj( "#total_amount" ).val('').attr("readonly", true);

      var dataUrl = {/literal}"{crmURL h=0 q='snippet=4'}"{literal} + '&priceSetId=' + priceSetId;

      var response = cj.ajax({
        url: dataUrl,
        async: false
      }).responseText;

      cj('#priceset').show( ).html( response );
      // freeze total amount text field.

      cj( "#totalAmountORPriceSet" ).hide( );
      cj( "#mem_type_id" ).hide( );
      var manual = "{/literal}{ts escape='js'}Manual membership and price{/ts}{literal}";
      cj("#price_set_id option[value='']").html( manual );
      cj( "#num_terms_row" ).hide( );
      cj(".crm-membership-form-block-financial_type_id-mode").hide();
    }

    buildMaxRelated(cj('#membership_type_id_1', false).val());

    function buildMaxRelated( memType, setDefault ) {
      var allMemberships = {/literal}{$allMembershipInfo}{literal};

      if ((memType > 0) && (allMemberships[memType]['has_related'])) {
        if (setDefault) cj('#max_related').val(allMemberships[memType]['max_related']);
        cj('#maxRelated').show();
        var cid = {/literal}{if $contactID}{$contactID}{else}null{/if}{literal};
        if (cid) {
          CRM.api('relationship', 'getcount', {contact_id: cid, membership_type_id: memType}, {
            success: function(result) {
              var relatable;
              if (result.result === 0) {
                relatable = '{/literal}{ts escape='js'}No contacts are currently eligible to inherit this relationship.{/ts}{literal}';
              }
              else if (result.result === 1) {
                relatable = '{/literal}{ts escape='js'}One contact is currently eligible to inherit this relationship.{/ts}{literal}';
              }
              else {
                relatable = '{/literal}{ts escape='js' 1='%1'}%1 contacts are currently eligible to inherit this relationship.{/ts}{literal}';
                relatable = ts(relatable, {1: result});
              }
              cj('#eligibleRelated').text(relatable);
            }
          });
        }
      } else {
        cj('#max_related').val('');
        cj('#maxRelated').hide();
      }
    }

    var lastMembershipTypes = [];
    var optionsMembershipTypes = [];

    // function to load custom data for selected membership types through priceset
    function processMembershipPriceset( membershipValues, autoRenewOption, reload ) {
      var currentMembershipType = [];
      var count = 0;
      var loadCustomData = 0;
      if ( membershipValues ) {
        optionsMembershipTypes = membershipValues;
      }

      if ( reload ) {
        lastMembershipTypes = [];
        {/literal}{if $allowAutoRenew}{literal}
        cj('#autoRenew').hide();
        var autoRenew = cj("#auto_renew");
        autoRenew.removeAttr('readOnly').prop('checked', false );
        if (autoRenewOption == 1) {
          cj('#autoRenew').show();
        }
        else if (autoRenewOption == 2) {
          autoRenew.attr('readOnly', true).prop('checked',  true );
          cj('#autoRenew').show();
        }
        {/literal}{/if}{literal}
      }

      cj("input,#priceset select,#priceset").each(function () {
        if ( cj(this).attr('price') ) {
          switch( cj(this).attr('type') ) {
            case 'checkbox':
              if ( cj(this).prop('checked') ) {
                eval( 'var option = ' + cj(this).attr('price') ) ;
                var ele = option[0];
                var memTypeId = optionsMembershipTypes[ele];
                if ( memTypeId && cj.inArray(optionsMembershipTypes[ele], currentMembershipType) == -1 ) {
                  currentMembershipType[count] = memTypeId;
                  count++;
                }
              }
              if ( reload ) {
                cj(this).click( function( ) {
                  processMembershipPriceset();
                });
              }
              break;

            case 'radio':
              if ( cj(this).prop('checked') && cj(this).val() ) {
                var memTypeId = optionsMembershipTypes[cj(this).val()];
                if ( memTypeId && cj.inArray(memTypeId, currentMembershipType) == -1 ) {
                  currentMembershipType[count] = memTypeId;
                  count++;
                }
              }
              if ( reload ) {
                cj(this).click( function( ) {
                  processMembershipPriceset();
                });
              }
              break;

            case 'select-one':
              if ( cj(this).val( ) ) {
                var memTypeId = optionsMembershipTypes[cj(this).val()];
                if ( memTypeId && cj.inArray(memTypeId, currentMembershipType) == -1 ) {
                  currentMembershipType[count] = memTypeId;
                  count++;
                }
              }
              if ( reload ) {
                cj(this).change( function( ) {
                  processMembershipPriceset();
                });
              }
              break;
          }
        }
      });

      for( i in currentMembershipType ) {
        if ( cj.inArray(currentMembershipType[i], lastMembershipTypes) == -1 ) {
          loadCustomData = 1;
          break;
        }
      }

      if ( !loadCustomData ) {
        for( i in lastMembershipTypes) {
          if ( cj.inArray(lastMembershipTypes[i], currentMembershipType) == -1 ) {
            loadCustomData = 1;
            break;
          }
        }
      }

      lastMembershipTypes = currentMembershipType;

      // load custom data only if change in membership type selection
      if ( !loadCustomData ) {
        return;
      }

      subTypeNames = currentMembershipType.join(',');
      if ( subTypeNames.length < 1 ) {
        subTypeNames = null;
      }

      CRM.buildCustomData('Membership', subTypeNames);
    }

  function enableAmountSection( setContributionType ) {
    if ( !cj('#record_contribution').prop('checked') ) {
      cj('#record_contribution').click( );
      cj('#recordContribution').show( );
    }
    if ( setContributionType ) {
    cj('#financial_type_id').val(setContributionType);
    }
  }
  </script>
  {/literal}
  {/if} {* closing of delete check if *}
{/if}{* closing of custom data if *}
