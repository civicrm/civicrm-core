{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{if array_key_exists('template', $fieldSpec)}
  {include file=$fieldSpec.template}
{else}
  <td class="label">{$form.$fieldName.label}
    {if array_key_exists('help', $fieldSpec)}{assign var=help value=$fieldSpec.help}{help id=$help.id file=$help.file}{/if}
    {if $action == 2 && array_key_exists('is_add_translate_dialog', $fieldSpec)}{include file='CRM/Core/I18n/Dialog.tpl' table=$entityTable field=$fieldName id=$entityID}{/if}
  </td>
  <td>
    {if array_key_exists('pre_html_text', $fieldSpec)}{$fieldSpec.pre_html_text}{/if}{if $form.$fieldName.html}{$form.$fieldName.html}{else}{$fieldSpec.place_holder}{/if}{if array_key_exists('post_html_text', $fieldSpec)}{$fieldSpec.post_html_text}{/if}<br />
    {if array_key_exists('description', $fieldSpec)}<span class="description">{$fieldSpec.description}</span>{/if}
    {if array_key_exists('documentation_link', $fieldSpec)}{docURL page=$fieldSpec.documentation_link.page resource=$fieldSpec.documentation_link.resource}{/if}
  </td>
{/if}
