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

  <p>{ts}You have received a donation at your personal page{/ts}: <a href="{$pcpInfoURL}">{$page_title}</a></p>
  <p>{ts}Your fundraising total has been updated.{/ts}<br/>
    {ts}The donor's information is listed below.  You can choose to contact them and convey your thanks if you wish.{/ts} <br/>
    {if $is_honor_roll_enabled}
      {ts}The donor's name has been added to your honor roll unless they asked not to be included.{/ts}<br/>
    {/if}
  </p>
  <table width="620" border="0" cellpadding="0" cellspacing="0" id="crm-event_receipt" style="font-family: Arial, Verdana, sans-serif; text-align: left;">
    <tr><td>{ts}Receive Date{/ts}:</td><td> {$receive_date|crmDate}</td></tr>
    <tr><td>{ts}Amount{/ts}:</td><td> {$total_amount|crmMoney}</td></tr>
    <tr><td>{ts}Name{/ts}:</td><td> {$donors_display_name}</td></tr>
    <tr><td>{ts}Email{/ts}:</td><td> {$donors_email}</td></tr>
  </table>
</body>
</html>
