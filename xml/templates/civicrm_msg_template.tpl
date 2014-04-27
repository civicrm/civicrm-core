-- +--------------------------------------------------------------------+
-- | CiviCRM version 4.5                                                |
-- +--------------------------------------------------------------------+
-- | Copyright CiviCRM LLC (c) 2004-2014                                |
-- +--------------------------------------------------------------------+
-- | This file is a part of CiviCRM.                                    |
-- |                                                                    |
-- | CiviCRM is free software; you can copy, modify, and distribute it  |
-- | under the terms of the GNU Affero General Public License           |
-- | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
-- |                                                                    |
-- | CiviCRM is distributed in the hope that it will be useful, but     |
-- | WITHOUT ANY WARRANTY; without even the implied warranty of         |
-- | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
-- | See the GNU Affero General Public License for more details.        |
-- |                                                                    |
-- | You should have received a copy of the GNU Affero General Public   |
-- | License and the CiviCRM Licensing Exception along                  |
-- | with this program; if not, contact CiviCRM LLC                     |
-- | at info[AT]civicrm[DOT]org. If you have questions about the        |
-- | GNU Affero General Public License or the licensing of CiviCRM,     |
-- | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
-- +--------------------------------------------------------------------+
--
-- Generated from {$smarty.template}
-- {$generated}
--
{* not sure how to define the below in Smarty, so doing it in PHP instead *}
{php}
  $ogNames = array(
    'case'         => ts('Message Template Workflow for Cases',          array('escape' => 'sql')),
    'contribution' => ts('Message Template Workflow for Contributions',  array('escape' => 'sql')),
    'event'        => ts('Message Template Workflow for Events',         array('escape' => 'sql')),
    'friend'       => ts('Message Template Workflow for Tell-a-Friend',  array('escape' => 'sql')),
    'membership'   => ts('Message Template Workflow for Memberships',    array('escape' => 'sql')),
    'meta'         => ts('Message Template Workflow for Meta Templates', array('escape' => 'sql')),
    'pledge'       => ts('Message Template Workflow for Pledges',        array('escape' => 'sql')),
    'uf'           => ts('Message Template Workflow for Profiles',       array('escape' => 'sql')),
    'petition'     => ts('Message Template Workflow for Petition',       array('escape' => 'sql')),
  );
  $ovNames = array(
    'case' => array(
      'case_activity' => ts('Cases - Send Copy of an Activity', array('escape' => 'sql')),
    ),
    'contribution' => array(
      'contribution_dupalert'         => ts('Contributions - Duplicate Organization Alert',                   array('escape' => 'sql')),
      'contribution_offline_receipt'  => ts('Contributions - Receipt (off-line)',                             array('escape' => 'sql')),
      'contribution_online_receipt'   => ts('Contributions - Receipt (on-line)',                              array('escape' => 'sql')),
      'contribution_recurring_notify' => ts('Contributions - Recurring Start and End Notification',           array('escape' => 'sql')),
      'contribution_recurring_cancelled' => ts('Contributions - Recurring Cancellation Notification',         array('escape' => 'sql')),
      'contribution_recurring_billing' => ts('Contributions - Recurring Billing Updates',                     array('escape' => 'sql')),
      'contribution_recurring_edit'    => ts('Contributions - Recurring Updates',                             array('escape' => 'sql')),
      'pcp_notify'                    => ts('Personal Campaign Pages - Admin Notification',                   array('escape' => 'sql')),
      'pcp_status_change'             => ts('Personal Campaign Pages - Supporter Status Change Notification', array('escape' => 'sql')),
      'pcp_supporter_notify'          => ts('Personal Campaign Pages - Supporter Welcome',                    array('escape' => 'sql')),
      'payment_or_refund_notification' => ts('Additional Payment Receipt or Refund Notification',             array('escape' => 'sql')),
    ),
    'event' => array(
      'event_offline_receipt' => ts('Events - Registration Confirmation and Receipt (off-line)', array('escape' => 'sql')),
      'event_online_receipt'  => ts('Events - Registration Confirmation and Receipt (on-line)',  array('escape' => 'sql')),
      'event_registration_receipt'  => ts('Events - Receipt only',                               array('escape' => 'sql')),
      'participant_cancelled' => ts('Events - Registration Cancellation Notice',                 array('escape' => 'sql')),
      'participant_confirm'   => ts('Events - Registration Confirmation Invite',                 array('escape' => 'sql')),
      'participant_expired'   => ts('Events - Pending Registration Expiration Notice',           array('escape' => 'sql')),
    ),
    'friend' => array(
      'friend' => ts('Tell-a-Friend Email', array('escape' => 'sql')),
    ),
    'membership' => array(
      'membership_offline_receipt' => ts('Memberships - Signup and Renewal Receipts (off-line)', array('escape' => 'sql')),
      'membership_online_receipt'  => ts('Memberships - Receipt (on-line)',                      array('escape' => 'sql')),
      'membership_autorenew_cancelled' => ts('Memberships - Auto-renew Cancellation Notification', array('escape' => 'sql')),
      'membership_autorenew_billing' => ts('Memberships - Auto-renew Billing Updates',           array('escape' => 'sql')),
    ),
    'meta' => array(
      'test_preview' => ts('Test-drive - Receipt Header', array('escape' => 'sql')),
    ),
    'pledge' => array(
      'pledge_acknowledge' => ts('Pledges - Acknowledgement',  array('escape' => 'sql')),
      'pledge_reminder'    => ts('Pledges - Payment Reminder', array('escape' => 'sql')),
    ),
    'uf' => array(
      'uf_notify' => ts('Profiles - Admin Notification', array('escape' => 'sql')),
    ),
    'petition' => array(
      'petition_sign' => ts('Petition - signature added', array('escape' => 'sql')),
      'petition_confirmation_needed' => ts('Petition - need verification', array('escape' => 'sql')),
    ),
  );
  $this->assign('ogNames',  $ogNames);
  $this->assign('ovNames',  $ovNames);
{/php}

