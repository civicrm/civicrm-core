{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.2                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2012                                |
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
{if $ppTypeConfirm}
  {include file="CRM/Core/BillingBlock.tpl"}

<div id="paypalExpress">
{* Put PayPal Express button after customPost block since it's the submit button in this case. *}
{if $paymentProcessor.payment_processor_type EQ 'PayPal_Express'}
    {assign var=expressButtonName value='_qf_Register_upload_express'}
    <fieldset class="crm-group payPalExpress-group"><legend>{ts}Checkout with PayPal{/ts}</legend>
    <div class="description">{ts}Click the PayPal button to continue.{/ts}</div>
    <div>{$form.$expressButtonName.html} <span style="font-size:11px; font-family: Arial, Verdana;">Checkout securely.  Pay without sharing your financial information. </span>
    </div>
    </fieldset>
{/if}
</div>
{else}
{if $action & 1024}
    {include file="CRM/Event/Form/Registration/PreviewHeader.tpl"}
{/if}

{include file="CRM/common/TrackingFields.tpl"}

<div class="crm-block crm-event-confirm-form-block">
    {if $isOnWaitlist}
        <div class="help">
            {ts}Please verify the information below. <span class="bold">Then click 'Continue' to be added to the WAIT LIST for this event</span>. If space becomes available you will receive an email with a link to a web page where you can complete your registration.{/ts}
        </div>
    {elseif $isRequireApproval}
        <div class="help">
            {ts}Please verify the information below. Then click 'Continue' to submit your registration. <span class="bold">Once approved, you will receive an email with a link to a web page where you can complete the registration process.</span>{/ts}
        </div>
    {else}
        <div id="help">
        {ts}Please verify the information below. Click the <strong>Go Back</strong> button below if you need to make changes.{/ts}
        {if $contributeMode EQ 'notify' and !$is_pay_later and ! $isAmountzero }
            {if $paymentProcessor.payment_processor_type EQ 'Google_Checkout'}
                {ts 1=$paymentProcessor.processorName}Click the <strong>%1</strong> button to checkout to Google, where you will select your payment method and complete the registration.{/ts}
            {else} 	
                {ts 1=$paymentProcessor.processorName}Click the <strong>Continue</strong> button to checkout to %1, where you will select your payment method and complete the registration.{/ts}
            {/if }
        {else}
            {ts}Otherwise, click the <strong>Continue</strong> button below to complete your registration.{/ts}
        {/if}
        </div>
        {if $is_pay_later and !$isAmountzero}
            <div class="bold">{$pay_later_receipt}</div>
        {/if}
    {/if}
    
    <div id="crm-submit-buttons" class="crm-submit-buttons">
	    {include file="CRM/common/formButtons.tpl" location="top"}
    </div>

    {if $event.confirm_text}
        <div id="intro_text" class="crm-section event_confirm_text-section">
	        <p>{$event.confirm_text}</p>
        </div>
    {/if}
    
    <div class="crm-group event_info-group">
        <div class="header-dark">
            {ts}Event Information{/ts}
        </div>
        <div class="display-block">
            {include file="CRM/Event/Form/Registration/EventInfoBlock.tpl"}
        </div>
    </div>
    
    {if $pcpBlock}
    <div class="crm-group pcp_display-group">
        <div class="header-dark">
           {ts}Contribution Honor Roll{/ts}
        </div>
        <div class="display-block">
            {if $pcp_display_in_roll}
                {ts}List my contribution{/ts}
                {if $pcp_is_anonymous}
                    <strong>{ts}anonymously{/ts}.</strong>
                {else}
            {ts}under the name{/ts}: <strong>{$pcp_roll_nickname}</strong><br/>
                    {if $pcp_personal_note}
                        {ts}With the personal note{/ts}: <strong>{$pcp_personal_note}</strong>
                    {else}
                     <strong>{ts}With no personal note{/ts}</strong>
                     {/if}
                {/if}
            {else}
                {ts}Don't list my contribution in the honor roll.{/ts}
            {/if}
            <br />
        </div>
    </div>
    {/if}
    
    {if $paidEvent} 
    
        <div class="crm-group event_fees-group">
            <div class="header-dark">
                {$event.fee_label}
            </div>
            {if $lineItem}
                {include file="CRM/Price/Page/LineItem.tpl" context="Event"}
            {elseif $amounts || $amount == 0}
			    <div class="crm-section no-label amount-item-section">
                    {foreach from= $amounts item=amount key=level}  
    					<div class="content">
    					    {$amount.amount|crmMoney}&nbsp;&nbsp;{$amount.label}
    					</div>
            			<div class="clear"></div>
                    {/foreach}
    		    </div>	
                {if $totalAmount}
        			<div class="crm-section no-label total-amount-section">
                		<div class="content bold">{ts}Total Amount{/ts}:&nbsp;&nbsp;{$totalAmount|crmMoney}</div>
                		<div class="clear"></div>
                	</div>
                {/if}	 		
                {if $hookDiscount.message}
                    <div class="crm-section hookDiscount-section">
                        <em>({$hookDiscount.message})</em>
                    </div>
                {/if}
            {/if}
        </div>
    {/if}
	
    {if $event.participant_role neq 'Attendee' and $defaultRole}
        <div class="crm-group participant_role-group">
            <div class="header-dark">
                {ts}Participant Role{/ts}
            </div>
            <div class="crm-section no-label participant_role-section">
                <div class="content">
                    {$event.participant_role}
                </div>
            	<div class="clear"></div>
            </div>
        </div>
    {/if}


    {if $customPre}
            <fieldset class="label-left">
                {include file="CRM/UF/Form/Block.tpl" fields=$customPre}
            </fieldset>
    {/if}
    
    {if $customPost}
            <fieldset class="label-left">  
                {include file="CRM/UF/Form/Block.tpl" fields=$customPost}
            </fieldset>
    {/if}

    {*display Additional Participant Profile Information*}
    {if $addParticipantProfile}
        {foreach from=$addParticipantProfile item=participant key=participantNo}
            <div class="crm-group participant_info-group">
                <div class="header-dark">
                    {ts 1=$participantNo+1}Participant %1{/ts}	
                </div>
                {if $participant.additionalCustomPre}
                    <fieldset class="label-left no-border"><div class="bold crm-additional-profile-view-title">{$participant.additionalCustomPreGroupTitle}</div>
                        {foreach from=$participant.additionalCustomPre item=value key=field}
                            <div class="crm-section {$field}-section">
                                <div class="label">{$field}</div>
                                <div class="content">{$value}</div>
                                <div class="clear"></div>
                            </div>
                        {/foreach}
                    </fieldset>
                {/if}

                {if $participant.additionalCustomPost}
		            {foreach from=$participant.additionalCustomPost item=value key=field}
                        <fieldset class="label-left no-border"><div class="bold crm-additional-profile-view-title">{$participant.additionalCustomPostGroupTitle.$field.groupTitle}</div>
                        {foreach from=$participant.additionalCustomPost.$field item=value key=field}
                            <div class="crm-section {$field}-section">
                                <div class="label">{$field}</div>
                                <div class="content">{$value}</div>
                                <div class="clear"></div>
                            </div>
                        {/foreach}		 
                        </fieldset>
		            {/foreach}
                {/if}
            </div>
        <div class="spacer"></div>
        {/foreach}
    {/if}
{*Changed by BOT *}
 {if $showPaymentFields}
 
    <div class="header-dark">
      {ts}Billing Name and Address{/ts}
   </div> 
        <table class="form-layout-compressed">
            <tr class="crm-contribution-form-block-payer_id">
                <td class="label">{$form.payer_id.label}</td><td>{$form.payer_id.html}
            </tr>
<tr class="crm-contribution-form-block-receipt-email">
                <td class="label">{$form.receipt_email.label}</td><td>{$form.receipt_email.html}
            </tr>
        </table>

       <div class="crm-section payment_processor-section">

      <div class="label">{$form.payment_processor.label}</div>
      <div class="content">{$form.payment_processor.html}</div>
      <div class="clear"></div>
 </div>
    <div id="billing-payment-block"></div>

    {include file="CRM/common/paymentBlock.tpl'}

    </div>
{/if}

   

    <div id="crm-submit-buttons" class="crm-submit-buttons">
	    {include file="CRM/common/formButtons.tpl" location="bottom"}
    </div>

    {if $event.confirm_footer_text}
        <div id="footer_text" class="crm-section event_confirm_footer-section">
            <p>{$event.confirm_footer_text}</p>
        </div>
    {/if}
</div>
{include file="CRM/common/showHide.tpl"}
{/if}
<script type="text/javascript">
{literal}
function toggleConfirmButton() {
  var payPalExpressId = '';
  {/literal}{if $payPalExpressId}{literal}
    var payPalExpressId = {/literal}{$payPalExpressId}{literal};
  {/literal}{/if}{literal}
  var elementObj = cj('input[name="payment_processor"]');
   if ( elementObj.attr('type') == 'hidden' ) {
      var processorTypeId = elementObj.val( );
   } else {
      var processorTypeId = elementObj.filter(':checked').val();
   }

   if (payPalExpressId !=0 && payPalExpressId == processorTypeId) {
      hide("crm-submit-buttons");
   } else { 
      show("crm-submit-buttons");
   } 
}

cj('input[name="payment_processor"]').change( function() {
 toggleConfirmButton();
});

cj(function() {
  toggleConfirmButton();
cj(".crm-contribution-form-block-receipt-email").hide();
	if ( cj("#payer_id").val() == 'payer_not_attending') {
		cj(".crm-contribution-form-block-receipt-email").show();
	} else {
	
		cj(".crm-contribution-form-block-receipt-email").hide();
	}
	cj("#payer_id").change( function() {
		if ( cj("#payer_id").val() == 'payer_not_attending') {
			cj(".crm-contribution-form-block-receipt-email").show();
		} else {
			cj(".crm-contribution-form-block-receipt-email").hide();
		}
	});
});
{/literal} 
</script>
{literal} 
<script type="text/javascript">
    

    {/literal}{if ($form.is_pay_later or $bypassPayment) and $paymentProcessor.payment_processor_type EQ 'PayPal_Express'}
    {literal} 
       showHidePayPalExpressOption( );
    {/literal}{/if}{literal}

    function showHidePayPalExpressOption( )
    {
    var payLaterElement = {/literal}{if $form.is_pay_later}true{else}false{/if}{literal};
    if ( ( cj("#bypass_payment").val( ) == 1 ) ||
         ( payLaterElement && document.getElementsByName('is_pay_later')[0].checked ) ) {
        show("crm-submit-buttons");
        hide("paypalExpress");
    } else {
        show("paypalExpress");
        hide("crm-submit-buttons");
    }
    }
    
    
</script>
{/literal} 