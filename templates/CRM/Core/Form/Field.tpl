{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
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
  <td>{$fieldSpec.pre_html_text}{if $form.$fieldName.html}{if $fieldSpec.formatter === 'crmMoney'}{$form.$fieldName.html|crmMoney:$fieldSpec.formatterParam}{else}{$form.$fieldName.html}{/if}{else}{$fieldSpec.place_holder}{/if}{$fieldSpec.post_html_text}<br />
    {if $fieldSpec.description}<span class="description">{$fieldSpec.description}</span>{/if}
    {if $fieldSpec.documentation_link}{docURL page=$fieldSpec.documentation_link.page resource=$fieldSpec.documentation_link.resource}{/if}
  </td>
{/if}
