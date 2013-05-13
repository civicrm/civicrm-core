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
{* this template is used for adding/editing/deleting contributions and pledge payments *}

{if $cdType}
  {include file="CRM/Custom/Form/CustomData.tpl"}
{elseif $priceSetId}
  {include file="CRM/Price/Form/PriceSet.tpl" context="standalone" extends="Contribution"}
{elseif $showAdditionalInfo and $formType }
  {include file="CRM/Contribute/Form/AdditionalInfo/$formType.tpl"}
{else}

  {if $contributionMode}
  <h3>{if $ppID}{ts}Credit Card Pledge Payment{/ts}{else}{ts}Credit Card Contribution{/ts}{/if}</h3>
    {elseif $context NEQ 'standalone'}
  <h3>{if $action eq 1 or $action eq 1024}{if $ppID}{ts}Pledge Payment{/ts}{else}{ts}New Contribution{/ts}{/if}{elseif $action eq 8}{ts}Delete Contribution{/ts}{else}{ts}Edit Contribution{/ts}{/if}</h3>
  {/if}

  <div class="crm-block crm-form-block crm-contribution-form-block">

  {if $contributionMode == 'test' }
    {assign var=contribMode value="TEST"}
    {elseif $contributionMode == 'live'}
    {assign var=contribMode value="LIVE"}
  {/if}

  {if !$email and $action neq 8 and $context neq 'standalone'}
  <div class="messages status no-popup">
    <div class="icon inform-icon"></div>&nbsp;{ts}You will not be able to send an automatic email receipt for this contribution because there is no email address recorded for this contact. If you want a receipt to be sent when this contribution is recorded, click Cancel and then click Edit from the Summary tab to add an email address before recording the contribution.{/ts}
  </div>
  {/if}
  {if $contributionMode}
  <div id="help">
    {if $contactId}
      {ts 1=$displayName 2=$contribMode}Use this form to submit a new contribution on behalf of %1. <strong>A %2 transaction will be submitted</strong> using the selected payment processor.{/ts}
    {else}
      {ts 1=$displayName 2=$contribMode}Use this form to submit a new contribution. <strong>A %2 transaction will be submitted</strong> using the selected payment processor.{/ts}
    {/if}
  </div>
  {/if}
  {if $action eq 8}
  <div class="messages status no-popup">
    <div class="icon inform-icon"></div>
    {ts}WARNING: Deleting this contribution will result in the loss of the associated financial transactions (if any).{/ts} {ts}Do you want to continue?{/ts}
  </div>
  {else}
  <div class="crm-submit-buttons">
    {include file="CRM/common/formButtons.tpl"}
    {if $newCredit AND $action EQ 1 AND $contributionMode EQ null}
      {if $contactId}
        {capture assign=ccModeLink}{crmURL p='civicrm/contact/view/contribution' q="reset=1&action=add&cid=`$contactId`&context=`$context`&mode=live"}{/capture}
      {else}
        {capture assign=ccModeLink}{crmURL p='civicrm/contact/view/contribution' q="reset=1&action=add&context=standalone&mode=live"}{/capture}
      {/if}
      <span class="action-link crm-link-credit-card-mode">&nbsp;<a href="{$ccModeLink}">&raquo; {ts}submit credit card contribution{/ts}</a>
    {/if}
  </div>
  {if $isOnline}{assign var=valueStyle value=" class='view-value'"}{else}{assign var=valueStyle value=""}{/if}
  <table class="form-layout-compressed">
    {if $context neq 'standalone'}
    <tr>
      <td class="font-size12pt label"><strong><strong>{ts}Contributor{/ts}</strong></td><td class="font-size12pt"><strong>{$displayName}</strong></td>
    </tr>
    {else}
      {if !$contributionMode and !$email and $outBound_option != 2}
        {assign var='profileCreateCallback' value=1 }
      {/if}
      {include file="CRM/Contact/Form/NewContact.tpl"}
    {/if}
    {if $contributionMode}
    <tr class="crm-contribution-form-block-payment_processor_id"><td class="label nowrap">{$form.payment_processor_id.label}<span class="marker"> * </span></td><td>{$form.payment_processor_id.html}</td></tr>
    {/if}
    <tr class="crm-contribution-form-block-contribution_type_id crm-contribution-form-block-financial_type_id">
      <td class="label">{$form.financial_type_id.label}</td><td{$valueStyle}>{$form.financial_type_id.html}&nbsp;
      {if $is_test}
        {ts}(test){/ts}
      {/if} {help id="id-financial_type"}
      </td>
    </tr>
    {if $action eq 2 and $lineItem and !$defaultContribution}
    <tr>
      <td class="label">{ts}Contribution Amount{/ts}</td>
      <td>{include file="CRM/Price/Page/LineItem.tpl" context="Contribution"}</td>
    </tr>
    {else}
    <tr  class="crm-contribution-form-block-total_amount">
      <td class="label">{$form.total_amount.label}</td>
      <td {$valueStyle}>
        <span id='totalAmount'>{$form.currency.html|crmAddClass:eight}&nbsp;{$form.total_amount.html|crmAddClass:eight}</span>
        {if $hasPriceSets}
          <span id='totalAmountORPriceSet'> {ts}OR{/ts}</span>
          <span id='selectPriceSet'>{$form.price_set_id.html}</span>
          <div id="priceset" class="hiddenElement"></div>
        {/if}

        {if $ppID}{ts}<a href='#' onclick='adjustPayment();'>adjust payment amount</a>{/ts}{help id="adjust-payment-amount"}{/if}
        <br /><span class="description">{ts}Actual amount given by contributor.{/ts}{if $hasPriceSets} {ts}Alternatively, you can use a price set.{/ts}{/if}</span>
      </td>
    </tr>

      {if $buildRecurBlock && !$ppID}
      <tr id='recurringPaymentBlock' class='hiddenElement'>
        <td></td>
        <td>
          <strong>{$form.is_recur.html} {ts}every{/ts}
            &nbsp;{$form.frequency_interval.html}
            &nbsp;{$form.frequency_unit.html}&nbsp;
            {ts}for{/ts}
            &nbsp;{$form.installments.html}
            &nbsp;{$form.installments.label}
          </strong>
          <br />
          <span class="description">
            {ts}Your recurring contribution will be processed automatically for the number of installments you specify. You can leave the number of installments blank if you want to make an open-ended commitment. In either case, you can choose to cancel at any time. You will receive an email receipt for each recurring contribution. The receipts will include a link you can use if you decide to modify or cancel your future contributions.{/ts}
          </span>
        </td>
      </tr>
      {/if}

    <tr id="adjust-option-type" class="crm-contribution-form-block-option_type">
      <td class="label"></td><td {$valueStyle}>{$form.option_type.html}</td>
    </tr>
    {/if}
    {if $contributionMode && $processorSupportsFutureStartDate}
    <tr id='start_date' class="crm-contribution-form-block-receive_date">
      <td class="label">{ts}Start Date{/ts}</td>
      <td {$valueStyle}>{if $hideCalender neq true}{include file="CRM/common/jcalendar.tpl" elementName=receive_date}{else}{$receive_date|crmDate}{/if}<br />
        <span class="description">{ts}You can set a start date for recurring contributions and the first payment will be on that date. For a single post-dated contribution you must select recurring and choose one installment{/ts}</span>
      </td>
    </tr>
    {/if}

  <tr class="crm-contribution-form-block-source">
    <td class="label">{$form.source.label}</td>
    <td {$valueStyle}>{$form.source.html|crmAddClass:huge} {help id="id-contrib_source"}
    </td>
  </tr>

  {* CRM-7362 --add campaign to contributions *}
  {include file="CRM/Campaign/Form/addCampaignToComponent.tpl" campaignTrClass="crm-contribution-form-block-campaign_id"}

    {if $contributionMode}
    {if $email and $outBound_option != 2}
     <tr class="crm-contribution-form-block-is_email_receipt">
       <td class="label">{$form.is_email_receipt.label}</td>
       <td>{$form.is_email_receipt.html}&nbsp; <span class="description">{ts 1=$email}Automatically email a receipt for this contribution to %1?{/ts}</span>
       </td>
     </tr>
     {elseif $context eq 'standalone' and $outBound_option != 2 }
       <tr id="email-receipt" style="display:none;" class="crm-contribution-form-block-is_email_receipt"><td class="label">{$form.is_email_receipt.label}</td><td>{$form.is_email_receipt.html} <span class="description">{ts}Automatically email a receipt for this contribution to {/ts}<span id="email-address"></span>?</span></td></tr>
    {/if}
    <tr id="fromEmail" style="display:none;" >
      <td class="label">{$form.from_email_address.label}</td>
      <td>{$form.from_email_address.html}</td>
    </tr>
    <tr id="receiptDate" class="crm-contribution-form-block-receipt_date">
      <td class="label">{$form.receipt_date.label}</td>
      <td>{include file="CRM/common/jcalendar.tpl" elementName=receipt_date}<br />
        <span class="description">{ts}Date that a receipt was sent to the contributor.{/ts}</span>
      </td>
    </tr>
    {/if}
    {if !$contributionMode}
      <tr class="crm-contribution-form-block-contribution_status_id">
        <td class="label">{$form.contribution_status_id.label}</td>
        <td>{$form.contribution_status_id.html}
        {if $contribution_status_id eq 2}{if $is_pay_later }: {ts}Pay Later{/ts} {else}: {ts}Incomplete Transaction{/ts}{/if}{/if}
        </td>
      </tr>

      {* Cancellation / Refunded fields are hidden unless contribution status is set to Cancelled or Refunded*}
      <tr id="cancelInfo" class="crm-contribution-form-block-cancelInfo">
        <td>&nbsp;</td>
        <td><fieldset><legend>{ts}Cancellation or Refund Information{/ts}</legend>
          <table class="form-layout-compressed">
            <tr id="cancelDate" class="crm-contribution-form-block-cancel_date">
              <td class="label">{$form.cancel_date.label}</td>
              <td>
                {if $hideCalendar neq true}
                  {include file="CRM/common/jcalendar.tpl" elementName=cancel_date}
                {else}
                  {$form.cancel_date.html|crmDate}
                {/if}
              </td>
            </tr>
            <tr id="cancelDescription" class="crm-contribution-form-block-cancel_reason">
              <td class="label">&nbsp;</td>
              <td class="description">{ts}Enter the cancellation or refunded date, or you can skip this field and the cancellation date or refunded date will be automatically set to TODAY.{/ts}</td>
            </tr>
            <tr id="cancelReason">
              <td class="label" style="vertical-align: top;">{$form.cancel_reason.label}</td>
              <td>{$form.cancel_reason.html|crmReplace:class:huge}</td>
            </tr>
          </table>
        </fieldset>
        </td>
      </tr>
    {/if}
  <tr id="softCreditID" class="crm-contribution-form-block-soft_credit_to"><td class="label">{$form.soft_credit_to.label}</td>
    <td {$valueStyle}>
      {$form.soft_credit_to.html} {help id="id-soft_credit"}
      {if $siteHasPCPs}
        <div id="showPCPLink"><a href='#' onclick='showPCP(); return false;'>{ts}credit this contribution to a personal campaign page{/ts}</a>{help id="id-link_pcp"}</div>
      {/if}
    </td>
  </tr>
    {if $siteHasPCPs}{* Credit contribution to PCP. *}
    <tr id="pcpID" class="crm-contribution-form-block-pcp_made_through_id">
      <td class="label">{$form.pcp_made_through.label}</td>
      <td>
        {$form.pcp_made_through.html} &nbsp;
        <span class="showSoftCreditLink">{ts}<a href="#" onclick='showSoftCredit(); return false;'>unlink from personal campaign page</a>{/ts}</span><br />
        <span class="description">{ts}Search for the Personal Campaign Page by the fund-raiser's last name or email address.{/ts}</span>
        <div class="spacer"></div>
        <div class="crm-contribution-form-block-pcp_details">
          <table class="crm-contribution-form-table-credit_to_pcp">
            <tr id="pcpDisplayRollID" class="crm-contribution-form-block-pcp_display_in_roll"><td class="label">{$form.pcp_display_in_roll.label}</td>
              <td>{$form.pcp_display_in_roll.html}</td>
            </tr>
            <tr id="nickID" class="crm-contribution-form-block-pcp_roll_nickname">
              <td class="label">{$form.pcp_roll_nickname.label}</td>
              <td>{$form.pcp_roll_nickname.html|crmAddClass:big}<br />
                <span class="description">{ts}Name or nickname contributor wants to be displayed in the Honor Roll. Enter "Anonymous" for anonymous contributions.{/ts}</span></td>
            </tr>
            <tr id="personalNoteID" class="crm-contribution-form-block-pcp_personal_note">
              <td class="label" style="vertical-align: top">{$form.pcp_personal_note.label}</td>
              <td>{$form.pcp_personal_note.html}
                <span class="description">{ts}Personal message submitted by contributor for display in the Honor Roll.{/ts}</span>
              </td>
            </tr>
          </table>
        </div>
      </td>
    </tr>
    {/if}
  </table>
    {if !$contributionMode}
    <div class="crm-accordion-wrapper crm-accordion_title-accordion crm-accordion-processed" id="paymentDetails_Information">
      <div class="crm-accordion-header">
        {ts}Payment Details{/ts}
      </div>
      <div class="crm-accordion-body">
        <table class="form-layout-compressed" >
          <tr class="crm-contribution-form-block-receive_date">
            <td class="label">{$form.receive_date.label}</td>
            <td {$valueStyle}>{include file="CRM/common/jcalendar.tpl" elementName=receive_date}<br />
              <span class="description">{ts}The date this contribution was received.{/ts}</span>
            </td>
          </tr>
          <tr class="crm-contribution-form-block-payment_instrument_id">
            <td class="label">{$form.payment_instrument_id.label}</td>
            <td {$valueStyle}>{$form.payment_instrument_id.html}<br />
              <span class="description">{ts}Leave blank for non-monetary contributions.{/ts}</span>
            </td>
          </tr>
          {if $showCheckNumber || !$isOnline}
            <tr id="checkNumber" class="crm-contribution-form-block-check_number">
              <td class="label">{$form.check_number.label}</td>
              <td>{$form.check_number.html|crmReplace:class:six}</td>
            </tr>
          {/if}
          <tr class="crm-contribution-form-block-trxn_id">
            <td class="label">{$form.trxn_id.label}</td>
            <td {$valueStyle}>{$form.trxn_id.html|crmReplace:class:twelve} {help id="id-trans_id"}</td>
          </tr>
          {if $email and $outBound_option != 2}
            <tr class="crm-contribution-form-block-is_email_receipt">
              <td class="label">
                {$form.is_email_receipt.label}</td><td>{$form.is_email_receipt.html}&nbsp;
                <span class="description">{ts 1=$email}Automatically email a receipt for this payment to %1?{/ts}</span>
              </td>
            </tr>
            {elseif $context eq 'standalone' and $outBound_option != 2 }
            <tr id="email-receipt" style="display:none;" class="crm-contribution-form-block-is_email_receipt">
              <td class="label">{$form.is_email_receipt.label}</td>
              <td>{$form.is_email_receipt.html} <span class="description">{ts}Automatically email a receipt for this payment to {/ts}<span id="email-address"></span>?</span>
              </td>
            </tr>
          {/if}
          <tr id="receiptDate" class="crm-contribution-form-block-receipt_date">
            <td class="label">{$form.receipt_date.label}</td>
            <td>{include file="CRM/common/jcalendar.tpl" elementName=receipt_date}<br />
              <span class="description">{ts}Date that a receipt was sent to the contributor.{/ts}</span>
            </td>
          </tr>
          <tr id="fromEmail" class="crm-contribution-form-block-receipt_date" style="display:none;">
            <td class="label">{$form.from_email_address.label}</td>
            <td>{$form.from_email_address.html}</td>
          </tr>
        </table>
      </div>
    </div>
    {/if}

  <div id="customData" class="crm-contribution-form-block-customData"></div>

  {*include custom data js file*}
  {include file="CRM/common/customData.tpl"}

    {literal}
    <script type="text/javascript">
      cj( function( ) {
    {/literal}
    CRM.buildCustomData( '{$customDataType}' );
    {if $customDataSubType}
      CRM.buildCustomData( '{$customDataType}', {$customDataSubType} );
    {/if}
    {literal}
    });

    // bind first click of accordion header to load crm-accordion-body with snippet
    // everything else taken care of by cj().crm-accordions()
    cj(function() {
      cj('#adjust-option-type').hide();
      cj('.crm-ajax-accordion .crm-accordion-header').one('click', function() {
        loadPanes(cj(this).attr('id'));
      });
      cj('.crm-ajax-accordion:not(.collapsed) .crm-accordion-header').each(function(index) {
        loadPanes(cj(this).attr('id'));
      });
    });
    // load panes function calls for snippet based on id of crm-accordion-header
    function loadPanes( id ) {
      var url = "{/literal}{crmURL p='civicrm/contact/view/contribution' q='snippet=4&formType=' h=0}{literal}" + id;
      {/literal}
      {if $contributionMode}
        url = url + "&mode={$contributionMode}";
      {/if}
      {if $qfKey}
        url = url + "&qfKey={$qfKey}";
      {/if}
      {literal}
      if (! cj('div.'+id).html()) {
        var loading = '<img src="{/literal}{$config->resourceBase}i/loading.gif{literal}" alt="{/literal}{ts escape='js'}loading{/ts}{literal}" />&nbsp;{/literal}{ts escape='js'}Loading{/ts}{literal}...';
        cj('div.'+id).html(loading);
        cj.ajax({
          url    : url,
          success: function(data) { cj('div.'+id).html(data); }
        });
      }
    }

  var url = "{/literal}{$dataUrl}{literal}";

  cj('#soft_credit_to').autocomplete( url, { width : 180, selectFirst : false, matchContains: true
  }).result( function(event, data, formatted) {
      cj( "#soft_contact_id" ).val( data[1] );
  });
  {/literal}
    {if $context eq 'standalone' and $outBound_option != 2 }
      {literal}
      cj( function( ) {
        cj("#contact_1").blur( function( ) {
          checkEmail( );
        });
        checkEmail( );
        showHideByValue( 'is_email_receipt', '', 'receiptDate', 'table-row', 'radio', true);
        showHideByValue( 'is_email_receipt', '', 'fromEmail', 'table-row', 'radio', false );
      });

      function checkEmail( ) {
        var contactID = cj("input[name='contact_select_id[1]']").val();
        if (contactID) {
          var postUrl = "{/literal}{crmURL p='civicrm/ajax/checkemail' h=0}{literal}";
          cj.post( postUrl, {contact_id: contactID},
            function (response) {
              if (response) {
                cj("#email-receipt").show( );
                cj("#email-address").html(response);
              }
              else {
                cj("#email-receipt").hide( );
              }
            }
          );
        }
        else {
          cj("#email-receipt").hide( );
        }
      }

      function profileCreateCallback( blockNo ) {
        checkEmail( );
      }
    {/literal}
    {/if}
  </script>

  <div class="accordion ui-accordion ui-widget ui-helper-reset">
  {* Additional Detail / Honoree Information / Premium Information *}
    {foreach from=$allPanes key=paneName item=paneValue}

      <div class="crm-accordion-wrapper crm-ajax-accordion crm-{$paneValue.id}-accordion {if $paneValue.open neq 'true'}collapsed{/if}">
        <div class="crm-accordion-header" id="{$paneValue.id}">

          {$paneName}
        </div><!-- /.crm-accordion-header -->
        <div class="crm-accordion-body">

          <div class="{$paneValue.id}"></div>
        </div><!-- /.crm-accordion-body -->
      </div><!-- /.crm-accordion-wrapper -->

    {/foreach}
  </div>

  {/if}