INSERT INTO civicrm_option_group
  (name,                         {localize field='title'}title{/localize},            {localize field='description'}description{/localize},      is_reserved, is_active) VALUES
{foreach from=$ogNames key=name item=description name=for_groups}
    ('msg_tpl_workflow_{$name}', {localize}'{$description}'{/localize},               {localize}'{$description}'{/localize},                     1,           1) {if $smarty.foreach.for_groups.last};{else},{/if}
{/foreach}

{foreach from=$ogNames key=name item=description}
  SELECT @tpl_ogid_{$name} := MAX(id) FROM civicrm_option_group WHERE name = 'msg_tpl_workflow_{$name}';
{/foreach}

INSERT INTO civicrm_option_value
  (option_group_id,        name,       {localize field='label'}label{/localize},   value,                                  weight) VALUES
{foreach from=$ovNames key=gName item=ovs name=for_groups}
{foreach from=$ovs key=vName item=label name=for_values}
      (@tpl_ogid_{$gName}, '{$vName}', {localize}'{$label}'{/localize},            {$smarty.foreach.for_values.iteration}, {$smarty.foreach.for_values.iteration}) {if $smarty.foreach.for_groups.last and $smarty.foreach.for_values.last};{else},{/if}
{/foreach}
{/foreach}

{foreach from=$ovNames key=gName item=ovs}
{foreach from=$ovs key=vName item=label}
    SELECT @tpl_ovid_{$vName} := MAX(id) FROM civicrm_option_value WHERE option_group_id = @tpl_ogid_{$gName} AND name = '{$vName}';
{/foreach}
{/foreach}

