{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
<div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="top"}</div>
<table class="form-layout-compressed">
  {foreach from=$settings_fields key="setting_name" item="setting_detail"}
    <tr class="crm-mail-form-block-{$setting_name}">
      <td class="label">{$form.$setting_name.label}</td>
      <td>
        {if !empty($setting_detail.wrapper_element)}
          {$setting_detail.wrapper_element.0}{$form.$setting_name.html}{$setting_detail.wrapper_element.1}
        {else}
          {$form.$setting_name.html}
        {/if}
        <div class="description">
          {ts}{$setting_detail.description}{/ts}
        </div>
        {if $setting_detail.help_text}
          {assign var='tplhelp_id' value = $setting_name|cat:'-id'|replace:'_':'-'}{help id="$tplhelp_id"}
        {/if}
      </td>
    </tr>
  {/foreach}
</table>
<div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
<div class="spacer"></div>
