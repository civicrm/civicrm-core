<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns = "http://www.w3.org/1999/xhtml">
  <head>
    <meta http-equiv = "Content-Type" content="text/html; charset=UTF-8" />
      <title></title>
  </head>
  <body>
    <table style = "margin-top:2px;padding-left:7px;">
      <tr>
        <td><img src = "{$resourceBase}/i/civi99.png" height = "34px" width = "99px"></td>
      </tr>
    </table>
    <center>
      <table style = "padding-right:19px;font-family: Arial, Verdana, sans-serif;" width = "500" height = "100" border = "0" cellpadding = "2" cellspacing = "1">
  <tr>
    <td style = "padding-left:15px;" ><b><font size = "4" align = "center">INVOICE</font></b></td>
          <td colspan = "1"></td>
          <td style = "padding-left:70px;"><b><font size = "1" align = "center" >Invoice Date:</font></b></td>
          <td><font size = "1" align = "right">{$domain_organization}</font></td>
  </tr>
        <tr>
         {if $organization_name}
           <td style = "padding-left:17px;"><font size = "1" align = "center" >{$display_name}  ({$organization_name})</font></td>
         {else}
           <td style = "padding-left:15px;"><font size = "1" align = "center" >{$display_name}</font></td>
         {/if}
         <td colspan = "1"></td>
         <td style = "padding-left:70px;"><font size = "1" align = "right">{$invoice_date}</font></td>
   <td><font size = "1" align = "right">
  {if $domain_street_address }
    {$domain_street_address}
  {/if}
  {if $domain_supplemental_address_1 }{$domain_supplemental_address_1}{/if}</font></td>
  </tr>
        <tr>
          <td style = "padding-left:17px;"><font size = "1" align = "center">{$street_address}   {$supplemental_address_1}</font></td>
          <td colspan = "1"></td>
          <td style = "padding-left:70px;"><b><font size = "1" align = "right">Invoice Number:</font></b></td>
    <td ><font size = "1" align = "right">{if $domain_supplemental_address_2 }{$domain_supplemental_address_2}{/if}
    {if $domain_state }{$domain_state}{/if}</font></td>
        </tr>
        <tr>
          <td style = "padding-left:17px;"><font size = "1" align = "center">{$supplemental_address_2}  {$stateProvinceAbbreviation}</font></td>
          <td colspan="1"></td>
          <td style = "padding-left:70px;"><font size = "1" align = "right">{$invoice_id}</font></td>
    <td><font size = "1" align = "right">{if $domain_city}
    {$domain_city}
        {/if}
        {if $domain_postal_code }
    {$domain_postal_code}
        {/if}
    </font></td>
  </tr>
  <tr>
          <td style = "padding-left:17px;"><font size = "1" align = "right">{$city}  {$postal_code}</font></td>
          <td colspan="1"></td>
    <td height = "10" style = "padding-left:70px;"><b><font size = "1"align = "right">Reference:</font></b></td>
    <td><font size = "1" align = "right"> {if $domain_country}
    {$domain_country}
        {/if}</font></td>
  </tr>
  <tr>
    <td></td>
    <td></td>
    <td style = "padding-left:70px;"><font size = "1"align = "right">{$source}</font></td>
    <td><font size = "1" align = "right"> {if $domain_phone}{$domain_phone}{/if}</font> </td>
  </tr>
  <tr>
    <td></td>
    <td></td>
    <td></td>
    <td><font size = "1" align = "right"> {if $domain_email}
    {$domain_email}
         {/if}</font> </td>
  </tr>
      </table>
      <table style = "margin-top:75px;font-family: Arial, Verdana, sans-serif" width = "590" border = "0"cellpadding = "-5" cellspacing = "19" id = "desc">
  <tr>
          <td colspan = "2" {$valueStyle}>
            <table> {* FIXME: style this table so that it looks like the text version (justification, etc.) *}
              <tr>
                <th style = "padding-right:34px;text-align:left;font-weight:bold;width:200px;"><font size = "1">Description</font></th>
                <th style = "padding-left:34px;text-align:right;font-weight:bold;" ><font size = "1">Quantity</font></th>
                <th style = "padding-left:34px;text-align:right;font-weight:bold;"><font size = "1">Unit Price</font></th>
                <th style = "padding-left:34px;text-align:right;font-weight:bold;width:20px;"><font size = "1">{$taxTerm} </font></th>
                <th style = "padding-left:34px;text-align:right;font-weight:bold;"><font size = "1">Amount {$defaultCurrency}</font></th>
              </tr>
              {foreach from=$lineItem item=value key=priceset name=taxpricevalue}
    {if $smarty.foreach.taxpricevalue.index eq 0}
            <tr><td  colspan = "5" ><hr size="3" style = "color:#000;"></hr></td></tr>
    {else}
      <tr><td  colspan = "5" style = "color:#F5F5F5;"><hr></hr></td></tr>
    {/if}
                <tr>
                  <td style="text-align:left;" ><font size = "1">
      {if $value.html_type eq 'Text'}{$value.label}{else}{$value.field_title} - {$value.label}{/if} {if $value.description}<div>{$value.description|truncate:30:"..."}</div>{/if}
      </font></td>
      <td style = "padding-left:34px;text-align:right;"><font size = "1"> {$value.qty}</font></td>
      <td style = "padding-left:34px;text-align:right;"><font size = "1"> {$value.unit_price|crmMoney:$currency}</font></td>
        {if $value.tax_amount != ''}
          <td style = "padding-left:34px;text-align:right;width:20px;"><font size = "1"> {$value.tax_rate}%</font></td>
                    {else}
          <td style = "padding-left:34px;text-align:right;width:20px;"><font size = "1">No {$taxTerm}</font></td>
              {/if}
        <td style = "padding-left:34px;text-align:right;"><font size = "1">{$value.subTotal|crmMoney:$currency}</font></td>
    </tr>
              {/foreach}
          <tr><td  colspan = "5" style = "color:#F5F5F5;"><hr></hr></td></tr>
          <tr>
      <td colspan = "3"></td>
      <td style = "padding-left:20px;text-align:right;"><font size = "1">Sub Total</font></td>
      <td style = "padding-left:34px;text-align:right;"><font size = "1"> {$subTotal|crmMoney:$currency}</font></td>
    </tr>
    {foreach from = $dataArray item = value key = priceset}
            <tr>
        <td colspan = "3"></td>
        {if $priceset}
          <td style = "padding-left:20px;text-align:right;"><font size = "1"> TOTAL {$taxTerm} {$priceset}%</font></td>
          <td style = "padding-left:34px;text-align:right"><font size = "1" align = "right">{$value|crmMoney:$currency}</font> </td>
                    {elseif $priceset == 0}
                      <td style = "padding-left:20px;text-align:right;"><font size = "1">TOTAL NO {$taxTerm}</font></td>
                      <td style = "padding-left:34px;text-align:right"><font size = "1" align = "right">{$value|crmMoney:$currency}</font> </td>
                  </tr>
        {/if}
    {/foreach}
          <tr>
      <td colspan = "3"></td>
      <td colspan = "2"><hr></hr></td>
    </tr>

          <tr>
      <td colspan = "3"></td>
      <td style = "padding-left:20px;text-align:right;"><b><font size = "1">TOTAL {$defaultCurrency}</font></b></td>
      <td style = "padding-left:34px;text-align:right;"><font size = "1">{$amount|crmMoney:$currency}</font></td>
    </tr>

    {if $is_pay_later == 0}
            <tr>
        <td colspan = "3"></td>
        <td style = "padding-left:20px;text-align:right;"><font size = "1">
           {if $contribution_status_id == $refundedStatusId}
          LESS Amount Credited
           {else}
          LESS Amount Paid
           {/if}
        </font></td>
        <td style = "padding-left:34px;text-align:right;"><font size = "1">{$amount|crmMoney:$currency}</font></td>
      </tr>
            <tr>
        <td colspan = "3"></td>
        <td colspan = "2" ><hr></hr></td>
      </tr>
            <tr>
        <td colspan = "3"></td>
        <td style = "padding-left:20px;text-align:right;"><b><font size = "1">AMOUNT DUE: </font></b></td>
                    <td style = "padding-left:34px;text-align:right;"><b><font size = "1">{$amountDue|crmMoney:$currency}</font></b></td>                 <td style = "padding-left:34px;"><font size = "1" align = "right"></fonts></td>
      </tr>
    {/if}
    <br/><br/><br/>
          <tr>
      <td colspan = "3"></td>
    </tr>
          <tr>
      <td><b><font size = "1" align = "center">DUE DATE: {$dueDate}</font></b></td>
      <td colspan = "3"></td>
    </tr>
            </table>
          </td>
        </tr>
      </table>
      <table style = "margin-top:5px;padding-right:45px;">
        <tr>
          <td><img src = "{$resourceBase}/i/contribute/cut_line.png" height = "15" width = "630"></td>
        </tr>
      </table>
  <table style = "margin-top:6px;padding-right:20px;font-family: Arial, Verdana, sans-serif" width = "480" border = "0"cellpadding = "-5" cellspacing="19" id = "desc">
    <tr>
      <td width="60%"><b><font size = "4" align = "right">PAYMENT ADVICE</font></b> <br/><br/> <font size = "1" align = "right"><b>To: </b>      <div style="width:17em;word-wrap:break-word;">
    {$domain_organization} <br />
    {$domain_street_address} {$domain_supplemental_address_1} <br />
    {$domain_supplemental_address_2} {$domain_state} <br />
    {$domain_city} {$domain_postal_code} <br />
    {$domain_country} <br />
    {$domain_phone} <br />
    {$domain_email}</div>
    </font><br/><br/><font size="1" align="right">{$notes}</font>
            </td>
            <td width="40%">
        <table  cellpadding = "-10" cellspacing = "22"  align="right" >
    <tr>
            <td  colspan = "2"></td>
      <td><font size = "1" align = "right" style="font-weight:bold;">Customer: </font></td>
      <td ><font size = "1" align = "right">{$display_name}</font></td>
    </tr>
    <tr>
      <td colspan = "2"></td>
      <td><font size = "1" align = "right" style="font-weight:bold;">Invoice Number: </font></td>
      <td><font size = "1" align = "right">{$invoice_id}</font></td>
    </tr>
    <tr><td  colspan = "5"style = "color:#F5F5F5;"><hr></hr></td></tr>
    {if $is_pay_later == 1}
    <tr>
                  <td colspan = "2"></td>
                  <td><font size = "1" align = "right" style="font-weight:bold;">Amount Due:</font></td>
                  <td><font size = "1" align = "right" style="font-weight:bold;">{$amount|crmMoney:$currency}</font></td>
    </tr>
    {else}
    <tr>
      <td colspan = "2"></td>
      <td><font size = "1" align = "right" style="font-weight:bold;">Amount Due: </font></td>
      <td><font size = "1" align = "right" style="font-weight:bold;">{$amountDue|crmMoney:$currency}</font></td>
    </tr>
    {/if}
    <tr>
      <td colspan = "2"></td>
      <td><font size = "1" align = "right" style="font-weight:bold;">Due Date:  </font></td>
      <td><font size = "1" align = "right">{$dueDate}</font></td>
    </tr>
    <tr>
                  <td colspan = "5" style = "color:#F5F5F5;"><hr></hr></td>
                </tr>
        </table>
            </td>
    </tr>
  </table>


      {if $contribution_status_id == $refundedStatusId || $contribution_status_id == $cancelledStatusId}
    <table style = "margin-top:2px;padding-left:7px;page-break-before: always;">
      <tr>
        <td><img src = "{$resourceBase}/i/civi99.png" height = "34px" width = "99px"></td>
      </tr>
    </table>
    <center>

      <table style = "padding-right:19px;font-family: Arial, Verdana, sans-serif" width = "500" height = "100" border = "0" cellpadding = "2" cellspacing = "1">
  <tr>
          <td style = "padding-left:15px;" ><b><font size = "4" align = "center">CREDIT NOTE</font></b></td>
          <td colspan = "1"></td>
          <td style = "padding-left:70px;"><b><font size = "1" align = "right">Date:</font></b></td>
          <td><font size = "1" align = "right">{$domain_organization}</font></td>
  </tr>
        <tr>
         {if $organization_name}
           <td style = "padding-left:17px;"><font size = "1" align = "center">{$display_name}  ({$organization_name})</font></td>
           {else}
           <td style = "padding-left:17px;"><font size = "1" align = "center">{$display_name}</font></td>
         {/if}
         <td colspan = "1"></td>
         <td style = "padding-left:70px;"><font size = "1" align = "right">{$invoice_date}</font></td>
   <td ><font size = "1" align = "right">
  {if $domain_street_address }
    {$domain_street_address}
  {/if}
  {if $domain_supplemental_address_1 }
    {$domain_supplemental_address_1}
  {/if}</font></td>
  </tr>
        <tr>
          <td style = "padding-left:17px;"><font size = "1" align = "center">{$street_address}   {$supplemental_address_1}</font></td>
          <td colspan = "1"></td>
          <td style = "padding-left:70px;"><b><font size = "1" align = "right">Credit Note Number:</font></b></td>
          <td><font size = "1" align = "right">{if $domain_supplemental_address_2 }
    {$domain_supplemental_address_2}
        {/if}
        {if $domain_state }
    {$domain_state}
        {/if}
    </font></td>
        </tr>
        <tr>
          <td style = "padding-left:17px;"><font size = "1" align = "center">{$supplemental_address_2}  {$stateProvinceAbbreviation}</font></td>
          <td colspan="1"></td>
          <td style = "padding-left:70px;"><font size = "1" align = "right">{$creditnote_id}</font></td>
    <td ><font size = "1" align = "right">{if $domain_city}
     {$domain_city}
         {/if}
         {if $domain_postal_code }
     {$domain_postal_code}
         {/if}
    </font></td>
  </tr>
  <tr>
          <td style = "padding-left:17px;"><font size = "1" align = "right">{$city}  {$postal_code}</font></td>
          <td colspan="1"></td>
          <td height = "10" style = "padding-left:70px;"><b><font size = "1"align = "right">Reference:</font></b></td>
    <td><font size = "1" align = "right"> {if $domain_country}
    {$domain_country}
        {/if}</font></td>
  </tr>
  <tr>
          <td></td>
          <td></td>
          <td style = "padding-left:70px;"><font size = "1"align = "right">{$source}</font></td>
    <td><font size = "1" align = "right"> {if $domain_phone}
    {$domain_phone}
         {/if}</font> </td>
  </tr>
  <tr>
    <td></td>
    <td></td>
    <td></td>
    <td><font size = "1" align = "right"> {if $domain_email}
    {$domain_email}
        {/if}</font> </td>
  </tr>
      </table>

      <table style = "margin-top:75px;font-family: Arial, Verdana, sans-serif" width = "590" border = "0"cellpadding = "-5" cellspacing = "19" id = "desc">
  <tr>
          <td colspan = "2" {$valueStyle}>
            <table> {* FIXME: style this table so that it looks like the text version (justification, etc.) *}
              <tr>
                <th style = "padding-right:28px;text-align:left;font-weight:bold;width:200px;"><font size = "1">Description</font></th>
                <th style = "padding-left:28px;text-align:right;font-weight:bold;"><font size = "1">Quantity</font></th>
                <th style = "padding-left:28px;text-align:right;font-weight:bold;"><font size = "1">Unit Price</font></th>
                <th style = "padding-left:28px;text-align:right;font-weight:bold;"><font size = "1">{$taxTerm} </font></th>
                <th style = "padding-left:28px;text-align:right;font-weight:bold;"><font size = "1">Amount {$defaultCurrency}</font></th>
              </tr>
              {foreach from=$lineItem item=value key=priceset name=pricevalue}
    {if $smarty.foreach.pricevalue.index eq 0}
          <tr><td  colspan = "5" ><hr size="3" style = "color:#000;"></hr></td></tr>
    {else}
    <tr><td  colspan = "5" style = "color:#F5F5F5;"><hr></hr></td></tr>
    {/if}
                <tr>
                  <td style ="text-align:left;"  ><font size = "1">
      {if $value.html_type eq 'Text'}{$value.label}{else}{$value.field_title} - {$value.label}{/if} {if $value.description}<div>{$value.description|truncate:30:"..."}</div>{/if}
      </font></td>
      <td style = "padding-left:28px;text-align:right;"><font size = "1"> {$value.qty}</font></td>
      <td style = "padding-left:28px;text-align:right;"><font size = "1"> {$value.unit_price|crmMoney:$currency}</font></td>
        {if $value.tax_amount != ''}
          <td style = "padding-left:28px;text-align:right;"><font size = "1"> {$value.tax_rate}%</font></td>
                    {else}
          <td style = "padding-left:28px;text-align:right"><font size = "1" >No {$taxTerm}</font></td>
              {/if}
       <td style = "padding-left:28px;text-align:right;"><font size = "1" >{$value.subTotal|crmMoney:$currency}</font></td>
    </tr>
                {/foreach}
          <tr><td  colspan = "5" style = "color:#F5F5F5;"><hr></hr></td></tr>
          <tr>
      <td colspan = "3"></td>
      <td style = "padding-left:28px;text-align:right;"><font size = "1">Sub Total</font></td>
      <td style = "padding-left:28px;text-align:right;"><font size = "1"> {$subTotal|crmMoney:$currency}</font></td>
    </tr>
    {foreach from = $dataArray item = value key = priceset}
            <tr>
        <td colspan = "3"></td>
        {if $priceset}
          <td style = "padding-left:28px;text-align:right;"><font size = "1"> TOTAL {$taxTerm} {$priceset}%</font></td>
          <td style = "padding-left:28px;text-align:right;"><font size = "1" align = "right">{$value|crmMoney:$currency}</font> </td>
                    {elseif $priceset == 0}
                      <td style = "padding-left:28px;text-align:right;"><font size = "1">TOTAL NO {$taxTerm}</font></td>
                      <td style = "padding-left:28px;text-align:right;"><font size = "1" align = "right">{$value|crmMoney:$currency}</font> </td>
                  </tr>
        {/if}
    {/foreach}
          <tr>
      <td colspan = "3"></td>
      <td colspan = "2"><hr></hr></td>
    </tr>

          <tr>
      <td colspan = "3"></td>
      <td style = "padding-left:28px;text-align:right;"><b><font size = "1">TOTAL {$defaultCurrency}</font></b></td>
      <td style = "padding-left:28px;text-align:right;"><font size = "1">{$amount|crmMoney:$currency}</font></td>
    </tr>

    {if $is_pay_later == 0}
            <tr>
        <td colspan = "3"></td>
        <td style = "padding-left:28px;text-align:right;"><font size = "1" >LESS Credit to invoice(s)</font></td>
        <td style = "padding-left:28px;text-align:right;"><font size = "1">{$amount|crmMoney:$currency}</font></td>
      </tr>
            <tr>
        <td colspan = "3"></td>
        <td colspan = "2" ><hr></hr></td>
      </tr>
            <tr>
        <td colspan = "3"></td>
        <td style = "padding-left:28px;text-align:right;"><b><font size = "1">REMAINING CREDIT</font></b></td>
        <td style = "padding-left:28px;text-align:right;"><b><font size = "1">{$amountDue|crmMoney:$currency}</font></b></td>
        <td style = "padding-left:28px;"><font size = "1" align = "right"></fonts></td>
      </tr>
    {/if}
    <br/><br/><br/>
          <tr>
      <td colspan = "3"></td>
    </tr>
          <tr>
      <td></td>
      <td colspan = "3"></td>
    </tr>
            </table>
          </td>
        </tr>
      </table>

      <table style = "margin-top:5px;padding-right:45px;">
        <tr>
          <td><img src = "{$resourceBase}/i/contribute/cut_line.png" height = "15" width = "630"></td>
        </tr>
      </table>

  <table style = "margin-top:6px;padding-right:20px;font-family: Arial, Verdana, sans-serif" width = "507" border = "0"cellpadding = "-5" cellspacing="19" id = "desc">
    <tr>
      <td width="60%"><font size = "4" align = "right"><b>CREDIT ADVICE</b><br/><br /><div  style="font-size:10px;max-width:300px;">Please do not pay on this advice. Deduct the amount of this Credit Note
from your next payment to us</div><br/></font></td>
      <td width="40%">
              <table    align="right" >
    <tr>
            <td colspan = "2"></td>
      <td><font size = "1" align = "right" style="font-weight:bold;">Customer: </font></td>
      <td><font size = "1" align = "right" >{$display_name}</font></td>
    </tr>
    <tr>
      <td colspan = "2"></td>
      <td><font size = "1" align = "right" style="font-weight:bold;">Credit Note#: </font></td>
      <td><font size = "1" align = "right">{$creditnote_id}</font></td>
    </tr>
    <tr><td  colspan = "5"style = "color:#F5F5F5;"><hr></hr></td></tr>
    <tr>
                  <td colspan = "2"></td>
      <td><font size = "1" align = "right" style="font-weight:bold;">Credit Amount:</font></td>
      <td width='50px'><font size = "1" align = "right" style="font-weight:bold;">{$amount|crmMoney:$currency}</font></td>
    </tr>
              </table>
            </td>
    </tr>
  </table>
 {/if}
    </center>
  </body>
</html>
