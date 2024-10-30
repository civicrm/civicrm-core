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
    <p>{ts}Your Event Registration has been cancelled.{/ts}</p>
   </td>
  </tr>
  <tr>
   <td>
    <table style="border: 1px solid #999; margin: 1em 0em 1em; border-collapse: collapse; width:100%;">
     <tr>
      <th {$headerStyle}>
       {ts}Event Information and Location{/ts}
      </th>
     </tr>
     <tr>
      <td colspan="2" {$valueStyle}>
       {event.title}<br />
       {event.start_date|crmDate:"%A"} {event.start_date|crmDate}{if {event.end_date|boolean}}-{if '{event.end_date|crmDate:"%Y%m%d"}' === '{event.start_date|crmDate:"%Y%m%d"}'}{event.end_date|crmDate:"Time"}{else}{event.end_date|crmDate:"%A"} {event.end_date|crmDate}{/if}{/if}
      </td>
     </tr>
    {if {event.is_show_location|boolean}}
        <tr>
          <td colspan="2" {$valueStyle}>
            {event.location}
          </td>
        </tr>
      {/if}

    {if {event.loc_block_id.phone_id.phone|boolean} || {event.loc_block_id.email_id.email|boolean}}
        <tr>
          <td colspan="2" {$labelStyle}>
            {ts}Event Contacts:{/ts}
          </td>
        </tr>

        {if {event.loc_block_id.phone_id.phone|boolean}}
          <tr>
            <td {$labelStyle}>
              {if {event.loc_block_id.phone_id.phone_type_id|boolean}}
                {event.loc_block_id.phone_id.phone_type_id:label}
              {else}
                {ts}Phone{/ts}
              {/if}
            </td>
            <td {$valueStyle}>
              {event.loc_block_id.phone_id.phone} {if {event.loc_block_id.phone_id.phone_ext|boolean}}&nbsp;{ts}ext.{/ts} {event.loc_block_id.phone_id.phone_ext}{/if}
            </td>
          </tr>
        {/if}
        {if {event.loc_block_id.phone_2_id.phone|boolean}}
          <tr>
            <td {$labelStyle}>
              {if {event.loc_block_id.phone_2_id.phone_type_id|boolean}}
                {event.loc_block_id.phone_2_id.phone_type_id:label}
              {else}
                {ts}Phone{/ts}
              {/if}
            </td>
            <td {$valueStyle}>
              {event.loc_block_id.phone_2_id.phone} {if {event.loc_block_id.phone_2_id.phone_ext|boolean}}&nbsp;{ts}ext.{/ts} {event.loc_block_id.phone_2_id.phone_ext}{/if}
            </td>
          </tr>
        {/if}

        {if {event.loc_block_id.email_id.email|boolean}}
          <tr>
            <td {$labelStyle}>
              {ts}Email{/ts}
            </td>
            <td {$valueStyle}>
              {event.loc_block_id.email_id.email}
            </td>
          </tr>
        {/if}

        {if {event.loc_block_id.email_2_id.email|boolean}}
          <tr>
            <td {$labelStyle}>
              {ts}Email{/ts}
            </td>
            <td {$valueStyle}>
              {event.loc_block_id.email_2_id.email}
            </td>
          </tr>
        {/if}
      {/if}

    {if '{contact.email}'}
      <tr>
       <th {$headerStyle}>
        {ts}Registered Email{/ts}
       </th>
      </tr>
      <tr>
       <td colspan="2" {$valueStyle}>
        {contact.email}
       </td>
      </tr>
     {/if}

     {if !empty('{participant.register_date}')}
      <tr>
       <td {$labelStyle}>
        {ts}Registration Date{/ts}
       </td>
       <td {$valueStyle}>
        {participant.register_date}
       </td>
      </tr>
     {/if}

    </table>
   </td>
  </tr>

  <tr>
   <td>
    <p>{ts 1='{domain.phone}' 2='{domain.email}'}Please contact us at %1 or send email to %2 if you have questions.{/ts}</p>
   </td>
  </tr>

 </table>

</body>
</html>
