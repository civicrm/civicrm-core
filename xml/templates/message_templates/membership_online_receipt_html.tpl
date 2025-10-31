<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
 <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
 <title></title>
</head>
<body>

{capture assign=headerStyle}colspan="2" style="text-align: left; padding: 4px; border-bottom: 1px solid #999; background-color: #eee;"{/capture}
{capture assign=labelStyle}style="padding: 4px; border-bottom: 1px solid #999; background-color: #f7f7f7;"{/capture}
{capture assign=valueStyle}style="padding: 4px; border-bottom: 1px solid #999;"{/capture}

  <!-- BEGIN HEADER -->
    {* To modify content in this section, you can edit the Custom Token named "Message Header". See also: https://docs.civicrm.org/user/en/latest/email/message-templates/#modifying-system-workflow-message-templates *}
    {site.message_header}
  <!-- END HEADER -->

  <!-- BEGIN CONTENT -->

  <table id="crm-event_receipt" style="font-family: Arial, Verdana, sans-serif; text-align: left; width:100%; max-width:700px; padding:0; margin:0; border:0px;">
  <tr>
   <td>
     {assign var="greeting" value="{contact.email_greeting_display}"}{if $greeting}<p>{$greeting},</p>{/if}
     {if $userText}
       <p>{$userText}</p>
     {elseif {contribution.contribution_page_id.receipt_text|boolean}}
       <p>{contribution.contribution_page_id.receipt_text}</p>
     {/if}
    {if {contribution.balance_amount|boolean} && {contribution.is_pay_later|boolean}}
      <p>{contribution.contribution_page_id.pay_later_receipt}</p>
    {/if}

   </td>
  </tr>
  </table>
  <table style="width:100%; max-width:500px; border: 1px solid #999; margin: 1em 0em 1em; border-collapse: collapse;">
    {if {membership.id|boolean} && !$isShowLineItems}
      <tr>
       <th {$headerStyle}>
        {ts}Membership Information{/ts}
       </th>
      </tr>
      <tr>
       <td {$labelStyle}>
        {ts}Membership Type{/ts}
       </td>
       <td {$valueStyle}>
         {ts}{membership.membership_type_id:label}{/ts}
       </td>
      </tr>
      {if {membership.start_date|boolean}}
       <tr>
        <td {$labelStyle}>
         {ts}Membership Start Date{/ts}
        </td>
        <td {$valueStyle}>
          {membership.start_date}
        </td>
       </tr>
      {/if}
      {if {membership.end_date|boolean}}
       <tr>
        <td {$labelStyle}>
         {ts}Membership Expiration Date{/ts}
        </td>
        <td {$valueStyle}>
          {membership.end_date}
        </td>
       </tr>
      {/if}
    {/if}
    {if {contribution.total_amount|boolean}}
      <tr>
        <th {$headerStyle}>{ts}Membership Fee{/ts}</th>
      </tr>

      {if !$isShowLineItems && {contribution.total_amount|boolean}}
        {foreach from=$lineItems item=line}
          <tr>
            <td {$labelStyle}>
              {if $line.membership_type_id}
                {ts 1="{membership.membership_type_id:label}"}%1 Membership{/ts}
              {else}
                {ts}Contribution Amount{/ts}
              {/if}
            </td>
            <td {$valueStyle}>
              {$line.line_total_inclusive|crmMoney:'{contribution.currency}'}
            </td>
          </tr>
        {/foreach}
      {elseif $isShowLineItems}
        <tr>
          <td colspan="2" {$valueStyle}>
            <table>
              <tr>
                <th>{ts}Item{/ts}</th>
                <th>{ts}Fee{/ts}</th>
                {if $isShowTax && {contribution.tax_amount|boolean}}
                  <th>{ts}SubTotal{/ts}</th>
                  <th>{ts}Tax Rate{/ts}</th>
                  <th>{ts}Tax Amount{/ts}</th>
                  <th>{ts}Total{/ts}</th>
                {/if}
                <th>{ts}Membership Start Date{/ts}</th>
                <th>{ts}Membership Expiration Date{/ts}</th>
              </tr>
              {foreach from=$lineItems item=line}
                <tr>
                  <td>{$line.title}</td>
                  <td>
                    {$line.line_total|crmMoney}
                  </td>
                  {if $isShowTax && {contribution.tax_amount|boolean}}
                    <td>
                      {$line.line_total|crmMoney:'{contribution.currency}'}
                    </td>
                    {if $line.tax_rate || $line.tax_amount != ""}
                      <td>
                        {$line.tax_rate|string_format:"%.2f"}%
                      </td>
                      <td>
                        {$line.tax_amount|crmMoney:'{contribution.currency}'}
                      </td>
                    {else}
                      <td></td>
                      <td></td>
                    {/if}
                    <td>
                      {$line.line_total_inclusive|crmMoney:'{contribution.currency}'}
                    </td>
                  {/if}
                  <td>
                    {$line.membership.start_date|crmDate:"Full"}
                  </td>
                  <td>
                    {$line.membership.end_date|crmDate:"Full"}
                  </td>
                </tr>
              {/foreach}
            </table>
          </td>
        </tr>

        {if $isShowTax && {contribution.tax_amount|boolean}}
          <tr>
            <td {$labelStyle}>
                {ts}Amount Before Tax:{/ts}
            </td>
            <td {$valueStyle}>
                {contribution.tax_exclusive_amount}
            </td>
          </tr>
          {foreach from=$taxRateBreakdown item=taxDetail key=taxRate}
            <tr>
              <td {$labelStyle}>{if $taxRate == 0}{ts}No{/ts} {$taxTerm}{else} {$taxTerm} {$taxDetail.percentage}%{/if}</td>
              <td {$valueStyle}>{$taxDetail.amount|crmMoney:'{contribution.currency}'}</td>
            </tr>
          {/foreach}
        {/if}
      {/if}
      <tr>
        <td {$labelStyle}>
            {ts}Amount{/ts}
        </td>
        <td {$valueStyle}>
            {contribution.total_amount}
        </td>
      </tr>
    {/if}

    {if {contribution.receive_date|boolean}}
      <tr>
        <td {$labelStyle}>
          {ts}Date{/ts}
        </td>
        <td {$valueStyle}>
          {contribution.receive_date}
        </td>
      </tr>
    {/if}

    {if {contribution.trxn_id|boolean}}
      <tr>
       <td {$labelStyle}>
        {ts}Transaction #{/ts}
       </td>
       <td {$valueStyle}>
         {contribution.trxn_id}
       </td>
      </tr>
    {/if}

    {if {contribution.contribution_recur_id|boolean}}
      <tr>
        <td colspan="2" {$labelStyle}>
          {ts}This membership will be renewed automatically.{/ts}
          {if $cancelSubscriptionUrl}
            {ts 1=$cancelSubscriptionUrl}You can cancel the auto-renewal option by <a href="%1">visiting this web page</a>.{/ts}
          {/if}
        </td>
      </tr>
      {if $updateSubscriptionBillingUrl}
        <tr>
          <td colspan="2" {$labelStyle}>
            {ts 1=$updateSubscriptionBillingUrl}You can update billing details for this automatically renewed membership by <a href="%1">visiting this web page</a>.{/ts}
          </td>
        </tr>
      {/if}
    {/if}

    {if $honor_block_is_active}
      <tr>
        <th {$headerStyle}>
          {$soft_credit_type}
        </th>
      </tr>
      {foreach from=$honoreeProfile item=value key=label}
        <tr>
          <td {$labelStyle}>
            {$label}
          </td>
          <td {$valueStyle}>
            {$value}
          </td>
        </tr>
      {/foreach}
    {/if}

    {if !empty($pcpBlock)}
      <tr>
        <th {$headerStyle}>
          {ts}Personal Campaign Page{/ts}
        </th>
      </tr>
      <tr>
        <td {$labelStyle}>
          {ts}Display In Honor Roll{/ts}
        </td>
        <td {$valueStyle}>
          {if $pcp_display_in_roll}{ts}Yes{/ts}{else}{ts}No{/ts}{/if}
        </td>
      </tr>
      {if $pcp_roll_nickname}
        <tr>
          <td {$labelStyle}>
            {ts}Nickname{/ts}
          </td>
          <td {$valueStyle}>
            {$pcp_roll_nickname}
          </td>
        </tr>
      {/if}
      {if $pcp_personal_note}
        <tr>
          <td {$labelStyle}>
            {ts}Personal Note{/ts}
          </td>
          <td {$valueStyle}>
            {$pcp_personal_note}
          </td>
        </tr>
      {/if}
    {/if}

    {if !empty($onBehalfProfile)}
      <tr>
        <th {$headerStyle}>
          {$onBehalfProfile_grouptitle}
        </th>
      </tr>
      {foreach from=$onBehalfProfile item=onBehalfValue key=onBehalfName}
        <tr>
          <td {$labelStyle}>
            {$onBehalfName}
          </td>
          <td {$valueStyle}>
            {$onBehalfValue}
          </td>
        </tr>
      {/foreach}
    {/if}

    {if {contribution.address_id.display|boolean}}
      <tr>
        <th {$headerStyle}>
          {ts}Billing Address{/ts}
        </th>
      </tr>
      <tr>
        <td colspan="2" {$valueStyle}>
          {contribution.address_id.name}<br/>
          {contribution.address_id.display}
        </td>
      </tr>
    {/if}
    {if {contact.email_primary.email|boolean}}
      <tr>
        <th {$headerStyle}>
          {ts}Registered Email{/ts}
        </th>
      </tr>
      <tr>
        <td colspan="2" {$valueStyle}>
          {contact.email_primary.email}
        </td>
      </tr>
    {/if}

    {if !empty($credit_card_type)}
      <tr>
        <th {$headerStyle}>
          {ts}Credit Card Information{/ts}
         </th>
      </tr>
      <tr>
        <td colspan="2" {$valueStyle}>
          {$credit_card_type}<br />
          {$credit_card_number}<br />
          {ts}Expires{/ts}: {$credit_card_exp_date|truncate:7:''|crmDate}<br />
        </td>
      </tr>
    {/if}

    {if !empty($selectPremium)}
      <tr>
        <th {$headerStyle}>
          {ts}Premium Information{/ts}
        </th>
      </tr>
      <tr>
        <td colspan="2" {$labelStyle}>
          {$product_name}
        </td>
      </tr>
      {if $option}
        <tr>
          <td {$labelStyle}>
            {ts}Option{/ts}
          </td>
          <td {$valueStyle}>
            {$option}
          </td>
        </tr>
      {/if}
      {if $sku}
        <tr>
          <td {$labelStyle}>
            {ts}SKU{/ts}
          </td>
          <td {$valueStyle}>
            {$sku}
          </td>
        </tr>
      {/if}
      {if $start_date}
        <tr>
          <td {$labelStyle}>
            {ts}Start Date{/ts}
          </td>
          <td {$valueStyle}>
            {$start_date|crmDate}
          </td>
        </tr>
      {/if}
      {if $end_date}
        <tr>
          <td {$labelStyle}>
            {ts}End Date{/ts}
          </td>
          <td {$valueStyle}>
            {$end_date|crmDate}
          </td>
        </tr>
      {/if}
      {if !empty($contact_email) OR !empty($contact_phone)}
        <tr>
          <td colspan="2" {$valueStyle}>
            <p>{ts}For information about this premium, contact:{/ts}</p>
            {if !empty($contact_email)}
              <p>{$contact_email}</p>
            {/if}
            {if !empty($contact_phone)}
              <p>{$contact_phone}</p>
            {/if}
          </td>
        </tr>
      {/if}
      {if $is_deductible AND !empty($price)}
        <tr>
          <td colspan="2" {$valueStyle}>
            <p>{ts 1=$price|crmMoney}The value of this premium is %1. This may affect the amount of the tax deduction you can claim. Consult your tax advisor for more information.{/ts}</p>
         </td>
        </tr>
      {/if}
    {/if}

    {if !empty($customPre)}
      <tr>
       <th {$headerStyle}>
         {$customPre_grouptitle}
       </th>
      </tr>
      {foreach from=$customPre item=customValue key=customName}
        <tr>
          <td {$labelStyle}>
            {$customName}
          </td>
          <td {$valueStyle}>
            {$customValue}
          </td>
        </tr>
      {/foreach}
    {/if}

    {if !empty($customPost)}
      <tr>
        <th {$headerStyle}>
          {$customPost_grouptitle}
        </th>
      </tr>
      {foreach from=$customPost item=customValue key=customName}
        <tr>
          <td {$labelStyle}>
            {$customName}
          </td>
          <td {$valueStyle}>
            {$customValue}
          </td>
        </tr>
      {/foreach}
    {/if}

  </table>

</body>
</html>
