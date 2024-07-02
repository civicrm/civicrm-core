<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
        "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
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

    <table id="crm-membership_receipt" style="font-family: Arial, Verdana, sans-serif; text-align: left; width:100%; max-width:700px; padding:0; margin:0; border:0px;">
    <tr>
      <td>
        {assign var="greeting" value="{contact.email_greeting_display}"}{if $greeting}<p>{$greeting},</p>{/if}
        {if $userText}
          <p>{$userText}</p>
        {else}
          <p>{ts}Thank you for this contribution.{/ts}</p>
        {/if}
      </td>
    </tr>
    <tr>
      <td>
        <table style="border: 1px solid #999; margin: 1em 0em 1em; border-collapse: collapse; width:100%;">
          {if !$isShowLineItems}
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
                {membership.membership_type_id:name}
              </td>
            </tr>
          {/if}
          {if '{membership.status_id:name}' !== 'Cancelled'}
            {if !$isShowLineItems}
              <tr>
                <td {$labelStyle}>
                  {ts}Membership Start Date{/ts}
                </td>
                <td {$valueStyle}>
                  {membership.start_date|crmDate:"Full"}
                </td>
              </tr>
              <tr>
                <td {$labelStyle}>
                  {ts}Membership Expiration Date{/ts}
                </td>
                <td {$valueStyle}>
                    {membership.end_date|crmDate:"Full"}
                </td>
              </tr>
            {/if}
            {if {contribution.total_amount|boolean}}
              <tr>
                <th {$headerStyle}>
                  {ts}Membership Fee{/ts}
                </th>
              </tr>
              {if {contribution.financial_type_id|boolean}}
                <tr>
                  <td {$labelStyle}>
                    {ts}Financial Type{/ts}
                  </td>
                  <td {$valueStyle}>
                    {contribution.financial_type_id:label}
                  </td>
                </tr>
              {/if}

              {if $isShowLineItems}
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
              {if {contribution.tax_amount|boolean}}
                <tr>
                  <td {$labelStyle}>
                    {ts}Total Tax Amount{/ts}
                  </td>
                  <td {$valueStyle}>
                    {contribution.tax_amount}
                  </td>
                </tr>
              {/if}
              <tr>
                <td {$labelStyle}>
                  {ts}Amount{/ts}
                </td>
                <td {$valueStyle}>
                  {contribution.total_amount}
                </td>
              </tr>
              {if {contribution.receive_date|boolean}}
                <tr>
                  <td {$labelStyle}>
                    {ts}Contribution Date{/ts}
                  </td>
                  <td {$valueStyle}>
                    {contribution.receive_date}
                  </td>
                </tr>
              {/if}
              {if {contribution.payment_instrument_id|boolean} && {contribution.paid_amount|boolean}}
                <tr>
                  <td {$labelStyle}>
                    {ts}Paid By{/ts}
                  </td>
                  <td {$valueStyle}>
                      {contribution.payment_instrument_id:label}
                  </td>
                </tr>
                {if {contribution.check_number|boolean}}
                  <tr>
                    <td {$labelStyle}>
                      {ts}Check Number{/ts}
                    </td>
                    <td {$valueStyle}>
                      {contribution.check_number}
                    </td>
                  </tr>
                {/if}
              {/if}
            {/if}
          {/if}
        </table>
      </td>
    </tr>

    {if !empty($isPrimary)}
      <tr>
        <td>
          <table style="border: 1px solid #999; margin: 1em 0em 1em; border-collapse: collapse; width:100%;">

            {if !empty($billingName)}
              <tr>
                <th {$headerStyle}>
                  {ts}Billing Name and Address{/ts}
                </th>
              </tr>
              <tr>
                <td {$labelStyle}>
                  {$billingName}<br/>
                  {$address}
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
                <td {$valueStyle}>
                  {$credit_card_type}<br/>
                  {$credit_card_number}
                </td>
              </tr>
              <tr>
                <td {$labelStyle}>
                  {ts}Expires{/ts}
                </td>
                <td {$valueStyle}>
                  {$credit_card_exp_date|truncate:7:''|crmDate}
                </td>
              </tr>
            {/if}

          </table>
        </td>
      </tr>
    {/if}

    {if !empty($customValues)}
      <tr>
        <td>
          <table style="border: 1px solid #999; margin: 1em 0em 1em; border-collapse: collapse; width:100%;">
            <tr>
              <th {$headerStyle}>
                {ts}Membership Options{/ts}
              </th>
            </tr>
            {foreach from=$customValues item=value key=customName}
              <tr>
                <td {$labelStyle}>
                  {$customName}
                </td>
                <td {$valueStyle}>
                  {$value}
                </td>
              </tr>
            {/foreach}
          </table>
        </td>
      </tr>
    {/if}

  </table>

</body>
</html>
