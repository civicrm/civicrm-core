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
  <!-- BEGIN HEADER -->

  <!-- BEGIN CONTENT -->

  <tr>
   <td>
    <p>{ts}Dear supporter{/ts},</p>
    <p>{ts 1="$contribPageTitle"}Thanks for creating a personal campaign page in support of %1.{/ts}</p>
   </td>
  </tr>

  {if $pcpStatus eq 'Approved'}

    <tr>
     <td>
      <table style="border: 1px solid #999; margin: 1em 0em 1em; border-collapse: collapse; width:100%;">
       <tr>
        <th {$headerStyle}>
         {ts}Promoting Your Page{/ts}
        </th>
       </tr>
       <tr>
        <td colspan="2" {$valuestyle}>
         {if $istellfriendenabled}
          <p>{ts}you can begin your fundraising efforts using our "tell a friend" form{/ts}:</p>
          <ol>
           <li><a href="{$loginurl}">{ts}login to your account{/ts}</a></li>
           <li><a href="{$pcptellfriendurl}">{ts}click this link and follow the prompts{/ts}</a></li>
          </ol>
         {else}
          <p>{ts}send email to family, friends and colleagues with a personal message about this campaign.{/ts} {ts}include this link to your fundraising page in your emails{/ts}: {$pcpinfourl}</p>
         {/if}
        </td>
       </tr>
       <tr>
        <th {$headerStyle}>
         {ts}Managing Your Page{/ts}
        </th>
       <tr>
        <td colspan="2" {$valuestyle}>
         <p>{ts}Whenever you want to preview, update or promote your page{/ts}:</p>
         <ol>
          <li><a href="{$loginUrl}">{ts}Login to your account{/ts}</a></li>
          <li><a href="{$pcpInfoURL}">{ts}Go to your page{/ts}</a></li>
         </ol>
         <p>{ts}When you view your campaign page WHILE LOGGED IN, the page includes links to edit
your page, tell friends, and update your contact info.{/ts}</p>
        </td>
       </tr>
       </tr>
      </table>
     </td>
    </tr>

   {elseif $pcpStatus EQ 'Waiting Review'}

    <tr>
     <td>
      <p>{ts}Your page requires administrator review before you can begin your fundraising efforts.{/ts}</p>
      <p>{ts}A notification email has been sent to the site administrator, and you will receive another notification from them as soon as the review process is complete.{/ts}</p>
      <p>{ts}You can still preview your page prior to approval{/ts}:</p>
      <ol>
       <li><a href="{$loginUrl}">{ts}Login to your account{/ts}</a></li>
       <li><a href="{$pcpInfoURL}">{ts}Click this link{/ts}</a></li>
      </ol>
     </td>
    </tr>

   {/if}

   {if $pcpNotifyEmailAddress}
    <tr>
     <td>
      <p>{ts}Questions? Send email to{/ts}: {$pcpNotifyEmailAddress}</p>
     </td>
    </tr>
   {/if}

 </table>
</center>

</body>
</html>
