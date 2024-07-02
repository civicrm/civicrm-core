<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
 <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
 <title></title>
</head>
<body>

  <!-- BEGIN HEADER -->
    {* To modify content in this section, you can edit the Custom Token named "Message Header". See also: https://docs.civicrm.org/user/en/latest/email/message-templates/#modifying-system-workflow-message-templates *}
    {site.message_header}
  <!-- END HEADER -->

  <!-- BEGIN CONTENT -->

  <table id="crm-event_receipt" style="font-family: Arial, Verdana, sans-serif; text-align: left; width:100%; max-width:700px; padding:0; margin:0; border:0px;">
  <tr>
   <td>

    <h1>{ts}Your Personal Campaign Page{/ts}</h1>

    {if $pcpStatus eq 'Approved'}

     <p>{ts}Your personal campaign page has been approved and is now live.{/ts}</p>
     <p>{ts}Whenever you want to preview, update or promote your page{/ts}:</p>
     <ol>
      <li><a href="{$loginUrl}">{ts}Login to your account{/ts}</a></li>
      <li><a href="{$pcpInfoURL}">{ts}Go to your page{/ts}</a></li>
     </ol>
     <p>{ts}When you view your campaign page WHILE LOGGED IN, the page includes links to edit your page, tell friends, and update your contact info.{/ts}</p>

     {if $isTellFriendEnabled}
      <p><a href="{$pcpTellFriendURL}">{ts}After logging in, you can use this form to promote your fundraising page{/ts}</a></p>
     {/if}

     {if $pcpNotifyEmailAddress}
      <p>{ts}Questions? Send email to{/ts}: {$pcpNotifyEmailAddress}</p>
     {/if}

    {elseif $pcpStatus eq 'Not Approved'}

     <p>{ts}Your personal campaign page has been reviewed. There were some issues with the content which prevented us from approving the page. We are sorry for any inconvenience.{/ts}</p>
     {if $pcpNotifyEmailAddress}
      <p>{ts}Please contact our site administrator for more information{/ts}: {$pcpNotifyEmailAddress}</p>
     {/if}

    {/if}

   </td>
  </tr>

 </table>

</body>
</html>
