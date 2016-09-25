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
{* this template is used for adding/editing/deleting contributions and pledge payments *}

{if $priceSetId}
  {include file="CRM/Price/Form/PriceSet.tpl" context="standalone" extends="Contribution"}
{elseif $showAdditionalInfo and $formType }
  {include file="CRM/Contribute/Form/AdditionalInfo/$formType.tpl"}
{else}
  {include file="CRM/Contribute/Form/AdditionalInfo/Payment.tpl"}
  <div class="crm-block crm-form-block crm-contribution-form-block">

  {if !$email and $action neq 8 and $context neq 'standalone'}
  <div class="messages status no-popup">
    <div class="icon inform-icon"></div>&nbsp;{ts}You will not be able to send an automatic email receipt for this contribution because there is no email address recorded for this contact. If you want a receipt to be sent when this contribution is recorded, click Cancel and then click Edit from the Summary tab to add an email address before recording the contribution.{/ts}
  </div>
  {/if}
  {if $contributionMode}
  <div class="help">
    {if $contactId}
      {ts 1=$displayName 2=$contributionMode|upper}Use this form to {if $payNow} edit {else} submit a new {/if} contribution on behalf of %1. <strong>A
        %2 transaction will be submitted</strong> using the selected payment processor.{/ts}
    {else}
      {ts 1=$displayName 2=$contributionMode|upper}Use this form to submit a new contribution. <strong>A %2 transaction will be submitted</strong> using the selected payment processor.{/ts}
    {/if}
  </div>
  {/if}
  {if $action eq 8}
  <div class="messages status no-popup">
    <div class="icon inform-icon"></div>
    {ts}WARNING: Deleting this contribution will result in the loss of the associated financial transactions (if any).{/ts} {ts}Do you want to continue?{/ts}
  </div>
  {else}
    {if $newCredit AND $action EQ 1 AND $contributionMode EQ null}
    <div class="action-link css_right crm-link-credit-card-mode">
      {if $contactId}
        {capture assign=ccModeLink}{crmURL p='civicrm/contact/view/contribution' q="reset=1&action=add&cid=`$contactId`&context=`$context`&mode=live"}{/capture}
      {else}
        {capture assign=ccModeLink}{crmURL p='civicrm/contact/view/contribution' q="reset=1&action=add&context=standalone&mode=live"}{/capture}
      {/if}
     <a class="open-inline-noreturn action-item crm-hover-button" href="{$ccModeLink}">&raquo; {ts}submit credit card contribution{/ts}</a>
    </div>
    {/if}
  <div class="crm-submit-buttons">
    {include file="CRM/common/formButtons.tpl"}
  </div>
  {if $isOnline}{assign var=valueStyle value=" class='view-value'"}{else}{assign var=valueStyle value=""}{/if}
  <table class="form-layout-compressed">
    {if $context neq 'standalone'}
    <tr>
      <td class="font-size12pt label"><strong><strong>{ts}Contributor{/ts}</strong></td><td class="font-size12pt"><strong>{$displayName}</strong></td>
    </tr>
    {else}
      <td class="label">{$form.contact_id.label}</td>
      <td>{$form.contact_id.html}</td>
    {/if}
    {if $contributionMode}
      <tr class="crm-contribution-form-block-payment_processor_id"><td class="label nowrap">{$form.payment_processor_id.label}<span class="crm-marker"> * </span></td><td>{$form.payment_processor_id.html}</td></tr>
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
        {if !$payNow}
          {if $hasPriceSets}
            <span id='totalAmountORPriceSet'> {ts}OR{/ts}</span>
            <span id='selectPriceSet'>{$form.price_set_id.html}</span>
            <div id="priceset" class="hiddenElement"></div>
          {/if}

          {if $ppID}{ts}<a href='#' onclick='adjustPayment();'>adjust payment amount</a>{/ts}{help id="adjust-payment-amount"}{/if}
          <div id="totalAmountBlock">
            {if $hasPriceSets}<span class="description">{ts}Alternatively, you can use a price set.{/ts}</span>{/if}
            <div id="totalTaxAmount" class="label"></div>
          </div>
        {/if}
      </td>
    </tr>

      {if $buildRecurBlock && !$payNow}
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
    {if !$contributionMode || $payNow}
      <tr class="crm-contribution-form-block-contribution_status_id">
        <td class="label">{$form.contribution_status_id.label}</td>
        <td>{$form.contribution_status_id.html}
        {if $contribution_status_id eq 2}{if $is_pay_later }: {ts}Pay Later{/ts} {else}: {ts}Incomplete Transaction{/ts}{/if}{/if}
        </td>
        <td>
        {if $contactId && $contribID && $contributionMode EQ null && $contribution_status_id eq 2}
          {capture assign=payNowLink}{crmURL p='civicrm/contact/view/contribution' q="reset=1&action=update&id=`$contribID`&cid=`$contactId`&context=`$context`&mode=live"}{/capture}
          <a class="open-inline action-item crm-hover-button" href="{$payNowLink}">&raquo; {ts}Pay with Credit Card{/ts}</a>
        {/if}
      </td>
      </tr>
    {/if}

    {if !$contributionMode}
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
                  {$form.cancel_date.value|crmDate}
                {/if}
              </td>
            </tr>
            <tr id="cancelDescription" class="crm-contribution-form-block-cancel_reason">
              <td class="label">&nbsp;</td>
              <td class="description">{ts}Enter the cancellation or refunded date, or you can skip this field and the cancellation date or refunded date will be automatically set to TODAY.{/ts}</td>
            </tr>
            <tr id="cancelReason">
              <td class="label" style="vertical-align: top;">{$form.cancel_reason.label}</td>
              <td>{$form.cancel_reason.html}</td>
            </tr>
            <tr id="refundTrxnID">
              <td class="label" style="vertical-align: top;">{$form.refund_trxn_id.label}</td>
              <td>{$form.refund_trxn_id.html}</td>
            </tr>
          </table>
        </fieldset>
        </td>
      </tr>
    {/if}
    {if $form.revenue_recognition_date && !$payNow}
      <tr class="crm-contribution-form-block-revenue_recognition_date">
        <td class="label">{$form.revenue_recognition_date.label}</td>
        <td>{$form.revenue_recognition_date.html}</td>
      </tr>
    {/if}
  </table>

  {include file='CRM/Core/BillingBlockWrapper.tpl'}

    <!-- start of soft credit -->
    {if !$payNow}
      <div class="crm-accordion-wrapper crm-accordion_title-accordion crm-accordion-processed {if $noSoftCredit}collapsed{/if}" id="softCredit">
        <div class="crm-accordion-header">
          {ts}Soft Credit{/ts}&nbsp;{help id="id-soft_credit"}
        </div>
        <div class="crm-accordion-body">
          <table class="form-layout-compressed">
            <tr class="crm-contribution-form-block-soft_credit_to">
              <td colspan="2">
                {include file="CRM/Contribute/Form/SoftCredit.tpl"}
              </td>
            </tr>
          </table>
        </div>
      </div>
    {/if}
    <!-- end of soft credit -->

    <!-- start of PCP -->
    {if $siteHasPCPs && !$payNow}
      <div class="crm-accordion-wrapper crm-accordion_title-accordion crm-accordion-processed {if $noPCP}collapsed{/if}" id="softCredit">
        <div class="crm-accordion-header">
          {ts}Personal Campaign Page{/ts}&nbsp;{help id="id-pcp"}
        </div>
        <div class="crm-accordion-body">
          <table class="form-layout-compressed">
            <tr class="crm-contribution-pcp-block crm-contribution-form-block-pcp_made_through_id">
              <td class="label">{$form.pcp_made_through_id.label}</td>
              <td>
                {$form.pcp_made_through_id.html} &nbsp;
                <div class="description">{ts}Search for the Personal Campaign Page by the fund-raiser's last name or email address.{/ts}</div>

                <div class="spacer"></div>
                 <div class="crm-contribution-form-block-pcp_details">
                  <table class="crm-contribution-form-table-credit_to_pcp">
                    <tr id="pcpDisplayRollID" class="crm-contribution-form-block-pcp_display_in_roll">
                      <td class="label">{$form.pcp_display_in_roll.label}</td>
                      <td>{$form.pcp_display_in_roll.html}</td>
                    </tr>
                    <tr id="nickID" class="crm-contribution-form-block-pcp_roll_nickname">
                      <td class="label">{$form.pcp_roll_nickname.label}</td>
                      <td>{$form.pcp_roll_nickname.html|crmAddClass:big}<br/>
                        <div class="description">{ts}Name or nickname contributor wants to be displayed in the Honor Roll. Enter "Anonymous" for anonymous contributions.{/ts}</div>
                      </td>
                    </tr>
                    <tr id="personalNoteID" class="crm-contribution-form-block-pcp_personal_note">
                      <td class="label" style="vertical-align: top">{$form.pcp_personal_note.label}</td>
                      <td>
                        {$form.pcp_personal_note.html}
                        <div
                          class="description">{ts}Personal message submitted by contributor for display in the Honor Roll.{/ts}</div>
                      </td>
                    </tr>
                  </table>
                </div>
              </td>
            </tr>
          </table>
        </div>
      </div>
      {include file="CRM/Contribute/Form/PCP.js.tpl"}
    {/if}
    <!-- end of PCP -->

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
            <td {$valueStyle}>{$form.payment_instrument_id.html} {help id="payment_instrument_id"}</td>
            </td>
          </tr>
          {if $showCheckNumber || !$isOnline}
            <tr id="checkNumber" class="crm-contribution-form-block-check_number">
              <td class="label">{$form.check_number.label}</td>
              <td>{$form.check_number.html}</td>
            </tr>
          {/if}
          <tr class="crm-contribution-form-block-trxn_id">
            <td class="label">{$form.trxn_id.label}</td>
            <td {$valueStyle}>{$form.trxn_id.html} {help id="id-trans_id"}</td>
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

  {if !$payNow}
    <div id="customData" class="crm-contribution-form-block-customData"></div>
  {/if}

  {*include custom data js file*}
  {include file="CRM/common/customData.tpl"}

    {literal}
    <script type="text/javascript">
      CRM.$(function($) {
    {/literal}
    CRM.buildCustomData( '{$customDataType}' );
    {if $customDataSubType}
      CRM.buildCustomData( '{$customDataType}', {$customDataSubType} );
    {/if}

    {if $buildPriceSet}{literal}buildAmount( );{/literal}{/if}
    {literal}
    });

    // bind first click of accordion header to load crm-accordion-body with snippet
    // everything else taken care of by cj().crm-accordions()
    CRM.$(function($) {
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
          success: function(data) { cj('div.'+id).html(data).trigger('crmLoad'); }
        });
      }
    }

  var url = "{/literal}{$dataUrl}{literal}";

  {/literal}
    {if $context eq 'standalone' and $outBound_option != 2 }
      {literal}
      CRM.$(function($) {

        var $form = $("form.{/literal}{$form.formClass}{literal}");
        $("#contact_id", $form).change(checkEmail);
        checkEmail( );

        function checkEmail( ) {
          var data = $("#contact_id", $form).select2('data');
          if (data && data.extra && data.extra.email && data.extra.email.length) {
            $("#email-receipt", $form).show();
            $("#email-address", $form).html(data.extra.email);
          }
          else {
            $("#email-receipt", $form).hide();
          }
        }

        showHideByValue( 'is_email_receipt', '', 'receiptDate', 'table-row', 'radio', true);
        showHideByValue( 'is_email_receipt', '', 'fromEmail', 'table-row', 'radio', false );
      });

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
    if (cj('#is_email_receipt').prop('checked' )) {
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
      CRM.$(function($) {
        checkEmailDependancies( );
        cj('#is_email_receipt').click( function( ) {
          checkEmailDependancies( );
        });
      });

      function checkEmailDependancies( ) {
        if (cj('#is_email_receipt').prop('checked' )) {
          cj('#fromEmail').show( );
          cj('#receiptDate').hide( );
        }
        else {
          cj('#fromEmail').hide( );
          cj('#receiptDate').show( );
        }
      }

    {/literal}{if !$contributionMode}{literal}
     CRM.$(function($) {
      showHideCancelInfo(cj('#contribution_status_id'));

      cj('#contribution_status_id').change(function() {
       showHideCancelInfo(cj('#contribution_status_id'));
      }
       );
     });

     function showHideCancelInfo(obj) {
       var cancelInfo_show_ids = [{/literal}{$cancelInfo_show_ids}{literal}];
       if (cancelInfo_show_ids.indexOf(obj.val()) > -1) {
         cj('#cancelInfo').show( );
         cj('#total_amount').attr('readonly', true);
       }
       else {
         status();
         cj('#cancelInfo').hide( );
         cj("#total_amount").removeAttr('readonly');
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

{/if} {* closing of main custom data if *}

{literal}
<script type="text/javascript">

{/literal}

// load form during form rule.
{if $buildPriceSet}{literal}buildAmount( );{/literal}{/if}

{literal}

// CRM-16451: set financial type of 'Price Set' in back office contribution
// instead of selecting manually
function buildAmount( priceSetId, financialtypeIds ) {
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

    cj('label[for="total_amount"]').text('{/literal}{ts}Total Amount{/ts}{literal}');
    cj(".crm-contribution-form-block-financial_type_id").show();
    cj("#financial_type_id option[value='']").attr('selected', true);

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

  cj( fname ).show( ).html( response ).trigger('crmLoad');
  // freeze total amount text field.
  cj( "#total_amount").val('');

  cj( "#totalAmountORPriceSet" ).hide( );
  cj( "#totalAmount").hide( );
  var manual = "{/literal}{ts}Manual contribution amount{/ts}{literal}";
  cj("#price_set_id option[value='']").html( manual );

  cj('label[for="total_amount"]').text('{/literal}{ts}Price Sets{/ts}{literal}');
  cj("#financial_type_id option[value="+financialtypeIds[priceSetId]+"]").prop('selected', true);
  cj(".crm-contribution-form-block-financial_type_id").css("display", "none");
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
var thousandMarker = "{/literal}{$config->monetaryThousandSeparator}{literal}";
var separator = "{/literal}{$config->monetaryDecimalPoint}{literal}";

cj('#fee_amount').change( function() {
  var totalAmount = cj('#total_amount').val().replace(thousandMarker,'').replace(separator,'.');
  var feeAmount = cj('#fee_amount').val().replace(thousandMarker,'').replace(separator,'.');
  var netAmount = totalAmount - feeAmount;
  if (totalAmount) {
    cj('#net_amount').val(CRM.formatMoney(netAmount, true));
  }
});

cj("#financial_type_id").on("change",function(){
    cj('#total_amount').trigger("change");
})

cj("#currency").on("change",function(){
  cj('#total_amount').trigger("change");
})

{/literal}{if $taxRates && $invoicing}{literal}
CRM.$(function($) {
  $('#total_amount').on("change",function(event) {
    if (event.handled !== true) {
      var freezeFinancialType = '{/literal}{$freezeFinancialType}{literal}';
      if (!freezeFinancialType) {
        var financialType = $('#financial_type_id').val();
        var taxRates = '{/literal}{$taxRates}{literal}';
        taxRates = JSON.parse(taxRates);
        var currencies = '{/literal}{$currencies}{literal}';
        currencies = JSON.parse(currencies);
        var currencySelect = $('#currency').val();
        var currencySymbol = currencies[currencySelect];
        var re= /\((.*?)\)/g;
        for(m = re.exec(currencySymbol); m; m = re.exec(currencySymbol)){
          currencySymbol = m[1];
        }
        var taxRate = taxRates[financialType];
        if (!taxRate) {
          taxRate = 0;
          cj("#totalTaxAmount").hide( );
        } else {
          cj("#totalTaxAmount").show( );
        }
        var totalAmount = $('#total_amount').val();
        // replace all thousandMarker and change the separator to a dot
        totalAmount = totalAmount.replace(thousandMarker,'').replace(separator,'.');

        var totalTaxAmount = '{/literal}{$totalTaxAmount}{literal}';
        var taxAmount = (taxRate/100)*totalAmount;
        taxAmount = isNaN (taxAmount) ? 0:taxAmount;
        var totalTaxAmount = taxAmount + Number(totalAmount);
        totalTaxAmount = formatMoney( totalTaxAmount, 2, separator, thousandMarker );

        $("#totalTaxAmount" ).html('Amount with tax : <span id="currencySymbolShow">' + currencySymbol + '</span> '+ totalTaxAmount);
      }
      event.handled = true;
    }
    return false;
  });

  $('#total_amount').trigger("change");
});
{/literal}{/if}{literal}

CRM.$(function($) {
  $('#price_set_id').click(function() {
    if( $('#price_set_id').val() ) {
      $('#totalAmountBlock').hide();
    }
    else {
      $('#totalAmountBlock').show();
    }
  });
});

function formatMoney (amount, c, d, t){
  var n = amount,
  c = isNaN(c = Math.abs(c)) ? 2 : c,
  d = d == undefined ? "," : d,
  t = t == undefined ? "." : t, s = n < 0 ? "-" : "",
  i = parseInt(n = Math.abs(+n || 0).toFixed(c)) + "",
  j = (j = i.length) > 3 ? j % 3 : 0;
return s + (j ? i.substr(0, j) + t : "") + i.substr(j).replace(/(\d{3})(?=\d)/g, "$1" + t) + (c ? d + Math.abs(n - i).toFixed(c).slice(2) : "");
};
</script>
{/literal}
