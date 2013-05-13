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
{* this template is used for adding/editing/deleting memberships for a contact  *}
{if $cancelAutoRenew}
  <div class="messages status no-popup">
    <div class="icon inform-icon"></div>
    <p>{ts 1=$cancelAutoRenew}This membership is set to renew automatically {if $endDate}on {$endDate|crmDate}{/if}. You will need to cancel the auto-renew option if you want to modify the Membership Type, End Date or Membership Status. <a href="%1">Click here</a> if you want to cancel the automatic renewal option.{/ts}</p>
  </div>
{/if}
<div class="spacer"></div>
{if $cdType }
  {include file="CRM/Custom/Form/CustomData.tpl"}
{elseif $priceSetId}
  {include file="CRM/Price/Form/PriceSet.tpl" context="standalone" extends="Membership"}
  {literal}
  <script type="text/javascript">
  cj( function() {
    var membershipValues = new Array;
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
  {if $membershipMode == 'test' }
    {assign var=registerMode value="TEST"}
    {elseif $membershipMode == 'live'}
    {assign var=registerMode value="LIVE"}
  {/if}
  {if !$emailExists and $action neq 8 and $context neq 'standalone'}
  <div class="messages status no-popup">
    <div class="icon inform-icon"></div>
    <p>{ts}You will not be able to send an automatic email receipt for this Membership because there is no email address recorded for this contact. If you want a receipt to be sent when this Membership is recorded, click Cancel and then click Edit from the Summary tab to add an email address before recording the Membership.{/ts}</p>
  </div>
  {/if}
  {if $context NEQ 'standalone'}
  <h3>{if $action eq 1}{ts}New Membership{/ts}{elseif $action eq 2}{ts}Edit Membership{/ts}{else}{ts}Delete Membership{/ts}{/if}</h3>
  {/if}
  {if $membershipMode}
  <div id="help">
    {ts 1=$displayName 2=$registerMode}Use this form to submit Membership Record on behalf of %1. <strong>A %2 transaction will be submitted</strong> using the selected payment processor.{/ts}
  </div>
  {/if}
  <div class="crm-block crm-form-block crm-membership-form-block">
    <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="top"}</div>
    {if $action eq 8}
    <div class="messages status no-popup">
      <div class="icon inform-icon"></div>&nbsp;
      <span class="font-red bold">{ts}WARNING: Deleting this membership will also delete any related payment (contribution) records.{/ts} {ts}This action cannot be undone.{/ts}</span>
      <p>{ts}Consider modifying the membership status instead if you want to maintain an audit trail and avoid losing payment data. You can set the status to Cancelled by editing the membership and clicking the Status Override checkbox.{/ts}</p>
      <p>{ts}Click 'Delete' if you want to continue.{/ts}</p>
    </div>
    {else}
      <table class="form-layout-compressed">
        {if $context neq 'standalone'}
          <tr>
            <td class="font-size12pt label"><strong>{ts}Member{/ts}</strong></td><td class="font-size12pt"><strong>{$displayName}</strong></td>
          </tr>
          {else}
          {if !$membershipMode and !$emailExists and $outBound_option != 2}
            {assign var='profileCreateCallback' value=1 }
          {/if}
        {include file="CRM/Contact/Form/NewContact.tpl"}
        {/if}
        {if $membershipMode}
          <tr><td class="label">{$form.payment_processor_id.label}</td><td>{$form.payment_processor_id.html}</td></tr>
        {/if}
        <tr class="crm-membership-form-block-membership_type_id">
          <td class="label">{$form.membership_type_id.label}</td>
          <td><span id='mem_type_id'>{$form.membership_type_id.html}</span>
            {if $hasPriceSets}
              <span id='totalAmountORPriceSet'> {ts}OR{/ts}</span>
              <span id='selectPriceSet'>{$form.price_set_id.html}</span>
              {if $buildPriceSet && $priceSet}
                <div id="priceset"><br/>{include file="CRM/Price/Form/PriceSet.tpl" extends="Membership"}</div>
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
            <span class="description">{ts}Maximum number of related memberships (leave blank for unlimited).{/ts}</span>
          </td>
        </tr>
        {if $action eq 1}
          <tr id="num_terms_row" class="crm-membership-form-block-num_terms">
            <td class="label">{$form.num_terms.label}</td>
            <td>&nbsp;{$form.num_terms.html}<br />
              <span class="description">{ts}Set the membership end date this many membership periods from now. Make sure the appropriate corresponding fee is entered below.{/ts}</span>
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

        <tr class="crm-membership-form-block-join_date"><td class="label">{$form.join_date.label}</td><td>{include file="CRM/common/jcalendar.tpl" elementName=join_date}
          <br />
          <span class="description">{ts}When did this contact first become a member?{/ts}</span></td></tr>
        <tr class="crm-membership-form-block-start_date"><td class="label">{$form.start_date.label}</td><td>{include file="CRM/common/jcalendar.tpl" elementName=start_date}
          <br />
          <span class="description">{ts}First day of current continuous membership period. Start Date will be automatically set based on Membership Type if you don't select a date.{/ts}</span></td></tr>
        <tr class="crm-membership-form-block-end_date"><td class="label">{$form.end_date.label}</td>
          <td>{if $isRecur && $endDate}{$endDate|crmDate}{else}{include file="CRM/common/jcalendar.tpl" elementName=end_date}{/if}
            <br />
            <span class="description">{ts}Latest membership period expiration date. End Date will be automatically set based on Membership Type if you don't select a date.{/ts}</span></td></tr>
        <tr id="autoRenew" class="crm-membership-form-block-auto_renew">
          <td class="label"> {$form.auto_renew.label} </td>
          <td> {$form.auto_renew.html} {help id="id-auto_renew" file="CRM/Member/Form/Membership.hlp" action=$action} </td>
        </tr>
        {if ! $membershipMode}
          <tr><td class="label">{$form.is_override.label}</td><td>{$form.is_override.html}&nbsp;&nbsp;{help id="id-status-override"}</td></tr>
        {/if}

        {if ! $membershipMode}
          {* Show read-only Status block - when action is UPDATE and is_override is FALSE *}
          <tr id="memberStatus_show">
            {if $action eq 2}
              <td class="label">{$form.status_id.label}</td><td class="view-value">{$membershipStatus}</td>
            {/if}
          </tr>

          {* Show editable status field when is_override is TRUE *}
          <tr id="memberStatus"><td class="label">{$form.status_id.label}</td><td>{$form.status_id.html}<br />
            <span class="description">{ts}If <strong>Status Override</strong> is checked, the selected status will remain in force (it will NOT be modified by the automated status update script).{/ts}</span></td></tr>

          {elseif $membershipMode}
          <tr class="crm-membership-form-block-financial_type_id-mode">
            <td class="label">{$form.financial_type_id.label}</td>
            <td>{$form.financial_type_id.html}<br />
              <span class="description">{ts}Select the appropriate financial type for this payment.{/ts}</span></td>
          </tr>
          <tr class="crm-membership-form-block-total_amount">
            <td class="label">{$form.total_amount.label}</td>
            <td>{$form.total_amount.html}<br />
              <span class="description">{ts}Membership payment amount.{/ts}</span></td>
          </tr>
          {if $context neq 'standalone'}
            <tr class="crm-membership-form-block-contribution-contact">
              <td class="label">{$form.is_different_contribution_contact.label}</td>
              <td>{$form.is_different_contribution_contact.html}&nbsp;&nbsp;{help id="id-contribution_contact"}</td>
            </tr>
            <tr id="record-different-contact">
              <td>&nbsp;</td>
              <td>
                <table class="compressed">
                  <tr class="crm-membership-form-block-honor-type">
                    <td class="label">{$form.honor_type_id.label}</td>
                    <td>{$form.honor_type_id.html}</td>
                  </tr>
                  <tr id ='contributionContact' class="crm-membership-form-block-contribution-type">
                  {include file="CRM/Contact/Form/NewContact.tpl"}
                  </tr>
                </table>
              </td>
            </tr>
          {/if}
          <tr class="crm-membership-form-block-billing">
            <td colspan="2">
            {include file='CRM/Core/BillingBlock.tpl'}
            </td>
          </tr>
        {/if}
        {if $accessContribution and ! $membershipMode AND ($action neq 2 or !$rows.0.contribution_id or $onlinePendingContributionId)}
          <tr id="contri">
            <td class="label">{if $onlinePendingContributionId}{ts}Update Payment Status{/ts}{else}{$form.record_contribution.label}{/if}</td>
            <td>{$form.record_contribution.html}<br />
              <span class="description">{ts}Check this box to enter or update payment information. You will also be able to generate a customized receipt.{/ts}</span></td>
          </tr>
          <tr class="crm-membership-form-block-record_contribution"><td colspan="2">
            <fieldset id="recordContribution"><legend>{ts}Membership Payment and Receipt{/ts}</legend>
              <table>{if $context neq 'standalone'}
                <tr class="crm-membership-form-block-contribution-contact">
                  <td class="label">{$form.is_different_contribution_contact.label}</td>
                  <td>{$form.is_different_contribution_contact.html}&nbsp;&nbsp;{help id="id-contribution_contact"}</td>
                </tr>
                <tr id="record-different-contact">
                  <td>&nbsp;</td>
                  <td>
                    <table class="compressed">
                      <tr class="crm-membership-form-block-honor-type">
                        <td class="label">{$form.honor_type_id.label}</td>
                        <td>{$form.honor_type_id.html}</td>
                      </tr>
                      <tr id ='contributionContact' class="crm-membership-form-block-contribution-type">
                      {include file="CRM/Contact/Form/NewContact.tpl"}
                      </tr>
                    </table>
                  </td>
                </tr>{/if}
                  <tr class="crm-membership-form-block-financial_type_id">
                      <td class="label">{$form.financial_type_id.label}</td>
                      <td>{$form.financial_type_id.html}<br />
                      <span class="description">{ts}Select the appropriate financial type for this payment.{/ts}</span></td>
                </tr>
                <tr class="crm-membership-form-block-total_amount">
                  <td class="label">{$form.total_amount.label}</td>
                  <td>{$form.total_amount.html}<br />
                    <span class="description">{ts}Membership payment amount. A contribution record will be created for this amount.{/ts}</span></td>
                </tr>
                <tr class="crm-membership-form-block-receive_date">
                  <td class="label">{$form.receive_date.label}</td>
                  <td>{include file="CRM/common/jcalendar.tpl" elementName=receive_date}</td>
                </tr>
                <tr class="crm-membership-form-block-payment_instrument_id">
                  <td class="label">{$form.payment_instrument_id.label}</td>
                  <td>{$form.payment_instrument_id.html}</td>
                </tr>
                <tr id="checkNumber" class="crm-membership-form-block-check_number">
                  <td class="label">{$form.check_number.label}</td>
                  <td>{$form.check_number.html|crmAddClass:six}</td>
                </tr>
                {if $action neq 2 }
                  <tr class="crm-membership-form-block-trxn_id">
                    <td class="label">{$form.trxn_id.label}</td>
                    <td>{$form.trxn_id.html}</td>
                  </tr>
                {/if}
                <tr class="crm-membership-form-block-contribution_status_id">
                  <td class="label">{$form.contribution_status_id.label}</td>
                  <td>{$form.contribution_status_id.html}</td>
                </tr>
              </table>
            </fieldset>
          </td></tr>
          {else}
          <div class="spacer"></div>
        {/if}

        {if $emailExists and $outBound_option != 2 }
          <tr id="send-receipt" class="crm-membership-form-block-send_receipt">
            <td class="label">{$form.send_receipt.label}</td><td>{$form.send_receipt.html}<br />
            <span class="description">{ts 1=$emailExists}Automatically email a membership confirmation and receipt to %1?{/ts}</span></td>
          </tr>
          {elseif $context eq 'standalone' and $outBound_option != 2 }
          <tr id="email-receipt" style="display:none;">
            <td class="label">{$form.send_receipt.label}</td><td>{$form.send_receipt.html}<br />
            <span class="description">{ts}Automatically email a membership confirmation and receipt to {/ts}<span id="email-address"></span>?</span></td>
          </tr>
        {/if}
        <tr id="fromEmail" style="display:none;">
          <td class="label">{$form.from_email_address.label}</td>
          <td>{$form.from_email_address.html}</td>
        </tr>
        <tr id='notice' style="display:none;">
          <td class="label">{$form.receipt_text_signup.label}</td>
          <td class="html-adjust"><span class="description">{ts}If you need to include a special message for this member, enter it here. Otherwise, the confirmation email will include the standard receipt message configured under System Message Templates.{/ts}</span>
            {$form.receipt_text_signup.html|crmAddClass:huge}</td>
        </tr>
      </table>
      <div id="customData"></div>
      {*include custom data js file*}
      {include file="CRM/common/customData.tpl"}
      {literal}
      <script type="text/javascript">
      cj(function() {
      {/literal}
        CRM.buildCustomData( '{$customDataType}' );
        {if $customDataSubType}
          CRM.buildCustomData( '{$customDataType}', {$customDataSubType} );
        {/if}
        {literal}
      });
      </script>
      {/literal}
      {if $accessContribution and $action eq 2 and $rows.0.contribution_id}
        <fieldset>
        {include file="CRM/Contribute/Form/Selector.tpl" context="Search"}
        </fieldset>
      {/if}
    {/if}

    <div class="spacer"></div>
    <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
  </div> <!-- end form-block -->

  {if $action neq 8} {* Jscript additions not need for Delete action *}
    {if $accessContribution and !$membershipMode AND ($action neq 2 or !$rows.0.contribution_id or $onlinePendingContributionId)}

    {include file="CRM/common/showHideByFieldValue.tpl"
    trigger_field_id    ="record_contribution"
    trigger_value       =""
    target_element_id   ="recordContribution"
    target_element_type ="table-row"
    field_type          ="radio"
    invert              = 0
    }
    {include file="CRM/common/showHideByFieldValue.tpl"
    trigger_field_id    ="payment_instrument_id"
    trigger_value       = '4'
    target_element_id   ="checkNumber"
    target_element_type ="table-row"
    field_type          ="select"
    invert              = 0
    }
    {/if}

    {literal}
    <script type="text/javascript">
    cj( function( ) {
      var mode   = {/literal}'{$membershipMode}'{literal};
      if ( !mode ) {
        // Offline form (mode = false) has the record_contribution checkbox
        cj('#record_contribution').click( function( ) {
          if ( cj(this).attr('checked') ) {
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
      setPaymentBlock(mode);

      // show/hide different contact section
      setDifferentContactBlock();
      cj('#is_different_contribution_contact').change( function() {
        setDifferentContactBlock();
      });
    });

    function setDifferentContactBlock( ) {
      // show/hide different contact section
      if ( cj('#is_different_contribution_contact').attr('checked') ) {
        cj('#record-different-contact').show();
      }
      else {
        cj('#record-different-contact').hide();
      }
    }
    </script>
    {/literal}

    {if ($emailExists and $outBound_option != 2) OR $context eq 'standalone' }
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
    {literal}

    <script type="text/javascript">

    {/literal}{if !$membershipMode}{literal}
    showHideMemberStatus();
    function showHideMemberStatus() {
      if ( cj( "#is_override" ).attr('checked' ) ) {
        cj('#memberStatus').show( );
        cj('#memberStatus_show').hide( );
      }
      else {
        cj('#memberStatus').hide( );
        cj('#memberStatus_show').show( );
      }
    }
    {/literal}{/if}

    {literal}
    function setPaymentBlock(mode, checkboxEvent) {
      var memType = parseInt(cj('#membership_type_id_1').val( ));
      var isPriceSet = 0;

      if ( cj('#price_set_id').length > 0 && cj('#price_set_id').val() ) {
        isPriceSet = 1;
      }

      if ( !memType || isPriceSet ) {
        return;
      }

      var allMemberships = {/literal}{$allMembershipInfo}{literal};
      if ( !mode ) {
        //check the record_contribution checkbox if membership is a paid one
        {/literal}{if $action eq 1}{literal}
          if (!checkboxEvent) {
            if (allMemberships[memType]['total_amount_numeric'] > 0) {
              cj('#record_contribution').attr('checked','checked');
              cj('#recordContribution').show();
            }
            else {
              cj('#record_contribution').removeAttr('checked');
              cj('#recordContribution').hide();
            }
          }
        {/literal}{/if}{literal}
      }

      // skip this for test and live modes because financial type is set automatically
      cj("#financial_type_id").val(allMemberships[memType]['financial_type_id']);
      var term = cj('#num_terms').val();
      if ( term ) {
        var feeTotal = allMemberships[memType]['total_amount_numeric'] * term;
        cj("#total_amount").val( feeTotal.toFixed(2) );
      }
      else {
        cj("#total_amount").val( allMemberships[memType]['total_amount'] );
      }
    }

    {/literal}
    {if $context eq 'standalone' and $outBound_option != 2 }
    {literal}
    cj( function( ) {
      cj("#contact_1").blur( function( ) {
        checkEmail( );
      } );
      checkEmail( );
    });

    function checkEmail( ) {
      var contactID = cj("input[name='contact_select_id[1]']").val();
      if ( contactID ) {
        var postUrl = "{/literal}{crmURL p='civicrm/ajax/checkemail' h=0}{literal}";
        cj.post( postUrl, {contact_id: contactID},
          function ( response ) {
            if ( response ) {
              cj("#email-receipt").show( );
              if ( cj("#send_receipt").is(':checked') ) {
                cj("#notice").show( );
              }
              cj("#email-address").html( response );
            }
            else {
              cj("#email-receipt").hide( );
              cj("#notice").hide( );
            }
          }
        );
	}
	else {
        cj("#email-receipt").hide( );
        cj("#notice").hide( );
      }
    }

    function profileCreateCallback( blockNo ) {
      checkEmail( );
    }
    {/literal}
    {/if}

    {literal}
    //keep read only always checked.
    cj( function( ) {
      var allowAutoRenew   = {/literal}'{$allowAutoRenew}'{literal};
      var alreadyAutoRenew = {/literal}'{$alreadyAutoRenew}'{literal};
      if ( allowAutoRenew || alreadyAutoRenew ) {
        cj( "#auto_renew" ).click(function( ) {
          if ( cj(this).attr( 'readonly' ) ) {
            cj(this).attr( 'checked', true );
          }
        });
      }
    });
    {/literal}

    {if $membershipMode or $action eq 2}
    {literal}

    buildAutoRenew( null, null );

    function buildAutoRenew( membershipType, processorId ) {
      var mode   = {/literal}'{$membershipMode}'{literal};
      var action = {/literal}'{$action}'{literal};

      //for update lets hide it when not already recurring.
      if ( action == 2 ) {
        //user can't cancel auto renew by unchecking.
        if ( cj("#auto_renew").attr( 'checked' ) ) {
          cj("#auto_renew").attr( 'readonly', true );
        }
        else {
          cj("#autoRenew").hide( );
        }
      }

      //we should do all auto renew for cc memberships.
      if ( !mode ) return;

      //get the required values in case missing.
      if ( !processorId )  processorId = cj( '#payment_processor_id' ).val( );
      if ( !membershipType ) membershipType = parseInt( cj('#membership_type_id_1').val( ) );

      //we don't have both required values.
      if ( !processorId || !membershipType ) {
        cj("#auto_renew").attr( 'checked', false );
        cj("#autoRenew").hide( );
        return;
      }

      var recurProcessors  = {/literal}{$recurProcessor}{literal};
      var autoRenewOptions = {/literal}{$autoRenewOptions}{literal};
      var currentOption    = autoRenewOptions[membershipType];

      if ( !currentOption || !recurProcessors[processorId] ) {
        cj("#auto_renew").attr( 'checked', false );
        cj("#autoRenew").hide( );
        return;
      }

      if ( currentOption == 1 ) {
        cj("#autoRenew").show( );
        if ( cj("#auto_renew").attr( 'readonly' ) ) {
          cj("#auto_renew").attr('checked', false );
          cj("#auto_renew").removeAttr( 'readonly' );
        }
      }
      else if ( currentOption == 2 ) {
        cj("#autoRenew").show( );
        cj("#auto_renew").attr( 'checked', true );
        cj("#auto_renew").attr( 'readonly', true );
      }
      else {
        cj("#auto_renew").attr( 'checked', false );
        cj("#autoRenew").hide( );
      }

      //play w/ receipt option.
      if ( cj("#auto_renew").attr( 'checked' ) ) {
        cj("#notice").hide( );
        cj("#send_receipt").attr( 'checked', false );
        cj("#send-receipt").hide( );
      }
      else {
        cj("#send-receipt").show( );
        if ( cj("#send_receipt").attr( 'checked' ) ) {
          cj("#notice").show( );
        }
      }
    }
    {/literal}
    {/if}

    {literal}
    function buildReceiptANDNotice( ) {
      if ( cj("#auto_renew").attr( 'checked' ) ) {
        cj("#notice").hide( );
        cj("#send-receipt").hide( );
      }
      else {
        cj("#send-receipt").show( );
        if ( cj("#send_receipt").attr( 'checked' ) ) {
          cj("#notice").show( );
        }
      }
    }

    var customDataType = '{/literal}{$customDataType}{literal}';

    // load form during form rule.
    {/literal}{if $buildPriceSet}{literal}
    cj( "#totalAmountORPriceSet" ).hide( );
    cj( "#mem_type_id" ).hide( );
    cj('#total_amount').attr("readonly", true);
    cj( "#num_terms_row" ).hide( );
    cj(".crm-membership-form-block-financial_type_id-mode").hide();
    {/literal}{/if}{literal}

    function buildAmount( priceSetId ) {
  if ( !priceSetId ) {
	  priceSetId = cj("#price_set_id").val( );
  }
        var fname = '#priceset';
        if ( !priceSetId ) {
        cj('#membership_type_id_1').val(0);
        CRM.buildCustomData(customDataType, 'null' );

        // hide price set fields.
        cj( fname ).hide( );

        // show/hide price set amount and total amount.
        cj( "#mem_type_id").show( );
        var choose = "{/literal}{ts}Choose price set{/ts}{literal}";
        cj("#price_set_id option[value='']").html( choose );
        cj( "#totalAmountORPriceSet" ).show( );
        cj('#total_amount').removeAttr("readonly");
        cj( "#num_terms_row").show( );
        cj(".crm-membership-form-block-financial_type_id-mode").show();

        {/literal}{if $allowAutoRenew}{literal}
        cj('#autoRenew').hide();
        var autoRenew = cj("#auto_renew");
        autoRenew.removeAttr( 'readOnly' );
        autoRenew.removeAttr( 'checked' );
        {/literal}{/if}{literal}
        return;
      }

      cj( "#total_amount" ).val( '' );
      cj('#total_amount').attr("readonly", true);

      var dataUrl = {/literal}"{crmURL h=0 q='snippet=4'}"{literal} + '&priceSetId=' + priceSetId;

      var response = cj.ajax({
        url: dataUrl,
        async: false
      }).responseText;

      cj( fname ).show( ).html( response );
      // freeze total amount text field.

      cj( "#totalAmountORPriceSet" ).hide( );
      cj( "#mem_type_id" ).hide( );
      var manual = "{/literal}{ts}Manual membership and price{/ts}{literal}";
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
      } else {
        cj('#max_related').val('');
        cj('#maxRelated').hide();
      }
    }

    var lastMembershipTypes = new Array;
    var optionsMembershipTypes = new Array;

    // function to load custom data for selected membership types through priceset
    function processMembershipPriceset( membershipValues, autoRenewOption, reload ) {
      var currentMembershipType = new Array;
      var count = 0;
      var loadCustomData = 0;
      if ( membershipValues ) {
        optionsMembershipTypes = membershipValues;
      }

      if ( reload ) {
        lastMembershipTypes = new Array;
        {/literal}{if $allowAutoRenew}{literal}
        cj('#autoRenew').hide();
        var autoRenew = cj("#auto_renew");
        autoRenew.removeAttr( 'readOnly' );
        autoRenew.removeAttr( 'checked' );
        if ( autoRenewOption == 1 ) {
          cj('#autoRenew').show();
        }
        else if ( autoRenewOption == 2 ) {
          autoRenew.attr( 'readOnly', true );
          autoRenew.attr( 'checked',  true );
          cj('#autoRenew').show();
        }
        {/literal}{/if}{literal}
      }

      cj("input,#priceset select,#priceset").each(function () {
        if ( cj(this).attr('price') ) {
          switch( cj(this).attr('type') ) {
            case 'checkbox':
              if ( cj(this).attr('checked') ) {
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
              if ( cj(this).attr('checked') && cj(this).val() ) {
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
        subTypeNames = 'null';
      }

      CRM.buildCustomData( customDataType, subTypeNames );
    }

  function enableAmountSection( setContributionType ) {
    if ( !cj('#record_contribution').attr('checked') ) {
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
