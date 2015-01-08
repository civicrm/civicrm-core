{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.5                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2014                                |
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
<div class="crm-block crm-form-block crm-{$formName}-block">
    <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="top"}</div>
    <table class="form-layout">
        {foreach from=$fields item=field key=fieldName}
            {assign var=n value=$fieldName}
            {if $form.$n}
              <tr class="crm-preferences-form-block-{$fieldName}">
                    {if $field.html_type EQ 'checkbox'|| $field.html_type EQ 'checkboxes'}
                        <td class="label"></td>
                        <td>
                            {$form.$n.html} {$form.$n.label}
                            {if $field.description}
                                <br /><span class="description">{$field.description}</span>
                            {/if}
                        </td>
                    {else}
                        <td class="label">{$form.$n.label}</td>
                        <td>
                            {$form.$n.html}
                            {if $field.description}
                                <br /><span class="description">{$field.description}</span>
                            {/if}
                        </td>
                    {/if}
              </tr>
          {/if}
        {/foreach}
  </table>

    <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
</div>
