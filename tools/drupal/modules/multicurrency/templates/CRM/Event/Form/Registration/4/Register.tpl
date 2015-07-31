{if $action & 1024}
    {include file="CRM/Event/Form/Registration/PreviewHeader.tpl"}
{/if}

<div class="form-item">
{if $event.intro_text}
    <div id="intro_text">
        <p>{$event.intro_text}</p>
    </div>
{/if}

{if $priceSet}
    <fieldset id="priceset"><legend>{$event.fee_label}</legend>
    <dl>
{if $priceSet.help_pre}
  <dt>&nbsp;</dt>
  <dd class="description">{$priceSet.help_pre}</dd>
{/if}
    {foreach from=$priceSet.fields item=element key=field_id}
        {if ($element.html_type eq 'CheckBox' || $element.html_type == 'Radio') && $element.options_per_line}
            {assign var="element_name" value=price_$field_id}
            <dt style="margin-top: .5em;">{$form.$element_name.label}</dt>
            <dd>
            {assign var="count" value="1"}
                <table class="form-layout-compressed">
                    <tr>
                    {foreach name=outer key=key item=item from=$form.$element_name}
                        {if is_numeric($key) }
                                <td class="labels font-light">{$form.$element_name.$key.html}</td>
                            {if $count == $element.options_per_line}
                            {assign var="count" value="1"}
                            </tr>
                            <tr>
                            {else}
                                {assign var="count" value=`$count+1`}
                            {/if}
                        {/if}
                {/foreach}
                    </tr>
                </table>
            </dd>
        {else}
            {assign var="name" value=`$element.name`}
            {assign var="element_name" value="price_"|cat:$field_id}
            <dt>{$form.$element_name.label}</dt>
            <dd>&nbsp;{$form.$element_name.html}</dd>
        {/if}
        {if $element.help_post}
            <dt>&nbsp;</dt>
            <dd class="description">{$element.help_post}</dd>
        {/if}
    {/foreach}
<div class="form-item">
     <dt></dt>
     <dd>{include file="CRM/Event/Form/CalculatePriceset.tpl"}</dd>
</div>
{if $priceSet.help_post}
  <dt>&nbsp;</dt>
  <dd class="description">{$priceSet.help_post}</dd>
{/if}
    </dl>
    </fieldset>
    <dl>
        {if $form.is_pay_later}
            <dt>&nbsp;</dt>
            <dd>{$form.is_pay_later.html}&nbsp;{$form.is_pay_later.label}</dd>
        {/if}
    </dl>
{else}
    {if $paidEvent}
<div class="status">
{if $config->defaultCurrency eq 'EUR'}
{ts}If you want to pay in pounds, click {/ts}<a href="{crmURL p='civicrm/event/register' q="reset=1&id=`$event.id`&currency=GBP"}"title="{ts}Pay for Camp in pounds.{/ts}"><strong>{ts}here{/ts}</strong></a>
{else}
{ts}If you want to pay in euros, click {/ts}<a href="{crmURL p='civicrm/event/register' q="reset=1&id=`$event.id`&currency=EUR"}"title="{ts}Pay for Camp in euros.{/ts}"><strong>{ts}here{/ts}</strong></a>
{/if}

</div>
     <table class="form-layout-compressed">
        <tr><td class="label nowrap">{$event.fee_label} <span class="crm-marker">*</span></td>
            <td>&nbsp;</td>
            <td>{$form.amount.html}</td>
        </tr>
        {if $form.is_pay_later}
        <tr><td>&nbsp;</td>
            <td>&nbsp;</td>
            <td>{$form.is_pay_later.html}&nbsp;{$form.is_pay_later.label}</td>
        </tr>
        {/if}
    </table>
    {/if}
{/if}

