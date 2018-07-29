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
{if $fieldSpec.template}
  {include file=$fieldSpec.template}
{else}
  <td class="label">{$form.$fieldName.label}
    {if $fieldSpec.help}{assign var=help value=$fieldSpec.help}{capture assign=helpFile}{if $fieldSpec.help}
      {$fieldSpec.help}
    {else}''{/if}
    {/capture}{help id=$help.id file=$help.file}{/if}
    {if $action == 2 && $fieldSpec.is_add_translate_dialog}{include file='CRM/Core/I18n/Dialog.tpl' table=$entityTable field=$fieldName id=$entityID}{/if}
  </td>
  <td>{if $form.$fieldName.html}{if $fieldSpec.formatter === 'crmMoney'}{$form.$fieldName.html|crmMoney}{else}{$form.$fieldName.html}{/if}{else}{$fieldSpec.place_holder}{/if}<br />
    {if $fieldSpec.description}<span class="description">{$fieldSpec.description}</span>{/if}
    {if $fieldSpec.documentation_link}{docURL page=$fieldSpec.documentation_link.page}{/if}
  </td>
{/if}
