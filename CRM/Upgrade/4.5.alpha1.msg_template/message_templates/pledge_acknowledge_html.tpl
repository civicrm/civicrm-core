<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
 <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
 <title></title>
</head>
<body>

{capture assign=headerStyle}colspan="2" style="text-align: left; padding: 4px; border-bottom: 1px solid #999; background-color: #eee;"{/capture}
{capture assign=labelStyle }style="padding: 4px; border-bottom: 1px solid #999; background-color: #f7f7f7;"{/capture}
{capture assign=valueStyle }style="padding: 4px; border-bottom: 1px solid #999;"{/capture}

<center>
 <table width="620" border="0" cellpadding="0" cellspacing="0" id="crm-event_receipt" style="font-family: Arial, Verdana, sans-serif; text-align: left;">

  <!-- BEGIN HEADER -->
  <!-- You can add table row(s) here with logo or other header elements -->
  <!-- END HEADER -->

  <!-- BEGIN CONTENT -->

  <tr>
   <td>
    <p>{ts 1=$contact.display_name}dear %1{/ts},</p>
    <p>{ts}thank you for your generous pledge. please print this acknowledgment for your records.{/ts}</p>
   </td>
  </tr>
  <tr>
   <td>
    <table style="border: 1px solid #999; margin: 1em 0em 1em; border-collapse: collapse; width:100%;">
     <tr>
      <th {$headerStyle}>
       {ts}Pledge Information{/ts}
      </th>
     </tr>
     <tr>
      <td {$labelStyle}>
       {ts}Pledge Received{/ts}
      </td>
      <td {$valueStyle}>
       {$create_date|truncate:10:''|crmDate}
      </td>
     </tr>
     <tr>
      <td {$labelStyle}>
       {ts}Total Pledge Amount{/ts}
      </td>
      <td {$valueStyle}>
       {$total_pledge_amount|crmMoney:$currency}
      </td>
     </tr>
     <tr>
      <th {$headerStyle}>
       {ts}Payment Schedule{/ts}
      </th>
     </tr>
     <tr>
      <td colspan="2" {$valueStyle}>
       <p>{ts 1=$scheduled_amount|crmMoney:$currency 2=$frequency_interval 3=$frequency_unit 4=$installments}%1 every %2 %3 for %4 installments.{/ts}</p>

       {if $frequency_day}
        <p>{ts 1=$frequency_day 2=$frequency_unit}Payments are due on day %1 of the %2.{/ts}</p>
       {/if}
      </td>
     </tr>

     {if $payments}
      {assign var="count" value="1"}
      {foreach from=$payments item=payment}
       <tr>
        <td {$labelStyle}>
         {ts 1=$count}Payment %1{/ts}
        </td>
        <td {$valueStyle}>
         {$payment.amount|crmMoney:$currency} {if $payment.status eq 1}{ts}paid{/ts} {$payment.receive_date|truncate:10:''|crmDate}{else}{ts}due{/ts} {$payment.due_date|truncate:10:''|crmDate}{/if}
        </td>
       </tr>
       {assign var="count" value=`$count+1`}
      {/foreach}
     {/if}

     <tr>
      <td colspan="2" {$valueStyle}>
       <p>{ts 1=$domain.phone 2=$domain.email}Please contact us at %1 or send email to %2 if you have questions
or need to modify your payment schedule.{/ts}</p>
      </td>
     </tr>

     {if $customGroup}
      {foreach from=$customGroup item=value key=customName}
       <tr>
        <th {$headerStyle}>
         {$customName}
        </th>
       </tr>
       {foreach from=$value item=v key=n}
        <tr>
         <td {$labelStyle}>
          {$n}
         </td>
         <td {$valueStyle}>
          {$v}
         </td>
        </tr>
       {/foreach}
      {/foreach}
     {/if}

    </table>
   </td>
  </tr>

 </table>
</center>

</body>
</html>
