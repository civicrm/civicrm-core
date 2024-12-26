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
{capture assign=emptyBlockStyle}style="padding: 10px; border-bottom: 1px solid #999;background-color: #f7f7f7;"{/capture}
{capture assign=emptyBlockValueStyle}style="padding: 10px; border-bottom: 1px solid #999;"{/capture}

  <!-- BEGIN HEADER -->
    {* To modify content in this section, you can edit the Custom Token named "Message Header". See also: https://docs.civicrm.org/user/en/latest/email/message-templates/#modifying-system-workflow-message-templates *}
    {site.message_header}
  <!-- END HEADER -->

  <!-- BEGIN CONTENT -->

  <table id="crm-event_receipt" style="font-family: Arial, Verdana, sans-serif; text-align: left; width:100%; max-width:700px; padding:0; margin:0; border:0px;">
  <tr>
    <td>
      {assign var="greeting" value="{contact.email_greeting_display}"}{if $greeting}<p>{$greeting},</p>{/if}
      {if {financial_trxn.total_amount|raw} < 0}
        <p>{ts}A refund has been issued based on changes in your registration selections.{/ts}</p>
      {else}
        <p>{ts}Below you will find a receipt for this payment.{/ts}</p>
        {if !{contribution.balance_amount|boolean}}
          <p>{ts}Thank you for completing this contribution.{/ts}</p>
        {/if}
      {/if}
    </td>
  </tr>
  <tr>
   <td>
    <table style="border: 1px solid #999; margin: 1em 0em 1em; border-collapse: collapse; width:100%;">
      {if {financial_trxn.total_amount|raw} < 0}
      <tr>
        <th {$headerStyle}>{ts}Refund Details{/ts}</th>
      </tr>
      <tr>
        <td {$labelStyle}>
        {ts}This Refund Amount{/ts}
        </td>
        <td {$valueStyle}>
          {financial_trxn.total_amount}
        </td>
      </tr>
    {else}
      <tr>
        <th {$headerStyle}>{ts}Payment Details{/ts}</th>
      </tr>
      <tr>
        <td {$labelStyle}>
        {ts}This Payment Amount{/ts}
        </td>
        <td {$valueStyle}>
        {financial_trxn.total_amount}
        </td>
      </tr>
    {/if}
    {if {financial_trxn.trxn_date|boolean}}
      <tr>
        <td {$labelStyle}>
        {ts}Transaction Date{/ts}
        </td>
        <td {$valueStyle}>
         {financial_trxn.trxn_date}
        </td>
      </tr>
    {/if}
    {if {financial_trxn.trxn_id|boolean}}
      <tr>
        <td {$labelStyle}>
        {ts}Transaction #{/ts}
        </td>
        <td {$valueStyle}>
          {financial_trxn.trxn_id}
        </td>
      </tr>
    {/if}
    {if {financial_trxn.payment_instrument_id|boolean}}
      <tr>
        <td {$labelStyle}>
        {ts}Paid By{/ts}
        </td>
        <td {$valueStyle}>
          {financial_trxn.payment_instrument_id:label}
        </td>
      </tr>
    {/if}
    {if {financial_trxn.check_number|boolean}}
      <tr>
        <td {$labelStyle}>
        {ts}Check Number{/ts}
        </td>
        <td {$valueStyle}>
          {financial_trxn.check_number}
        </td>
      </tr>
    {/if}

  <tr>
    <th {$headerStyle}>{ts}Contribution Details{/ts}</th>
  </tr>
  {if {contribution.total_amount|boolean}}
  <tr>
    <td {$labelStyle}>
      {ts}Total Fee{/ts}
    </td>
    <td {$valueStyle}>
      {contribution.total_amount}
    </td>
  </tr>
  {/if}
  {if {contribution.paid_amount|boolean}}
  <tr>
    <td {$labelStyle}>
      {ts}Total Paid{/ts}
    </td>
    <td {$valueStyle}>
      {contribution.paid_amount}
    </td>
  </tr>
  {/if}
  {if {contribution.balance_amount|boolean}}
  <tr>
    <td {$labelStyle}>
      {ts}Balance Owed{/ts}
    </td>
    <td {$valueStyle}>
      {contribution.balance_amount}
    </td>
  </tr>
  {/if}
  </table>

  </td>
  </tr>
    <tr>
      <td>
  <table style="border: 1px solid #999; margin: 1em 0em 1em; border-collapse: collapse; width:100%;">
    {if {contribution.address_id.display|boolean}}
        <tr>
          <th {$headerStyle}>
              {ts}Billing Name and Address{/ts}
          </th>
        </tr>
        <tr>
          <td colspan="2" {$valueStyle}>
              {contribution.address_id.name}<br/>
              {contribution.address_id.display}
          </td>
        </tr>
      {/if}
    {if {financial_trxn.pan_truncation|boolean}}
      <tr>
        <th {$headerStyle}>
          {ts}Credit Card Information{/ts}
        </th>
      </tr>
      <tr>
        <td colspan="2" {$valueStyle}>
          {financial_trxn.card_type_id:label}<br />
          ************{financial_trxn.pan_truncation}<br />
        </td>
      </tr>
    {/if}
    {if {event.id|boolean}}
      <tr>
        <th {$headerStyle}>
          {ts}Event Information and Location{/ts}
        </th>
      </tr>
      <tr>
        <td colspan="2" {$valueStyle}>
          {event.event_title}<br />
          {event.start_date|crmDate}{if {event.end_date|boolean}}-{if '{event.end_date|crmDate:"%Y%m%d"}' === '{event.start_date|crmDate:"%Y%m%d"}'}{event.end_date|crmDate:"Time"}{else}{event.end_date}{/if}{/if}
        </td>
      </tr>

      {if {event.is_show_location|boolean}}
            <tr>
              <td colspan="2" {$valueStyle}>
                  {event.location}
              </td>
            </tr>
          {/if}
          {if {event.loc_block_id.phone_id.phone|boolean} || {event.loc_block_id.email_id.email|boolean}}
            <tr>
              <td colspan="2" {$labelStyle}>
                  {ts}Event Contacts:{/ts}
              </td>
            </tr>

             {if {event.loc_block_id.phone_id.phone|boolean}}
            <tr>
              <td {$labelStyle}>
                  {if {event.loc_block_id.phone_id.phone_type_id|boolean}}
                      {event.loc_block_id.phone_id.phone_type_id:label}
                  {else}
                      {ts}Phone{/ts}
                  {/if}
              </td>
              <td {$valueStyle}>
                  {event.loc_block_id.phone_id.phone} {if {event.loc_block_id.phone_id.phone_ext|boolean}}&nbsp;{ts}ext.{/ts} {event.loc_block_id.phone_id.phone_ext}{/if}
              </td>
            </tr>
          {/if}
             {if {event.loc_block_id.phone_2_id.phone|boolean}}
            <tr>
              <td {$labelStyle}>
                  {if {event.loc_block_id.phone_2_id.phone_type_id|boolean}}
                      {event.loc_block_id.phone_2_id.phone_type_id:label}
                  {else}
                      {ts}Phone{/ts}
                  {/if}
              </td>
              <td {$valueStyle}>
                  {event.loc_block_id.phone_2_id.phone} {if {event.loc_block_id.phone_2_id.phone_ext|boolean}}&nbsp;{ts}ext.{/ts} {event.loc_block_id.phone_2_id.phone_ext}{/if}
              </td>
            </tr>
          {/if}

              {if {event.loc_block_id.email_id.email|boolean}}
            <tr>
              <td {$labelStyle}>
                  {ts}Email{/ts}
              </td>
              <td {$valueStyle}>
                  {event.loc_block_id.email_id.email}
              </td>
            </tr>
          {/if}

              {if {event.loc_block_id.email_2_id.email|boolean}}
                <tr>
                  <td {$labelStyle}>
                      {ts}Email{/ts}
                  </td>
                  <td {$valueStyle}>
                      {event.loc_block_id.email_2_id.email}
                  </td>
                </tr>
              {/if}
            {/if}

          {/if}
        </table>
      </td>
    </tr>
  </table>
 </body>
</html>
