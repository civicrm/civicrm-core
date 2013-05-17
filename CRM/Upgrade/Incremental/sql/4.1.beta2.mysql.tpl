-- CRM-9384 Add sample CiviMail Newsletter Message Template
INSERT INTO civicrm_msg_template
  (msg_title,      msg_subject,                  msg_text,                  msg_html,                  workflow_id,        is_default, is_reserved)
  VALUES
  ('Sample CiviMail Newsletter Template', 'Sample CiviMail Newsletter', '', '<table width=612 cellpadding=0 cellspacing=0 bgcolor="#f4fff4">
  <tr>
    <td colspan="2" bgcolor="#ffffff" valign="middle" >
      <table border="0" cellpadding="0" cellspacing="0" >
        <tr>
          <td>
          <a href="http://www.YOUR-SITE.org"><img src="http://drupal.demo.civicrm.org/files/garland_logo.png" border=0 alt="Replace this logo with the URL to your own"></a>
          </td>
          <td>&nbsp; &nbsp;</td>
          <td>
          <a href="http://www.YOUR-SITE.org" style="text-decoration: none;"><font size=5 face="Arial, Verdana, sans-serif" color="#8bc539">Your Newsletter Title</font></a>
          </td>
        </tr>
      </table>
    </td>
  </tr>
  <tr>
    <td valign="top">
      <!-- left column -->
      <table cellpadding="10" cellspacing="0" border="0">
      <tr>
        <td style="font-family: Arial, Verdana, sans-serif; font-size: 12px;" >
        <font face="Arial, Verdana, sans-serif" size="2" >
        Greetings {literal}{contact.display_name}{/literal},
        <br /><br />
        This is a sample template designed to help you get started creating and sending your own CiviMail messages. This template uses an HTML layout that is generally compatible with the wide variety of email clients that your recipients might be using (e.g. Gmail, Outlook, Yahoo, etc.).
        <br /><br />You can select this "Sample CiviMail Newsletter Template" from the "Use Template" drop-down in Step 3 of creating a mailing, and customize it to your needs. Then check the "Save as New Template" box on the bottom the page to save your customized version for use in future mailings.
        <br /><br />The logo you use must be uploaded to your server.  Copy and paste the URL path to the logo into the &lt;img src= tag in the HTML at the top.  Click "Source" or the Image button if you are using the text editor.
        <br /><br />
        Edit the color of the links and headers using the color button or by editing the HTML.
        <br /><br />
        Your newsletter message and donation appeal can go here.  Click the link button to <a href="#">create links</a> - remember to use a fully qualified URL starting with http:// in all your links!
        <br /><br />
        To use CiviMail:
        <ul>
          <li><a href="http://book.civicrm.org/user/initial-set-up/email-system-configuration">Configure your Email System</a>.</li>
          <li>Make sure your web hosting provider allows outgoing bulk mail, and see if they have a restriction on quantity.  If they don\'t allow bulk mail, consider <a href="http://wiki.civicrm.org/confluence/display/CRM/Hosting+provider+information">finding a new host</a>.</li>
        </ul>
        Sincerely,
        <br /><br />
        Your Team
        </font>
        </td>
      </tr>
      </table>
    </td>

    <td valign="top" bgcolor="#f3f3ff" >
      <!-- right column -->
      <table width=180 cellpadding=10 cellspacing=0 border=0>
      <tr>
        <td style="color: #000; font-family: Arial, Verdana, sans-serif; font-size: 12px;" >
        <font face="Arial, Verdana, sans-serif" size="2" >
        <font color="#003399"><strong>Featured Events</strong> </font><br />
        Fundraising Dinner<br />
        Training Meeting<br />
        Board of Directors Annual Meeting<br />

        <br /><br />
        <font color="#003399"><strong>Community Events</strong></font><br />
        Bake Sale<br />
        Charity Auction<br />
        Art Exhibit<br />

        <br /><br />
        <font color="#003399"><strong>Important Dates</strong></font><br />
        Tuesday August 27<br />
        Wednesday September 8<br />
        Thursday September 29<br />
        Saturday October 1<br />
        Sunday October 20<br />
        </font>
        </td>
      </tr>
      </table>
    </td>
  </tr>

  <tr>
    <td colspan="2">
      <table cellpadding="10" cellspacing="0" border="0">
      <tr>
        <td>
        <font face="Arial, Verdana, sans-serif" size="2" >
        <font color="#8bc539"><strong>Helpful Tips</strong></font>
        <br /><br />
        <font color="#3b5187">Tokens</font><br />
        Click "Insert Tokens" to dynamically insert names, addresses, and other contact data of your recipients.
        <br /><br />
        <font color="#3b5187">Plain Text Version</font><br />
        Some people refuse HTML emails altogether.  We recommend sending a plain-text version of your important communications to accommodate them.  Luckily, CiviCRM accommodates for this!  Just click "Plain Text" and copy and paste in some text.  Line breaks (carriage returns) and fully qualified URLs like http://www.example.com are all you get, no HTML here!
        <br /><br />
        <font color="#3b5187">Play by the Rules</font><br />
        The address of the sender is required by the Can Spam Act law.  This is an available token called domain.address.  An unsubscribe or opt-out link is also required.  There are several available tokens for this. <em>{literal}{action.optOutUrl}{/literal}</em> creates a link for recipients to click if they want to opt out of receiving  emails from your organization. <em>{literal}{action.unsubscribeUrl}{/literal}</em> creates a link to unsubscribe from the specific mailing list used to send this message. Click on "Insert Tokens" to find these and look for tokens named "Domain" or "Unsubscribe".  This sample template includes both required tokens at the bottom of the message. You can also configure a default Mailing Footer containing these tokens.
        <br /><br />
        <font color="#3b5187">Composing Offline</font><br />
        If you prefer to compose an HTML email offline in your own text editor, you can upload this HTML content into CiviMail or simply click "Source" and then copy and paste the HTML in.
        <br /><br />
        <font color="#3b5187">Images</font><br />
        Most email clients these days (Outlook, Gmail, etc) block image loading by default.  This is to protect their users from annoying or harmful email.  Not much we can do about this, so encourage recipients to add you to their contacts or "whitelist".  Also use images sparingly, do not rely on images to convey vital information, and always use HTML "alt" tags which describe the image content.
        </td>
      </tr>
      </table>
    </td>
  </tr>
  <tr>
    <td colspan="2" style="color: #000; font-family: Arial, Verdana, sans-serif; font-size: 10px;">
      <font face="Arial, Verdana, sans-serif" size="2" >
      <hr>
      <a href="{literal}{action.unsubscribeUrl}{/literal}" title="click to unsubscribe">Click here</a> to unsubscribe from this mailing list.<br /><br />
      Our mailing address is:<br />
      {literal}{domain.address}{/literal}
    </td>
  </tr>
  </table>', NULL, 1, 0);
