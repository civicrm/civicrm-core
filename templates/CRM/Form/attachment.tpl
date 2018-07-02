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
{if $form.attachFile_1 OR $currentAttachmentInfo}
{if $action EQ 4 AND $currentAttachmentInfo} {* For View action we exclude the form fields and just show any current attachments. *}
    <tr>
        <td class="label"><label>{ts}Current Attachment(s){/ts}</label></td>
        <td class="view-value">
          {foreach from=$currentAttachmentInfo key=attKey item=attVal}
                <div id="attachStatusMesg" class="status hiddenElement"></div>
                <div id="attachFileRecord_{$attVal.fileID}">
                  <strong><a href="{$attVal.url}"><i class="crm-i {$attVal.icon}"></i> {$attVal.cleanName}</a></strong>
                  {if $attVal.description}&nbsp;-&nbsp;{$attVal.description}{/if}
                  {if !empty($attVal.tag)}
                    <br />
                    {ts}Tags{/ts}: {$attVal.tag}
                    <br />
                  {/if}
                </div>
          {/foreach}
        </td>
    </tr>
{elseif $action NEQ 4}
    {if $context EQ 'pcpCampaign'}
      {capture assign=attachTitle}{ts}Include a Picture or an Image{/ts}{/capture}
    {else}
      {capture assign=attachTitle}{ts}Attachment(s){/ts}{/capture}
    {/if}
    {if !$noexpand}
    <div class="crm-accordion-wrapper {if $context NEQ 'pcpCampaign' AND !$currentAttachmentInfo}collapsed{/if}">
       <div class="crm-accordion-header">
          {$attachTitle}
      </div><!-- /.crm-accordion-header -->
     <div class="crm-accordion-body">
     {/if}
    <div id="attachments">
      <table class="form-layout-compressed">
      {if $form.attachFile_1}
        {if $context EQ 'pcpCampaign'}
            <div class="description">{ts}You can upload a picture or image to include on your page. Your file should be in .jpg, .gif, or .png format. Recommended image size is 250 x 250 pixels. Images over 360 pixels wide will be automatically resized to fit.{/ts}</div>
        {/if}
        <tr>
          <td class="label">{$form.attachFile_1.label}</td>
          <td>{$form.attachFile_1.html}&nbsp;{$form.attachDesc_1.html}<a href="#" class="crm-hover-button crm-clear-attachment" style="visibility: hidden;" title="{ts}Clear{/ts}"><i class="crm-i fa-times"></i></a>
            <div class="description">{ts}Browse to the <strong>file</strong> you want to upload.{/ts}{if $maxAttachments GT 1} {ts 1=$maxAttachments}You can have a maximum of %1 attachment(s).{/ts}{/if} {ts 1=$config->maxFileSize}Each file must be less than %1M in size. You can also add a short description.{/ts}</div>
          </td>
        </tr>
        {if $form.tag_1.html}
          <tr>
            <td class="label">{$form.tag_1.label}</td>
            <td><div class="crm-select-container crm-attachment-tags">{$form.tag_1.html}</div></td>
          </tr>
        {/if}
        {if $tagsetInfo.file}
          <tr>{include file="CRM/common/Tagset.tpl" tagsetType='file' tableLayout=true tagsetElementName="file_taglist_1"}</tr>
        {/if}
        {section name=attachLoop start=2 loop=$numAttachments+1}
          {assign var=index value=$smarty.section.attachLoop.index}
          {assign var=attachName value="attachFile_"|cat:$index}
          {assign var=attachDesc value="attachDesc_"|cat:$index}
          {assign var=tagElement value="tag_"|cat:$index}
            <tr class="attachment-fieldset solid-border-top"><td colspan="2"></td></tr>
            <tr>
                <td class="label">{$form.attachFile_1.label}</td>
                <td>{$form.$attachName.html}&nbsp;{$form.$attachDesc.html}<a href="#" class="crm-hover-button crm-clear-attachment" style="visibility: hidden;" title="{ts}Clear{/ts}"><i class="crm-i fa-times"></i></a></td>
            </tr>
            <tr>
              <td class="label">{$form.$tagElement.label}</td>
              <td><div class="crm-select-container crm-attachment-tags">{$form.$tagElement.html}</div></td>
            </tr>
            {if $tagsetInfo.file}
              <tr>{include file="CRM/common/Tagset.tpl" tagsetType='file' tableLayout=true tagsetElementName="file_taglist_$index"}</tr>
            {/if}
        {/section}

      {/if}
      {if $currentAttachmentInfo}
        <tr class="attachment-fieldset solid-border-top"><td colspan="2"></td></tr>
        <tr>
            <td class="label">{ts}Current Attachment(s){/ts}</td>
            <td class="view-value">
          {foreach from=$currentAttachmentInfo key=attKey item=attVal}
                <div class="crm-attachment-wrapper crm-entity" id="file_{$attVal.fileID}">
                  <strong><a class="crm-attachment" href="{$attVal.url}">{$attVal.cleanName}</a></strong>
                  {if $attVal.description}&nbsp;-&nbsp;{$attVal.description}{/if}
                  {if $attVal.deleteURLArgs}
                   <a href="#" class="crm-hover-button delete-attachment" data-filename="{$attVal.cleanName}" data-args="{$attVal.deleteURLArgs}" title="{ts}Delete File{/ts}"><span class="icon delete-icon"></span></a>
                  {/if}
                  {if !empty($attVal.tag)}
                    <br/>
                    {ts}Tags{/ts}: {$attVal.tag}
                    <br/>
                  {/if}
                </div>
          {/foreach}
            </td>
        </tr>
        <tr>
            <td class="label">&nbsp;</td>
            <td>{$form.is_delete_attachment.html}&nbsp;{$form.is_delete_attachment.label}
            </td>
        </tr>
      {/if}
      </table>
    </div>
  </div><!-- /.crm-accordion-body -->
  </div><!-- /.crm-accordion-wrapper -->
    {literal}
    <script type="text/javascript">
      CRM.$(function($) {
        var $form = $("form.{/literal}{$form.formClass}{literal}");
        $form
          .on('click', '.crm-clear-attachment', function(e) {
            e.preventDefault();
            $(this).css('visibility', 'hidden').closest('td').find(':input').val('');
          })
          .on('change', '#attachments :input', function() {
            $(this).closest('td').find('.crm-clear-attachment').css('visibility', 'visible');
          });
      });
    </script>
    {/literal}
 {/if} {* edit/add if*}

{if $currentAttachmentInfo}
{include file="CRM/Form/attachmentjs.tpl"}
{/if}

{/if} {* top level if *}
