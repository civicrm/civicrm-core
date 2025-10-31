{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{* this template is used for adding/editing/deleting Message Templates *}
{if $action neq 8}
  <div class="help">
    {ts}Use this form to add or edit re-usable message templates.{/ts} {help id="id-intro" file="CRM/Admin/Page/MessageTemplates.hlp"}
  </div>
{/if}
{capture assign='tokenTitle'}{ts}Tokens{/ts}{/capture}

<h3>{if $action eq 1}{ts}New Message Template{/ts}{elseif $action eq 2}{ts}Edit Message Template{/ts}{else}{ts}Delete Message Template{/ts}{/if}</h3>

<div class="crm-block crm-form-block">
  <div class="form-item" id="message_templates">
    {if $action eq 8}
      <div class="messages status no-popup">
        {icon icon="fa-info-circle"}{/icon}
        {ts 1=$msg_title|escape}Do you want to delete the message template '%1'?{/ts}
      </div>
    {else}
      <table class="form-layout-compressed">
        <tr>
          <td class="label-left">{$form.msg_title.label}</td>
          <td>{$form.msg_title.html}
            <br /><span class="description html-adjust">{ts}Descriptive title of message - used for template selection.{/ts}</span>
          </td>
        </tr>
        <tr>
          <td class="label-left">{$form.file_type.label}</td>
          <td>{$form.file_type.html}
            <br /><span class="description html-adjust">{ts}Compose a message on-screen for general use in emails or document output, or upload a pre-composed document for mail-merge.{/ts}</span>
          </td>
        </tr>
        <tr>
          <td class="label-left">{$form.msg_subject.label}</td>
          <td>
            {$form.msg_subject.html|crmAddClass:huge}
            <input class="crm-token-selector big" data-field="msg_subject" />
            {help id="id-token-subject" tplFile=$tplFile file="CRM/Contact/Form/Task/Email.hlp" title=$tokenTitle}
          </td>
        </tr>
        <tr>
          <td class="label-left">{$form.file_id.label}</td>
          <td>{$form.file_id.html}
            {if !empty($attachment)}
            {foreach from=$attachment key=attKey item=attVal}
            <div class="crm-attachment-wrapper crm-entity" id="file_{$attVal.fileID}">
              <strong><a class="crm-attachment" href="{$attVal.url}">{$attVal.cleanName}</a></strong>
              {if $attVal.description}&nbsp;-&nbsp;{$attVal.description}{/if}
              {if $attVal.deleteURLArgs}
                <a href="#" class="crm-hover-button delete-attachment" data-mimetype="{$attVal.mime_type}" data-filename="{$attVal.cleanName}" data-args="{$attVal.deleteURLArgs}" title="{ts escape='htmlattribute'}Delete File{/ts}"><span class="icon delete-icon"></span></a>
              {/if}
              {include file="CRM/Form/attachmentjs.tpl" context='MessageTemplate'}
              {/foreach}
              {/if}
              <br /><span class="description html-adjust">{ts}Upload the document in .docx or .odt format.{/ts}</span>
          </td>
        </tr>
        <tr>
      </table>

      <details id="msg_html_section" class="crm-accordion-bold crm-html_email-accordion " open>
        <summary>
          {ts}Message Body{/ts}
        </summary>
        <div class="crm-accordion-body">
          <div class="helpIcon" id="helphtml">
            <input class="crm-token-selector big" data-field="msg_html" />
            {help id="id-token-html" tplFile=$tplFile file="CRM/Contact/Form/Task/Email.hlp" title=$tokenTitle}
          </div>
          <div class="clear"></div>
          <div class='html'>
            {$form.msg_html.html|crmAddClass:huge}
          </div>
        </div>
      </details>

      <details id="msg_text_section" class="crm-accordion-bold crm-plaint_text_email-accordion " open>
        <summary>
          {ts}Optional Plain-Text Format{/ts}
          {help id="msg_text" file="CRM/Contact/Form/Task/Email.hlp" title=$form.msg_text.textLabel}
        </summary>
        <div class="crm-accordion-body">
          <div class="helpIcon" id="helptext">
            <input class="crm-token-selector big" data-field="msg_text" />
            {help id="id-token-text" tplFile=$tplFile file="CRM/Contact/Form/Task/Email.hlp" title=$tokenTitle}
          </div>
          <div class="clear"></div>
          <div class='text'>
            {$form.msg_text.html|crmAddClass:huge}
          </div>
        </div>
      </details>

      <details id="pdf_format" class="crm-accordion-bold crm-html_email-accordion " open>
        <summary>
          {$form.pdf_format_id.label}
        </summary>
        <div class="crm-accordion-body">
          <div class="spacer"></div>
          <div class='html'>
            {$form.pdf_format_id.html}
            {help id="pdf_format_id" file="CRM/Contact/Form/Task/PDFLetterCommon.hlp"}
            <div class="description">{ts}Page format to use when creating PDF files using this template.{/ts}</div>
          </div>
        </div>
      </details>

      {if !$isWorkflow}
        <table class="form-layout-compressed">
          <tr>
            <td class="label-left">{$form.is_active.label}</td>
            <td>{$form.is_active.html}</td>
          </tr>
        </table>
      {/if}
    {/if}
    <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
    <br clear="all" />
  </div>
</div> <!-- end of crm-form-block -->
{include file="CRM/Mailing/Form/InsertTokens.tpl"}

{literal}
  <script type='text/javascript'>
    CRM.$(function($) {
      var mimeType = null;
      // if default file is selected then hide the file upload field
      if ($('a.delete-attachment').length) {
        $('#file_id').hide();
        mimeType = $('a.delete-attachment').data('mimetype');
      }

      var selector = $("input[id$='_file_type']").attr('type') == 'radio' ? "input[id$='_file_type']:checked" : "input[id$='_file_type']";
      showHideUpload($(selector).val());
      $("input[id$='_file_type']").on('click', function(){
        showHideUpload(this.value);
      });
      function showHideUpload(type) {
        var show = (type == 1) ? false : true;
        $("#msg_html_section, #msg_text_section, #pdf_format").toggle(show);
        $("#file_id").parent().parent().toggle(!show);

        // auto file type validation
        if (type) {
          var validType = 'application/vnd.oasis.opendocument.text, application/vnd.openxmlformats-officedocument.wordprocessingml.document';
          $("#file_id").attr('accept', validType);
        }
      }
    });
  </script>
{/literal}
