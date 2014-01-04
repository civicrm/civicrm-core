{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.4                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2013                                |
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
<div id="help">
    {ts}<p>CiviCRM offers several options to send emails. The default option should work fine on linux systems. If you are using windows, you probably need to enter settings for your SMTP/Sendmail server. You can send a test email to check your settings by clicking "Save and Send Test Email". If you're unsure of the correct values, check with your system administrator, ISP or hosting provider.</p>

    <p>If you do not want users to send outbound mail from CiviCRM, select "Disable Outbound Email". NOTE: If you disable outbound email, and you are using Online Contribution pages or online Event Registration - you will need to disable automated receipts and registration confirmations.</p>

   <p>If you choose Redirect to Database, all emails will be recorded as archived mailings instead of being sent out.</p>{/ts}

</div>
     <table class="form-layout-compressed">
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
                            <span class="description">{ts}Enter the SMTP server (machine) name. EXAMPLE: smtp.example.com{/ts}</span>
                       </td>
                    </tr>
                    <tr class="crm-smtp-form-block-smtpPort">
                       <td class="label">{$form.smtpPort.label}</td>
                       <td>{$form.smtpPort.html}<br />
                           <span class="description">{ts}The standard SMTP port is 25. You should only change that value if your SMTP server is running on a non-standard port.{/ts}</span>
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
            <span class="place-left">&nbsp;</span>
            <span class="crm-button crm-button-type-next crm-button_qf_Smtp_refresh_test">{$form._qf_Smtp_refresh_test.html}</span>
        </div>
</div>

{literal}
<script type="text/javascript">
    cj( function( ) {
      var mailSetting = cj("input[name='outBound_option']:checked").val( );

      var archiveWarning = "{/literal}{ts escape='js'}WARNING: You are switching from a testing mode (Redirect to Database) to a live mode. Check Mailings > Archived Mailings, and delete any test mailings that are not in Completed status prior to running the mailing cron job for the first time. This will ensure that test mailings are not actually sent out.{/ts}{literal}"

        showHideMailOptions( cj("input[name='outBound_option']:checked").val( ) ) ;

        function showHideMailOptions( value ) {
            switch( value ) {
              case "0":
                cj("#bySMTP").show( );
                cj("#bySendmail").hide( );
                cj("#_qf_Smtp_refresh_test").show( );
                if (mailSetting == '5') {
                  alert(archiveWarning);
                }
              break;
              case "1":
                cj("#bySMTP").hide( );
                cj("#bySendmail").show( );
                cj("#_qf_Smtp_refresh_test").show( );
                if (mailSetting == '5') {
                  alert(archiveWarning);
                }
              break;
              case "3":
                cj('.mailoption').hide();
                cj("#_qf_Smtp_refresh_test").show( );
                if (mailSetting == '5') {
                  alert(archiveWarning);
                }
              break;
              default:
                cj("#bySMTP").hide( );
                cj("#bySendmail").hide( );
                cj("#_qf_Smtp_refresh_test").hide( );
            }
        }

        cj("input[name='outBound_option']").click( function( ) {
            showHideMailOptions( cj(this).val( ) );
        });
    });

</script>
{/literal}
