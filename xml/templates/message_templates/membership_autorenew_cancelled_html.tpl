<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
 <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
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

  <table id="crm-event_receipt" style="font-family: Arial, Verdana, sans-serif; text-align: left; width:100%; max-width:700px; padding:0; margin:0; border:0px;">
  <tr>
   <td>
    {assign var="greeting" value="{contact.email_greeting_display}"}{if $greeting}<p>{$greeting},</p>{/if}
    <p>{ts 1=$membershipType}The automatic renewal of your %1 membership has been cancelled as requested. This does not affect the status of your membership - you will receive a separate notification when your membership is up for renewal.{/ts}</p>

   </td>
  </tr>
 </table>
 <table style="width:100%; max-width:500px; border: 1px solid #999; margin: 1em 0em 1em; border-collapse: collapse;">

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
         {ts}Membership Expiration Date{/ts}
        </td>
        <td {$valueStyle}>
          {$mem_end_date|crmDate}
        </td>
       </tr>
      {/if}

 </table>

</body>
</html>
