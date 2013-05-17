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
    <table style="border: 1px solid #999; margin: 1em 0em 1em; border-collapse: collapse; width:100%;">
     <tr>
      <th {$headerStyle}>
       {ts}Activity Summary{/ts} - {$activityTypeName}
      </th>
     </tr>
     {if $isCaseActivity}
      <tr>
       <td {$labelStyle}>
        {ts}Your Case Role(s){/ts}
       </td>
       <td {$valueStyle}>
        {$contact.role}
       </td>
      </tr>
      {if $manageCaseURL}
      <tr>
        <td colspan="2" {$valueStyle}>
      <a href="{$manageCaseURL}" title="{ts}Manage Case{/ts}">{ts}Manage Case{/ts}</a>
        </td>
      </tr>
      {/if}
     {/if}
     {if $editActURL}
     <tr>
       <td colspan="2" {$valueStyle}>
     <a href="{$editActURL}" title="{ts}Edit this activity{/ts}">{ts}Edit this activity{/ts}</a>
       </td>
     </tr>
     {/if}
     {if $viewActURL}
     <tr>
       <td colspan="2" {$valueStyle}>
     <a href="{$viewActURL}" title="{ts}View this activity{/ts}">{ts}View this activity{/ts}</a>
       </td>
     </tr>
     {/if}
     {foreach from=$activity.fields item=field}
      <tr>
       <td {$labelStyle}>
        {$field.label}{if $field.category}({$field.category}){/if}
       </td>
       <td {$valueStyle}>
        {if $field.type eq 'Date'}
         {$field.value|crmDate:$config->dateformatDatetime}
        {else}
         {$field.value}
        {/if}
       </td>
      </tr>
     {/foreach}

     {foreach from=$activity.customGroups key=customGroupName item=customGroup}
      <tr>
       <th {$headerStyle}>
        {$customGroupName}
       </th>
      </tr>
      {foreach from=$customGroup item=field}
       <tr>
        <td {$labelStyle}>
         {$field.label}
        </td>
        <td {$valueStyle}>
         {if $field.type eq 'Date'}
          {$field.value|crmDate:$config->dateformatDatetime}
         {else}
          {$field.value}
         {/if}
        </td>
       </tr>
      {/foreach}
     {/foreach}
    </table>
   </td>
  </tr>
 </table>
</center>

</body>
</html>