{*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2018                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
*}
<div class="crm-block crm-form-block crm-smtp-form-block">
  <div>
  <h3>{ts}General{/ts}</h3>
     <table class="form-layout-compressed">
       <tr class="crm-smtp-form-block-allow_mail_from_logged_in_contact">
         <td class="label">{$form.allow_mail_from_logged_in_contact.html}</td>
         <td>{$form.allow_mail_from_logged_in_contact.label} {help id=allow_mail_contact_email}</td>
       </tr>
     </table>
  </div>
  {crmRegion name="smtp-mailer-config"}
  <div class="crm-smtp-mailer-form-block">
  <h3>{ts}Mailer Configuration{/ts}</h3>
  <div class="help">
    <p>{ts}CiviCRM offers several options to send emails. You can send a test email to check your settings by clicking "Save and Send Test Email". If you're unsure of the correct values, check with your system administrator, ISP or hosting provider.{/ts}</p>
    <p>{ts}If you do not want users to send outbound mail from CiviCRM, select "Disable Outbound Email". NOTE: If you disable outbound email, and you are using Online Contribution pages or online Event Registration - you will need to disable automated receipts and registration confirmations.{/ts}</p>
    <p>{ts}If you choose Redirect to Database, all emails will be recorded as archived mailings instead of being sent out. They can be found in the civicrm_mailing_spool table in the CiviCRM database.{/ts}</p>
  </div>
     <table>
           <tr class="crm-smtp-form-block-outBound_option">
              <td class="label">{$form.outBound_option.label}</td>
              <td>{$form.outBound_option.html}</td>
           </tr>
        </table>
            <div id="bySMTP" class="mailoption">
            <fieldset>
                <legend>{ts}SMTP Configuration{/ts}</legend>
                <table class="form-layout-compressed">
                    <tr class="crm-smtp-form-block-smtpServer">
                       <td class="label">{$form.smtpServer.label}</td>
                       <td>{$form.smtpServer.html}<br  />
                            <span class="description">{ts}Enter the SMTP server (machine) name, such as "smtp.example.com".  If the server uses SSL, add "ssl://" to the beginning of the server name, such as "ssl://smtp.example.com".{/ts}</span>
                       </td>
                    </tr>
                    <tr class="crm-smtp-form-block-smtpPort">
                       <td class="label">{$form.smtpPort.label}</td>
                       <td>{$form.smtpPort.html}<br />
                           <span class="description">{ts}The most common SMTP port possibilities are 25, 465, and 587.  Check with your mail provider for the appropriate one.{/ts}</span>
                       </td>
                    </tr>
                    <tr class="crm-smtp-form-block-smtpAuth">
                       <td class="label">{$form.smtpAuth.label}</td>
                       <td>{$form.smtpAuth.html}<br />
                         <span class="description">{ts}Does your SMTP server require authentication (user name + password)?{/ts}</span>
                       </td>
                    </tr>
                    <tr class="crm-smtp-form-block-smtpUsername">
                       <td class="label">{$form.smtpUsername.label}</td>
                       <td>{$form.smtpUsername.html}</td>
                    </tr>
                    <tr class="crm-smtp-form-block-smtpPassword">
                       <td class="label">{$form.smtpPassword.label}</td>
                       <td>{$form.smtpPassword.html}<br />
                           <span class="description">{ts}If your SMTP server requires authentication, enter your Username and Password here.{/ts}</span>
                       </td>
                    </tr>
                </table>
           </fieldset>
        </div>
        <div id="bySendmail" class="mailoption">
            <fieldset>
                <legend>{ts}Sendmail Configuration{/ts}</legend>
                   <table class="form-layout-compressed">
                     <tr class="crm-smtp-form-block-sendmail_path">
                        <td class="label">{$form.sendmail_path.label}</td>
                        <td>{$form.sendmail_path.html}<br />
                            <span class="description">{ts}Enter the Sendmail Path. EXAMPLE: /usr/sbin/sendmail{/ts}</span>
                        </td>
                     </tr>
                     <tr class="crm-smtp-form-block-sendmail_args">
                        <td class="label">{$form.sendmail_args.label}</td>
                        <td>{$form.sendmail_args.html}</td>
                     </tr>
                    </table>
            </fieldset>
        </div>
        <div class="spacer"></div>
        <div class="crm-submit-buttons">
            {include file="CRM/common/formButtons.tpl"}
        </div>

{literal}
<script type="text/javascript">
    CRM.$(function($) {
      var mailSetting = $("input[name='outBound_option']:checked").val( );

      var archiveWarning = "{/literal}{ts escape='js'}WARNING: You are switching from a testing mode (Redirect to Database) to a live mode. Check Mailings > Archived Mailings, and delete any test mailings that are not in Completed status prior to running the mailing cron job for the first time. This will ensure that test mailings are not actually sent out.{/ts}{literal}"

        showHideMailOptions( $("input[name='outBound_option']:checked").val( ) ) ;

        function showHideMailOptions( value ) {
            switch( value ) {
              case "0":
                $("#bySMTP").show( );
                $("#bySendmail").hide( );
                $("#_qf_Smtp_refresh_test").prop('disabled', false);
                if (mailSetting == '5') {
                  alert(archiveWarning);
                }
              break;
              case "1":
                $("#bySMTP").hide( );
                $("#bySendmail").show( );
                $("#_qf_Smtp_refresh_test").prop('disabled', false);
                if (mailSetting == '5') {
                  alert(archiveWarning);
                }
              break;
              case "3":
                $('.mailoption').hide();
                $("#_qf_Smtp_refresh_test").prop('disabled', false);
                if (mailSetting == '5') {
                  alert(archiveWarning);
                }
              break;
              default:
                $("#bySMTP").hide( );
                $("#bySendmail").hide( );
                $("#_qf_Smtp_refresh_test").prop('disabled', true);
            }
        }

        $("input[name='outBound_option']").click( function( ) {
            showHideMailOptions( $(this).val( ) );
        });
    });

</script>
{/literal}
  </div>
  {/crmRegion}
</div>
