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
{capture assign=pcpURL     }{crmURL p="civicrm/pcp/info" q="reset=1&id=`$pcpId`" h=0 a=1}{/capture}

<center>
 <table width="620" border="0" cellpadding="0" cellspacing="0" id="crm-event_receipt" style="font-family: Arial, Verdana, sans-serif; text-align: left;">

  <!-- BEGIN HEADER -->
  <!-- You can add table row(s) here with logo or other header elements -->
  <!-- END HEADER -->

  <!-- BEGIN CONTENT -->

  <tr>
   <td>
    <table style="border: 1px solid #999; margin: 1em 0em 1em; border-collapse: collapse; width:100%;">
     <tr>
      <th {$headerStyle}>
       {ts}Personal Campaign Page Notification{/ts}
      </th>
     </tr>
     <tr>
      <td {$labelStyle}>
       {ts}Action{/ts}:
      </td>
      <td {$valueStyle}>
       {if $mode EQ 'Update'}
        {ts}Updated personal campaign page{/ts}
       {else}
        {ts}New personal campaign page{/ts}
       {/if}
      </td>
     </tr>
     <tr>
      <td {$labelStyle}>
       {ts}Personal Campaign Page Title{/ts}
      </td>
      <td {$valueStyle}>
       {$pcpTitle}
      </td>
     </tr>
     <tr>
      <td {$labelStyle}>
       {ts}Current Status{/ts}
      </td>
      <td {$valueStyle}>
       {$pcpStatus}
      </td>
     </tr>

     <tr>
      <td {$labelStyle}>
       <a href="{$pcpURL}">{ts}View Page{/ts}</a>
      </td>
      <td></td>
     </tr>
     <tr>
      <td {$labelStyle}>
       {ts}Supporter{/ts}
      </td>
      <td {$valueStyle}>
       <a href="{$supporterUrl}">{$supporterName}</a>
      </td>
     </tr>
     <tr>
      <td {$labelStyle}>
       {ts}Linked to Contribution Page{/ts}
      </td>
      <td {$valueStyle}>
       <a href="{$contribPageUrl}">{$contribPageTitle}</a>
      </td>
     </tr>
     <tr>
      <td {$labelStyle}>
       <a href="{$managePCPUrl}">{ts}Manage Personal Campaign Pages{/ts}</a>
      </td>
      <td></td>
     </tr>

    </table>
   </td>
  </tr>
 </table>
</center>

</body>
</html>
