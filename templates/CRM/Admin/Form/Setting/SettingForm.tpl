{*
+--------------------------------------------------------------------+
| CiviCRM version 5                                                  |
+--------------------------------------------------------------------+
| Copyright CiviCRM LLC (c) 2004-2019                                |
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