<br />
<div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
</div>

  {literal}
  <script type="text/javascript">
  function verify( ) {
    if (cj('#is_email_receipt').attr( 'checked' )) {
      var ok = confirm( '{/literal}{ts escape='js'}Click OK to save this contribution record AND send a receipt to the contributor now{/ts}{literal}.' );
      if (!ok) {
        return false;
      }
    }
  }
  
  function status() {
    cj("#cancel_date").val('');
    cj("#cancel_reason").val('');
  }

  </script>
  {/literal}

  {if $action neq 8}
    {literal}
    <script type="text/javascript">
      cj( function( ) {
        checkEmailDependancies( );
        cj('#is_email_receipt').click( function( ) {
          checkEmailDependancies( );
        });
      });

      function checkEmailDependancies( ) {
        if (cj('#is_email_receipt').attr( 'checked' )) {
          cj('#fromEmail').show( );
          cj('#receiptDate').hide( );
        }
        else {
          cj('#fromEmail').hide( );
          cj('#receiptDate').show( );
        }
      }

    {/literal}{if !$contributionMode}{literal}
     cj( function( ) {
      showHideCancelInfo(cj('#contribution_status_id'));	
      
      cj('#contribution_status_id').change(function() {
       showHideCancelInfo(this);
      }
       );
     });

     function showHideCancelInfo(obj) {
       contributionStatus = cj(obj).val();
       if (contributionStatus == 3 || contributionStatus == 7) {
         cj('#cancelInfo').show( );
       }
       else {
       	 status();          
         cj('#cancelInfo').hide( );
       }
     }

    {/literal}{/if}{literal}
    </script>	
    {/literal}
      {if !$contributionMode}
        {include file="CRM/common/showHideByFieldValue.tpl"
        trigger_field_id    ="payment_instrument_id"
        trigger_value       = '4'
        target_element_id   ="checkNumber"
        target_element_type ="table-row"
        field_type          ="select"
        invert              = 0
        }
    {/if}
  {/if} {* not delete mode if*}

  {* include jscript to warn if unsaved form field changes *}
  {include file="CRM/common/formNavigate.tpl"}

