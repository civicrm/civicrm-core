{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{if $action & 1024}
    {include file="CRM/Event/Form/Registration/PreviewHeader.tpl"}
{/if}

<div class="crm-event-id-{$event.id} crm-block crm-event-confirm-form-block">
    <div class="messages status section continue_message-section"><p>
    {capture assign=register}{ts}Register{/ts}{/capture}
    {if $isOnWaitlist}
        {ts}Please verify your information.{/ts} <strong>{ts}If space becomes available you will receive an email with a link to complete your registration.{/ts}</strong>
        {ts 1=$register}Click <strong>%1</strong> to be added to the WAIT LIST for this event.{/ts}
    {elseif $isRequireApproval}
        {ts}Please verify your information.{/ts}
        {ts 1=$register}Click <strong>%1</strong> to submit your registration for approval.{/ts}
    {else}
        {ts}Please verify your information.{/ts}
        {$verifyText}
    {/if}
    </p></div>
    {if $is_pay_later and !$isAmountzero and !$isOnWaitlist and !$isRequireApproval}
    <div class="bold pay-later-receipt-instructions">{$pay_later_receipt}</div>
    {/if}

    <div id="crm-submit-buttons" class="crm-submit-buttons">
      {include file="CRM/common/formButtons.tpl" location="top"}
    </div>

    {if $confirm_text}
        <div id="intro_text" class="crm-section event_confirm_text-section">
          <p>{$confirm_text|purify}</p>
        </div>
    {/if}

    {if $paidEvent && !$isRequireApproval && !$isOnWaitlist}
        <div class="crm-group event_fees-group">
            <div class="header-dark">
                {$event.fee_label}
            </div>
            {if $lineItem}
                {include file="CRM/Price/Page/LineItem.tpl" context="Event" displayLineItemFinancialType=false getTaxDetails=$totalTaxAmount}
            {elseif $amounts || $amount == 0}
          <div class="crm-section no-label amount-item-section">
                    {foreach from=$amounts item=amount key=level}
              <div class="content">
                  {$amount.amount|crmMoney:$currency}&nbsp;&nbsp;{$amount.label}
              </div>
                  <div class="clear"></div>
                    {/foreach}
            </div>
                {if $totalTaxAmount}
                  <div class="crm-section no-label total-amount-section">
                  <div class="content bold">{ts}Total {$taxTerm} Amount{/ts}:&nbsp;&nbsp;{$totalTaxAmount|crmMoney:$currency}</div>
                  <div class="clear"></div>
                  </div>
                {/if}
                {if $totalAmount}
                <div class="crm-section no-label total-amount-section">
                    <div class="content bold">{ts}Total Amount{/ts}:&nbsp;&nbsp;{$totalAmount|crmMoney:$currency}</div>
                    <div class="clear"></div>
                  </div>
                {/if}
                {if $hookDiscount}
                    <div class="crm-section hookDiscount-section">
                        <em>({$hookDiscount.message})</em>
                    </div>
                {/if}
            {/if}

        </div>
    {/if}

    {if $showPaymentOnConfirm}
    <div class="crm-group event_info-group">
      <div class="header-dark">
          {ts}Payment details{/ts}
      </div>
    {if !empty($form.payment_processor_id.label)}
      <fieldset class="crm-public-form-item crm-group payment_options-group" style="display:none;">
        <legend>{ts}Payment Options{/ts}</legend>
        <div class="crm-section payment_processor-section">
          <div class="label">{$form.payment_processor_id.label}</div>
          <div class="content">{$form.payment_processor_id.html}</div>
          <div class="clear"></div>
        </div>
      </fieldset>
    {/if}
    {if $totalAmount > 0}
      {include file='CRM/Core/BillingBlockWrapper.tpl'}
    {/if}
    {literal}<script>function calculateTotalFee() { return {/literal}{$totalAmount}{literal} }</script>{/literal}
    </div>
    {/if}

    {if $pcpBlock && $pcp_display_in_roll}
    <div class="crm-group pcp_display-group">
        <div class="header-dark">
           {ts}Contribution Honor Roll{/ts}
        </div>
        <div class="display-block">
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
          <br />
        </div>
    </div>
    {/if}

    {include file="CRM/Event/Form/Registration/DisplayProfile.tpl"}

    {if $billingName or $address}
      <div class="crm-group billing_name_address-group">
            <div class="header-dark">
                {ts}Billing Name and Address{/ts}
            </div>
          <div class="crm-section no-label billing_name-section">
            <div class="content">{$billingName}</div>
            <div class="clear"></div>
          </div>
          <div class="crm-section no-label billing_address-section">
            <div class="content">{$address|nl2br}</div>
            <div class="clear"></div>
          </div>
      </div>
    {/if}

    {if $credit_card_type}
      {crmRegion name="event-confirm-billing-block"}
        <div class="crm-group credit_card-group">
          <div class="header-dark">
            {ts}Credit Card Information{/ts}
          </div>
          <div class="crm-section no-label credit_card_details-section">
            <div class="content">{$credit_card_type}</div>
            <div class="content">{$credit_card_number}</div>
            <div class="content">{if $credit_card_exp_date}{ts}Expires{/ts}: {$credit_card_exp_date|truncate:7:''|crmDate}{/if}</div>
            <div class="clear"></div>
          </div>
        </div>
      {/crmRegion}
    {/if}

    <div class="crm-group event_info-group">
        <div class="header-dark">
            {ts}Event Information{/ts}
        </div>
        <div class="display-block">
            {include file="CRM/Event/Form/Registration/EventInfoBlock.tpl"}
        </div>
    </div>

    <div id="crm-submit-buttons" class="crm-submit-buttons">
      {include file="CRM/common/formButtons.tpl" location="bottom"}
    </div>

    {if array_key_exists('confirm_footer_text', $event) && $event.confirm_footer_text}
        <div id="footer_text" class="crm-section event_confirm_footer-section">
            <p>{$event.confirm_footer_text|purify}</p>
        </div>
    {/if}
</div>
