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
    {if $fieldSpec.help.id}{help id=$fieldSpec.help.id file=$fieldSpec.help.file}{/if}
    {if $action == 2 && array_key_exists('is_add_translate_dialog', $fieldSpec)}{include file='CRM/Core/I18n/Dialog.tpl' table=$entityTable field=$fieldName id=$entityID}{/if}
  </td>
  <td>
    {if $fieldSpec.pre_html_text}{$fieldSpec.pre_html_text}{/if}{if $form.$fieldName.html}{$form.$fieldName.html}{else}{$fieldSpec.place_holder}{/if}{if $fieldSpec.post_html_text}{$fieldSpec.post_html_text}{/if}<br />
    {if $fieldSpec.description}<span class="description">{$fieldSpec.description}</span>{/if}
    {if $fieldSpec.documentation_link.page}{docURL page=$fieldSpec.documentation_link.page resource=$fieldSpec.documentation_link.resource}{/if}
  </td>
{/if}
