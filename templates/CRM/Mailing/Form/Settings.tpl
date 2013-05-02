{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.3                                                |
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

<script  type="text/javascript">
{literal}

cj( function($) {
  // hide all the selects that contains only one option
  cj('.crm-message-select select').each(function (){
    if (cj(this).find('option').size() == 1) {
      cj(this).parent().parent().hide();
    }
  });
  if (!cj('#override_verp').attr('checked')){
    cj('.crm-mailing-settings-form-block-forward_replies,.crm-mailing-settings-form-block-auto_responder').hide();
  }
  cj('#override_verp').click(function(){
      cj('.crm-mailing-settings-form-block-forward_replies,.crm-mailing-settings-form-block-auto_responder').toggle();
       if (!cj('#override_verp').attr('checked')) {
             cj('#forward_replies').attr('checked',false);
             cj('#auto_responder').attr('checked',false);
           }
    });

});
{/literal}
</script>

<div class="crm-block crm-form-block crm-mailing-settings-form-block">
{include file="CRM/common/WizardHeader.tpl"}
<div id="help">
    {ts}These settings control tracking and responses to recipient actions. The number of recipients selected to receive this mailing is shown in the box to the right. If this count doesn't match your expectations, click <strong>Previous</strong> to review your selection(s).{/ts}
</div>
{include file="CRM/Mailing/Form/Count.tpl"}
<div class="crm-block crm-form-block crm-mailing-settings-form-block">
  <fieldset><legend>{ts}Tracking{/ts}</legend>
    <table class="form-layout"><tr class="crm-mailing-settings-form-block-url_tracking">
    <td class="label">{$form.url_tracking.label}</td>
        <td>{$form.url_tracking.html}
            <span class="description">{ts}Track the number of times recipients click each link in this mailing. NOTE: When this feature is enabled, all links in the message body will be automaticallly re-written to route through your CiviCRM server prior to redirecting to the target page.{/ts}</span>
        </td></tr><tr class="crm-mailing-settings-form-block-open_tracking">
    <td class="label">{$form.open_tracking.label}</td>
        <td>{$form.open_tracking.html}
            <span class="description">{ts}Track the number of times recipients open this mailing in their email software.{/ts}</span>
        </td></tr>
    </table>
  </fieldset>
  <fieldset><legend>{ts}Responding{/ts}</legend>
    <table class="form-layout">
        <tr class="crm-mailing-settings-form-block-override_verp"><td class="label">{$form.override_verp.label}</td>
            <td>{$form.override_verp.html}
                <span class="description">{ts}Recipients' replies are sent to a CiviMail specific address instead of the sender's address so they can be stored within CiviCRM.{/ts}</span>
            </td>
        </tr>
        <tr class="crm-mailing-settings-form-block-forward_replies"><td class="label ">{$form.forward_replies.label}</td>
            <td>{$form.forward_replies.html}
                <span class="description">{ts}If a recipient replies to this mailing, forward the reply to the FROM Email address specified for the mailing.{/ts}</span>
            </td>
  </tr>
    <tr class="crm-mailing-settings-form-block-auto_responder"><td class="label">{$form.auto_responder.label}</td>
        <td>{$form.auto_responder.html} &nbsp; {$form.reply_id.html}
            <span class="description">{ts}If a recipient replies to this mailing, send an automated reply using the selected message.{/ts}</span>
        </td>
    </tr>
    <tr class="crm-mailing-settings-form-block-unsubscribe_id crm-message-select"><td class="label">{$form.unsubscribe_id.label}</td>
        <td>{$form.unsubscribe_id.html}
            <span class="description">{ts}Select the automated message to be sent when a recipient unsubscribes from this mailing.{/ts}</span>
        </td>
    <tr>
    <tr class="crm-mailing-settings-form-block-resubscribe_id crm-message-select"><td class="label">{$form.resubscribe_id.label}</td>
        <td>{$form.resubscribe_id.html}
            <span class="description">{ts}Select the automated message to be sent when a recipient resubscribes to this mailing.{/ts}</span>
        </td>
    </tr>
    <tr class="crm-mailing-settings-form-block-optout_id crm-message-select"><td class="label ">{$form.optout_id.label}</td>
        <td>{$form.optout_id.html}
            <span class="description">{ts}Select the automated message to be sent when a recipient opts out of all mailings from your site.{/ts}</span>
        </td>
    </tr>
   </table>
  </fieldset>
  <fieldset><legend>Online Publication</legend>
    <table class="form-layout">
    <tr class="crm-mailing-group-form-block-visibility">
       <td class="label">{$form.visibility.label}</td><td>{$form.visibility.html} {help id="mailing-visibility"}
       </td>
       </tr>
    </table>
  </fieldset>
  <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl"}</div>
</div>

{* include jscript to warn if unsaved form field changes *}
{include file="CRM/common/formNavigate.tpl"}
</div>

