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
{elseif $onbehalf}
   {include file=CRM/Contribute/Form/Contribution/OnBehalfOf.tpl}
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

{capture assign='reqMark'}<span class="marker" title="{ts}This field is required.{/ts}">*</span>{/capture}
<div class="crm-block crm-contribution-main-form-block">
  <div id="intro_text" class="crm-section intro_text-section">
      {$intro_text}
  </div>
  {if $islifetime or $ispricelifetime }
    <div id="help">You have a current Lifetime Membership which does not need top be renewed.</div>
  {/if}

  

  {if $pledgeBlock}
      {if $is_pledge_payment}
        <div class="crm-section {$form.pledge_amount.name}-section">
          <div class="label">{$form.pledge_amount.label}&nbsp;<span class="marker">*</span></div>
          <div class="content">{$form.pledge_amount.html}</div>
          <div class="clear"></div>
        </div>
      {else}
        <div class="crm-section {$form.is_pledge.name}-section">
          <div class="label">&nbsp;</div>
          <div class="content">
            {$form.is_pledge.html}&nbsp;
            {if $is_pledge_interval}
              {$form.pledge_frequency_interval.html}&nbsp;
            {/if}
            {$form.pledge_frequency_unit.html}<span id="installments_num">&nbsp;{ts}for{/ts}&nbsp;{$form.pledge_installments.html}&nbsp;{ts}installments.{/ts}</span>
          </div>
          <div class="clear"></div>
        </div>
      {/if}
  {/if}


  {if $form.is_recur}
    <div class="crm-section {$form.is_recur.name}-section">
      <div class="label">&nbsp;</div>
      <div class="content">
        <p><strong>{$form.is_recur.html} {ts}every{/ts} &nbsp;{$form.frequency_interval.html} &nbsp; {$form.frequency_unit.html}&nbsp; {ts}for{/ts} &nbsp; {$form.installments.html} &nbsp;{$form.installments.label}</strong></p>
        <p><span class="description">{ts}Your recurring contribution will be processed automatically for the number of installments you specify. You can leave the number of installments blank if you want to make an open-ended commitment. In either case, you can choose to cancel at any time.{/ts}
            {if $is_email_receipt}
                {ts}You will receive an email receipt for each recurring contribution. The receipts will include a link you can use if you decide to modify or cancel your future contributions.{/ts}
            {/if}
            </span></p>
      </div>
      <div class="clear"></div>
    </div>
  {/if}
  {if $pcpSupporterText}
      <div class="crm-section pcpSupporterText-section">
        <div class="label">&nbsp;</div>
        <div class="content">{$pcpSupporterText}</div>
        <div class="clear"></div>
      </div>
  {/if}
      {assign var=n value=email-$bltID}
      <div class="crm-section {$form.$n.name}-section">
        <div class="label">{$form.$n.label}</div>
        <div class="content">
          {$form.$n.html}
        </div>
        <div class="clear"></div>
      </div>

  <div class="crm-section default_amount-section">
  {if $defaultAmount}
        <div class="label">Requested Amount</div>
       	      <div class="content">
   	      	   {$defaultAmount|crmMoney}
   	       </div>	
         <div class="clear"></div>
      </div> 
   {/if}

    {include file="CRM/common/CMSUser.tpl"}
    {include file="CRM/Contribute/Form/Contribution/PremiumBlock.tpl" context="makeContribution"}

    <div class="crm-group custom_pre_profile-group">
      {include file="CRM/UF/Form/Block.tpl" fields=$customPre}
    </div>

    <div class="crm-group custom_post_profile-group">
    {include file="CRM/UF/Form/Block.tpl" fields=$customPost}
    </div>

    {if $is_monetary and $form.bank_account_number}
    <div id="payment_notice">
      <fieldset class="crm-group payment_notice-group">
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
      <div id="footer_text" class="crm-section contribution_footer_text-section">
       <p>{$footer_text}</p>
      </div>
    {/if}
    <br>
    {if $isShare}
        {capture assign=contributionUrl}{crmURL p='civicrm/contribute/transact' q="$qParams" a=true fe=1 h=1}{/capture}
        {include file="CRM/common/SocialNetwork.tpl" url=$contributionUrl title=$title pageURL=$contributionUrl}
    {/if}
