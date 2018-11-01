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
{* @todo the invoicing_blocks id is used by contribution preferences - get that out of the shared code & back to where it belongs *}
{* @todo with a small amount of tinkering most of this can be replaced by re-using the foreach loop in CRM_Core_EntityForm.tpl *}
<table class="form-layout" id="invoicing_blocks">
  {foreach from=$fields item=field key=fieldName}
    {assign var=n value=$fieldName}
    {if $form.$n}
      <tr class="crm-preferences-form-block-{$fieldName}">
        {if $field.html_type EQ 'checkbox'|| $field.html_type EQ 'checkboxes'}
          <td class="label"></td>
          <td>
            {$form.$n.html}
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
