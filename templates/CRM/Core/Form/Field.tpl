{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{if !empty($fieldSpec.template)}
  {include file=$fieldSpec.template}
{else}
  <td class="label">{$form.$fieldName.label}
    {if !empty($fieldSpec.help|smarty:nodefaults)}{assign var=help value=$fieldSpec.help}{help id=$help.id file=$help.file}{/if}
    {if $action == 2 && !empty($fieldSpec.is_add_translate_dialog)}{include file='CRM/Core/I18n/Dialog.tpl' table=$entityTable field=$fieldName id=$entityID}{/if}
  </td>
  <td>{if !empty($fieldSpec.pre_html_text)}{$fieldSpec.pre_html_text}{/if}{if $form.$fieldName.html}{$form.$fieldName.html}{else}{$fieldSpec.place_holder}{/if}{if !empty($fieldSpec.post_html_text)}{$fieldSpec.post_html_text}{/if}<br />
    {if !empty($fieldSpec.description)}<span class="description">{$fieldSpec.description}</span>{/if}
    {if !empty($fieldSpec.documentation_link)}{docURL page=$fieldSpec.documentation_link.page resource=$fieldSpec.documentation_link.resource}{/if}
  </td>
{/if}
