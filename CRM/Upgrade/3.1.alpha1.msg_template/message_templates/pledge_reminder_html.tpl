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
  <!-- BEGIN HEADER -->

  <!-- BEGIN CONTENT -->

  <tr>
   <td>
    <p>{ts 1=$contact.display_name}Dear %1{/ts},</p>
    <p>{ts 1=$next_payment|truncate:10:''|crmDate}This is a reminder that the next payment on your pledge is due on %1.{/ts}</p>
   </td>
  </tr>
  <tr>
   <td>
    <table style="border: 1px solid #999; margin: 1em 0em 1em; border-collapse: collapse; width:100%;">
     <tr>
      <th {$headerStyle}>
       {ts}Payment Due{/ts}
      </th>
     </tr>
     <tr>
      <td {$labelStyle}>
       {ts}Amount Due{/ts}
      </td>
      <td {$valueStyle}>
       {$amount_due|crmMoney}
      </td>
     </tr>
    </table>
   </td>
  </tr>

  <tr>
   <td>
    {if $contribution_page_id}
     {capture assign=contributionUrl}{crmURL p='civicrm/contribute/transact' q="reset=1&id=`$contribution_page_id`&cid=`$contact.contact_id`&pledgeId=`$pledge_id`&cs=`$checksumValue`" a=true h=0}{/capture}
     <p><a href="{$contributionUrl}">{ts}Go to a web page where you can make your payment online{/ts}</a></p>
    {else}
     <p>{ts}Please mail your payment to{/ts}: {$domain.address}</p>
    {/if}
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
       {$amount|crmMoney}
      </td>
     </tr>
     <tr>
      <td {$labelStyle}>
       {ts}Total Paid{/ts}
      </td>
      <td {$valueStyle}>
       {$amount_paid|crmMoney}
      </td>
     </tr>
    </table>
   </td>
  </tr>

  <tr>
   <td>
    <p>{ts 1=$domain.phone 2=$domain.email}Please contact us at %1 or send email to %2 if you have questions
or need to modify your payment schedule.{/ts}</p>
    <p>{ts}Thank your for your generous support.{/ts}</p>
   </td>
  </tr>

 </table>
</center>

</body>
</html>
