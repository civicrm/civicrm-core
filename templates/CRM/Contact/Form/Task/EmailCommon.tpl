{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{*common template for compose mail*}
{capture assign='tokenTitle'}{ts}Tokens{/ts}{/capture}
<details class="crm-accordion-bold crm-html_email-accordion " open>
<summary>
  {ts}Message Body{/ts}
</summary>
 <div class="crm-accordion-body">
  <div class="helpIcon" id="helphtml">
    <input class="crm-token-selector big" data-field="html_message" />
    {help id="id-token-html" file="CRM/Contact/Form/Task/Email.hlp" title=$tokenTitle}
  </div>
  <div class="clear"></div>
    <div class='html'>
      {$form.html_message.html}<br />
    </div>
  </div>
</details>

<details class="crm-accordion-bold crm-plaint_text_email-accordion">
<summary>
  {ts}Plain-Text Format{/ts}
  {help id="text_message" file="CRM/Contact/Form/Task/Email.hlp" title=$tokenTitle}
</summary>
 <div class="crm-accordion-body">
   <div class="helpIcon" id="helptext">
     <input class="crm-token-selector big" data-field="text_message" />
     {help id="id-token-text" tplFile=$tplFile file="CRM/Contact/Form/Task/Email.hlp" title=$tokenTitle}
   </div>
    <div class='text'>
      {$form.text_message.html}<br />
    </div>
  </div>
</details>

<div id="editMessageDetails">
  <div id="updateDetails" >
    {if array_key_exists('updateTemplate', $form)}{$form.updateTemplate.html}&nbsp;{$form.updateTemplate.label}{/if}
  </div>
  <div>
    {if array_key_exists('saveTemplate', $form)}{$form.saveTemplate.html}&nbsp;{$form.saveTemplate.label}{/if}
  </div>
</div>

<div id="saveDetails" class="section">
  {if array_key_exists('saveTemplateName', $form)}
    <div class="label">{$form.saveTemplateName.label}</div>
    <div class="content">{$form.saveTemplateName.html|crmAddClass:huge}</div>
  {/if}
</div>

{if !$noAttach}
    {include file="CRM/Form/attachment.tpl"}
{/if}

{include file="CRM/Mailing/Form/InsertTokens.tpl"}