</div>

{* Hide Credit Card Block and Billing information if contribution is pay later. *}
{if $form.is_pay_later and $hidePaymentInformation}
{include file="CRM/common/showHideByFieldValue.tpl"
    trigger_field_id    ="is_pay_later"
    trigger_value       =""
    target_element_id   ="billing-payment-block"
    target_element_type ="table-row"
    field_type          ="radio"
    invert              = 1
}
{/if}

<script type="text/javascript">
{if $pcp}pcpAnonymous();{/if}
{literal}

if ( {/literal}"{$form.is_recur}"{literal} ) {
    if ( document.getElementsByName("is_recur")[0].checked == true ) {
  window.onload = function() {
      enablePeriod();
  }
    }
}

function enablePeriod ( ) {
    var frqInt  = {/literal}"{$form.frequency_interval}"{literal};
    if ( document.getElementsByName("is_recur")[0].checked == true ) {
  document.getElementById('installments').value = '';
  if ( frqInt ) {
      document.getElementById('frequency_interval').value    = '';
      document.getElementById('frequency_interval').disabled = true;
  }
  document.getElementById('installments').disabled   = true;
  document.getElementById('frequency_unit').disabled = true;

  //get back to auto renew settings.
  var allowAutoRenew = {/literal}'{$allowAutoRenewMembership}'{literal};
  if ( allowAutoRenew && cj("#auto_renew") ) {
     showHideAutoRenew( null );
  }
    } else {
  if ( frqInt ) {
      document.getElementById('frequency_interval').disabled = false;
  }
  document.getElementById('installments').disabled   = false;
  document.getElementById('frequency_unit').disabled = false;

  //disabled auto renew settings.
  var allowAutoRenew = {/literal}'{$allowAutoRenewMembership}'{literal};
  if ( allowAutoRenew && cj("#auto_renew") ) {
      cj("#auto_renew").attr( 'checked', false );
      cj('#allow_auto_renew').hide( );
  }
    }
}

{/literal}{if $relatedOrganizationFound and $reset}{literal}
   cj( "#is_for_organization" ).attr( 'checked', true );
   showOnBehalf( false );
{/literal}{elseif $onBehalfRequired}{literal}
   showOnBehalf( true );
{/literal}{/if}{literal}

{/literal}{if $honor_block_is_active AND $form.honor_type_id.html}{literal}
    enableHonorType();
{/literal} {/if}{literal}

function enableHonorType( ) {
  var element = document.getElementsByName("honor_type_id");
  for (var i = 0; i < element.length; i++ ) {
    var isHonor = false;
    if ( element[i].checked == true ) {
      var isHonor = true;
      break;
    }
  }
  if ( isHonor ) {
    show('honorType', 'block');
    show('honorTypeEmail', 'block');
  }
  else {
    document.getElementById('honor_first_name').value = '';
    document.getElementById('honor_last_name').value  = '';
    document.getElementById('honor_email').value      = '';
    document.getElementById('honor_prefix_id').value  = '';
    hide('honorType', 'block');
    hide('honorTypeEmail', 'block');
  }
}

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

{/literal}{if $form.is_pay_later and $paymentProcessor.payment_processor_type EQ 'PayPal_Express'}{literal}
    showHidePayPalExpressOption();
{/literal} {/if}

{literal}
function showHidePayPalExpressOption() {
  if (document.getElementsByName("is_pay_later")[0].checked) {
    show("crm-submit-buttons");
    hide("paypalExpress");
  }
  else {
    show("paypalExpress");
    hide("crm-submit-buttons");
  }
}
{/literal}
</script>
{/if}