{/if} {* closing of main custom data if *}

{literal}
<script type="text/javascript">
cj(function() {
  cj().crmAccordions();
});

{/literal}

// load form during form rule.
{if $buildPriceSet}{literal}buildAmount( );{/literal}{/if}

{if $siteHasPCPs}
  {literal}
  var pcpUrl = "{/literal}{$pcpDataUrl}{literal}";

  cj('#pcp_made_through').autocomplete( pcpUrl, { width : 360, selectFirst : false, matchContains: true
  }).result( function(event, data, formatted) {
      cj( "#pcp_made_through_id" ).val( data[1] );
  });
{/literal}

  {if $pcpLinked}
    {literal}hideSoftCredit( );{/literal}{* hide soft credit on load if we have PCP linkage *}
  {else}
    {literal}cj('#pcpID').hide();{/literal}{* hide PCP section *}
  {/if}

  {literal}
  function hideSoftCredit ( ){
    cj("#softCreditID").hide();
  }
  function showPCP( ) {
    cj('#pcpID').show();
    cj("#softCreditID").hide();
  }
  function showSoftCredit( ) {
    cj('#pcp_made_through_id').val('');
    cj('#pcp_made_through').val('');
    cj('#pcp_roll_nickname').val('');
    cj('#pcp_personal_note').val('');
    cj('#pcp_display_in_roll').attr('checked', false);
    cj("#pcpID").hide();
    cj('#softCreditID').show();
  }
  {/literal}
{/if}

