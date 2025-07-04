{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{* add/update/view CiviCRM Profile *}
{if $action eq 8}
  <h3>{ts}Delete CiviCRM Profile{/ts} - {$profileTitle}</h3>
{/if}
<div class="crm-block crm-form-block crm-uf_group-form-block">
{if ($action eq 2 or $action eq 4) and $snippet neq 'json'} {* Update or View*}
  <div class="action-link">
    <a href="{crmURL p='civicrm/admin/uf/group/field' q="action=browse&reset=1&gid=$gid"}" class="button"><span>{ts}View or Edit Fields for this Profile{/ts}</a></span>
    <div class="clear"></div>
  </div>
{/if}
{if $action eq 8 or $action eq 64}
    <div class="messages status no-popup">
           {icon icon="fa-info-circle"}{/icon}
           {$message}
    </div>
{else}
    <table class="form-layout">
      {foreach from=$entityFields item=fieldSpec}
        {if not in_array($fieldSpec.name, $advancedFieldsConverted)}
          {assign var=fieldName value=$fieldSpec.name}
          <tr class="crm-{$entityInClassFormat}-form-block-{$fieldName}">
            {include file="CRM/Core/Form/Field.tpl"}
          </tr>
        {/if}
      {/foreach}
        {if $uf_group_type_extra}
          <tr class="crm-uf_group-form-block-uf_group_type_extra">
            <td class="label">{ts}Used in Forms{/ts} {help id='id-used_for_extra' file="CRM/UF/Form/Group.hlp"}</td>
            <td class="html-adjust">{$uf_group_type_extra}</td>
          </tr>
        {/if}
        <tr class="crm-uf_group-form-block-help_pre">
            <td class="label">{$form.help_pre.label} {help id='id-help_pre' file="CRM/UF/Form/Group.hlp"} {if $action == 2}{include file='CRM/Core/I18n/Dialog.tpl' table='civicrm_uf_group' field='help_pre' id=$gid}{/if}</td>
            <td class="html-adjust">{$form.help_pre.html}</td>
        </tr>
        <tr class="crm-uf_group-form-block-help_post">
            <td class="label">{$form.help_post.label} {help id='id-help_post' file="CRM/UF/Form/Group.hlp"} {if $action == 2}{include file='CRM/Core/I18n/Dialog.tpl' table='civicrm_uf_group' field='help_post' id=$gid}{/if}</td>
            <td class="html-adjust">{$form.help_post.html}</td>
        </tr>
        <tr class="crm-uf_group-form-block-is_active">
            <td class="label"></td><td class="html-adjust">{$form.is_active.html} {$form.is_active.label}</td>
        </tr>
    </table>
    {* adding advance setting tab *}
    {include file='CRM/UF/Form/AdvanceSetting.tpl'}
{/if}

<div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
</div>
{include file="CRM/common/showHide.tpl"}
