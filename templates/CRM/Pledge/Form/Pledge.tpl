{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{* this template is used for adding/editing/deleting pledge *}
{if $showAdditionalInfo and $formType}
  {include file="CRM/Contribute/Form/AdditionalInfo/$formType.tpl"}
{else}
{if !$email and $action neq 8 and $context neq 'standalone'}
<div class="messages status no-popup">
  {icon icon="fa-info-circle"}{/icon}
  {ts}You will not be able to send an acknowledgment for this pledge because there is no email address recorded for this contact. If you want a acknowledgment to be sent when this pledge is recorded, click Cancel and then click Edit from the Summary tab to add an email address before recording the pledge.{/ts}
</div>
{/if}
{if $action EQ 2}
    {* Check if current Total Pledge Amount is different from original pledge amount. *}
    {math equation="x / y" x=$amount y=$installments format="%.2f" assign="currentInstallment"}
    {* Check if current Total Pledge Amount is different from original pledge amount. *}
    {if $currentInstallment NEQ $eachPaymentAmount}
      {assign var=originalPledgeAmount value=$installments*$eachPaymentAmount}
    {/if}
{/if}
<div class="crm-block crm-form-block crm-pledge-form-block">
   {if $action eq 8}
    <div class="messages status no-popup">
    {icon icon="fa-info-circle"}{/icon}
    <span class="font-red bold">{ts}WARNING: Deleting this pledge will also delete any related pledge payments.{/ts} {ts}This action cannot be undone.{/ts}</span>
    <p>{ts}Consider cancelling the pledge instead if you want to maintain an audit trail and avoid losing payment data. To set the pledge status to Cancelled and cancel any not-yet-paid pledge payments, first click Cancel on this form. Then click the more &gt; link from the pledge listing, and select the Cancel action.{/ts}</p>
    </div>
   {else}
      <table class="form-layout-compressed">
          <tr class="crm-pledge-form-contact-id">
            <td class="label">{$form.contact_id.label}</td>
            <td>{$form.contact_id.html}</td>
          </tr>
          <tr class="crm-pledge-form-block-amount">
            <td class="label">{$form.amount.label}</td>
            <td>
              <span>{$form.currency.html|crmAddClass:eight}&nbsp;{$form.amount.html|crmAddClass:eight}</span>
              {if $action EQ 2 && $originalPledgeAmount}<div class="messages status no-popup">{icon icon="fa-info-circle"}{/icon}{ts 1=$originalPledgeAmount|crmMoney:$currency} Pledge total has changed due to payment adjustments. Original pledge amount was %1.{/ts}</div>{/if}
            </td>
          </tr>
          <tr class="crm-pledge-form-block-installments">
            <td class="label">{$form.installments.label}</td>
            <td>{$form.installments.html} {ts}installments of{/ts}
              <span class='currency-symbol'>
                {if $action eq 1 or $isPending}
                  {$form.eachPaymentAmount.html}
                {elseif $action eq 2 and !$isPending}
                  {$eachPaymentAmount|crmMoney:$currency}
                {/if}
              </span>&nbsp;{ts}every{/ts}&nbsp;{$form.frequency_interval.html}&nbsp;{$form.frequency_unit.html}
            </td>
          </tr>
          <tr class="crm-pledge-form-block-frequency_day">
            <td class="label nowrap">{$form.frequency_day.label}</td>
            <td>{$form.frequency_day.html} {ts}day of the period{/ts}
            </td>
          </tr>
          <tr class="crm-pledge-form-block-create_date">
            <td class="label">{$form.create_date.label}</td>
            <td>
              {$form.create_date.html}
            </td>
          </tr>

          <tr class="crm-pledge-form-block-start_date">
            <td class="label">{$form.start_date.label}</td>
            <td>
              {$form.start_date.html}
            </td>
          </tr>

        {if $email and $outBound_option != 2}
            {if !empty($form.is_acknowledge)}
          <tr class="crm-pledge-form-block-is_acknowledge">
            <td class="label">{$form.is_acknowledge.label}</td>
            <td>{$form.is_acknowledge.html}
              <span class="description">{ts 1=$email}Automatically email an acknowledgment of this pledge to %1?{/ts}</span>
            </td>
          </tr>
            {/if}
        {elseif $context eq 'standalone' and $outBound_option != 2}
          <tr id="acknowledgment-receipt" style="display:none;">
            <td class="label">{$form.is_acknowledge.label}</td>
            <td>
              {$form.is_acknowledge.html}
              <span class="description">{ts 1='<span id="email-address"></span>'}Automatically email an acknowledgment of this pledge to %1?{/ts}</span>
            </td>
          </tr>
        {/if}
          <tr id="fromEmail" style="display:none;">
            <td class="label">{$form.from_email_address.label}</td>
            <td>{$form.from_email_address.html}  {help id="from_email_address" file="CRM/Contact/Form/Task/Help/Email/id-from_email.hlp"}</td>
          </tr>
          <tr id="acknowledgeDate">
            <td class="label" class="crm-pledge-form-block-acknowledge_date">{$form.acknowledge_date.label}</td>
            <td>
              {$form.acknowledge_date.html}
            </td>
          </tr>
          <tr class="crm-pledge-form-block-financial_type_id">
            <td class="label">{$form.financial_type_id.label} {help id='financial_type_id' file="CRM/Pledge/Form/Pledge.hlp"}
</td>
            <td>{$form.financial_type_id.html}</td>
          </tr>

      {* CRM-7362 --add campaign *}
      {include file="CRM/Campaign/Form/addCampaignToComponent.tpl"
      campaignTrClass="crm-pledge-form-block-campaign_id"}

          <tr class="crm-pledge-form-block-contribution_page_id">
            <td class="label">{$form.contribution_page_id.label} {help id='contribution_page_id' file="CRM/Pledge/Form/Pledge.hlp"}</td>
            <td>{$form.contribution_page_id.html}</td>
          </tr>

          <tr class="crm-pledge-form-block-status">
            <td class="label"><label>{ts}Pledge Status{/ts}</label></td>
            <td class="view-value">{$status}<br />
              <span class="description">{ts}Pledges are "Pending" until the first payment is received. Once a payment is received, status is "In Progress" until all scheduled payments are completed. Overdue pledges are ones with payment(s) past due.{/ts}</span>
            </td>
          </tr>
          <tr>
            <td colspan=2>{include file="CRM/common/customDataBlock.tpl" groupID='' customDataType='Pledge' customDataSubType=false entityID=$pledgeID cid=false}</td>
          </tr>
       </table>
{literal}
<script type="text/javascript">
  // bind first click of accordion header to load crm-accordion-body with snippet
  // everything else taken care of by $().crm-accordions()
  CRM.$(function($) {
    $('.crm-ajax-accordion summary').one('click', function() {
      loadPanes($(this).attr('id'));
    });
    $('#currency').on('change', function() {
      replaceCurrency($('#currency option:selected').text());
    });
    $('.crm-ajax-accordion[open] summary').each(function(index) {
      loadPanes($(this).attr('id'));
    });

    function replaceCurrency(val) {
      var symbol = '';
      var eachPaymentAmout = $('#eachPaymentAmount');
      var pos = val.indexOf("(") + 1;
      if (pos) {
        symbol = val.slice(pos, val.lastIndexOf(")"));
      }
      $('.currency-symbol').text(symbol).append("&nbsp;").append(eachPaymentAmout);
    }

    // load panes function calls for snippet based on id of crm-accordion-header
    function loadPanes( id ) {
      var url = "{/literal}{crmURL p='civicrm/contact/view/pledge' q='snippet=4&formType=' h=0}{literal}" + id;
      if ( ! $('div.'+id).html() ) {
        var loading = '<img src="{/literal}{$config->resourceBase}i/loading.gif{literal}" alt="{/literal}{ts escape='js'}loading{/ts}{literal}" />&nbsp;{/literal}{ts escape='js'}Loading{/ts}{literal}...';
        $('div.'+id).html(loading);
        $.ajax({
          url    : url,
            success: function(data) { $('div.'+id).html(data).trigger('crmLoad'); }
        });
      }
    }
  });
</script>
{/literal}


<div class="accordion ui-accordion ui-widget ui-helper-reset">
{foreach from=$allPanes key=paneName item=paneValue}
<details class="crm-accordion-bold crm-ajax-accordion crm-{$paneValue.id}-accordion" {if $paneValue.open neq 'true'}{else}open{/if}>
<summary  id="{$paneValue.id}">
        {$paneName}
  </summary>
 <div class="crm-accordion-body">
       <div class="{$paneValue.id}"></div>
 </div>
</details>

{/foreach}
</div>
{/if} {* not delete mode if*}

<br />
<div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
</div>
{literal}
     <script type="text/javascript">

     function verify( ) {
        if (cj('#is_acknowledge').is(':checked')) {
            var emailAddress = '{/literal}{$email}{literal}';
      if ( !emailAddress ) {
      var emailAddress = cj('#email-address').html();
      }
      var message = '{/literal}{ts escape="js" 1="%1"}Click OK to save this Pledge record AND send an acknowledgment to %1 now.{/ts}{literal}';
         return confirm(ts(message, {1: emailAddress}));
        }
     }

     function calculatedPaymentAmount( ) {
       var thousandMarker = {/literal}{crmSetting name="monetaryThousandSeparator" group="CiviCRM Localization"}{literal};
       var separator      = '{/literal}{$config->monetaryDecimalPoint}{literal}';
       var amount = document.getElementById("amount").value;
       // replace all thousandMarker and change the separator to a dot
       amount = amount.replace(thousandMarker,'').replace(separator,'.');
       var installments = document.getElementById("installments").value;
       if ( installments != '' && installments != NaN) {
            amount =  amount/installments;
            var installmentAmount = formatMoney( amount, 2, separator, thousandMarker );
            document.getElementById("eachPaymentAmount").value = installmentAmount;
       }
     }

     function formatMoney (amount, c, d, t){
       var n = amount,
       c = isNaN(c = Math.abs(c)) ? 2 : c,
       d = d == undefined ? "," : d,
       t = t == undefined ? "." : t, s = n < 0 ? "-" : "",
       i = parseInt(n = Math.abs(+n || 0).toFixed(c)) + "",
       j = (j = i.length) > 3 ? j % 3 : 0;
     return s + (j ? i.substr(0, j) + t : "") + i.substr(j).replace(/(\d{3})(?=\d)/g, "$1" + t) + (c ? d + Math.abs(n - i).toFixed(c).slice(2) : "");
     };

    {/literal}
    {if $context eq 'standalone' and $outBound_option != 2}
    {literal}
    CRM.$(function($) {
      var $form = $("form.{/literal}{$form.formClass}{literal}");
      $("#contact_id", $form).change(checkEmail);
      checkEmail( );

      function checkEmail( ) {
        var data = $("#contact_id", $form).select2('data');
        if (data && data.extra && data.extra.email && data.extra.email.length) {
          $("#acknowledgment-receipt", $form).show();
          $("#email-address", $form).html(data.extra.email);
        }
        else {
          $("#acknowledgment-receipt", $form).hide();
        }
      }

      showHideByValue( 'is_acknowledge', '', 'acknowledgeDate', 'table-row', 'radio', true);
      showHideByValue( 'is_acknowledge', '', 'fromEmail', 'table-row', 'radio', false );
    });

    {/literal}
    {/if}
</script>

{if $email and $outBound_option != 2}
{include file="CRM/common/showHideByFieldValue.tpl"
    trigger_field_id    ="is_acknowledge"
    trigger_value       =""
    target_element_id   ="acknowledgeDate"
    target_element_type ="table-row"
    field_type          ="radio"
    invert              = 1
}
{include file="CRM/common/showHideByFieldValue.tpl"
    trigger_field_id    ="is_acknowledge"
    trigger_value       =""
    target_element_id   ="fromEmail"
    target_element_type ="table-row"
    field_type          ="radio"
    invert              = 0
}
{/if}
{/if}
{* closing of main custom data if *}
