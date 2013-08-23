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
{if $ppType}
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

{capture assign='reqMark'}<span class="marker"  title="{ts}This field is required.{/ts}">*</span>{/capture}
<div class="crm-block crm-event-register-form-block">

{* moved to tpl since need to show only for primary participant page *}
{if $requireApprovalMsg || $waitlistMsg}
  <div id = "id-waitlist-approval-msg" class="messages status">
    	{if $requireApprovalMsg}
	    <div id="id-req-approval-msg">{$requireApprovalMsg}</div>
	{/if}
        {if $waitlistMsg}
	    <div id="id-waitlist-msg">{$waitlistMsg}</div>
	{/if} 
  </div>
{/if}

{if $contact_id}
<div class="messages status">
    {ts 1=$display_name}Welcome %1{/ts}. (<a href="{crmURL p='civicrm/event/register' q="cid=0&reset=1&id=`$event.id`"}" title="{ts}Click here to register a different person for this event.{/ts}">{ts 1=$display_name}Not %1, or want to register a different person{/ts}</a>?)</div>
{/if}
{if $event.intro_text}
    <div id="intro_text" class="crm-section intro_text-section">
        <p>{$event.intro_text}</p>
    </div>
{/if}

{if $pcpSupporterText}
    <div class="crm-section pcpSupporterText-section">
        <div class="content">{$pcpSupporterText}</div>
    </div>
{/if}

{if $form.additional_participants.html}
    <div class="crm-section additional_participants-section" id="noOfparticipants">
        <div class="label">{$form.additional_participants.label}</div>
        <div class="content">
            {$form.additional_participants.html} &nbsp; ({ts}including yourself{/ts})<br />
            <span class="description">{ts}Fill in your registration information on this page. If you are registering additional people, you will be able to enter their registration information after you complete this page and click &quot;Continue&quot;.{/ts}</span>
        </div>
        <div class="clear"></div>
    </div>
{/if}

{if $priceSet}
    {if ! $quickConfig}<fieldset id="priceset" class="crm-group priceset-group"><legend>{$event.fee_label}</legend>{/if}
      {include file="CRM/Price/Form/PriceSet.tpl" extends="Event"}
			{include file="CRM/Price/Form/ParticipantCount.tpl"}
    {if ! $quickConfig}</fieldset>{/if}

    {if $form.is_pay_later}
        <div class="crm-section pay_later-section">
	        <div class="label">&nbsp;</div>
            <div class="content">{$form.is_pay_later.html}&nbsp;{$form.is_pay_later.label}</div>
            <div class="clear"></div>
        </div>
    {/if}
{/if}

{if $pcp && $is_honor_roll }
    <fieldset class="crm-group pcp-group">
        <div class="crm-section pcp-section">
            <div class="crm-section display_in_roll-section">
                <div class="content">
                    {$form.pcp_display_in_roll.html} &nbsp;
                    {$form.pcp_display_in_roll.label}
                </div>
                <div class="clear"></div>
            </div>
            <div id="nameID" class="crm-section is_anonymous-section">
                <div class="content">
                    {$form.pcp_is_anonymous.html}
                </div>
                <div class="clear"></div>
            </div>
            <div id="nickID" class="crm-section pcp_roll_nickname-section">
                <div class="label">{$form.pcp_roll_nickname.label}</div>
                <div class="content">{$form.pcp_roll_nickname.html}
                <div class="description">{ts}Enter the name you want listed with this contribution. You can use a nick name like 'The Jones Family' or 'Sarah and Sam'.{/ts}</div>
                </div>
                <div class="clear"></div>
            </div>
            <div id="personalNoteID" class="crm-section pcp_personal_note-section">
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

{* User account registration option. Displays if enabled for one of the profiles on this page. *}
{include file="CRM/common/CMSUser.tpl"}