{literal}
function buildAmount( priceSetId ) {
  if (!priceSetId) priceSetId = cj("#price_set_id").val( );

  var fname = '#priceset';
  if (!priceSetId) {
    // hide price set fields.
    cj(fname).hide( );

    // show/hide price set amount and total amount.
    cj("#totalAmountORPriceSet").show( );
    cj("#totalAmount").show( );
    var choose = "{/literal}{ts}Choose price set{/ts}{literal}";
    cj("#price_set_id option[value='']").html( choose );

    //we might want to build recur block.
    if (cj("#is_recur")) buildRecurBlock( null );
    return;
  }

  //don't allow recurring w/ priceset.
  if ( cj( "#is_recur" ) && cj( 'input:radio[name="is_recur"]:checked').val( ) ) {
    //reset the values of recur block.
    cj("#installments").val('');
    cj("#frequency_interval").val('');
    cj('input:radio[name="is_recur"]')[0].checked = true;
    cj("#recurringPaymentBlock").hide( );
  }

  var dataUrl = {/literal}"{crmURL h=0 q='snippet=4'}"{literal} + '&priceSetId=' + priceSetId;

  var response = cj.ajax({
    url: dataUrl,
    async: false
  }).responseText;

  cj( fname ).show( ).html( response );
  // freeze total amount text field.
  cj( "#total_amount").val('');

  cj( "#totalAmountORPriceSet" ).hide( );
  cj( "#totalAmount").hide( );
  var manual = "{/literal}{ts}Manual contribution amount{/ts}{literal}";
  cj("#price_set_id option[value='']").html( manual );
}

function adjustPayment( ) {
  cj('#adjust-option-type').show();
  cj("#total_amount").removeAttr("READONLY");
  cj("#total_amount").css('background-color', '#ffffff');
}

{/literal}{if $processorSupportsFutureStartDate}{literal}
cj ('input:radio[name="is_recur"]').click( function( ) {
  showStartDate( );
});

showStartDate( );

function showStartDate( ) {
  if (cj( 'input:radio[name="is_recur"]:checked').val( ) == 0 ) {
    cj('#start_date').hide( );
  }
  else {
    cj('#start_date').show( );
  }
}

{/literal}{/if}{literal}
cj('#fee_amount').change( function() {
  var totalAmount = cj('#total_amount').val();
  var feeAmount = cj('#fee_amount').val();  
  var netAmount = totalAmount.replace(/,/g, '') - feeAmount.replace(/,/g, '');
  if (!cj('#net_amount').val()) {
    cj('#net_amount').val(netAmount);
  }
});
</script>
{/literal}
