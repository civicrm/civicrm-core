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
  {include file="CRM/Contribute/Form/Contribution/PreviewHeader.tpl"}
{/if}

<div class="crm-contribution-page-id-{$contributionPageID} crm-block crm-contribution-thankyou-form-block" data-page-id="{$contributionPageID}" data-page-template="thankyou">
  {if $thankyou_text}
    <div id="thankyou_text" class="crm-section thankyou_text-section">
      {$thankyou_text}
    </div>
  {/if}

  {* Show link to Tell a Friend (CRM-2153) *}
  {if $friendText}
    <div id="tell-a-friend" class="crm-section friend_link-section">
      <a href="{$friendURL}" title="{$friendText|escape:'html'}" class="button"><span><i class="crm-i fa-chevron-right" aria-hidden="true"></i> {$friendText}</span></a>
    </div>{if !$linkText}<br /><br />{/if}
  {/if}
  {* Add button for donor to create their own Personal Campaign page *}
  {if $linkText}
    <div class="crm-section create_pcp_link-section">
      <a href="{$linkTextUrl}" title="{$linkText|escape:'html'}" class="button"><span><i class="crm-i fa-chevron-right" aria-hidden="true"></i> {$linkText}</span></a>
    </div><br /><br />
  {/if}

  <div class="help">
    {* PayPal_Standard sets contribution_mode to 'notify'. We don't know if transaction is successful until we receive the IPN (payment notification) *}
    {if $is_pay_later}
      <div class="bold">{$pay_later_receipt}</div>
      {if $is_email_receipt}
        <div>
          {if $onBehalfEmail AND ($onBehalfEmail neq $email)}
            {ts 1=$email 2=$onBehalfEmail}An email confirmation with these payment instructions has been sent to %1 and to %2.{/ts}
          {else}
            {ts 1=$email}An email confirmation with these payment instructions has been sent to %1.{/ts}
          {/if}
        </div>
      {/if}
    {elseif $isPendingOutcome}
      <div>{ts 1=$paymentProcessorName|escape}Your contribution has been submitted to %1 for processing.{/ts}</div>
        {if $is_email_receipt}
      <div>
        {if $onBehalfEmail AND ($onBehalfEmail neq $email)}
          {ts 1=$email 2=$onBehalfEmail}An email receipt will be sent to %1 and to %2 once the transaction is processed successfully.{/ts}
        {else}
          {ts 1=$email}An email receipt will be sent to %1 once the transaction is processed successfully.{/ts}
        {/if}
      </div>
    {/if}
  {else}
    <div>{ts}Your transaction has been processed successfully.{/ts}</div>
      {if $is_email_receipt}
        <div>
          {if $onBehalfEmail AND ($onBehalfEmail neq $email)}
            {ts 1=$email 2=$onBehalfEmail}An email receipt has also been sent to %1 and to %2{/ts}
          {elseif $email}
            {ts 1=$email}An email receipt has also been sent to %1{/ts}
          {/if}
        </div>
      {/if}
  {/if}
  </div>
  <div class="spacer"></div>

  {include file="CRM/Contribute/Form/Contribution/MembershipBlock.tpl"}

  {if $amount GTE 0 OR $minimum_fee GTE 0 OR ($priceSetID and $lineItem)}
    <div class="crm-group amount_display-group">
      <div class="header-dark">
        {if !$membershipBlock AND $amount OR ($priceSetID and $lineItem)}{ts}Contribution Information{/ts}{else}{ts}Membership Fee{/ts}{/if}
      </div>

      <div class="display-block">
        {if $lineItem and $priceSetID}
          {if !$amount}{assign var="amount" value=0}{/if}
          {assign var="totalAmount" value=$amount}
          {include file="CRM/Price/Page/LineItem.tpl" context="Contribution" displayLineItemFinancialType=false pricesetFieldsCount=false currencySymbol='' hookDiscount=''}
        {elseif $membership_amount}
          {$membership_name} {ts}Membership{/ts}: <strong>{$membership_amount|crmMoney}</strong><br />
          {if $amount}
            {if !$is_separate_payment}
              {ts}Contribution Amount{/ts}: <strong>{$amount|crmMoney}</strong><br />
            {else}
              {ts}Additional Contribution{/ts}: <strong>{$amount|crmMoney}</strong><br />
            {/if}
          {/if}
          <strong> -------------------------------------------</strong><br />
          {ts}Total{/ts}: <strong>{$orderTotal|crmMoney}</strong><br />
        {else}
          {if $totalTaxAmount}
            {ts}Tax Amount{/ts}: <strong>{$totalTaxAmount|crmMoney}</strong><br />
          {/if}
          {if $installments}{ts}Installment Amount{/ts}{else}{ts}Amount{/ts}{/if}: <strong>{$amount|crmMoney:$currency}{if $amount_level} &ndash; {$amount_level}{/if}</strong>
        {/if}

        {if $receive_date}
          {ts}Date{/ts}: <strong>{$receive_date|crmDate}</strong><br />
        {/if}
        {if $trxn_id}
          {ts}Transaction #{/ts}: {$trxn_id}<br />
        {/if}
        {if $membership_trx_id}
          {ts}Membership Transaction #{/ts}: {$membership_trx_id}
        {/if}

        {* Recurring contribution / pledge information *}
        {if $is_recur}
          {if !empty($auto_renew)} {* Auto-renew membership confirmation *}
            {crmRegion name="contribution-thankyou-recur-membership"}
              <br />
                {if $frequency_interval > 1}
                  {* dev/translation#80 All 'every %1' strings are incorrectly using ts, but focusing on the most important one until we find a better fix. *}
                  {if $frequency_unit eq 'day'}
                    <strong>{ts 1=$frequency_interval 2=$frequency_unit}This membership will be renewed automatically every %1 days.{/ts}</strong>
                  {elseif $frequency_unit eq 'week'}
                    <strong>{ts 1=$frequency_interval 2=$frequency_unit}This membership will be renewed automatically every %1 weeks.{/ts}</strong>
                  {elseif $frequency_unit eq 'month'}
                    <strong>{ts 1=$frequency_interval 2=$frequency_unit}This membership will be renewed automatically every %1 months.{/ts}</strong>
                  {elseif $frequency_unit eq 'year'}
                    <strong>{ts 1=$frequency_interval 2=$frequency_unit}This membership will be renewed automatically every %1 years.{/ts}</strong>
                  {/if}
                {else}
                  {* dev/translation#80 All 'every %1' strings are incorrectly using ts, but focusing on the most important one until we find a better fix. *}
                  {if $frequency_unit eq 'day'}
                    <strong>{ts}This membership will be renewed automatically every day.{/ts}</strong>
                  {elseif $frequency_unit eq 'week'}
                    <strong>{ts}This membership will be renewed automatically every week.{/ts}</strong>
                  {elseif $frequency_unit eq 'month'}
                    <strong>{ts}This membership will be renewed automatically every month.{/ts}</strong>
                  {elseif $frequency_unit eq 'year'}
                    <strong>{ts}This membership will be renewed automatically every year.{/ts}</strong>
                  {/if}
                {/if}
              <div class="description crm-auto-renew-cancel-info">({ts}You will receive an email receipt which includes information about how to cancel the auto-renewal option.{/ts})</div>
            {/crmRegion}
          {else}
            {crmRegion name="contribution-thankyou-recur"}
              {if $installments > 1}
                {if $frequency_interval > 1}
                  {if $frequency_unit eq 'day'}
                    <p><strong>{ts 1=$frequency_interval 2=$frequency_unit 3=$installments}This recurring contribution will be automatically processed every %1 days for a total %3 installments (including this initial contribution).{/ts}</strong></p>
                  {elseif $frequency_unit eq 'week'}
                    <p><strong>{ts 1=$frequency_interval 2=$frequency_unit 3=$installments}This recurring contribution will be automatically processed every %1 weeks for a total %3 installments (including this initial contribution).{/ts}</strong></p>
                  {elseif $frequency_unit eq 'month'}
                    <p><strong>{ts 1=$frequency_interval 2=$frequency_unit 3=$installments}This recurring contribution will be automatically processed every %1 months for a total %3 installments (including this initial contribution).{/ts}</strong></p>
                  {elseif $frequency_unit eq 'year'}
                    <p><strong>{ts 1=$frequency_interval 2=$frequency_unit 3=$installments}This recurring contribution will be automatically processed every %1 years for a total %3 installments (including this initial contribution).{/ts}</strong></p>
                  {/if}
                {else}
                  <p><strong>{ts 1=$frequency_unit 2=$installments}This recurring contribution will be automatically processed every %1 for a total %2 installments (including this initial contribution).{/ts}</strong></p>
                {/if}
              {else}
                {if $frequency_interval > 1}
                  {if $frequency_unit eq 'day'}
                    <p><strong>{ts 1=$frequency_interval}This recurring contribution will be automatically processed every %1 days.{/ts}</strong></p>
                  {elseif $frequency_unit eq 'week'}
                    <p><strong>{ts 1=$frequency_interval}This recurring contribution will be automatically processed every %1 weeks.{/ts}</strong></p>
                  {elseif $frequency_unit eq 'month'}
                    <p><strong>{ts 1=$frequency_interval}This recurring contribution will be automatically processed every %1 months.{/ts}</strong></p>
                  {elseif $frequency_unit eq 'year'}
                    <p><strong>{ts 1=$frequency_interval}This recurring contribution will be automatically processed every %1 years.{/ts}</strong></p>
                  {/if}
                {else}
                  {* dev/translation#32 All 'every %1' strings are incorrectly using ts, but focusing on the most important one until we find a better fix. *}
                  {if $frequency_unit eq 'day'}
                    <p><strong>{ts}This contribution will be automatically processed every day.{/ts}</strong></p>
                  {elseif $frequency_unit eq 'week'}
                    <p><strong>{ts}This contribution will be automatically processed every week.{/ts}</strong></p>
                  {elseif $frequency_unit eq 'month'}
                    <p><strong>{ts}This contribution will be automatically processed every month.{/ts}</strong></p>
                  {elseif $frequency_unit eq 'year'}
                    <p><strong>{ts}This contribution will be automatically processed every year.{/ts}</strong></p>
                  {/if}
                {/if}
              {/if}
              {if $is_email_receipt}
                <p>
                  {ts}You will receive an email receipt which includes information about how to update or cancel this recurring contribution.{/ts}
                </p>
              {/if}
            {/crmRegion}
          {/if}
        {/if}

        {if $is_pledge}
          {if $pledge_frequency_interval GT 1}
            <p><strong>{ts 1=$pledge_frequency_interval 2=$pledge_frequency_unit 3=$pledge_installments}I pledge to contribute this amount every %1 %2s for %3 installments.{/ts}</strong></p>
          {else}
            <p><strong>{ts 1=$pledge_frequency_interval 2=$pledge_frequency_unit 3=$pledge_installments}I pledge to contribute this amount every %2 for %3 installments.{/ts}</strong></p>
          {/if}
          <p>
            {if $is_pay_later}
              {ts 1=$receiptFromEmail}We will record your initial pledge payment when we receive it from you. You will be able to modify or cancel future pledge payments at any time by logging in to your account or contacting us at %1.{/ts}
            {else}
              {ts 1=$receiptFromEmail}Your initial pledge payment has been processed. You will be able to modify or cancel future pledge payments at any time by contacting us at %1.{/ts}
            {/if}
            {if $max_reminders}
              {ts 1=$initial_reminder_day}We will send you a payment reminder %1 days prior to each scheduled payment date. The reminder will include a link to a page where you can make your payment online.{/ts}
            {/if}
          </p>
        {/if}
      </div>
    </div>
  {/if}

  {if $onbehalfProfile && $onbehalfProfile|@count}
    <div class="crm-group onBehalf_display-group label-left crm-profile-view">
      {include file="CRM/UF/Form/Block.tpl" fields=$onbehalfProfile prefix='onbehalf' hideFieldset=false}
     </div>
  {/if}

  {if $honoreeProfileFields && $honoreeProfileFields|@count}
    <div class="crm-group honor_block-group">
      <div class="header-dark">
        {$soft_credit_type}
      </div>
      <div class="display-block">
       <div class="label-left crm-section honoree_profile-section">
          <strong>{$honorName}</strong><br/>
          {include file="CRM/UF/Form/Block.tpl" fields=$honoreeProfileFields prefix='honor' hideFieldset=false}
        </div>
      </div>
   </div>
  {/if}

  {if $customPre}
    <fieldset class="label-left crm-profile-view">
      {include file="CRM/UF/Form/Block.tpl" fields=$customPre prefix=false hideFieldset=false}
    </fieldset>
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
  {if !$emailExists && $email}
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

  {if in_array('credit_card_number', $paymentFields) || in_array('bank_account_number', $paymentFields) && ($amount GT 0 OR $minimum_fee GT 0)}
    {crmRegion name="contribution-thankyou-billing-block"}
      <div class="crm-group credit_card-group">
        {if $paymentFieldsetLabel}
          <div class="header-dark">
            {$paymentFieldsetLabel}
          </div>
        {/if}
          {if in_array('bank_account_number', $paymentFields) && $bank_account_number}
          <div class="display-block">
            {ts}Account Holder{/ts}: {$account_holder}<br />
            {ts}Bank Identification Number{/ts}: {$bank_identification_number}<br />
            {ts}Bank Name{/ts}: {$bank_name}<br />
            {ts}Bank Account Number{/ts}: {$bank_account_number}<br />
          </div>
        {else}
          <div class="crm-section no-label credit_card_details-section">
            <div class="content">{$credit_card_type}</div>
            <div class="content">{$credit_card_number}</div>
            <div class="content">{if $credit_card_exp_date}{ts}Expires{/ts}: {$credit_card_exp_date|truncate:7:''|crmDate}{/if}</div>
            <div class="clear"></div>
          </div>
        {/if}
      </div>
    {/crmRegion}
  {/if}

  {include file="CRM/Contribute/Form/Contribution/PremiumBlock.tpl" context="thankContribution" showPremiumSelectionFields=false preview=false}

  {if $customPost}
    <fieldset class="label-left crm-profile-view">
      {include file="CRM/UF/Form/Block.tpl" fields=$customPost prefix=false hideFieldset=false}
    </fieldset>
  {/if}

  <div id="thankyou_footer" class="contribution_thankyou_footer-section">
    <p>
      {$thankyou_footer}
    </p>
  </div>
  {if $isShare}
    {capture assign=contributionUrl}{crmURL p='civicrm/contribute/transact' q="$qParams" a=1 fe=1 h=1}{/capture}
    {include file="CRM/common/SocialNetwork.tpl" url=$contributionUrl title=false pageURL=$contributionUrl emailMode=false}
  {/if}
</div>
