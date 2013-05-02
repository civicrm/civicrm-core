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

    <p>Dear {contact.display_name},</p>
    <p>This is being sent to you as a receipt of {$grant_status} grant.</p>
Grant Program Name: {$grant_programs} <br>
Grant  Type             : {$grant_type}<br>
Total Amount            : {$params.amount_total}<br>
{if customField}
{foreach from=$customField key=key item=data}
<b>{$customGroup.$key}</b><br>
{foreach from=$data key=dkey item=ddata}
{$ddata.label} : {$ddata.value}<br>
{/foreach}
{/foreach}
{/if}
 </body>
</html>