{assign var=n value=email-$bltID}
<table class="form-layout-compressed">
    <tr><td class="label nowrap">{$form.$n.label}</td><td>{$form.$n.html}</td></tr>
 </table>
 {if $form.additional_participants.html}
    <div id="noOfparticipants_show">
        <a href="#" class="button" onclick="hide('noOfparticipants_show'); show('noOfparticipants'); document.getElementById('additional_participants').focus(); return false;"><span>&raquo; {ts}Register additional people for this event{/ts}</span></a>
    </div><div class="spacer"></div>
 {/if}
    <div id="noOfparticipants" style="display:none">
        <div class="form-item">
            <table class="form-layout">
            <tr><td><a href="#" onclick="hide('noOfparticipants'); show('noOfparticipants_show'); return false;"><img src="{$config->resourceBase}i/TreeMinus.gif" class="action-icon" alt="{ts}close section{/ts}"/></a></a>
                    <label>{$form.additional_participants.label}</label></td>
                <td>{$form.additional_participants.html|crmReplace:class:two}<br />
                    <span class="description">{ts}You will be able to enter registration information for each additional person after you complete this page and click Continue.{/ts}</span>
                </td>
             </tr>
            </table>
        </div>
    </div>

{* User account registration option. Displays if enabled for one of the profiles on this page. *}
{include file="CRM/common/CMSUser.tpl"}

{include file="CRM/UF/Form/Block.tpl" fields=$customPre}

{if $paidEvent}
    {include file='CRM/Core/BillingBlock.tpl'}
{/if}

{include file="CRM/UF/Form/Block.tpl" fields=$customPost}

{if $isCaptcha}
  {include file='CRM/common/ReCAPTCHA.tpl'}
{/if}

<div id="paypalExpress">
{* Put PayPal Express button after customPost block since it's the submit button in this case. *}
{if $paymentProcessor.payment_processor_type EQ 'PayPal_Express'}
    {assign var=expressButtonName value='_qf_Register_upload_express'}
    <fieldset><legend>{ts}Checkout with PayPal{/ts}</legend>
    <table class="form-layout-compressed">
    <tr><td class="description">{ts}Click the PayPal button to continue.{/ts}</td></tr>
    <tr><td>{$form.$expressButtonName.html} <span style="font-size:11px; font-family: Arial, Verdana;">Checkout securely.  Pay without sharing your financial information. </span></td></tr>
    </table>
    </fieldset>
{/if}
</div>

   <div id="crm-submit-buttons">
     {$form.buttons.html}
   </div>

    {if $event.footer_text}
        <div id="footer_text">
            <p>{$event.footer_text}</p>
        </div>
    {/if}
</div>

{* Hide Credit Card Block and Billing information if registration is pay later. *}
{if $form.is_pay_later and $hidePaymentInformation}
{include file="CRM/common/showHideByFieldValue.tpl"
    trigger_field_id    ="is_pay_later"
    trigger_value       =""
    target_element_id   ="payment_information"
    target_element_type ="table-row"
    field_type          ="radio"
    invert              = 1
}
{/if}
{literal}
<script type="text/javascript">

    function allowParticipant( ) {
  var additionalParticipant = document.getElementById('additional_participants').value;
  var validNumber = "";
  for( i = 0; i< additionalParticipant.length; i++ ) {
      if ( additionalParticipant.charAt(i) >=1 || additionalParticipant.charAt(i) <=9 ) {
    validNumber += additionalParticipant.charAt(i);
      } else {
    document.getElementById('additional_participants').value = validNumber;
      }
  }
    }
    {/literal}{if $form.is_pay_later and $paymentProcessor.payment_processor_type EQ 'PayPal_Express'}{literal}
  showHidePayPalExpressOption();
    {/literal} {/if}{literal}
    function showHidePayPalExpressOption()
    {
        if (document.getElementsByName("is_pay_later")[0].checked) {
      show("crm-submit-buttons");
      hide("paypalExpress");
  } else {
            show("paypalExpress");
            hide("crm-submit-buttons");
        }
    }

</script>
{/literal}
