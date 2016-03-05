{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2015                                |
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
{* this template is used for adding/editing/deleting Message Templates *}
{capture assign=tokenDocsRepeated}{docURL page="user/common-workflows/tokens-and-mail-merge" text="token documentation"}{/capture}

<h3>{if $action eq 1}{ts}New Message Template{/ts}{elseif $action eq 2}{ts}Edit Message Template{/ts}{else}{ts}Delete Message Template{/ts}{/if}</h3>
{if $action neq 8}
<div class="help">
    {ts}Use this form to add or edit re-usable message templates.{/ts} {help id="id-intro" file="CRM/Admin/Page/MessageTemplates.hlp"}
</div>
{/if}

<div class="crm-block crm-form-block">
<div class="form-item" id="message_templates">
{if $action eq 8}
   <div class="messages status no-popup">
       <div class="icon inform-icon"></div>
       {ts}Do you want to delete this message template?{/ts}
   </div>
{else}
        <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="top"}</div>
        <table class="form-layout-compressed">
        <tr>
            <td class="label-left">{$form.msg_title.label}</td>
            <td>{$form.msg_title.html}
                <br /><span class="description html-adjust">{ts}Descriptive title of message - used for template selection.{/ts}</span>
            </td>
        </tr>
        <tr>
            <td class="label-left">{$form.msg_subject.label}</td>
            <td>
              {$form.msg_subject.html|crmAddClass:huge}
              <input class="crm-token-selector big" data-field="msg_subject" />
              {help id="id-token-subject" tplFile=$tplFile isAdmin=$isAdmin file="CRM/Contact/Form/Task/Email.hlp"}
             <br /><span class="description">{ts}Subject for email message.{/ts} {ts 1=$tokenDocsRepeated}Tokens may be included (%1).{/ts}</span>
            </td>
        </tr>
        <tr>
  </table>

      <div class="crm-accordion-wrapper crm-html_email-accordion ">
        <div class="crm-accordion-header">
            {ts}HTML Format{/ts}
            {help id="id-message-text" file="CRM/Contact/Form/Task/Email.hlp"}
        </div><!-- /.crm-accordion-header -->
         <div class="crm-accordion-body">
           <div class="helpIcon" id="helphtml">
             <input class="crm-token-selector big" data-field="msg_html" />
             {help id="id-token-html" tplFile=$tplFile isAdmin=$isAdmin file="CRM/Contact/Form/Task/Email.hlp"}
           </div>
                <div class="clear"></div>
                <div class='html'>
                    {$form.msg_html.html}
                    <div class="description">{ts}An HTML formatted version of this message will be sent to contacts whose Email Format preference is 'HTML' or 'Both'.{/ts} {ts 1=$tokenDocsRepeated}Tokens may be included (%1).{/ts}</div>
                </div>
        </div><!-- /.crm-accordion-body -->
      </div><!-- /.crm-accordion-wrapper -->

      <div class="crm-accordion-wrapper crm-plaint_text_email-accordion ">
        <div class="crm-accordion-header">
                {ts}Plain-Text Format{/ts}
        </div><!-- /.crm-accordion-header -->
            <div class="crm-accordion-body">
              <div class="helpIcon" id="helptext">
                <input class="crm-token-selector big" data-field="msg_text" />
                {help id="id-token-text" tplFile=$tplFile file="CRM/Contact/Form/Task/Email.hlp"}
              </div>
              <div class="clear"></div>
                <div class='text'>
                    {$form.msg_text.html|crmAddClass:huge}
                    <div class="description">{ts}Text formatted message.{/ts} {ts 1=$tokenDocsRepeated}Tokens may be included (%1).{/ts}</div>
                </div>
            </div><!-- /.crm-accordion-body -->
      </div><!-- /.crm-accordion-wrapper -->

      <div class="crm-accordion-wrapper crm-html_email-accordion ">
        <div class="crm-accordion-header">
            {$form.pdf_format_id.label}
        </div><!-- /.crm-accordion-header -->
         <div class="crm-accordion-body">
                <div class="spacer"></div>
                <div class='html'>
                    {$form.pdf_format_id.html}
                    {help id="id-msg-template" file="CRM/Contact/Form/Task/PDFLetterCommon.hlp"}
                    <div class="description">{ts}Page format to use when creating PDF files using this template.{/ts}</div>
                </div>
        </div><!-- /.crm-accordion-body -->
      </div><!-- /.crm-accordion-wrapper -->

    {if !$workflow_id}
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
