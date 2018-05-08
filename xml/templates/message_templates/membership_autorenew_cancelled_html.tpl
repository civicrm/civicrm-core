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
 <table width="500" border="0" cellpadding="0" cellspacing="0" id="crm-event_receipt" style="font-family: Arial, Verdana, sans-serif; text-align: left;">

  <!-- BEGIN HEADER -->
  <!-- You can add table row(s) here with logo or other header elements -->
  <!-- END HEADER -->

  <!-- BEGIN CONTENT -->

  <tr>
   <td>

    <p>{ts 1=$membershipType}The automatic renewal of your %1 membership has been cancelled as requested. This does not affect the status of your membership - you will receive a separate notification when your membership is up for renewal.{/ts}</p>

   </td>
  </tr>
 </table>
 <table width="500" style="border: 1px solid #999; margin: 1em 0em 1em; border-collapse: collapse;">

      <tr>
       <th {$headerStyle}>
        {ts}Membership Information{/ts}
       </th>
      </tr>
      <tr>
       <td {$labelStyle}>
        {ts}Membership Status{/ts}
       </td>
       <td {$valueStyle}>
        {$membership_status}
       </td>
      </tr>
      {if $mem_start_date}
       <tr>
        <td {$labelStyle}>
         {ts}Membership Start Date{/ts}
        </td>
        <td {$valueStyle}>
         {$mem_start_date|crmDate}
        </td>
       </tr>
      {/if}
      {if $mem_end_date}
       <tr>
        <td {$labelStyle}>
         {ts}Membership End Date{/ts}
        </td>
        <td {$valueStyle}>
          {$mem_end_date|crmDate}
        </td>
       </tr>
      {/if}

 </table>
</center>

</body>
</html>