{include file="CRM/UF/Form/Block.tpl" fields=$customPre} 
 <div class="crm-section payment_processor-section">
      <div class="label">{$form.payment_processor.label}</div>
      <div class="content">{$form.payment_processor.html}</div>
      <div class="clear"></div>
 </div>

 <div id="billing-payment-block"></div>
 {include file="CRM/common/paymentBlock.tpl'}


{include file="CRM/UF/Form/Block.tpl" fields=$customPost}   

{if $isCaptcha}
    {include file='CRM/common/ReCAPTCHA.tpl'}
{/if}

<div id="crm-submit-buttons" class="crm-submit-buttons">
    {include file="CRM/common/formButtons.tpl" location="bottom"}
</div>

{if $event.footer_text}
    <div id="footer_text" class="crm-section event_footer_text-section">
        <p>{$event.footer_text}</p>
    </div>
{/if}
</div>
 
<script type="text/javascript">
{literal}
function toggleConfirmButton() {
  var payPalExpressId = {/literal}{$payPalExpressId}{literal};
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
});
{/literal} 
</script>
{/if}
{literal} 
<script type="text/javascript">
    {/literal}{if $pcp && $is_honor_roll }pcpAnonymous();{/if}{literal}

    function allowParticipant( ) { 		
	{/literal}{if $allowGroupOnWaitlist}{literal}
	    var additionalParticipants = cj('#additional_participants').val();
	    var pricesetParticipantCount = 0;
	    {/literal}{if $priceSet}{literal}
	      pricesetParticipantCount = pPartiCount;
	    {/literal}{/if}{literal}
	
	    allowGroupOnWaitlist( additionalParticipants, pricesetParticipantCount );
	{/literal}{/if}{literal}
    }

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

    {/literal}{if ($form.is_pay_later or $bypassPayment) and $showHidePaymentInformation}{literal} 
       showHidePaymentInfo( );
    {/literal} {/if}{literal}

    function showHidePaymentInfo( )
    {	
	var payLater = {/literal}{if $form.is_pay_later}true{else}false{/if}{literal};

	if ( ( cj("#bypass_payment").val( ) == 1 ) ||
	     ( payLater && document.getElementsByName('is_pay_later')[0].checked ) ) {
	     hide( 'billing-payment-block' );		
	} else {
             show( 'billing-payment-block' );
	}
    }
    
    {/literal}{if $allowGroupOnWaitlist}{literal}
       allowGroupOnWaitlist( 0, 0 );
    {/literal}{/if}{literal}
    
    function allowGroupOnWaitlist( additionalParticipants, pricesetParticipantCount )
    {	
      {/literal}{if $isAdditionalParticipants}{literal}
      if ( !additionalParticipants ) {
      	additionalParticipants = cj('#additional_participants').val();
      }
      {/literal}{else}{literal}
        additionalParticipants = 0;
      {/literal}{/if}{literal}

      additionalParticipants = parseInt( additionalParticipants );
      if ( ! additionalParticipants ) {
      	 additionalParticipants = 0;
      }     

      var availableRegistrations = {/literal}'{$availableRegistrations}'{literal};
      var totalParticipants = parseInt( additionalParticipants ) + 1;
      
      if ( pricesetParticipantCount ) {
      	// add priceset count if any 
      	totalParticipants += parseInt(pricesetParticipantCount) - 1;
      }
      var isrequireApproval = {/literal}'{$requireApprovalMsg}'{literal};
 
      if ( totalParticipants > availableRegistrations ) {
         cj( "#id-waitlist-msg" ).show( );
         cj( "#id-waitlist-approval-msg" ).show( );

         //set the value for hidden bypass payment. 
         cj( "#bypass_payment").val( 1 );

         //hide pay later.
         {/literal}{if $form.is_pay_later}{literal} 
	    cj("#is-pay-later").hide( );
         {/literal} {/if}{literal}
 
      }	else {
         if ( isrequireApproval ) {
            cj( "#id-waitlist-approval-msg" ).show( );
            cj( "#id-waitlist-msg" ).hide( );
         } else {
            cj( "#id-waitlist-approval-msg" ).hide( );
         }
         //reset value since user don't want or not eligible for waitlist 
         cj( "#bypass_payment").val( 0 );

         //need to show paylater if exists.
         {/literal}{if $form.is_pay_later}{literal} 
	    cj("#is-pay-later").show( );
         {/literal} {/if}{literal}
      }

      //now call showhide payment info.
      {/literal}
      {if ($form.is_pay_later or $bypassPayment) and $paymentProcessor.payment_processor_type EQ 'PayPal_Express'}{literal} 
         showHidePayPalExpressOption( );
      {/literal}{/if}
      {literal}
  
      {/literal}{if ($form.is_pay_later or $bypassPayment) and $showHidePaymentInformation}{literal} 
         showHidePaymentInfo( );
      {/literal}{/if}{literal}
    }
    
    {/literal}{if $pcp && $is_honor_roll }{literal}
    function pcpAnonymous( ) {
        // clear nickname field if anonymous is true
        if ( document.getElementsByName("pcp_is_anonymous")[1].checked ) { 
            document.getElementById('pcp_roll_nickname').value = '';
        }
        if ( ! document.getElementsByName("pcp_display_in_roll")[0].checked ) { 
            hide('nickID', 'block');
            hide('nameID', 'block');
    	hide('personalNoteID', 'block');
        } else {
            if ( document.getElementsByName("pcp_is_anonymous")[0].checked ) {
                show('nameID', 'block');
                show('nickID', 'block');
    	        show('personalNoteID', 'block');
            } else {
                show('nameID', 'block');
                hide('nickID', 'block');
    	    hide('personalNoteID', 'block');
            }
        }
    }
    {/literal}{/if}{literal}

</script>
{/literal} 
