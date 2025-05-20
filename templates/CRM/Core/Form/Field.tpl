{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{if array_key_exists('template', $fieldSpec) && $fieldSpec.template}
  {include file=$fieldSpec.template}
{else}
  <td class="label">{$form.$fieldName.label}
    {if array_key_exists('help', $fieldSpec) && $fieldSpec.help.id}
      {help values=$fieldSpec.help title=$fieldSpec.title}
    {/if}
    {if $action == 2 && array_key_exists('is_add_translate_dialog', $fieldSpec)}{include file='CRM/Core/I18n/Dialog.tpl' table=$entityTable field=$fieldName id=$entityID}{/if}
  </td>
  <td>
    {if $form.$fieldName.html}{$form.$fieldName.html}{/if}{if array_key_exists('post_html_text', $fieldSpec) && $fieldSpec.post_html_text}{$fieldSpec.post_html_text}{/if}<br />
    {if array_key_exists('description', $fieldSpec) && $fieldSpec.description}<span class="description">{$fieldSpec.description}</span>{/if}
    {if array_key_exists('documentation_link', $fieldSpec) && $fieldSpec.documentation_link.page}{docURL page=$fieldSpec.documentation_link.page resource=$fieldSpec.documentation_link.resource}{/if}
  </td>
{/if}