INSERT INTO civicrm_msg_template
  (msg_title,      msg_subject,                  msg_text,                  msg_html,                  workflow_id,        is_default, is_reserved) VALUES
{foreach from=$ovNames key=gName item=ovs name=for_groups}
{foreach from=$ovs key=vName item=title name=for_values}
      {fetch assign=subject file="`$smarty.const.SMARTY_DIR`/../../xml/templates/message_templates/`$vName`_subject.tpl"}
      {fetch assign=text    file="`$smarty.const.SMARTY_DIR`/../../xml/templates/message_templates/`$vName`_text.tpl"}
      {fetch assign=html    file="`$smarty.const.SMARTY_DIR`/../../xml/templates/message_templates/`$vName`_html.tpl"}
      ('{$title}', '{$subject|escape:"quotes"}', '{$text|escape:"quotes"}', '{$html|escape:"quotes"}', @tpl_ovid_{$vName}, 1,          0),
      ('{$title}', '{$subject|escape:"quotes"}', '{$text|escape:"quotes"}', '{$html|escape:"quotes"}', @tpl_ovid_{$vName}, 0,          1) {if $smarty.foreach.for_groups.last and $smarty.foreach.for_values.last};{else},{/if}
{/foreach}
{/foreach}

{* Sample CiviMail Newsletter message template *}
INSERT INTO civicrm_msg_template
  (msg_title,      msg_subject,                  msg_text,                  msg_html,                  workflow_id,        is_default, is_reserved)
  VALUES
  ('Sample CiviMail Newsletter Template', 'Sample CiviMail Newsletter', '', '<table width=612 cellpadding=0 cellspacing=0 bgcolor="#ffffff">
  <tr>
    <td colspan="2" bgcolor="#ffffff" valign="middle" >
      <table border="0" cellpadding="0" cellspacing="0" >
        <tr>
          <td>
          <a href="http://www.civicrm.org"><img src="http://civicrm.org/sites/civicrm.org/files/top-logo_2.png" border=0 alt="Replace this logo with the URL to your own"></a>
          </td>
          <td>&nbsp; &nbsp;</td>
          <td>
          <a href="http://www.civicrm.org" style="text-decoration: none;"><font size=5 face="Arial, Verdana, sans-serif" color="#8bc539">Your Newsletter Title</font></a>
          </td>
        </tr>
      </table>
    </td>
  </tr>
  <tr>
    <td valign="top" width="70%">
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
          <li><a href="http://book.civicrm.org/user/advanced-configuration/email-system-configuration/">Configure your Email System</a>.</li>
          <li>Make sure your web hosting provider allows outgoing bulk mail, and see if they have a restriction on quantity.  If they don\'t allow bulk mail, consider <a href="https://civicrm.org/providers/hosting">finding a new host</a>.</li>
        </ul>
        Sincerely,
        <br /><br />
        Your Team
        <br /><br />
        </font>
        </td>
      </tr>
      </table>
    </td>

    <td valign="top" width="30%" bgcolor="#ffffff" style="border: 1px solid #056085;">
      <!-- right column -->
      <table cellpadding=10 cellspacing=0 border=0>
      <tr>
        <td bgcolor="#056085"><font face="Arial, Verdana, sans-serif" size="4" color="#ffffff">News and Events</font></td>
      </tr>
      <tr>
        <td style="color: #000; font-family: Arial, Verdana, sans-serif; font-size: 12px;" >
        <font face="Arial, Verdana, sans-serif" size="2" >
        <font color="#056085"><strong>Featured Events</strong> </font><br />
        Fundraising Dinner<br />
        Training Meeting<br />
        Board of Directors Annual Meeting<br />

        <br /><br />
        <font color="#056085"><strong>Community Events</strong></font><br />
        Bake Sale<br />
        Charity Auction<br />
        Art Exhibit<br />

        <br /><br />
        <font color="#056085"><strong>Important Dates</strong></font><br />
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
        <font color="#7dc857"><strong>Helpful Tips</strong></font>
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
      <hr />
      <a href="{literal}{action.unsubscribeUrl}{/literal}" title="click to unsubscribe">Click here</a> to unsubscribe from this mailing list.<br /><br />
      Our mailing address is:<br />
      {literal}{domain.address}{/literal}
    </td>
  </tr>
  </table>', NULL, 1, 0);
