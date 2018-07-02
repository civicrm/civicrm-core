{*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2018                                |
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
    {include file="CRM/Contribute/Form/Contribution/PreviewHeader.tpl"}
{/if}

{include file="CRM/common/TrackingFields.tpl"}

<div class="crm-contribution-page-id-{$contributionPageID} crm-block crm-contribution-confirm-form-block">
    <div class="help">
        <p>{ts}Please verify the information below carefully. Click <strong>Go Back</strong> if you need to make changes.{/ts}
            {if $contributeMode EQ 'notify' and ! $is_pay_later}
                {ts 1=$paymentProcessor.name 2=$button}Click the <strong>%2</strong> button to go to %1, where you will select your payment method and complete the contribution.{/ts}
            {elseif ! $is_monetary or $amount LE 0.0 or $is_pay_later}
                {ts 1=$button}To complete this transaction, click the <strong>%1</strong> button below.{/ts}
            {else}
                {ts 1=$button}To complete your contribution, click the <strong>%1</strong> button below.{/ts}
            {/if}
        </p>
    </div>
    <div id="crm-submit-buttons" class="crm-submit-buttons">
        {include file="CRM/common/formButtons.tpl" location="top"}
    </div>
    {if $is_pay_later}
        <div class="bold pay_later_receipt-section">{$pay_later_receipt}</div>
    {/if}

    {include file="CRM/Contribute/Form/Contribution/MembershipBlock.tpl" context="confirmContribution"}

    {if $amount GTE 0 OR $minimum_fee GTE 0 OR ( $priceSetID and $lineItem ) }
    <div class="crm-group amount_display-group">
       {if !$useForMember}
        <div class="header-dark">
            {if !$membershipBlock AND $amount OR ( $priceSetID and $lineItem ) }{ts}Contribution Amount{/ts}{else}{ts}Membership Fee{/ts} {/if}
        </div>
  {/if}
        <div class="display-block">
            {if !$useForMember}
              {if $lineItem and $priceSetID}
                {if !$amount}{assign var="amount" value=0}{/if}
                {assign var="totalAmount" value=$amount}
                {include file="CRM/Price/Page/LineItem.tpl" context="Contribution"}
              {elseif $is_separate_payment }
                {if $amount AND $minimum_fee}
                    {$membership_name} {ts}Membership{/ts}: <strong>{$minimum_fee|crmMoney}</strong><br />
                    {ts}Additional Contribution{/ts}: <strong>{$amount|crmMoney}</strong><br />
                    <strong> -------------------------------------------</strong><br />
                    {ts}Total{/ts}: <strong>{$amount+$minimum_fee|crmMoney}</strong><br />
                {elseif $amount }
                    {ts}Amount{/ts}: <strong>{$amount|crmMoney} {if $amount_level } &ndash; {$amount_level} {/if}</strong>
                {else}
                    {$membership_name} {ts}Membership{/ts}: <strong>{$minimum_fee|crmMoney}</strong>
                {/if}
              {else}
                {if $totalTaxAmount }
                  {ts 1=$taxTerm}Total %1 Amount{/ts}: <strong>{$totalTaxAmount|crmMoney} </strong><br />
                {/if}
		{if $amount}
                    {if $installments}{ts}Installment Amount{/ts}{else}{ts}Total Amount{/ts}{/if}: <strong>{$amount|crmMoney}{if $amount_level } &ndash; {$amount_level}{/if}</strong>
                {else}
                    {$membership_name} {ts}Membership{/ts}: <strong>{$minimum_fee|crmMoney}</strong>
                {/if}
              {/if}
                {/if}

            {if $is_recur}
                {if !empty($auto_renew)} {* Auto-renew membership confirmation *}
{crmRegion name="contribution-confirm-recur-membership"}
                    <br />
                    <strong>
                        {if $autoRenewOption == 1}
                          {ts 1=$frequency_interval 2=$frequency_unit}I want this membership to be renewed automatically every %1 %2(s).{/ts}
                        {elseif $autoRenewOption == 2}
                          {ts 1=$frequency_interval 2=$frequency_unit}This membership will be renewed automatically every %1 %2(s).{/ts}
                        {/if}
                    </strong></p>
                    <div class="description crm-auto-renew-cancel-info">({ts}Your initial membership fee will be processed once you complete the confirmation step. You will be able to cancel the auto-renewal option by visiting the web page link that will be included in your receipt.{/ts})</div>
{/crmRegion}
                {else}
{crmRegion name="contribution-confirm-recur"}
                    {if $installments}
                      {if $frequency_interval > 1}
                        <p><strong>{ts 1=$frequency_interval 2=$frequency_unit 3=$installments}I want to contribute this amount every %1 %2s for %3 installments.{/ts}</strong></p>
                      {else}
                        <p><strong>{ts 1=$frequency_unit 2=$installments}I want to contribute this amount every %1 for %2 installments.{/ts}</strong></p>
                      {/if}
                    {else}
                      {if $frequency_interval > 1}
                        <p><strong>{ts 1=$frequency_interval 2=$frequency_unit}I want to contribute this amount every %1 %2s.{/ts}</strong></p>
                      {else}
                        <p><strong>{ts 1=$frequency_unit }I want to contribute this amount every %1.{/ts}</strong></p>
                      {/if}
                    {/if}
                    <p>{ts}Your initial contribution will be processed once you complete the confirmation step. You will be able to cancel the recurring contribution by visiting the web page link that will be included in your receipt.{/ts}</p>
{/crmRegion}
                {/if}
            {/if}

            {if $is_pledge }
                {if $pledge_frequency_interval GT 1}
                    <p><strong>{ts 1=$pledge_frequency_interval 2=$pledge_frequency_unit 3=$pledge_installments}I pledge to contribute this amount every %1 %2s for %3 installments.{/ts}</strong></p>
                {else}
                    <p><strong>{ts 1=$pledge_frequency_interval 2=$pledge_frequency_unit 3=$pledge_installments}I pledge to contribute this amount every %2 for %3 installments.{/ts}</strong></p>
                {/if}
                {if $is_pay_later}
                    <p>{ts 1=$receiptFromEmail 2=$button}Click &quot;%2&quot; below to register your pledge. You will be able to modify or cancel future pledge payments at any time by logging in to your account or contacting us at %1.{/ts}</p>
                {else}
                    <p>{ts 1=$receiptFromEmail 2=$button}Your initial pledge payment will be processed when you click &quot;%2&quot; below. You will be able to modify or cancel future pledge payments at any time by logging in to your account or contacting us at %1.{/ts}</p>
                {/if}
            {/if}
        </div>
    </div>
    {/if}

    {if $onbehalfProfile|@count}
      <div class="crm-group onBehalf_display-group label-left crm-profile-view">
         {include file="CRM/UF/Form/Block.tpl" fields=$onbehalfProfile prefix='onbehalf'}
      </div>
    {/if}

    {if $honoreeProfileFields|@count}
        <div class="crm-group honor_block-group">
            <div class="header-dark">
                {$soft_credit_type}
            </div>
            <div class="display-block">
                <div class="label-left crm-section honoree_profile-section">
                    <strong>{$honorName}</strong></br>
                    {include file="CRM/UF/Form/Block.tpl" fields=$honoreeProfileFields mode=8 prefix='honor'}
                </div>
            </div>
         </div>
    {/if}

    {if $customPre}
      <fieldset class="label-left crm-profile-view">
        {include file="CRM/UF/Form/Block.tpl" fields=$customPre}
      </fieldset>
    {/if}

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

    {if ( $contributeMode ne 'notify' and (!$is_pay_later or $isBillingAddressRequiredForPayLater) and $is_monetary and ( $amount GT 0 OR $minimum_fee GT 0 ) ) or $email }
        {if $contributeMode ne 'notify' and (!$is_pay_later or $isBillingAddressRequiredForPayLater) and $is_monetary and ( $amount GT 0 OR $minimum_fee GT 0 ) }
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
        {/if}
        {if $email}
            <div class="crm-group contributor_email-group">
                <div class="header-dark">
                    {ts}Your Email{/ts}
                </div>
                <div class="crm-section no-label contributor_email-section">
                  <div class="content">{$email}</div>
                  <div class="clear"></div>
                </div>
            </div>
        {/if}
    {/if}

    {* Show credit or debit card section for 'direct' mode, except for PayPal Express (detected because credit card number is empty) *}
    {if $contributeMode eq 'direct' and ! $is_pay_later and $is_monetary and ( $amount GT 0 OR $minimum_fee GT 0 )}
{crmRegion name="contribution-confirm-billing-block"}
       {if ($credit_card_number or $bank_account_number)}
        <div class="crm-group credit_card-group">
            {if $paymentFieldsetLabel}
              <div class="header-dark">
                {$paymentFieldsetLabel}
              </div>
            {/if}
            {if $paymentProcessor.payment_type == 2}
                <div class="display-block">
                    {ts}Account Holder{/ts}: {$account_holder}<br />
                    {ts}Bank Account Number{/ts}: {$bank_account_number}<br />
                    {ts}Bank Identification Number{/ts}: {$bank_identification_number}<br />
                    {ts}Bank Name{/ts}: {$bank_name}<br />
                </div>
                {if $contributeMode eq 'direct'}
                  <div class="crm-group debit_agreement-group">
                      <div class="header-dark">
                          {ts}Agreement{/ts}
                      </div>
                      <div class="display-block">
                          {ts}Your account data will be used to charge your bank account via direct debit. While submitting this form you agree to the charging of your bank account via direct debit.{/ts}
                      </div>
                  </div>
                {/if}
            {else}
                <div class="crm-section no-label credit_card_details-section">
                  <div class="content">{$credit_card_type}</div>
                  <div class="content">{$credit_card_number}</div>
                  <div class="content">{if $credit_card_exp_date}{ts}Expires{/ts}: {$credit_card_exp_date|truncate:7:''|crmDate}{/if}</div>
                  <div class="clear"></div>
                </div>
            {/if}
        </div>
      {/if}
{/crmRegion}
    {/if}

    {include file="CRM/Contribute/Form/Contribution/PremiumBlock.tpl" context="confirmContribution"}

    {if $customPost}
      <fieldset class="label-left crm-profile-view">
        {include file="CRM/UF/Form/Block.tpl" fields=$customPost}
      </fieldset>
    {/if}

    {if $contributeMode NEQ 'notify' and $is_monetary and ( $amount GT 0 OR $minimum_fee GT 0 ) } {* In 'notify mode, contributor is taken to processor payment forms next *}
    <div class="messages status continue_instructions-section">
        <p>
        {if $is_pay_later OR $amount LE 0.0}
            {ts 1=$button}Your transaction will not be completed until you click the <strong>%1</strong> button. Please click the button one time only.{/ts}
        {else}
            {ts 1=$button}Your contribution will not be completed until you click the <strong>%1</strong> button. Please click the button one time only.{/ts}
        {/if}
        </p>
    </div>
    {/if}

    <div id="crm-submit-buttons" class="crm-submit-buttons">
        {include file="CRM/common/formButtons.tpl" location="bottom"}
    </div>
</div>
