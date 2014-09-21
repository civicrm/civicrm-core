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
   </td>
  </tr>

  <tr>
   <td>&nbsp;</td>
  </tr>

    {if $recur_txnType eq 'START'}
     {if $auto_renew_membership}
       <tr>
        <td>
         <p>{ts}Thanks for your auto renew membership sign-up.{/ts}</p>
         <p>{ts 1=$recur_frequency_interval 2=$recur_frequency_unit}This membership will be automatically renewed every %1 %2(s). {/ts}</p>
        </td>
       </tr>
       <tr>
        <td {$labelStyle}>
         {ts 1=$cancelSubscriptionUrl}This membership will be renewed automatically. You can cancel the auto-renewal option by <a href="%1">visiting this web page</a>.{/ts}
        </td>
       </tr>
       <tr>
        <td {$labelStyle}>
         {ts 1=$updateSubscriptionBillingUrl}You can update billing details for this automatically renewed membership by <a href="%1">visiting this web page</a>.{/ts}
        </td>
       </tr>
     {else}
      <tr>
       <td>
        <p>{ts}Thanks for your recurring contribution sign-up.{/ts}</p>
        <p>{ts 1=$recur_frequency_interval 2=$recur_frequency_unit}This recurring contribution will be automatically processed every %1 %2(s){/ts}{if $recur_installments }{ts 1=$recur_installments} for a total of %1 installment(s){/ts}{/if}.</p>
        <p>{ts}Start Date{/ts}: {$recur_start_date|crmDate}</p>
       </td>
      </tr>
      <tr>
        <td {$labelStyle}>
         {ts 1=$cancelSubscriptionUrl} You can cancel the recurring contribution option by <a href="%1">visiting this web page</a>.{/ts}
        </td>
      </tr>
      <tr>
        <td {$labelStyle}>
         {ts 1=$updateSubscriptionBillingUrl}You can update billing details for this recurring contribution by <a href="%1">visiting this web page</a>.{/ts}
        </td>
       </tr>
       <tr>
        <td {$labelStyle}>
   {ts 1=$updateSubscriptionUrl}You can update recurring contribution amount or change the number of installments details for this recurring contribution by <a href="%1">visiting this web page</a>.{/ts}
        </td>
       </tr>
     {/if}

    {elseif $recur_txnType eq 'END'}

     {if $auto_renew_membership}
      <tr>
       <td>
        <p>{ts}Your auto renew membership sign-up has ended and your membership will not be automatically renewed.{/ts}</p>
       </td>
      </tr>
     {else}
      <tr>
       <td>
        <p>{ts}Your recurring contribution term has ended.{/ts}</p>
        <p>{ts 1=$recur_installments}You have successfully completed %1 recurring contributions. Thank you for your support.{/ts}</p>
       </td>
      </tr>
      <tr>
       <td>
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
       </td>
      </tr>

     {/if}
    {/if}

 </table>
</center>

</body>
</html>
