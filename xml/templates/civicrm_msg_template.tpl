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

{* Sample CiviMail Newsletter message templates *}
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

  {* Sample CiviMail Responsive Newsletter message templates CRM-14940 *}
  INSERT INTO civicrm_msg_template
    (msg_title,      msg_subject,                  msg_text,                  msg_html,                  workflow_id,        is_default, is_reserved)
    VALUES
    ('Sample Responsive Design Newsletter - Single Column', 'Sample Responsive Design Newsletter - Single Column', '', '<meta content="text/html; charset=utf-8" http-equiv="Content-Type" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <title></title>
    {literal}
    <style type="text/css">img {height: auto !important;}
             /* Client-specific Styles */
             #outlook a {padding:0;} /* Force Outlook to provide a "view in browser" menu link. */
             body{width:100% !important; -webkit-text-size-adjust:100%; -ms-text-size-adjust:100%; margin:0; padding:0;}

             /* Prevent Webkit and Windows Mobile platforms from changing default font sizes, while not breaking desktop design. */
             .ExternalClass {width:100%;} /* Force Hotmail to display emails at full width */
             .ExternalClass, .ExternalClass p, .ExternalClass span, .ExternalClass font, .ExternalClass td, .ExternalClass div {line-height: 100%;} /* Force Hotmail to display normal line spacing. */
             #backgroundTable {margin:0; padding:0; width:100% !important; line-height: 100% !important;}
             img {outline:none; text-decoration:none;border:none; -ms-interpolation-mode: bicubic;}
             a img {border:none;}
             .image_fix {display:block;}
             p {margin: 0px 0px !important;}
             table td {border-collapse: collapse;}
             table { border-collapse:collapse; mso-table-lspace:0pt; mso-table-rspace:0pt; }
             a {text-decoration: none;text-decoration:none;}
             /*STYLES*/
             table[class=full] { width: 100%; clear: both; }
             /*IPAD STYLES*/
             @media only screen and (max-width: 640px) {
             a[href^="tel"], a[href^="sms"] {
             text-decoration: none;
             color:#136388; /* or whatever your want */
             pointer-events: none;
             cursor: default;
             }
             .mobile_link a[href^="tel"], .mobile_link a[href^="sms"] {
             text-decoration: default;
             color:#136388;
             pointer-events: auto;
             cursor: default;
             }
             table[class=devicewidth] {width: 440px!important;text-align:center!important;}
             table[class=devicewidthmob] {width: 416px!important;text-align:center!important;}
             table[class=devicewidthinner] {width: 416px!important;text-align:center!important;}
             img[class=banner] {width: 440px!important;auto!important;}
             img[class=col2img] {width: 440px!important;height:auto!important;}
             table[class="cols3inner"] {width: 100px!important;}
             table[class="col3img"] {width: 131px!important;}
             img[class="col3img"] {width: 131px!important;height: auto!important;}
             table[class="removeMobile"]{width:10px!important;}
             img[class="blog"] {width: 440px!important;height: auto!important;}
             }

             /*IPHONE STYLES*/
             @media only screen and (max-width: 480px) {
             a[href^="tel"], a[href^="sms"] {
             text-decoration: none;
             color: #136388; /* or whatever your want */
             pointer-events: none;
             cursor: default;
             }
             .mobile_link a[href^="tel"], .mobile_link a[href^="sms"] {
             text-decoration: none;
             color:#136388;  
             pointer-events: auto;
             cursor: default;
             }
             table[class=devicewidth] {width: 280px!important;text-align:center!important;}
             table[class=devicewidthmob] {width: 260px!important;text-align:center!important;}
             table[class=devicewidthinner] {width: 260px!important;text-align:center!important;}
             img[class=banner] {width: 280px!important;height:100px!important;}
             img[class=col2img] {width: 280px!important;height:auto!important;}
             table[class="cols3inner"] {width: 260px!important;}
             img[class="col3img"] {width: 280px!important;height: auto!important;}
             table[class="col3img"] {width: 280px!important;}
             img[class="blog"] {width: 280px!important;auto!important;}
             td[class="padding-top-right15"]{padding:15px 15px 0 0 !important;}
             td[class="padding-right15"]{padding-right:15px !important;}
             }

    		 @media only screen and (max-device-width: 800px)
    { td[class="padding-top-right15"]{padding:15px 15px 0 0 !important;}
             td[class="padding-right15"]{padding-right:15px !important;}}		 
    		 @media only screen and (max-device-width: 769px) {
    			 .devicewidthmob {font-size:16px;}
    			 }

    			  @media only screen and (max-width: 640px) {
    				 .desktop-spacer {display:none !important;} 
    			  }
    </style>
    {/literal}
    <!-- Start of preheader --><!-- Start of preheader -->
    <table bgcolor="#89c66b" border="0" cellpadding="0" cellspacing="0" id="backgroundTable" width="100%">
    	<tbody>
    		<tr>
    			<td>
    			<table align="center" border="0" cellpadding="0" cellspacing="0" class="devicewidth" width="650">
    				<tbody>
    					<tr>
    						<td width="100%">
    						<table align="center" border="0" cellpadding="0" cellspacing="0" class="devicewidth" width="650">
    							<tbody><!-- Spacing -->
    								<tr>
    									<td height="20" width="100%">&nbsp;</td>
    								</tr>
    								<!-- Spacing -->
    								<tr>
    									<td>
    									<table align="left" border="0" cellpadding="0" cellspacing="0" class="devicewidthinner" width="310">
    										<tbody>
    											<tr>
    												<td align="left" style="font-family: Helvetica, arial, sans-serif; font-size: 16px; line-height:120%; color: #f8f8f8;padding-left:15px; padding-bottom:5px;" valign="middle">Organization or Program Name Here</td>
    											</tr>
    										</tbody>
    									</table>

    									<table align="right" border="0" cellpadding="0" cellspacing="0" class="emhide" width="310">
    										<tbody>
    											<tr>
    												<td align="right" style="font-family: Helvetica, arial, sans-serif; font-size: 16px;color: #f8f8f8;padding-right:15px;" valign="middle">Month and Year</td>
    											</tr>
    										</tbody>
    									</table>
    									</td>
    								</tr>
    								<!-- Spacing -->
    								<tr>
    									<td height="20" width="100%">&nbsp;</td>
    								</tr>
    								<!-- Spacing -->
    							</tbody>
    						</table>
    						</td>
    					</tr>
    				</tbody>
    			</table>
    			</td>
    		</tr>
    	</tbody>
    </table>
    <!-- End of main-banner-->

    <table bgcolor="#d8d8d8" border="0" cellpadding="0" cellspacing="0" id="backgroundTable" st-sortable="left-image" width="100%">
    	<tbody>
    		<tr>
    			<td>
    			<table align="center" border="0" cellpadding="0" cellspacing="0" class="devicewidth" width="650">
    				<tbody>
    					<tr>
    						<td width="100%">
    						<table align="center" bgcolor="#ffffff" border="0" cellpadding="0" cellspacing="0" class="devicewidth" width="650">
    							<tbody><!-- Spacing -->
    								<tr>
    									<td height="20" width="100%">
    									<table align="center" border="0" cellpadding="2" cellspacing="0" width="93%">
    										<tbody>
    											<tr>
    												<td rowspan="2" style="padding-top:10px; padding-bottom:10px;" width="38%"><img src="https://civicrm.org/sites/default/files/civicrm/custom/images/top-logo_2.png" alt="Replace with Your Logo" /></td>
    												<td align="right" width="62%">
    												<h6 class="collapse">&nbsp;</h6>
    												</td>
    											</tr>
    											<tr>
    												<td align="right">
    												<h5 style="font-family: Gill Sans, Gill Sans MT, Myriad Pro, DejaVu Sans Condensed, Helvetica, Arial, sans-serif; color:#136388;">&nbsp;</h5>
    												</td>
    											</tr>
    										</tbody>
    									</table>
    									</td>
    								</tr>
    								<tr>
    									<td>
    									<table align="center" border="0" cellpadding="0" cellspacing="0" class="devicewidth" width="650">
    										<tbody>
    											<tr>
    												<td width="100%">
    												<table align="center" bgcolor="#ffffff" border="0" cellpadding="0" cellspacing="0" class="devicewidth" width="650">
    													<tbody><!-- /Spacing -->
    														<tr>
    															<td style="font-family: Helvetica, arial, sans-serif; font-size: 23px; color:#f8f8f8; text-align:left; line-height: 32px; padding:5px 15px; background-color:#136388;">Headline Here</td>
    														</tr>
    														<!-- Spacing -->
    														<tr>
    															<td>
    															<table align="center" border="0" cellpadding="0" cellspacing="0" class="devicewidthinner" width="650">
    																<tbody><!-- hero story -->
    																	<tr>
    																		<td align="center" class="devicewidthinner" width="100%">
    																		<div class="imgpop"><a href="#"><img alt="" border="0" class="blog" height="auto" src="https://civicrm.org/sites/default/files/civicrm/custom/images/650x396.png" style="display:block; border:none; outline:none; text-decoration:none; padding:0; line-height:0;" width="650" /></a></div>
    																		</td>
    																	</tr>
    																	<!-- /hero image --><!-- Spacing -->
    																	<tr>
    																		<td height="15" style="font-size:1px; line-height:1px; mso-line-height-rule: exactly;" width="100%">&nbsp;</td>
    																	</tr>
    																	<!-- /Spacing -->
    																	<tr>
    																		<td style="font-family: Helvetica, arial, sans-serif; font-size: 18px;  text-align:left; line-height: 26px; padding:0 15px; color:#89c66b;"><a href="#" style="color:#89c66b;">Your Heading Here</a></td>
    																	</tr>
    																	<!-- Spacing -->
    																	<tr>
    																		<td height="15" style="font-size:1px; line-height:1px; mso-line-height-rule: exactly;" width="100%">&nbsp;</td>
    																	</tr>
    																	<!-- /Spacing --><!-- content -->
    																	<tr>
    																		<td style="padding:0 15px;">
    																		<p style="font-family: Helvetica, arial, sans-serif; font-size: 16px; color: #7a6e67; text-align:left; line-height: 26px; padding-bottom:10px;">{literal}{contact.email_greeting}{/literal},																		</p>
    																		<p style="font-family: Helvetica, arial, sans-serif; font-size: 16px; color: #7a6e67; text-align:left; line-height: 26px; padding-bottom:10px;"><span class="padding-right15" style="font-family: Helvetica, arial, sans-serif; font-size: 14px; color: #7a6e67; text-align:left; line-height: 24px;">Replace with your text and images, and remember to link the facebook and twitter links in the footer to your pages. Have fun!</span></p>
    																		</td>
    																	</tr>
    																	<tr>
    																		<td class="padding-right15" style="font-family: Helvetica, arial, sans-serif; font-size: 16px; font-weight:bold; color: #333333; text-align:left;line-height: 24px; padding-top:10px; padding-left:15px;"><a href="#" style="color:#136388;text-decoration:none;font-weight:bold;" target="_blank" title="read more">Read More</a></td>
    																	</tr>
    																	<!-- /button --><!-- Spacing -->
    																	<tr>
    																		<td height="20" style="font-size:1px; line-height:1px; mso-line-height-rule: exactly;">&nbsp;</td>
    																	</tr>
    																	<!-- Spacing --><!-- end of content -->
    																</tbody>
    															</table>
    															</td>
    														</tr>
    													</tbody>
    												</table>
    												</td>
    											</tr>
    										</tbody>
    									</table>
    									</td>
    								</tr>
    							</tbody>
    						</table>
    						</td>
    					</tr>
    				</tbody>
    			</table>
    			</td>
    		</tr>
    	</tbody>
    </table>
    <!-- end of hero image and story --><!-- story 1 -->

    <table bgcolor="#d8d8d8" border="0" cellpadding="0" cellspacing="0" id="backgroundTable" st-sortable="left-image" width="100%">
    	<tbody>
    		<tr>
    			<td>
    			<table align="center" border="0" cellpadding="0" cellspacing="0" class="devicewidth" width="650">
    				<tbody>
    					<tr>
    						<td width="100%">
    						<table align="center" bgcolor="#ffffff" border="0" cellpadding="0" cellspacing="0" class="devicewidth" width="650">
    							<tbody><!-- Spacing -->
    								<tr>
    									<td>
    									<table align="center" border="0" cellpadding="0" cellspacing="0" class="devicewidth" width="650">
    										<tbody>
    											<tr>
    												<td width="100%">
    												<table align="center" bgcolor="#ffffff" border="0" cellpadding="0" cellspacing="0" class="devicewidth" width="650">
    													<tbody>
    														<tr>
    															<td>
    															<table align="center" border="0" cellpadding="0" cellspacing="0" class="devicewidthinner" width="650">
    																<tbody><!-- image -->
    																	<tr>
    																		<td align="center" class="devicewidthinner" width="100%">
    																		<div class="imgpop"><a href="#" target="_blank"><img alt="" border="0" class="blog" height="250" src="https://civicrm.org/sites/default/files/civicrm/custom/images/banner-image-650-250.png" style="display:block; border:none; outline:none; text-decoration:none; padding:0; line-height:0;" width="650" /></a></div>
    																		</td>
    																	</tr>
    																	<!-- /image --><!-- Spacing -->
    																	<tr>
    																		<td height="15" style="font-size:1px; line-height:1px; mso-line-height-rule: exactly;" width="100%">&nbsp;</td>
    																	</tr>
    																	<!-- /Spacing -->
    																	<tr>
    																		<td style="font-family: Helvetica, arial, sans-serif; font-size: 18px;  text-align:left; line-height: 26px; padding:0 15px;"><a href="#" style="color:#89c66b;">Your Heading  Here</a></td>
    																	</tr>
    																	<!-- Spacing -->
    																	<tr>
    																		<td height="15" style="font-size:1px; line-height:1px; mso-line-height-rule: exactly;" width="100%">&nbsp;</td>
    																	</tr>
    																	<!-- /Spacing --><!-- content -->
    																	<tr>
    																		<td style="padding:0 15px;">
    																		<p style="font-family: Helvetica, arial, sans-serif; font-size: 16px; color: #7a6e67; text-align:left; line-height: 26px; padding-bottom:10px;">Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna </p>
    																		</td>
    																	</tr>
    																	<tr>
    																		<td class="padding-right15" style="font-family: Helvetica, arial, sans-serif; font-size: 16px; font-weight:bold; color: #333333; text-align:left;line-height: 24px; padding-top:10px; padding-left:15px;"><a href="#" style="color:#136388;text-decoration:none;font-weight:bold;" target="_blank" title="read more">Read More</a></td>
    																	</tr>
    																	<!-- /button --><!-- Spacing -->
    																	<tr>
    																		<td height="20" style="font-size:1px; line-height:1px; mso-line-height-rule: exactly;">&nbsp;</td>
    																	</tr>
    																	<!-- Spacing --><!-- end of content -->
    																</tbody>
    															</table>
    															</td>
    														</tr>
    													</tbody>
    												</table>
    												</td>
    											</tr>
    										</tbody>
    									</table>
    									</td>
    								</tr>
    							</tbody>
    						</table>
    						</td>
    					</tr>
    				</tbody>
    			</table>
    			</td>
    		</tr>
    	</tbody>
    </table>
    <!-- /story 2--><!-- banner1 -->

    <table bgcolor="#d8d8d8" border="0" cellpadding="0" cellspacing="0" id="backgroundTable" st-sortable="left-image" width="100%">
    	<tbody>
    		<tr>
    			<td>
    			<table align="center" border="0" cellpadding="0" cellspacing="0" class="devicewidth" width="650">
    				<tbody>
    					<tr>
    						<td width="100%">
    						<table align="center" bgcolor="#ffffff" border="0" cellpadding="0" cellspacing="0" class="devicewidth" width="650">
    							<tbody><!-- Spacing -->
    								<tr>
    									<td>
    									<table align="center" border="0" cellpadding="0" cellspacing="0" class="devicewidth" width="650">
    										<tbody>
    											<tr>
    												<td width="100%">
    												<table align="center" bgcolor="#89c66b" border="0" cellpadding="0" cellspacing="0" class="devicewidth" width="650">
    													<tbody>
    														<tr>
    															<td>
    															<table align="center" border="0" cellpadding="0" cellspacing="0" class="devicewidthinner" width="650">
    																<tbody><!-- image -->
    																	<tr>
    																		<td align="center" class="devicewidthinner" width="100%">
    																		<div class="imgpop"><a href="#" target="_blank"><img alt="" border="0" class="blog" height="auto" src="https://civicrm.org/sites/default/files/civicrm/custom/images/banner-image-650-250.png" style="display:block; border:none; outline:none; text-decoration:none; padding:0; line-height:0;" width="650" /></a></div>
    																		</td>
    																	</tr>
    																	<!-- /image --><!-- content --><!-- Spacing -->
    																	<tr>
    																		<td height="15" style="font-size:1px; line-height:1px; mso-line-height-rule: exactly;" width="100%">&nbsp;</td>
    																	</tr>
    																	<!-- /Spacing -->
    																	<tr>
    																		<td style="padding:15px;">
    																		<p style="font-family: Helvetica, arial, sans-serif; font-size: 16px; color: #f0f0f0; text-align:left; line-height: 26px; padding-bottom:10px;">Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna </p>
    																		</td>
    																	</tr>
    																	<!-- /button --><!-- white button -->
    																	<tr>
    																		<td class="padding-right15" style="font-family: Helvetica, arial, sans-serif; font-size: 16px; font-weight:bold; color: #333333; text-align:left;line-height: 24px; padding-top:10px; padding-bottom:10px; padding-left:15px;"><a href="#" style="color:#ffffff;text-decoration:none;font-weight:bold;" target="_blank" title="read more">Read More</a></td>
    																	</tr>
    																	<!-- /button --><!-- Spacing --><!-- end of content -->
    																</tbody>
    															</table>
    															</td>
    														</tr>
    													</tbody>
    												</table>
    												</td>
    											</tr>
    										</tbody>
    									</table>
    									</td>
    								</tr>
    							</tbody>
    						</table>
    						</td>
    					</tr>
    				</tbody>
    			</table>
    			</td>
    		</tr>
    	</tbody>
    </table>
    <!-- /banner 1--><!-- banner 2 -->

    <table bgcolor="#d8d8d8" border="0" cellpadding="0" cellspacing="0" id="backgroundTable" st-sortable="left-image" width="100%">
    	<tbody>
    		<tr>
    			<td>
    			<table align="center" border="0" cellpadding="0" cellspacing="0" class="devicewidth" width="650">
    				<tbody>
    					<tr>
    						<td width="100%">
    						<table align="center" bgcolor="#ffffff" border="0" cellpadding="0" cellspacing="0" class="devicewidth" width="650">
    							<tbody><!-- Spacing -->
    								<tr>
    									<td>
    									<table align="center" border="0" cellpadding="0" cellspacing="0" class="devicewidth" width="650">
    										<tbody>
    											<tr>
    												<td width="100%">
    												<table align="center" bgcolor="#136388" border="0" cellpadding="0" cellspacing="0" class="devicewidth" width="650">
    													<tbody>
    														<tr>
    															<td>
    															<table align="center" border="0" cellpadding="0" cellspacing="0" class="devicewidthinner" width="650">
    																<tbody><!-- image -->
    																	<tr>
    																		<td align="center" class="devicewidthinner" width="100%">
    																		<div class="imgpop"><a href="#" target="_blank"><img alt="" border="0" class="blog" height="auto" src="https://civicrm.org/sites/default/files/civicrm/custom/images/banner-image-650-250.png" style="display:block; border:none; outline:none; text-decoration:none; padding:0; line-height:0;" width="650" /></a></div>
    																		</td>
    																	</tr>
    																	<!-- /image --><!-- content --><!-- Spacing -->
    																	<tr>
    																		<td height="15" style="font-size:1px; line-height:1px; mso-line-height-rule: exactly;" width="100%">&nbsp;</td>
    																	</tr>
    																	<!-- /Spacing -->
    																	<tr>
    																		<td style="padding: 15px;">
    																		<p style="font-family: Helvetica, arial, sans-serif; font-size: 16px; color: #f0f0f0; text-align:left; line-height: 26px; padding-bottom:10px;">Remember to link the facebook and twitter links below to your pages!</p>
    																		</td>
    																	</tr>
    																	<!-- /button --><!-- white button -->
    																	<tr>
    																		<td class="padding-right15" style="font-family: Helvetica, arial, sans-serif; font-size: 16px; font-weight:bold; color: #333333; text-align:left;line-height: 24px; padding-top:10px; padding-bottom:10px; padding-left:15px;"><a href="#" style="color:#ffffff;text-decoration:none;font-weight:bold;" target="_blank" title="read more">Read More</a></td>
    																	</tr>
    																	<!-- /button --><!-- Spacing --><!-- end of content -->
    																</tbody>
    															</table>
    															</td>
    														</tr>
    													</tbody>
    												</table>
    												</td>
    											</tr>
    										</tbody>
    									</table>
    									</td>
    								</tr>
    							</tbody>
    						</table>
    						</td>
    					</tr>
    				</tbody>
    			</table>
    			</td>
    		</tr>
    	</tbody>
    </table>
    <!-- /banner2 --><!-- footer -->

    <table bgcolor="#d8d8d8" border="0" cellpadding="0" cellspacing="0" id="backgroundTable" st-sortable="footer" width="100%">
    	<tbody>
    		<tr>
    			<td>
    			<table align="center" border="0" cellpadding="0" cellspacing="0" class="devicewidth" width="650">
    				<tbody>
    					<tr>
    						<td width="100%">
    						<table align="center" bgcolor="#89c66b"  border="0" cellpadding="0" cellspacing="0" class="devicewidth" width="650">
    							<tbody><!-- Spacing -->
    								<tr>
    									<td height="10" style="font-size:1px; line-height:1px; mso-line-height-rule: exactly;">&nbsp;</td>
    								</tr>
    								<!-- Spacing -->
    								<tr>
    									<td><!-- logo -->
    									<table align="left" border="0" cellpadding="0" cellspacing="0" width="250">
    										<tbody>
    											<tr>
    												<td width="20">&nbsp;</td>
    												<td align="left" height="40" width="250"><span style="font-family: Helvetica, arial, sans-serif; font-size: 13px; text-align:left; line-height: 26px; padding-bottom:10px;"><a href="{literal}{action.unsubscribeUrl}{/literal}" style="color: #f0f0f0; ">Unsubscribe | </a><a href="{literal}{action.subscribeUrl}{/literal}"  style="color: #f0f0f0;">Subscribe |</a> <a href="{literal}{action.optOutUrl}{/literal}" style="color: #f0f0f0;">Opt out</a></span></td>
    											</tr>
    											<tr>
    												<td width="20">&nbsp;</td>
    												<td align="left" height="40" width="250"><span style="font-family: Helvetica, arial, sans-serif; font-size: 13px; text-align:left; line-height: 26px; padding-bottom:10px; color: #f0f0f0;">{literal}{domain.address}{/literal}</span></td>
    											</tr>
    										</tbody>
    									</table>
    									<!-- end of logo --><!-- start of social icons -->

    									<table align="right" border="0" cellpadding="0" cellspacing="0" height="40" vaalign="middle" width="60">
    										<tbody>
    											<tr>
    												<td align="left" height="22" width="22">
    												<div class="imgpop"><a href="#" target="_blank"><img alt="" border="0" height="22" src="https://civicrm.org/sites/default/files/civicrm/custom/images/facebook.png" style="display:block; border:none; outline:none; text-decoration:none;" width="22" /> </a></div>
    												</td>
    												<td align="left" style="font-size:1px; line-height:1px;" width="10">&nbsp;</td>
    												<td align="right" height="22" width="22">
    												<div class="imgpop"><a href="#" target="_blank"><img alt="" border="0" height="22" src="https://civicrm.org/sites/default/files/civicrm/custom/images/twitter.png" style="display:block; border:none; outline:none; text-decoration:none;" width="22" /> </a></div>
    												</td>
    												<td align="left" style="font-size:1px; line-height:1px;" width="20">&nbsp;</td>
    											</tr>
    										</tbody>
    									</table>
    									<!-- end of social icons --></td>
    								</tr>
    								<!-- Spacing -->
    								<tr>
    									<td height="10" style="font-size:1px; line-height:1px; mso-line-height-rule: exactly;">&nbsp;</td>
    								</tr>
    								<!-- Spacing -->
    							</tbody>
    						</table>
    						</td>
    					</tr>
    				</tbody>
    			</table>
    			</td>
    		</tr>
    	</tbody>
    </table>
    <!-- end of footer -->', NULL, 1, 0);
    
    INSERT INTO civicrm_msg_template
      (msg_title,      msg_subject,                  msg_text,                  msg_html,                  workflow_id,        is_default, is_reserved)
      VALUES
      ('Sample Responsive Design Newsletter - Two Column', 'Sample Responsive Design Newsletter - Two Column', '', '<meta content="text/html; charset=utf-8" http-equiv="Content-Type" />
      <meta content="width=device-width, initial-scale=1.0" name="viewport" />
      <title></title>
      {literal}
      <style type="text/css">img {height: auto !important;}
               /* Client-specific Styles */
               #outlook a {padding:0;} /* Force Outlook to provide a "view in browser" menu link. */
               body{width:100% !important; -webkit-text-size-adjust:100%; -ms-text-size-adjust:100%; margin:0; padding:0;}

               /* Prevent Webkit and Windows Mobile platforms from changing default font sizes, while not breaking desktop design. */
               .ExternalClass {width:100%;} /* Force Hotmail to display emails at full width */
               .ExternalClass, .ExternalClass p, .ExternalClass span, .ExternalClass font, .ExternalClass td, .ExternalClass div {line-height: 100%;} /* Force Hotmail to display normal line spacing. */
               #backgroundTable {margin:0; padding:0; width:100% !important; line-height: 100% !important;}
               img {outline:none; text-decoration:none;border:none; -ms-interpolation-mode: bicubic;}
               a img {border:none;}
               .image_fix {display:block;}
               p {margin: 0px 0px !important;}
               table td {border-collapse: collapse;}
               table { border-collapse:collapse; mso-table-lspace:0pt; mso-table-rspace:0pt; }
               a {color: #33b9ff;text-decoration: none;text-decoration:none!important;}
               /*STYLES*/
               table[class=full] { width: 100%; clear: both; }
               /*IPAD STYLES*/
               @media only screen and (max-width: 640px) {
               a[href^="tel"], a[href^="sms"] {
               text-decoration: none;
               color: #0a8cce; /* or whatever your want */
               pointer-events: none;
               cursor: default;
               }
               .mobile_link a[href^="tel"], .mobile_link a[href^="sms"] {
               text-decoration: default;
               color: #0a8cce !important;
               pointer-events: auto;
               cursor: default;
               }
               table[class=devicewidth] {width: 440px!important;text-align:center!important;}
               table[class=devicewidthmob] {width: 414px!important;text-align:center!important;}
               table[class=devicewidthinner] {width: 414px!important;text-align:center!important;}
               img[class=banner] {width: 440px!important;auto!important;}
               img[class=col2img] {width: 440px!important;height:auto!important;}
               table[class="cols3inner"] {width: 100px!important;}
               table[class="col3img"] {width: 131px!important;}
               img[class="col3img"] {width: 131px!important;height: auto!important;}
               table[class="removeMobile"]{width:10px!important;}
               img[class="blog"] {width: 440px!important;height: auto!important;}
               }

               /*IPHONE STYLES*/
               @media only screen and (max-width: 480px) {
               a[href^="tel"], a[href^="sms"] {
               text-decoration: none;
               color: #0a8cce; /* or whatever your want */
               pointer-events: none;
               cursor: default;
               }
               .mobile_link a[href^="tel"], .mobile_link a[href^="sms"] {
               text-decoration: default;
               color: #0a8cce !important; 
               pointer-events: auto;
               cursor: default;
               }
               table[class=devicewidth] {width: 280px!important;text-align:center!important;}
               table[class=devicewidthmob] {width: 260px!important;text-align:center!important;}
               table[class=devicewidthinner] {width: 260px!important;text-align:center!important;}
               img[class=banner] {width: 280px!important;height:100px!important;}
               img[class=col2img] {width: 280px!important;height:auto!important;}
               table[class="cols3inner"] {width: 260px!important;}
               img[class="col3img"] {width: 280px!important;height: auto!important;}
               table[class="col3img"] {width: 280px!important;}
               img[class="blog"] {width: 280px!important;auto!important;}
               td[class="padding-top-right15"]{padding:15px 15px 0 0 !important;}
               td[class="padding-right15"]{padding-right:15px !important;}
               }

      		 @media only screen and (max-device-width: 800px)
      { td[class="padding-top-right15"]{padding:15px 15px 0 0 !important;}
               td[class="padding-right15"]{padding-right:15px !important;}}		 
      		 @media only screen and (max-device-width: 769px) {
      			 .devicewidthmob {font-size:14px;}
      			 }

      			  @media only screen and (max-width: 640px) {
      				 .desktop-spacer {display:none !important;} 
      			  }
      </style>
      {/literal}
      <!-- Start of preheader --><!-- Start of preheader -->
      <table bgcolor="#0B4151" border="0" cellpadding="0" cellspacing="0" id="backgroundTable" width="100%">
      	<tbody>
      		<tr>
      			<td>
      			<table align="center" border="0" cellpadding="0" cellspacing="0" class="devicewidth" width="700">
      				<tbody>
      					<tr>
      						<td width="100%">
      						<table align="center" border="0" cellpadding="0" cellspacing="0" class="devicewidth" width="700">
      							<tbody><!-- Spacing -->
      								<tr>
      									<td height="20" width="100%">&nbsp;</td>
      								</tr>
      								<!-- Spacing -->
      								<tr>
      									<td>
      									<table align="left" border="0" cellpadding="0" cellspacing="0" class="devicewidthinner" width="360">
      										<tbody>
      											<tr>
      												<td align="left" style="font-family: Helvetica, arial, sans-serif; font-size: 14px; line-height:120%; color: #f8f8f8;padding-left:15px;" valign="middle">Organization or Program Name Here</td>
      											</tr>
      										</tbody>
      									</table>

      									<table align="right" border="0" cellpadding="0" cellspacing="0" class="emhide" width="320">
      										<tbody>
      											<tr>
      												<td align="right" style="font-family: Helvetica, arial, sans-serif; font-size: 16px;color: #f8f8f8;padding-right:15px;" valign="middle">Month Year</td>
      											</tr>
      										</tbody>
      									</table>
      									</td>
      								</tr>
      								<!-- Spacing -->
      								<tr>
      									<td height="20" width="100%">&nbsp;</td>
      								</tr>
      								<!-- Spacing -->
      							</tbody>
      						</table>
      						</td>
      					</tr>
      				</tbody>
      			</table>
      			</td>
      		</tr>
      	</tbody>
      </table>
      <!-- End of preheader --><!-- start of logo -->

      <table bgcolor="#d8d8d8" border="0" cellpadding="0" cellspacing="0" id="backgroundTable" st-sortable="left-image" width="100%">
      	<tbody>
      		<tr>
      			<td>
      			<table align="center" border="0" cellpadding="0" cellspacing="0" class="devicewidth" width="700">
      				<tbody>
      					<tr>
      						<td width="100%">
      						<table align="center" bgcolor="#f8f8f8" border="0" cellpadding="0" cellspacing="0" class="devicewidth" width="700">
      							<tbody><!-- Spacing -->
      								<tr>
      									<td height="20" width="100%">
      									<table align="center" border="0" cellpadding="2" cellspacing="0" width="93%">
      										<tbody>
      											<tr>
      												<td rowspan="2" width="38%"><a href="#"><img border="0" src="https://civicrm.org/sites/default/files/civicrm/custom/images/top-logo_2.png" /></a></td>
      												<td align="right" width="62%">
      												<h6 class="collapse">&nbsp;</h6>
      												</td>
      											</tr>
      											<tr>
      												<td align="right">

      												</td>
      											</tr>
      										</tbody>
      									</table>
      									</td>
      								</tr>

      							</tbody>
      						</table>
      						</td>
      					</tr>
      				</tbody>
      			</table>
      			</td>
      		</tr>
      	</tbody>
      </table>
      <!-- end of logo --> <!-- hero story 1 -->

      <table bgcolor="#d8d8d8" border="0" cellpadding="0" cellspacing="0" id="backgroundTable" st-sortable="left-image" width="101%">
      	<tbody>
      		<tr>
      			<td>
      			<table align="center" border="0" cellpadding="0" cellspacing="0" class="devicewidth" width="700">
      				<tbody>
      					<tr>
      						<td width="100%">
      						<table align="center" bgcolor="#f8f8f8" border="0" cellpadding="0" cellspacing="0" class="devicewidth" width="700">
      							<tbody>
      								<tr>
      									<td>
      									<table align="center" border="0" cellpadding="0" cellspacing="0" class="devicewidth" width="700">
      										<tbody>
      											<tr>
      												<td width="100%">
      												<table align="center" bgcolor="#f8f8f8" border="0" cellpadding="0" cellspacing="0" class="devicewidth" width="700">
      													<tbody><!-- /Spacing -->
      														<tr>
      															<td style="font-family: Helvetica, arial, sans-serif; font-size: 24px; color:#f8f8f8; text-align:left; line-height: 26px; padding:5px 15px; background-color: #80C457">Hero Story Heading</td>
      														</tr>
      														<!-- Spacing -->
      														<tr>
      															<td>
      															<table align="center" border="0" cellpadding="0" cellspacing="0" class="devicewidthinner" width="700">
      																<tbody><!-- image -->
      																	<tr>
      																		<td align="center" class="devicewidthinner" width="100%">
      																		<div class="imgpop"><a href="#" target="_blank"><img alt="" border="0" class="blog" height="396" src="https://civicrm.org/sites/default/files/civicrm/custom/images/700x396.png" style="display:block; border:none; outline:none; text-decoration:none; padding:0; line-height:0;" width="700" /></a></div>
      																		</td>
      																	</tr>
      																	<!-- /image --><!-- Spacing -->
      																	<tr>
      																		<td height="15" style="font-size:1px; line-height:1px; mso-line-height-rule: exactly;" width="100%">&nbsp;</td>
      																	</tr>
      																	<!-- /Spacing --><!-- hero story -->
      																	<tr>
      																		<td style="font-family: Helvetica, arial, sans-serif; font-size: 18px;  text-align:left; line-height: 26px; padding:0 15px;"><a href="#" style="color:#076187; text-decoration:none; " target="_blank">Subheading Here</a></td>
      																	</tr>
      																	<!-- Spacing -->
      																	<tr>
      																		<td height="15" style="font-size:1px; line-height:1px; mso-line-height-rule: exactly;" width="100%">&nbsp;</td>
      																	</tr><!-- /Spacing -->
      																	<tr>
      																		<td style="font-family: Helvetica, arial, sans-serif; font-size: 14px; color: #7a6e67; text-align:left; line-height: 26px; padding:0 15px;"><span class="padding-right15" style="font-family: Helvetica, arial, sans-serif; font-size: 14px; color: #7a6e67; text-align:left; line-height: 24px;">Replace with your text and images, and remember to link the facebook and twitter links in the footer to your pages. Have fun!</span></td>
      																	</tr>

      <!-- Spacing -->
      																	<tr>
      																		<td height="15" style="font-size:1px; line-height:1px; mso-line-height-rule: exactly;" width="100%">&nbsp;</td>
      																	</tr><!-- /Spacing -->

                <!-- /Spacing --><!-- /hero story -->

      																	<!-- Spacing -->                                                            <!-- Spacing -->



      																	<!-- Spacing --><!-- end of content -->
      																</tbody>
      															</table>
      															</td>
      														</tr>
      													</tbody>
      												</table>
      												</td>
      											</tr>
      										</tbody>
      									</table>
      									</td>
      								</tr>
      								<!-- Section Heading -->
      								<tr>
      									<td style="font-family: Helvetica, arial, sans-serif; font-size: 24px; color:#f8f8f8; text-align:left; line-height: 26px; padding:5px 15px; background-color: #80C457">Section Heading Here</td>
      								</tr>
      								<!-- /Section Heading -->
      							</tbody>
      						</table>
      						</td>
      					</tr>
      				</tbody>
      			</table>
      			</td>
      		</tr>
      	</tbody>
      </table>
      <!-- /hero story 1 --><!-- story one -->

      <table bgcolor="#d8d8d8" border="0" cellpadding="0" cellspacing="0" id="backgroundTable" st-sortable="left-image" width="100%">
      	<tbody>
      		<tr>
      			<td>
      			<table align="center" border="0" cellpadding="0" cellspacing="0" class="devicewidth" width="700">
      				<tbody>
      					<tr>
      						<td width="100%">
      						<table align="center" bgcolor="#ffffff" border="0" cellpadding="0" cellspacing="0" class="devicewidth" width="700">
      							<tbody><!-- Spacing -->
      								<tr>
      									<td class="desktop-spacer" height="20" style="font-size:1px; line-height:1px; mso-line-height-rule: exactly;">&nbsp;</td>
      								</tr>
      								<!-- Spacing -->
      								<tr>
      									<td>
      									<table align="center" border="0" cellpadding="0" cellspacing="0" class="devicewidth" width="660">
      										<tbody>
      											<tr>
      												<td><!-- Start of left column -->
      												<table align="left" border="0" cellpadding="0" cellspacing="0" class="devicewidth" width="330">
      													<tbody><!-- image -->
      														<tr>
      															<td align="center" class="devicewidth" height="150" valign="top" width="330"><a href="#"><img alt="" border="0" class="col2img" src="https://civicrm.org/sites/default/files/civicrm/custom/images/330x150.png" style="display:block; border:none; outline:none; text-decoration:none; display:block;" width="330" /></a></td>
      														</tr>
      														<!-- /image -->
      													</tbody>
      												</table>
      												<!-- end of left column --><!-- spacing for mobile devices-->

      												<table align="left" border="0" cellpadding="0" cellspacing="0" class="mobilespacing">
      													<tbody>
      														<tr>
      															<td height="15" style="font-size:1px; line-height:1px; mso-line-height-rule: exactly;" width="100%">&nbsp;</td>
      														</tr>
      													</tbody>
      												</table>
      												<!-- end of for mobile devices--><!-- start of right column -->

      												<table align="right" border="0" cellpadding="0" cellspacing="0" class="devicewidthmob" width="310">
      													<tbody>
      														<tr>
      															<td class="padding-top-right15" style="font-family: Helvetica, arial, sans-serif; font-size: 18px; text-align:left; line-height: 24px;"><a href="#" style="color:#076187; text-decoration:none; " target="_blank">Heading Here</a><a href="#" style="color:#076187; text-decoration:none;" target="_blank" title="CiviCRM helps keep the “City Beautiful” Movement”going strong"></a></td>
      														</tr>
      														<!-- end of title --><!-- Spacing -->
      														<tr>
      															<td height="15" style="font-size:1px; line-height:1px; mso-line-height-rule: exactly;" width="100%">&nbsp;</td>
      														</tr>
      														<!-- /Spacing --><!-- content -->
      														<tr>
      															<td class="padding-right15" style="font-family: Helvetica, arial, sans-serif; font-size: 14px; color: #7a6e67; text-align:left; line-height: 24px;"><span class="padding-right15" style="font-family: Helvetica, arial, sans-serif; font-size: 14px; color: #7a6e67; text-align:left; line-height: 24px;">Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod
                                                                  tempor incididunt ut labore et dolore magna </span></td>
      														</tr>
      														<tr>
      															<td class="padding-right15" style="font-family: Helvetica, arial, sans-serif; font-size: 14px; font-weight:bold; color: #333333; text-align:left;line-height: 24px; padding-top:10px;"><a href="#" style="color:#80C457;text-decoration:none;font-weight:bold;" target="_blank" title="CiviCRM helps keep the “City Beautiful” Movement”going strong">Read More</a></td>
      														</tr>
      														<!-- /button --><!-- end of content -->
      													</tbody>
      												</table>
      												<!-- end of right column --></td>
      											</tr>
      										</tbody>
      									</table>
      									</td>
      								</tr>
      								<!-- Spacing -->
      								<tr>
      									<td height="20" style="font-size:1px; line-height:1px; mso-line-height-rule: exactly;">&nbsp;</td>
      								</tr>
      							</tbody>
      						</table>
      						</td>
      					</tr>
      				</tbody>
      			</table>
      			</td>
      		</tr>
      	</tbody>
      </table>
      <!-- /story one -->
      <!-- story two -->

      <table bgcolor="#d8d8d8" border="0" cellpadding="0" cellspacing="0" id="backgroundTable" st-sortable="left-image" width="100%">
      	<tbody>
      		<tr>
      			<td>
      			<table align="center" border="0" cellpadding="0" cellspacing="0" class="devicewidth" width="700">
      				<tbody>
      					<tr>
      						<td width="100%">
      						<table align="center" bgcolor="#ffffff" border="0" cellpadding="0" cellspacing="0" class="devicewidth" width="700">
      							<tbody><!-- Spacing -->
      								<tr>
      									<td bgcolor="#076187" height="0" style="font-size:1px; line-height:1px; mso-line-height-rule: exactly;">&nbsp;</td>
      								</tr>
      								<!-- Spacing --><!-- Spacing -->
      								<tr>
      									<td class="desktop-spacer" height="20" style="font-size:1px; line-height:1px; mso-line-height-rule: exactly;">&nbsp;</td>
      								</tr>
      								<!-- Spacing -->
      								<tr>
      									<td>
      									<table align="center" border="0" cellpadding="0" cellspacing="0" class="devicewidth" width="660">
      										<tbody>
      											<tr>
      												<td><!-- Start of left column -->
      												<table align="left" border="0" cellpadding="0" cellspacing="0" class="devicewidth" width="330">
      													<tbody><!-- image -->
      														<tr>
      															<td align="center" class="devicewidth" height="150" valign="top" width="330"><a href="#"><img alt="" border="0" class="col2img" src="https://civicrm.org/sites/default/files/civicrm/custom/images/330x150.png" style="display:block; border:none; outline:none; text-decoration:none; display:block;" width="330" /></a></td>
      														</tr>
      														<!-- /image -->
      													</tbody>
      												</table>
      												<!-- end of left column --><!-- spacing for mobile devices-->

      												<table align="left" border="0" cellpadding="0" cellspacing="0" class="mobilespacing">
      													<tbody>
      														<tr>
      															<td height="15" style="font-size:1px; line-height:1px; mso-line-height-rule: exactly;" width="100%">&nbsp;</td>
      														</tr>
      													</tbody>
      												</table>
      												<!-- end of for mobile devices--><!-- start of right column -->

      												<table align="right" border="0" cellpadding="0" cellspacing="0" class="devicewidthmob" width="310">
      													<tbody>
      														<tr>
      															<td class="padding-top-right15" style="font-family: Helvetica, arial, sans-serif; font-size: 18px; text-align:left; line-height: 24px;"><a href="#" style="color:#076187; text-decoration:none; " target="_blank">Heading Here</a><a href="#" style="color:#076187; text-decoration:none;" target="_blank" title="How CiviCRM will take Tribodar Eco Learning Center to another level"></a></td>
      														</tr>
      														<!-- end of title --><!-- Spacing -->
      														<tr>
      															<td height="15" style="font-size:1px; line-height:1px; mso-line-height-rule: exactly;" width="100%">&nbsp;</td>
      														</tr>
      														<!-- /Spacing --><!-- content -->
      														<tr>
      															<td class="padding-right15" style="font-family: Helvetica, arial, sans-serif; font-size: 14px; color: #7a6e67; text-align:left; line-height: 24px;"><span class="padding-right15" style="font-family: Helvetica, arial, sans-serif; font-size: 14px; color: #7a6e67; text-align:left; line-height: 24px;">Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod
                                                                  tempor incididunt ut labore et dolore magna </span></td>
      														</tr>
      														<tr>
      															<td class="padding-right15" style="font-family: Helvetica, arial, sans-serif; font-size: 14px; font-weight:bold; color: #333333; text-align:left;line-height: 24px; padding-top:10px;"><a href="#" style="color:#80C457;text-decoration:none;font-weight:bold;" target="_blank" title="How CiviCRM will take Tribodar Eco Learning Center to another level">Read More</a></td>
      														</tr>
      														<!-- /button --><!-- end of content -->
      													</tbody>
      												</table>
      												<!-- end of right column --></td>
      											</tr>
      										</tbody>
      									</table>
      									</td>
      								</tr>
      								<!-- Spacing -->
      								<tr>
      									<td height="20" style="font-size:1px; line-height:1px; mso-line-height-rule: exactly;">&nbsp;</td>
      								</tr>
      							</tbody>
      						</table>
      						</td>
      					</tr>
      				</tbody>
      			</table>
      			</td>
      		</tr>
      	</tbody>
      </table>
      <!-- /story two --><!-- story three -->

      <table bgcolor="#d8d8d8" border="0" cellpadding="0" cellspacing="0" id="backgroundTable" st-sortable="left-image" width="100%">
      	<tbody>
      		<tr>
      			<td>
      			<table align="center" border="0" cellpadding="0" cellspacing="0" class="devicewidth" width="700">
      				<tbody>
      					<tr>
      						<td width="100%">
      						<table align="center" bgcolor="#ffffff" border="0" cellpadding="0" cellspacing="0" class="devicewidth" width="700">
      							<tbody><!-- Spacing -->
      								<tr>
      									<td bgcolor="#076187" height="0" style="font-size:1px; line-height:1px; mso-line-height-rule: exactly;">&nbsp;</td>
      								</tr>
      								<!-- Spacing --><!-- Spacing -->
      								<tr>
      									<td height="20" class="desktop-spacer" style="font-size:1px; line-height:1px; mso-line-height-rule: exactly;">&nbsp;</td>
      								</tr>
      								<!-- Spacing -->
      								<tr>
      									<td>
      									<table align="center" border="0" cellpadding="0" cellspacing="0" class="devicewidth" width="660">
      										<tbody>
      											<tr>
      												<td><!-- Start of left column -->
      												<table align="left" border="0" cellpadding="0" cellspacing="0" class="devicewidth" width="330">
      													<tbody><!-- image -->
      														<tr>
      															<td align="center" class="devicewidth" height="150" valign="top" width="330"><a href="#"><img alt="" border="0" class="col2img" src="https://civicrm.org/sites/default/files/civicrm/custom/images/330x150.png" style="display:block; border:none; outline:none; text-decoration:none; display:block;" width="330" /></a></td>
      														</tr>
      														<!-- /image -->
      													</tbody>
      												</table>
      												<!-- end of left column --><!-- spacing for mobile devices-->

      												<table align="left" border="0" cellpadding="0" cellspacing="0" class="mobilespacing">
      													<tbody>
      														<tr>
      															<td height="15" style="font-size:1px; line-height:1px; mso-line-height-rule: exactly;" width="100%">&nbsp;</td>
      														</tr>
      													</tbody>
      												</table>
      												<!-- end of for mobile devices--><!-- start of right column -->

      												<table align="right" border="0" cellpadding="0" cellspacing="0" class="devicewidthmob" width="310">
      													<tbody>
      														<tr>
      															<td class="padding-top-right15" style="font-family: Helvetica, arial, sans-serif; font-size: 18px;  text-align:left; line-height: 24px;"><a href="#" style="color:#076187; text-decoration:none; " target="_blank">Heading Here</a><a href="#" style="color:#076187; text-decoration:none;" target="_blank" title="CiviCRM provides a soup-to-nuts open-source solution for Friends of the San Pedro River"></a></td>
      														</tr>
      														<!-- end of title --><!-- Spacing -->
      														<tr>
      															<td height="15" style="font-size:1px; line-height:1px; mso-line-height-rule: exactly;" width="100%">&nbsp;</td>
      														</tr>
      														<!-- /Spacing --><!-- content -->
      														<tr>
      															<td class="padding-right15" style="font-family: Helvetica, arial, sans-serif; font-size: 14px; color: #7a6e67; text-align:left; line-height: 24px;"><span class="padding-right15" style="font-family: Helvetica, arial, sans-serif; font-size: 14px; color: #7a6e67; text-align:left; line-height: 24px;">Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod
                                                                  tempor incididunt ut labore et dolore magna </span></td>
      														</tr>
      														<tr>
      															<td style="font-family: Helvetica, arial, sans-serif; font-size: 14px; font-weight:bold; color: #333333; text-align:left;line-height: 24px; padding-top:10px;"><a href="#" style="color:#80C457;text-decoration:none;font-weight:bold;" target="_blank" title="CiviCRM provides a soup-to-nuts open-source solution for Friends of the San Pedro River">Read More</a></td>
      														</tr>
      														<!-- /button --><!-- end of content -->
      													</tbody>
      												</table>
      												<!-- end of right column --></td>
      											</tr>
      										</tbody>
      									</table>
      									</td>
      								</tr>
      								<!-- Spacing -->
      								<tr>
      									<td height="20" style="font-size:1px; line-height:1px; mso-line-height-rule: exactly;">&nbsp;</td>
      								</tr>
      								<!-- Spacing -->
      							</tbody>
      						</table>
      						</td>
      					</tr>
      				</tbody>
      			</table>
      			</td>
      		</tr>
      	</tbody>
      </table>
      <!-- /story three -->





      <!-- story four -->
      <table bgcolor="#d8d8d8" border="0" cellpadding="0" cellspacing="0" id="backgroundTable" st-sortable="left-image" width="100%">
      	<tbody>
      		<tr>
      			<td>
      			<table align="center" border="0" cellpadding="0" cellspacing="0" class="devicewidth" width="700">
      				<tbody>
      					<tr>
      						<td width="100%">
      						<table align="center" bgcolor="#ffffff" border="0" cellpadding="0" cellspacing="0" class="devicewidth" width="700">
      							<tbody>
                                  <!-- Spacing -->
      								<tr>
      									<td bgcolor="#076187" height="0" style="font-size:1px; line-height:1px; mso-line-height-rule: exactly;">&nbsp;</td>
      								</tr>
      								<!-- Spacing -->
                                  <!-- Spacing -->
      								<tr>
      									<td class="desktop-spacer" height="20" style="font-size:1px; line-height:1px; mso-line-height-rule: exactly;">&nbsp;</td>
      								</tr>
      								<!-- Spacing -->
      								<tr>
      									<td>
      									<table align="center" border="0" cellpadding="0" cellspacing="0" class="devicewidth" width="660">
      										<tbody>
      											<tr>
      												<td><!-- Start of left column -->
      												<table align="left" border="0" cellpadding="0" cellspacing="0" class="devicewidth" width="330">
      													<tbody><!-- image -->
      														<tr>
      															<td align="center" class="devicewidth" height="150" valign="top" width="330"><a href="#"><img alt="" border="0" class="col2img" src="https://civicrm.org/sites/default/files/civicrm/custom/images/330x150.png" style="display:block; border:none; outline:none; text-decoration:none; display:block;" width="330" /></a></td>
      														</tr>
      														<!-- /image -->
      													</tbody>
      												</table>
      												<!-- end of left column --><!-- spacing for mobile devices-->

      												<table align="left" border="0" cellpadding="0" cellspacing="0" class="mobilespacing">
      													<tbody>
      														<tr>
      															<td height="15" style="font-size:1px; line-height:1px; mso-line-height-rule: exactly;" width="100%">&nbsp;</td>
      														</tr>
      													</tbody>
      												</table>
      												<!-- end of for mobile devices--><!-- start of right column -->

      												<table align="right" border="0" cellpadding="0" cellspacing="0" class="devicewidthmob" width="310">
      													<tbody>
      														<tr>
      															<td class="padding-top-right15" style="font-family: Helvetica, arial, sans-serif; font-size: 18px;text-align:left; line-height: 24px;"><a href="#" style="color:#076187; text-decoration:none; " target="_blank">Heading Here</a><a href="#" style="color:#076187; text-decoration:none;" target="_blank" title="Google Summer of Code"></a></td>
      														</tr>
      														<!-- end of title --><!-- Spacing -->
      														<tr>
      															<td height="15" style="font-size:1px; line-height:1px; mso-line-height-rule: exactly;" width="100%">&nbsp;</td>
      														</tr>
      														<!-- /Spacing --><!-- content -->
      														<tr>
      															<td class="padding-right15" style="font-family: Helvetica, arial, sans-serif; font-size: 14px; color: #7a6e67; text-align:left; line-height: 24px;"><span class="padding-right15" style="font-family: Helvetica, arial, sans-serif; font-size: 14px; color: #7a6e67; text-align:left; line-height: 24px;">Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod
                                                                  tempor incididunt ut labore et dolore magna </span></td>
      														</tr>
      														<tr>
      															<td style="font-family: Helvetica, arial, sans-serif; font-size: 14px; font-weight:bold; color: #333333; text-align:left;line-height: 24px; padding-top:10px;"><a href="#" style="color:#80C457;text-decoration:none;font-weight:bold;" target="_blank" title="Google Summer of Code">Read More</a></td>
      														</tr>
      														<!-- /button --><!-- end of content -->
      													</tbody>
      												</table>
      												<!-- end of right column --></td>
      											</tr>
      										</tbody>
      									</table>
      									</td>
      								</tr>
      								<!-- Spacing -->
      								<tr>
      									<td height="20" style="font-size:1px; line-height:1px; mso-line-height-rule: exactly;">&nbsp;</td>
      								</tr>
      								<!-- Spacing -->
      							</tbody>
      						</table>
      						</td>
      					</tr>
      				</tbody>
      			</table>
      			</td>
      		</tr>
      	</tbody>
      </table>
      <!-- /story four -->

      <!-- footer -->

      <table bgcolor="#d8d8d8" border="0" cellpadding="0" cellspacing="0" id="backgroundTable" width="100%">
      	<tbody>
      		<tr>
      			<td>
      			<table align="center" border="0" cellpadding="0" cellspacing="0" class="devicewidth" width="700">
      				<tbody><!-- Spacing -->
      					<tr>
      						<td bgcolor="#076187" height="10" style="font-size:1px; line-height:1px; padding-top:10px; mso-line-height-rule: exactly;">&nbsp;</td>
      					</tr>
      					<!-- Spacing -->
      					<tr>
      						<td width="100%">
      						<table align="center" bgcolor="#076187" border="0" cellpadding="0" cellspacing="0" class="devicewidth" width="700">
      							<tbody>
      								<tr>
      									<td><!-- logo -->
      									<table align="right" border="0" cellpadding="0" cellspacing="0" height="40" vaalign="middle" width="60">
      										<tbody>
      											<tr>
      												<td align="left" height="22" width="22">
      												<div class="imgpop"><a href="#"><img alt="" border="0" height="22" src="https://civicrm.org/sites/default/files/civicrm/custom/images/facebook.png" style="display:block; border:none; outline:none; text-decoration:none;" width="22" /></a> </div>
      											  </td>
      												<td align="left" style="font-size:1px; line-height:1px;" width="10">&nbsp;</td>
      												<td align="right" height="22" width="22">
      												<div class="imgpop"><a href="#" target="_blank"><img alt="" border="0" height="22" src="https://civicrm.org/sites/default/files/civicrm/custom/images/twitter.png" style="display:block; border:none; outline:none; text-decoration:none;" width="22" /> </a></div>
      												</td>
      												<td align="left" style="font-size:1px; line-height:1px;" width="20">&nbsp;</td>
      											</tr>
      										</tbody>
      									</table>
      									<!-- end of logo --><!-- start of social icons -->

      									<table align="right" border="0" cellpadding="0" cellspacing="0" height="40" vaalign="middle" width="120">
      										<tbody>
      											<tr>
      												<td valign="top" width="120">
      												<div style="width:110px;"><img alt="Sent with CiviMail" height="100" src="http://civicrm.org/sites/civicrm.org/files/civicrm/custom/image/newsletter-stamp.png" style="display:block; outline:none; text-decoration:none; -ms-interpolation-mode: bicubic;" width="100" /></div>
      												</td>
      											</tr>
      										</tbody>
      									</table>
      									<!-- end of social icons --></td>
      								</tr>
      								<!-- Spacing -->
      								<tr>
      									<td height="10" style="font-size:1px; line-height:1px; mso-line-height-rule: exactly;">&nbsp;</td>
      								</tr>
      								<!-- Spacing -->
      							</tbody>
      						</table>
      						</td>
      					</tr>
      				</tbody>
      			</table>
      			</td>
      		</tr>
      	</tbody>
      </table>
      <!-- End of footer --><!-- Start of postfooter -->

      <table bgcolor="#d8d8d8" border="0" cellpadding="0" cellspacing="0" id="backgroundTable" st-sortable="left-image" width="100%">
      	<tbody>
      		<tr>
      			<td>
      			<table align="center" border="0" cellpadding="0" cellspacing="0" class="devicewidth" width="700">
      				<tbody>
      					<tr>
      						<td width="100%">
      						<table align="center" bgcolor="#f8f8f8" border="0" cellpadding="0" cellspacing="0" class="devicewidth" width="700">
      							<tbody><!-- Spacing -->
      								<tr bgcolor="#80C457">
      									<td height="10" width="100%">&nbsp;</td>
      								</tr>
      								<!-- Spacing -->
      								<tr bgcolor="#80C457">
      									<td align="center" st-content="viewonline" style="font-family: Helvetica, arial, sans-serif; font-size: 13px;color: #7a6e67;text-align:center;" valign="middle"><a href="#" style="color:#f8f8f8; text-decoration:none; font-family:Tahoma, Verdana, Arial, Sans-serif;">Go to our website</a><span>&nbsp;|&nbsp;</span><a href="{literal}{action.unsubscribeUrl}{/literal}" style="color:#f8f8f8; text-decoration:none; font-family:Tahoma, Verdana, Arial, Sans-serif;">Unsubscribe from this mailing</a><span>&nbsp;|&nbsp;</span><a href="{literal}{action.subscribeUrl}{/literal}" style="color:#f8f8f8; text-decoration:none; font-family:Tahoma, Verdana, Arial, Sans-serif;">Subscribe to this mailing</a><span>&nbsp;|&nbsp;</span><a href="{literal}{action.optOutUrl}{/literal}" style="color:#f8f8f8; text-decoration:none; font-family:Tahoma, Verdana, Arial, Sans-serif;">Opt out of all mailings</a></td>
      								</tr>
      								<tr bgcolor="#80C457">
      									<td align="center" st-content="viewonline" style="font-family: Helvetica, arial, sans-serif; font-size: 13px;color: #7a6e67;text-align:center;" valign="middle"><span style="color:#f8f8f8; text-decoration:none; font-family:Tahoma, Verdana, Arial, Sans-serif;">{literal}{domain.address}{/literal}</span></td>
      								</tr>
      								<!-- Spacing -->
      								<tr>
      									<td bgcolor="#80C457" height="10" width="100%">&nbsp;</td>
      								</tr>
      								<!-- Spacing -->
      							</tbody>
      						</table>
      						</td>
      					</tr>
      				</tbody>
      			</table>
      			</td>
      		</tr>
      	</tbody>
      </table>
      <!-- End of footer -->', NULL, 1, 0);