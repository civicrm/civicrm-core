<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns = "http://www.w3.org/1999/xhtml">
  <head>
    <meta http-equiv = "Content-Type" content="text/html; charset=UTF-8" />
      <title></title>
  </head>
  <body>
    <table style = "margin-top:2px;padding-left:29px;">
      <tr>
        <td><img src = "{$imageUploadURL}/logo.png" height = "39" width = "108"></td>
	</tr>
    </table>
    <center>
      <br/><br/>
      <table style = "padding-right:19px;" width = "500" height = "100" border = "0" cellpadding = "2" cellspacing = "1">
	<tr>
          {if $contribution_status_id == $refundedStatusId}
            <td style = "padding-left:35px;" ><b><font size = "3" align = "center">CREDIT NOTE</font></b></td>
            {else}
              <td style = "padding-left:35px;" ><b><font size = "3" align = "center">TAX INVOICE</font></b></td>
          {/if}
          <td colspan = "1"></td>
          {if $contribution_status_id == $refundedStatusId}
            <td style = "padding-left:88px;"><b><font size = "1" align = "right">Date:</font></b></td> 
            {else}
              <td style = "padding-left:88px;"><b><font size = "1" align = "right">Invoice Date:</font></b></td> 
          {/if}
          <td ><b><font size = "1" align = "right">{$organization_name}</font></b></td>       
	</tr>
        <tr>
         {if $organization_name}
           <td style = "padding-left:48px;"><font size = "1" align = "center">{$display_name}  ({$organization_name})</font></td>
           {else}
             <td style = "padding-left:48px;"><font size = "1" align = "center">{$display_name}</font></td>
         {/if}
         <td colspan = "1"></td>
         <td style = "padding-left:88px;"><font size = "1" align = "right">{$invoice_date}</font></td>  
	</tr>
        <tr>
          <td style = "padding-left:48px;"><font size = "1" align = "center">{$street_address}   {$supplemental_address_1}</font></td>
          <td colspan = "1"></td>
          {if $contribution_status_id == $refundedStatusId}
            <td style = "padding-left:88px;"><b><font size = "1" align = "right">Credit Note Number:</font></b></td>     
            {else}
              <td style = "padding-left:88px;"><b><font size = "1" align = "right">Incoice Number:</font></b></td> 
          {/if}
        </tr>
        <tr>
          <td style = "padding-left:48px;"><font size = "1" align = "center">{$supplemental_address_2}  {$stateProvinceAbbreviation}</font></td>
          <td colspan="1"></td>
          <td style = "padding-left:88px;"><font size = "1" align = "right">{$invoice_id}</font></td>
	</tr>
	<tr>
          <td style = "padding-left:48px;"><font size = "1" align = "right">{$city}  {$postal_code}</font></td>    
          <td colspan="1"></td>
          <td height = "10" style = "padding-left:88px;"><b><font size = "1"align = "right">Reference: {$title}</font></b></td>  
	</tr>
      </table>

      <table style = "margin-top:75px;padding-right:20px;" width = "590" border = "0"cellpadding = "-5" cellspacing = "19" id = "desc">
	<tr>
          <td colspan = "2" {$valueStyle}>
            <table> {* FIXME: style this table so that it looks like the text version (justification, etc.) *}
              <tr>
                <th style = "padding-right:30px;"><center><b><font size = "1" align = "center">Description</font></b></center></th>
                <th style = "padding-left:30px;"><center><b><font size = "1" align = "center">Quantity</font> </b></center></th>
                <th style = "padding-left:30px;"><center><b><font size = "1" align = "center">Unit Price</font></b></center></th>
                <th style = "padding-left:33px;"><center><b><font size = "1" align = "center">VAT </font></b></center></th>
                <th style = "padding-left:30px;"><center><b><font size = "1" align = "center">Amount {$defaultCurrency}</font></b></center></th>
              </tr>
              {foreach from=$lineItem item=value key=priceset}
	        <tr><td  colspan = "5" style = "color:#F5F5F5;"><hr></hr></td></tr>
                <tr>
                  <td><font size = "1" align = "center">
		  {if $value.html_type eq 'Text'}{$value.label}{else}{$value.field_title} - {$value.label}{/if} {if $value.description}<div>{$value.description|truncate:30:"..."}</div>{/if}
		  </font></td>
		  <td style = "padding-left:52px;"><font size = "1" align = "center"> {$value.qty}</font></td>
		  <td style = "padding-left:52px;"><font size = "1" align = "center"> {$value.unit_price|crmMoney:$currency}</font></td>
		    {if $value.tax_rate}
		      <td style = "padding-left:52px;"><center><font size = "1" align = "center"> {$value.tax_rate}%</font></center></td>
		      {elseif $value.tax_amount != ''}
		        <td style = "padding-left:52px;"><center><font size = "1" align = "center">VAT (exempt)</font></center></td>
                        {else}
		          <td style = "padding-left:52px;"><center><font size = "1" align = "center"></font>No VAT</center></td>
	            {/if}
		    <td style = "padding-left:52px;"><font size = "1" align = "center">{$value.subTotal|crmMoney:$currency}</font></td>
		</tr>
                {/foreach}
	        <tr><td  colspan = "5" style = "color:#F5F5F5;"><hr></hr></td></tr>
	        <tr>
		  <td colspan = "3"></td>
		  <td style = "padding-left:52px;"><font size = "1" align = "right">Sub Total</font></td>
		  <td style = "padding-left:52px;"><font size = "1" align = "center"> {$subTotal|crmMoney:$currency}</font></td>
		</tr>
		{foreach from = $dataArray item = value key = priceset}
	          <tr>
		    <td colspan = "3"></td>
		    {if $priceset}
		      <td style = "padding-left:52px;"><font size = "1" align="right"> TOTAL VAT {$priceset}%</font></td>    
		      <td style = "padding-left:52px;"><font size = "1" align = "right">{$value|crmMoney:$currency}</font> </td>
                      {elseif $priceset == 0}
                        <td style = "padding-left:52px;"><font size = "1" align = "right">TOTAL NO VAT</font></td>
                        <td style = "padding-left:52px;"><font size = "1" align = "right">{$value|crmMoney:$currency}</font> </td>
                  </tr>
		    {/if}
		{/foreach}
	        <tr>
		  <td colspan = "3"></td>
		  <td colspan = "2"><hr></hr></td>
		</tr>

	        <tr>
		  <td colspan = "3"></td>
		  <td style = "padding-left:52px;"><b><font size = "1" align = "right">TOTAL {$defaultCurrency}</font></b></td>
		  <td style = "padding-left:52px;"><font size = "1" align = "center">{$amount|crmMoney:$currency}</font></td>     
		</tr>

		{if $is_pay_later == 0}
	          <tr>
		    <td colspan = "3"></td>
		    {if $contribution_status_id == $refundedStatusId}
		      <td style = "padding-left:52px;"><font size = "1" align = "right">LESS Credit to invoice(s)</font></td>
		      {else}
		        <td style = "padding-left:52px;"><font size = "1" align = "righ">LESS Amount Paid</font></td>
		    {/if}
		    <td style = "padding-left:52px;"><font size = "1" align = "right">{$amount|crmMoney:$currency}</font></td>
		  </tr>
	          <tr>
		    <td colspan = "3"></td>
		    <td colspan = "2" ><hr></hr></td>
		  </tr>
	          <tr>
		    <td colspan = "3"></td>
		    {if $contribution_status_id == $refundedStatusId}
		      <td style = "padding-left:52px;"><b><font size = "1" align = "center">REMAINING CREDIT</font></b></td>
		      <td style = "padding-left:52px;"><b><font size = "1" align = "center">{$amountDue|crmMoney:$currency}</font></b></td>
		      {else}
		        <td style = "padding-left:52px;"><b><font size = "1" align = "center">AMOUNT DUE: </font></b></td> 
                        <td style = "padding-left:52px;"><b><font size = "1" align = "center">{$amountDue|crmMoney:$currency}</font></b></td> 
                    {/if}
		    <td style = "padding-left:52px;"><font size = "1" align = "right"></fonts></td>
		  </tr>
		{/if}
		<br/><br/><br/>
	        <tr>
		  <td colspan = "3"></td>
		</tr>
	        <tr>
		  {if $contribution_status_id == $refundedStatusId}
		    <td ></td>
		    {else}
		      <td><b><font size = "1" align = "center">DUE DATE: {$dueDate}</font></b></td>
                  {/if}
		  <td colspan = "3"></td>
		</tr>
            </table>
          </td>
        </tr>
      </table>

      <table style = "margin-top:5px;padding-right:45px;">
        <tr>
          <td><img src = "{$imageUploadURL}/img.png" height = "15" width = "630"></td>
        </tr>
      </table>

      {if $contribution_status_id == $refundedStatusId}
        <table  style = "margin-top:6px;padding-right:30px;" width = "585" border = "0" cellpadding = "-10" cellspacing = "19" id = "desc">
	  <tr>
	    <td ><b><font size = "4" align = "right">CREDIT ADVICE<br/><br/><font size = "1" >{$notes}</font></font></b></td>
	    <td>
              <table style = "margin-top:0px;margin-right:-2;padding-right:45px;" cellpadding = "-10" cellspacing = "22"   >
		<tr>
	          <td colspan = "2"></td>
		  <td><font size = "1" align = "right">Customer: </font></td>
		  <td><font size = "1" align = "right">{$display_name}</font></td>   
		</tr>
		<tr>
		  <td colspan = "2"></td>
		  <td><font size = "1" align = "right">Credit Note#: </font></td>
		  <td><font size = "1" align = "right">{$invoice_id}</font></td>   
		</tr>
		<tr><td  colspan = "5"style = "color:#F5F5F5;"><hr></hr></td></tr>
		<tr>
                  <td colspan = "2"></td>
		  <td><font size = "1" align = "right">Credit Amount:</font></td> 
		  <td><font size = "1" align = "right">{$amount|crmMoney:$currency}</font></td> 
		</tr>
              </table>
            </td>
	  </tr>
	</table>
      {else}
	<table style = "margin-top:6px;padding-right:30px;" width = "585" border = "0"cellpadding = "-10" cellspacing="19" id = "desc">
	  <tr>
	    <td><b><font size = "4" align = "right">PAYMENT ADVICE</font><b/> <br/><br/> <b><font size = "1" align = "right">To:            
              {$organization_name}</font><b/><br/><br/><b><font size="1" align="right">{$notes}</font><b/>
            </td>
            <td>
	      <table style = "margin-right:-2;" cellpadding = "-10" cellspacing = "22">
		<tr>
	          <td  colspan = "2"></td>
		  <td><font size = "1" align = "right">Customer: </font></td>
		  <td><font size = "1" align = "right">{$display_name}</font></td>   
		</tr>
		<tr>
		  <td colspan = "2"></td>
		  <td><font size = "1" align = "right">Invoice Number: </font></td>
		  <td><font size = "1" align = "right">{$invoice_id}</font></td>   
		</tr>
		<tr><td  colspan = "5"style = "color:#F5F5F5;"><hr></hr></td></tr>
		{if $is_pay_later == 1}       
		<tr>
                  <td colspan = "2"></td>
                  <td><font size = "1" align = "right">Amount Due:</font></td> 
                  <td><font size = "1" align = "right">{$amount|crmMoney:$currency}</font></td> 
		</tr>
		{else}
		<tr>
		  <td colspan = "2"></td>
		  <td><font size = "1" align = "right">Amount Due: </font></td>
		  <td><font size = "1" align = "right">{$amountDue|crmMoney:$currency}</font></td>   
		</tr>
		{/if}
		<tr>   
		  <td colspan = "2"></td>
		  <td><font size = "1" align = "right">Due Date:  </font></td>
		  <td><font size = "1" align = "right">{$dueDate}</font></td>   
		</tr>
		<tr>
                  <td colspan = "5" style = "color:#F5F5F5;"><hr></hr></td>
                </tr>
	      </table>
            </td>
	  </tr>   
	</table>
      {/if}
    </center>
  </body>
</html>
