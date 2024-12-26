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
    <table style="border: 1px solid #999; margin: 1em 0em 1em; border-collapse: collapse; width:100%;">
     <tr>
      <td {$labelStyle}>
       {ts}Submitted For{/ts}
      </td>
      <td {$valueStyle}>
       {contact.display_name}
      </td>
     </tr>
     <tr>
      <td {$labelStyle}>
       {ts}Date{/ts}
      </td>
      <td {$valueStyle}>
       {domain.now|crmDate:"Full"}
      </td>
     </tr>
     <tr>
      <td {$labelStyle}>
       {ts}Contact Summary{/ts}
      </td>
      <td {$valueStyle}>
       {$contactLink}
      </td>
     </tr>

     <tr>
      <th {$headerStyle}>
       {$grouptitle}
      </th>
     </tr>

     {foreach from=$values item=value key=valueName}
      <tr>
       <td {$labelStyle}>
        {$valueName}
       </td>
       <td {$valueStyle}>
        {$value}
       </td>
      </tr>
     {/foreach}
    </table>
   </td>
  </tr>

 </table>

</body>
</html>
