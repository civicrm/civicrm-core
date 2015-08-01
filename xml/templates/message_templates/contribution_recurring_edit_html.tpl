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
    <p>{ts 1=$contact.display_name}Dear %1{/ts},</p>
    <p>{ts}Your recurring contribution has been updated as requested:{/ts}
    <p>{ts 1=$amount 2=$recur_frequency_interval 3=$recur_frequency_unit}Recurring contribution is for %1, every %2 %3(s){/ts}{if $installments}{ts 1=$installments} for %1 installments{/ts}{/if}.</p>

    <p>{ts 1=$receipt_from_email}If you have questions please contact us at %1.{/ts}</p>
   </td>
  </tr>
  <tr>
 </table>
</center>

</body>
</html>
