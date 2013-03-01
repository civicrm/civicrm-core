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

    <p>{ts 1=$displayName}Dear %1{/ts},</p>

    {if $recur_txnType eq 'START'}

     <p>{ts}Thanks for your recurring contribution sign-up.{/ts}</p>
     <p>{ts 1=$recur_frequency_interval 2=$recur_frequency_unit 3=$recur_installments}This recurring contribution will be automatically processed every %1 %2(s) for a total %3 installment(s).{/ts}</p>
     <p>{ts}Start Date{/ts}: {$recur_start_date|crmDate}</p>
     <p>{ts 1=$receipt_from_name 2=$receipt_from_email}You have pledged to make this recurring donation. You will be charged periodically (per frequency listed above), and you will receive an email receipt from %1 following each charge. These recurring donations will continue until you explicitly cancel the donation. You may change or cancel your recurring donation at anytime by logging into your account. If you have questions about recurring donations please contact us at %2.{/ts}</p>

    {elseif $recur_txnType eq 'END'}

     <p>{ts}Your recurring contribution term has ended.{/ts}</p>
     <p>{ts 1=$recur_installments}You have successfully completed %1 recurring contributions. Thank you for your support.{/ts}</p>
     <table style="border: 1px solid #999; margin: 1em 0em 1em; border-collapse: collapse; width:100%;">
      <tr>
       <th {$headerStyle}>
        {ts 1=$recur_installments}Interval of Subscription for %1 installment(s){/ts}
       </th>
      </tr>
      <tr>
       <td {$labelStyle}>
        {ts}Start Date{/ts}
       </td>
       <td {$valueStyle}>
        {$recur_start_date|crmDate}
       </td>
      </tr>
      <tr>
       <td {$labelStyle}>
        {ts}End Date{/ts}
       </td>
       <td {$valueStyle}>
        {$recur_end_date|crmDate}
       </td>
      </tr>
     </table>

    {/if}

   </td>
  </tr>

 </table>
</center>

</body>
</html>
