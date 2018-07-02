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
<div class="crm-block crm-form-block crm-mailing-upload-form-block">
{include file="CRM/common/WizardHeader.tpl"}

<div class="help">
    {ts}You can either <strong>upload</strong> the mailing content from your computer OR <strong>compose</strong> the content on this screen.{/ts} {help id="content-intro"}
</div>

{include file="CRM/Mailing/Form/Count.tpl"}

<table class="form-layout-compressed">
    <tr class="crm-mailing-upload-form-block-from_email_address"><td class="label">{$form.from_email_address.label}</td>
        <td>{$form.from_email_address.html} {help id ="id-from_email" file="CRM/Contact/Form/Task/Email.hlp" isAdmin=$isAdmin}</td>
    </tr>
    {if $trackReplies}
    <tr class="crm-mailing-upload-form-block-reply_to_address">
        <td style="color:#3E3E3E;"class="label">{ts}Reply-To{/ts}<span class="crm-marker">*</span></td>
        <td>{ts}Auto-Generated{/ts}</td>
    </tr>
    {else}
    <tr class="crm-mailing-upload-form-block-reply_to_address">
        <td class="label">{$form.reply_to_address.label}</td>
        <td>{$form.reply_to_address.html}</td>
    </tr>
    {/if}
    <tr class="crm-mailing-upload-form-block-template">
      <td class="label">{$form.template.label}</td>
  <td>{$form.template.html}</td>
    </tr>
    <tr class="crm-mailing-upload-form-block-subject"><td class="label">{$form.subject.label}</td>
        <td colspan="2">
          {$form.subject.html|crmAddClass:huge}&nbsp;
          <input class="crm-token-selector big" data-field="subject" />
          {help id="id-token-subject" tplFile=$tplFile isAdmin=$isAdmin file="CRM/Contact/Form/Task/Email.hlp"}
        </td>
    </tr>
    <tr class="crm-mailing-upload-form-block-upload_type"><td></td><td colspan="2">{$form.upload_type.label} {$form.upload_type.html} {help id="upload-compose"}</td></tr>
</table>

<fieldset id="compose_id"><legend>{ts}Compose On-screen{/ts}</legend>
{include file="CRM/Contact/Form/Task/EmailCommon.tpl" upload=1 noAttach=1}
</fieldset>

  {capture assign=docLink}{docURL page="Sample CiviMail Messages" text="More information and sample messages..." resource="wiki"}{/capture}
  <fieldset id="upload_id"><legend>{ts}Upload Content{/ts}</legend>
    <table class="form-layout-compressed">
        <tr class="crm-mailing-upload-form-block-textFile">
            <td class="label">{$form.textFile.label}</td>
            <td>{$form.textFile.html}<br />
                <span class="description">{ts}Browse to the <strong>TEXT</strong> message file you have prepared for this mailing.{/ts}<br /> {$docLink}</span>
            </td>
        </tr>
        <tr class="crm-mailing-upload-form-block-htmlFile">
            <td class="label">{$form.htmlFile.label}</td>
            <td>{$form.htmlFile.html}<br />
                <span class="description">{ts}Browse to the <strong>HTML</strong> message file you have prepared for this mailing.{/ts}<br /> {$docLink}</span>
            </td>
        </tr>
    </table>
  </fieldset>

  {include file="CRM/Form/attachment.tpl"}

  <fieldset><legend>{ts}Header / Footer{/ts}</legend>
    <table class="form-layout-compressed">
        <tr class="crm-mailing-upload-form-block-header_id">
            <td class="label">{$form.header_id.label}</td>
            <td>{$form.header_id.html}<br />
                <span class="description">{ts}You may choose to include a pre-configured Header block above your message.{/ts}</span>
            </td>
        </tr>
        <tr class="crm-mailing-upload-form-block-footer_id">
            <td class="label">{$form.footer_id.label}</td>
            <td>{$form.footer_id.html}<br />
                <span class="description">{ts}You may choose to include a pre-configured Footer block below your message. This is a good place to include the required unsubscribe, opt-out and postal address tokens.{/ts}</span>
            </td>
        </tr>
    </table>
  </fieldset>

  <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl"}</div>
</div><!-- / .crm-form-block -->

{* -- Javascript for showing/hiding the upload/compose options -- *}
{include file="CRM/common/showHide.tpl"}
{literal}
<script type="text/javascript">
    showHideUpload();
    function showHideUpload()
    {
  if (document.getElementsByName("upload_type")[0].checked) {
            cj('#compose_id').hide();
      cj('.crm-mailing-upload-form-block-template').hide();
      cj('#upload_id').show();
        } else {
            cj('#compose_id').show();
      cj('.crm-mailing-upload-form-block-template').show();
      cj('#upload_id').hide();
            verify( );
        }
    }
</script>
{/literal}
