{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
<div class="crm-block crm-form-block crm-mailing-upload-form-block">
{include file="CRM/common/WizardHeader.tpl"}

<div class="help">
    {ts}You can either <strong>upload</strong> the sms content from your computer OR <strong>compose</strong> the content on this screen.{/ts}
</div>

{include file="CRM/Mailing/Form/Count.tpl"}

<table class="form-layout-compressed">
  {if array_key_exists('SMStemplate', $form)}
    <tr class="crm-mailing-upload-form-block-template">
      <td class="label">{$form.SMStemplate.label}</td>
      <td>{$form.SMStemplate.html}</td>
    </tr>
  {/if}
    <tr class="crm-mailing-upload-form-block-upload_type"><td></td><td colspan="2">{$form.upload_type.label} {$form.upload_type.html} {help id="upload_type"}</td></tr>
</table>

<fieldset id="compose_id"><legend>{ts}Compose On-screen{/ts}</legend>
{include file="CRM/Contact/Form/Task/SMSCommon.tpl" upload=1 noAttach=1}
{include file="CRM/Mailing/Form/InsertTokens.tpl"}
</fieldset>

   <fieldset id="upload_id"><legend>{ts}Upload Content{/ts}</legend>
    <table class="form-layout-compressed">
        <tr class="crm-mailing-upload-form-block-textFile">
            <td class="label">{$form.textFile.label}</td>
            <td>{$form.textFile.html}<br />
                <span class="description">{ts}Browse to the <strong>TEXT</strong> message file you have prepared for this SMS.{/ts}<br /> {$docLink}</span>
            </td>
        </tr>
    </table>
  </fieldset>

  <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location=''}</div>
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
        }
    }
</script>
{/literal}
