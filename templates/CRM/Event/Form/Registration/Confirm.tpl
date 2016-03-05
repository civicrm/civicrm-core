{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2015                                |
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
{if $action & 1024}
    {include file="CRM/Event/Form/Registration/PreviewHeader.tpl"}
{/if}

{include file="CRM/common/TrackingFields.tpl"}

<div class="crm-event-id-{$event.id} crm-block crm-event-confirm-form-block">
    {if $isOnWaitlist}
        <div class="help">
            {ts}Please verify the information below. <span class="bold">Then click 'Continue' to be added to the WAIT LIST for this event</span>. If space becomes available you will receive an email with a link to a web page where you can complete your registration.{/ts}
        </div>
    {elseif $isRequireApproval}
        <div class="help">
            {ts}Please verify the information below. Then click 'Continue' to submit your registration. <span class="bold">Once approved, you will receive an email with a link to a web page where you can complete the registration process.</span>{/ts}
        </div>
    {else}
        <div class="help">
        {ts}Please verify the information below. Click the <strong>Go Back</strong> button below if you need to make changes.{/ts}
        {if $contributeMode EQ 'notify' and !$is_pay_later and ! $isAmountzero }
            {if $paymentProcessor.payment_processor_type EQ 'Google_Checkout'}
                {ts 1=$paymentProcessor.name}Click the <strong>%1</strong> button to checkout to Google, where you will select your payment method and complete the registration.{/ts}
            {else}
                {ts 1=$paymentProcessor.name}Click the <strong>Continue</strong> button to checkout to %1, where you will select your payment method and complete the registration.{/ts}
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

    {if $paidEvent && !$isRequireApproval && !$isOnWaitlist}
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
                {if $totalTaxAmount}
                  <div class="crm-section no-label total-amount-section">
                  <div class="content bold">{ts}Total Tax Amount{/ts}:&nbsp;&nbsp;{$totalTaxAmount|crmMoney}</div>
                  <div class="clear"></div>
                  </div>
                {/if}
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

    {include file="CRM/Event/Form/Registration/DisplayProfile.tpl"}

    {if $contributeMode ne 'notify' and (!$is_pay_later or $isBillingAddressRequiredForPayLater) and $paidEvent and !$isAmountzero and !$isOnWaitlist and !$isRequireApproval}
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

    {if $contributeMode eq 'direct' and ! $is_pay_later and !$isAmountzero and !$isOnWaitlist and !$isRequireApproval}
        <div class="crm-group credit_card-group">
            <div class="header-dark">
                {ts}Credit Card Information{/ts}
            </div>
            <div class="crm-section no-label credit_card_details-section">
                <div class="content">{$credit_card_type}</div>
            <div class="content">{$credit_card_number}</div>
            <div class="content">{ts}Expires{/ts}: {$credit_card_exp_date|truncate:7:''|crmDate}</div>
            <div class="clear"></div>
          </div>
        </div>
    {/if}

    {if $contributeMode NEQ 'notify'} {* In 'notify mode, contributor is taken to processor payment forms next *}
    <div class="messages status section continue_message-section">
        <p>
        {ts}Your registration will not be submitted until you click the <strong>Continue</strong> button. Please click the button one time only. If you need to change any details, click the Go Back button below to return to the previous screen.{/ts}
        </p>
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
