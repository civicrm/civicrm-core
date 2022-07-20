<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
  <head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
      <title></title>
  </head>
  <body>
  <div style="padding-top:100px;margin-right:50px;border-style: none;">
    {if $config->empoweredBy}
      <table style="margin-top:5px;padding-bottom:50px;" cellpadding="5" cellspacing="0">
        <tr>
          <td><img src="{$resourceBase}/i/civi99.png" height="34px" width="99px"></td>
        </tr>
      </table>
    {/if}
      <table style="font-family: Arial, Verdana, sans-serif;" width="100%" height="100" border="0" cellpadding="5" cellspacing="0">
        <tr>
          <td width="30%"><b><font size="4" align="center">{ts}INVOICE{/ts}</font></b></td>
          <td width="50%" valign="bottom"><b><font size="1" align="center">{ts}Invoice Date:{/ts}</font></b></td>
          <td valign="bottom" style="white-space: nowrap"><b><font size="1" align="right">{domain.name}</font></b></td>
        </tr>
        <tr>
          <td><font size="1" align="center">{contact.display_name}{if '{contact.current_employer}'} ({contact.current_employer}){/if}</font></td>
          <td><font size="1" align="right">{$invoice_date}</font></td>
          <td style="white-space: nowrap">
            <font size="1" align="right">
              {domain.street_address}
              {domain.supplemental_address_1}
           </font>
          </td>
        </tr>
        <tr>
          <td><font size="1" align="center">{$street_address} {$supplemental_address_1}</font></td>
          <td><b><font size="1" align="right">{ts}Invoice Number:{/ts}</font></b></td>
          <td>
            <font size="1" align="right">
              {domain.supplemental_address_2}
              {domain.state_province_id:label}
           </font>
          </td>
        </tr>
        <tr>
          <td><font size="1" align="center">{$supplemental_address_2} {$stateProvinceAbbreviation}</font></td>
          <td><font size="1" align="right">{contribution.invoice_number}</font></td>
          <td style="white-space: nowrap">
            <font size="1" align="right">
              {domain.city}
              {domain.postal_code}
           </font>
          </td>
        </tr>
        <tr>
          <td><font size="1" align="right">{$city}  {$postal_code}</font></td>
          <td height="10"><b><font size="1" align="right">{ts}Reference:{/ts}</font></b></td>
          <td><font size="1" align="right">{domain.country_id:label}</font></td>
        </tr>
        <tr>
          <td><font size="1" align="right"> {$country}</font></td>
          <td><font size="1" align="right">{contribution.source}</font></td>
          <td valign="top" style="white-space: nowrap"><font size="1" align="right">{domain.email}</font> </td>
        </tr>
        <tr>
          <td></td>
          <td></td>
          <td valign="top"><font size="1" align="right">{domain.phone}</font> </td>
        </tr>
      </table>

             <table style="padding-top:75px;font-family: Arial, Verdana, sans-serif;" width="100%" border="0" cellpadding="5" cellspacing="0">
              <tr>
                <th style="text-align:left;font-weight:bold;width:100%"><font size="1">{ts}Description{/ts}</font></th>
                <th style="text-align:right;font-weight:bold;white-space: nowrap"><font size="1">{ts}Quantity{/ts}</font></th>
                <th style="text-align:right;font-weight:bold;white-space: nowrap"><font size="1">{ts}Unit Price{/ts}</font></th>
                <th style="text-align:right;font-weight:bold;white-space: nowrap"><font size="1">{domain.tax_term}</font></th>
                <th style="text-align:right;font-weight:bold;white-space: nowrap"><font size="1">{ts 1=$currency}Amount %1{/ts}</font></th>
              </tr>
              {foreach from=$lineItem item=value key=priceset name=taxpricevalue}
                {if $smarty.foreach.taxpricevalue.index eq 0}
                {else}
                {/if}
                <tr>
                  <td style="text-align:left;nowrap"><font size="1">
                    {if $value.html_type eq 'Text'}
                      {$value.label}
                    {else}
                      {$value.field_title} - {$value.label}
                    {/if}
                    {if $value.description}
                      <div>{$value.description|truncate:30:"..."}</div>
                    {/if}
                   </font>
                  </td>
                  <td style="text-align:right;"><font size="1">{$value.qty}</font></td>
                  <td style="text-align:right;"><font size="1">{$value.unit_price|crmMoney:$currency}</font></td>
                  {if $value.tax_amount != ''}
                    <td style="text-align:right;"><font size="1">{if $value.tax_rate}{$value.tax_rate}%{/if}</font></td>
                  {else}
                    <td style="text-align:right;"><font size="1">{if $taxTerm}{ts 1=$taxTerm}-{/ts}{/if}</font></td>
                  {/if}
                  <td style="text-align:right;"><font size="1">{$value.subTotal|crmMoney:$currency}</font></td>
                </tr>
              {/foreach}
              <tr>
                <td colspan="3"></td>
                <td style="text-align:right;"><font size="1">{ts}Sub Total{/ts}</font></td>
                <td style="text-align:right;"><font size="1">{$subTotal|crmMoney:$currency}</font></td>
              </tr>
              {if !empty($dataArray)}
              {foreach from=$dataArray item=value key=priceset}
                <tr>
                  <td colspan="3"></td>
                    {if $priceset}
                      <td style="text-align:right;white-space: nowrap"><font size="1">{if $taxTerm}{ts 1=$taxTerm 2=$priceset}TOTAL %1 %2%{/ts}{/if}</font></td>
                      <td style="text-align:right"><font size="1" align="right">{$value|crmMoney:$currency}</font> </td>
                    {elseif $priceset == 0}
                      <td style="text-align:right;white-space: nowrap"><font size="1">{if $taxTerm}{ts 1=$taxTerm}TOTAL %1{/ts}{/if}</font></td>
                      <td style="text-align:right"><font size="1" align="right">{$value|crmMoney:$currency}</font> </td>
                    {/if}
                </tr>
              {/foreach}
              {/if}
              <tr>
                <td colspan="3"></td>
                <td style="text-align:right;white-space: nowrap"><b><font size="1">{ts 1=$currency}TOTAL %1{/ts}</font></b></td>
                <td style="text-align:right;"><font size="1">{$amount|crmMoney:$currency}</font></td>
              </tr>
              <tr>
                <td colspan="3"></td>
                <td style="text-align:right;white-space: nowrap"><font size="1">
                  {if '{contribution.contribution_status_id:name}' == 'Refunded'}
                    {ts}Amount Credited{/ts}
                  {else}
                    {ts}Amount Paid{/ts}
                  {/if}
                 </font>
                </td>
                <td style="text-align:right;"><font size="1">{$amountPaid|crmMoney:$currency}</font></td>
              </tr>
              <tr>
                <td colspan="3"></td>
                <td colspan="2"><hr></hr></td>
              </tr>
              <tr>
                <td colspan="3"></td>
                <td style="text-align:right;white-space: nowrap" ><b><font size="1">{ts}AMOUNT DUE:{/ts}</font></b></td>
                <td style="text-align:right;"><b><font size="1">{$amountDue|crmMoney:$currency}</font></b></td>
              </tr>

              <br/><br/><br/>
              <tr>
                <td colspan="5"></td>
              </tr>
              {if '{contribution.contribution_status_id:name}' == 'Pending' && '{contribution.is_pay_later}' == 1}
                <tr>
                  <td colspan="3"><b><font size="1" align="center">{ts 1=$dueDate}DUE DATE: %1{/ts}</font></b></td>
                  <td colspan="2"></td>
                </tr>
              {/if}
            </table>
          </td>
        </tr>
      </table>

      {if '{contribution.contribution_status_id:name}' == 'Pending' && '{contribution.is_pay_later}' == 1}
        <table style="margin-top:5px;" width="100%" border="0" cellpadding="0" cellspacing="0">
          <tr>
            <td><img src="{$resourceBase}/i/contribute/cut_line.png" height="15"></td>
          </tr>
        </table>

        <table style="margin-top:5px;font-family: Arial, Verdana, sans-serif" width="100%" border="0" cellpadding="5" cellspacing="0" id="desc">
          <tr>
            <td width="60%"><b><font size="4" align="right">{ts}PAYMENT ADVICE{/ts}</font></b><br/><br/><font size="1" align="left"><b>{ts}To:{/ts}</b><div style="width:24em;word-wrap:break-word;">
              {domain.name}<br />
              {domain.street_address} {domain.supplemental_address_1}<br />
              {domain.supplemental_address_2} {domain.state_province_id:label}<br />
              {domain.city} {domain.postal_code}<br />
              {domain.country_id:label}<br />
              {domain.email}</div>
              {domain.phone}<br />
             </font><br/><br/><font size="1" align="left">{$notes}</font>
            </td>
            <td width="40%">
              <table cellpadding="5" cellspacing="0"  width="100%" border="0">
                <tr>
                  <td width="100%"><font size="1" align="right" style="font-weight:bold;">{ts}Customer:{/ts}</font></td>
                  <td style="white-space: nowrap"><font size="1" align="right">{contact.display_name}</font></td>
                </tr>
                <tr>
                  <td><font size="1" align="right" style="font-weight:bold;">{ts}Invoice Number:{/ts}</font></td>
                  <td><font size="1" align="right">{contribution.invoice_number}</font></td>
                </tr>
                <tr><td colspan="5" style="color:#F5F5F5;"><hr></td></tr>
                {if $is_pay_later == 1}
                  <tr>
                    <td><font size="1" align="right" style="font-weight:bold;">{ts}Amount Due:{/ts}</font></td>
                    <td><font size="1" align="right" style="font-weight:bold;">{$amount|crmMoney:$currency}</font></td>
                  </tr>
                {else}
                  <tr>
                    <td><font size="1" align="right" style="font-weight:bold;">{ts}Amount Due:{/ts}</font></td>
                    <td><font size="1" align="right" style="font-weight:bold;">{$amountDue|crmMoney:$currency}</font></td>
                  </tr>
                {/if}
                <tr>
                  <td><font size="1" align="right" style="font-weight:bold;">{ts}Due Date:{/ts}</font></td>
                  <td><font size="1" align="right">{$dueDate}</font></td>
                </tr>
                <tr>
                  <td colspan="5" style="color:#F5F5F5;"><hr></td>
                </tr>
              </table>
      {/if}

      {if '{contribution.contribution_status_id:name}' === 'Refunded' || '{contribution.contribution_status_id:name}' === 'Cancelled'}
      {if $config->empoweredBy}
        <table style="margin-top:2px;padding-left:7px;page-break-before: always;">
          <tr>
            <td><img src="{$resourceBase}/i/civi99.png" height="34px" width="99px"></td>
          </tr>
        </table>
      {/if}

      <table style="font-family: Arial, Verdana, sans-serif" width="100%" height="100" border="0" cellpadding="5" cellspacing="5">
        <tr>
          <td style="padding-left:15px;"><b><font size="4" align="center">{ts}CREDIT NOTE{/ts}</font></b></td>
          <td style="padding-left:30px;"><b><font size="1" align="right">{ts}Date:{/ts}</font></b></td>
          <td><font size="1" align="right">{domain.name}</font></td>
        </tr>
        <tr>
          <td style="padding-left:17px;"><font size="1" align="center">{contact.display_name}{if '{contact.current_employer}'} ({contact.current_employer}){/if}</font></td>
          <td style="padding-left:30px;"><font size="1" align="right">{$invoice_date}</font></td>
          <td>
            <font size="1" align="right">
              {domain.street_address}
              {domain.supplemental_address_1}
           </font>
          </td>
        </tr>
        <tr>
          <td style="padding-left:17px;"><font size="1" align="center">{$street_address}   {$supplemental_address_1}</font></td>
          <td style="padding-left:30px;"><b><font size="1" align="right">{ts}Credit Note Number:{/ts}</font></b></td>
          <td>
            <font size="1" align="right">
              {domain.supplemental_address_2}
              {domain.state_province_id:label}
           </font>
          </td>
        </tr>
        <tr>
          <td style="padding-left:17px;"><font size="1" align="center">{$supplemental_address_2}  {$stateProvinceAbbreviation}</font></td>
          <td style="padding-left:30px;"><font size="1" align="right">{contribution.creditnote_id}</font></td>
          <td>
            <font size="1" align="right">
              {domain.city}
              {domain.postal_code}
           </font>
          </td>
        </tr>
        <tr>
          <td style="padding-left:17px;"><font size="1" align="right">{$city}  {$postal_code}</font></td>
          <td height="10" style="padding-left:30px;"><b><font size="1" align="right">{ts}Reference:{/ts}</font></b></td>
          <td>
            <font size="1" align="right">
              {domain.country_id:label}
           </font>
          </td>
        </tr>
        <tr>
          <td></td>
          <td style="padding-left:30px;"><font size="1" align="right">{contribution.source}</font></td>
          <td>
            <font size="1" align="right">
              {domain.email}
           </font>
          </td>
        </tr>
        <tr>
          <td></td>
          <td></td>
          <td>
            <font size="1" align="right">
              {domain.phone}
           </font>
          </td>
        </tr>
      </table>

      <table style="margin-top:75px;font-family: Arial, Verdana, sans-serif" width="100%" border="0" cellpadding="5" cellspacing="5" id="desc">
        <tr>
          <td colspan="2">
            <table>
              <tr>
                <th style="padding-right:28px;text-align:left;font-weight:bold;width:200px;"><font size="1">{ts}Description{/ts}</font></th>
                <th style="padding-left:28px;text-align:right;font-weight:bold;"><font size="1">{ts}Quantity{/ts}</font></th>
                <th style="padding-left:28px;text-align:right;font-weight:bold;"><font size="1">{ts}Unit Price{/ts}</font></th>
                <th style="padding-left:28px;text-align:right;font-weight:bold;"><font size="1">{domain.tax_term}</font></th>
                <th style="padding-left:28px;text-align:right;font-weight:bold;"><font size="1">{ts 1=$currency}Amount %1{/ts}</font></th>
              </tr>
              {foreach from=$lineItem item=value key=priceset name=pricevalue}
                {if $smarty.foreach.pricevalue.index eq 0}
                  <tr><td colspan="5"><hr size="3" style="color:#000;"></hr></td></tr>
                {else}
                  <tr><td colspan="5" style="color:#F5F5F5;"><hr></hr></td></tr>
                {/if}
                <tr>
                  <td style ="text-align:left;"  >
                    <font size="1">
                      {if $value.html_type eq 'Text'}
                        {$value.label}
                      {else}
                        {$value.field_title} - {$value.label}
                      {/if}
                      {if $value.description}
                        <div>{$value.description|truncate:30:"..."}</div>
                      {/if}
                   </font>
                  </td>
                  <td style="padding-left:28px;text-align:right;"><font size="1">{$value.qty}</font></td>
                  <td style="padding-left:28px;text-align:right;"><font size="1">{$value.unit_price|crmMoney:$currency}</font></td>
                  {if $value.tax_amount != ''}
                    <td style="padding-left:28px;text-align:right;"><font size="1">{if $value.tax_rate}{$value.tax_rate}%{/if}</font></td>
                  {else}
                    <td style="padding-left:28px;text-align:right"><font size="1">{if $taxTerm}{ts 1=$taxTerm}No %1{/ts}{/if}</font></td>
                  {/if}
                  <td style="padding-left:28px;text-align:right;"><font size="1">{$value.subTotal|crmMoney:$currency}</font></td>
                </tr>
              {/foreach}
              <tr><td colspan="5" style="color:#F5F5F5;"><hr></hr></td></tr>
              <tr>
                <td colspan="3"></td>
                <td style="padding-left:28px;text-align:right;"><font size="1">{ts}Sub Total{/ts}</font></td>
                <td style="padding-left:28px;text-align:right;"><font size="1">{$subTotal|crmMoney:$currency}</font></td>
              </tr>
              {if !empty($dataArray)}
              {foreach from=$dataArray item=value key=priceset}
                <tr>
                  <td colspan="3"></td>
                  {if $priceset}
                    <td style="padding-left:28px;text-align:right;"><font size="1">{if $taxTerm}{ts 1=$taxTerm 2=$priceset}TOTAL %1 %2%{/ts}{/if}</font></td>
                    <td style="padding-left:28px;text-align:right;"><font size="1" align="right">{$value|crmMoney:$currency}</font> </td>
                  {elseif $priceset == 0}
                    <td style="padding-left:28px;text-align:right;"><font size="1">{if $taxTerm}{ts 1=$taxTerm}TOTAL NO %1{/ts}{/if}</font></td>
                    <td style="padding-left:28px;text-align:right;"><font size="1" align="right">{$value|crmMoney:$currency}</font> </td>
                  {/if}
                </tr>
              {/foreach}
              {/if}
              <tr>
                <td colspan="3"></td>
                <td colspan="2"><hr></hr></td>
              </tr>
              <tr>
                <td colspan="3"></td>
                <td style="padding-left:28px;text-align:right;"><b><font size="1">{ts 1=$currency}TOTAL %1{/ts}</font></b></td>
                <td style="padding-left:28px;text-align:right;"><font size="1">{$amount|crmMoney:$currency}</font></td>
              </tr>
              {if '{contribution.is_pay_later}' == 0}
                <tr>
                  <td colspan="3"></td>
                  <td style="padding-left:28px;text-align:right;"><font size="1">{ts}LESS Credit to invoice(s){/ts}</font></td>
                  <td style="padding-left:28px;text-align:right;"><font size="1">{$amount|crmMoney:$currency}</font></td>
                </tr>
                <tr>
                  <td colspan="3"></td>
                  <td colspan="2"><hr></hr></td>
                </tr>
                <tr>
                  <td colspan="3"></td>
                  <td style="padding-left:28px;text-align:right;"><b><font size="1">{ts}REMAINING CREDIT{/ts}</font></b></td>
                  <td style="padding-left:28px;text-align:right;"><b><font size="1">{$amountDue|crmMoney:$currency}</font></b></td>
                  <td style="padding-left:28px;"><font size="1" align="right"></font></td>
                </tr>
              {/if}
              <br/><br/><br/>
              <tr>
                <td colspan="3"></td>
              </tr>
              <tr>
                <td></td>
                <td colspan="3"></td>
              </tr>
            </table>
          </td>
        </tr>
      </table>

      <table width="100%" style="margin-top:5px;padding-right:45px;">
        <tr>
          <td><img src="{$resourceBase}/i/contribute/cut_line.png" height="15" width="100%"></td>
        </tr>
      </table>

      <table style="margin-top:6px;font-family: Arial, Verdana, sans-serif" width="100%" border="0" cellpadding="5" cellspacing="5" id="desc">
        <tr>
          <td width="60%"><font size="4" align="right"><b>{ts}CREDIT ADVICE{/ts}</b><br/><br /><div style="font-size:10px;max-width:300px;">{ts}Please do not pay on this advice. Deduct the amount of this Credit Note from your next payment to us{/ts}</div><br/></font></td>
          <td width="40%">
            <table align="right">
              <tr>
                <td colspan="2"></td>
                <td><font size="1" align="right" style="font-weight:bold;">{ts}Customer:{/ts}</font></td>
                <td><font size="1" align="right">{contact.display_name}</font></td>
              </tr>
              <tr>
                <td colspan="2"></td>
                <td><font size="1" align="right" style="font-weight:bold;">{ts}Credit Note#:{/ts}</font></td>
                <td><font size="1" align="right">{contribution.creditnote_id}</font></td>
              </tr>
              <tr><td colspan="5"style="color:#F5F5F5;"><hr></hr></td></tr>
              <tr>
                <td colspan="2"></td>
                <td><font size="1" align="right" style="font-weight:bold;">{ts}Credit Amount:{/ts}</font></td>
                <td width='50px'><font size="1" align="right" style="font-weight:bold;">{$amount|crmMoney:$currency}</font></td>
              </tr>
            </table>
          </td>
        </tr>
      </table>
    {/if}

  </div>
  </body>
</html>
