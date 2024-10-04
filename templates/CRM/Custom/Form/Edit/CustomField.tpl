{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}

{if $element.help_pre}
  <tr class="custom_field-help-pre-row {$element.element_name}-row-help-pre">
    <td>&nbsp;</td>
    <td class="html-adjust description">{$element.help_pre}</td>
  </tr>
{/if}
{if $element.html_type === 'Hidden'}
  {* Hidden field - render in hidden row *}
  <tr class="custom_field-row {$element.element_name}-row hiddenElement">
    <td>{$formElement.html}</td>
  </tr>
{elseif $element.options_per_line}
  <tr class="custom_field-row {$element.element_name}-row">
    <td class="label">{$formElement.label}{if $element.help_post}{help id=$element.id file="CRM/Custom/Form/CustomField.hlp" title=$element.label}{/if}</td>
    <td class="html-adjust">

      <div class="crm-multiple-checkbox-radio-options crm-options-per-line" style="--crm-opts-per-line:{$element.options_per_line};">
        {foreach name=outer key=key item=item from=$formElement}
          {if is_array($item) && array_key_exists('html', $item)}
            <div class="crm-option-label-pair" >{$formElement.$key.html}</div>
          {/if}
        {/foreach}
      </div>

      {* Include the edit options list for admins *}
      {if $formElement.html|strstr:"crm-option-edit-link"}
        {$formElement.html|regex_replace:"@^.*(<a href=.*? class=.crm-option-edit-link.*?</a>)$@":"$1"}
      {/if}

    </td>
  </tr>
{else}
  <tr class="custom_field-row {$element.element_name}-row">
    <td class="label">{$formElement.label}
      {if $element.help_post}{help id=$element.id file="CRM/Custom/Form/CustomField.hlp" title=$element.label}{/if}
    </td>
    <td class="html-adjust">
      {$formElement.html}&nbsp;
      {if $element.data_type eq 'File'}
        {if array_key_exists('element_value', $element) && $element.element_value.data}
          <div class="crm-attachment-wrapper crm-entity" id="file_{$element.element_name}">
            <span class="html-adjust"><br/>&nbsp;{ts}Attached File{/ts}: &nbsp;
              {if $element.element_value.displayURL}
                <a href="{$element.element_value.displayURL}" class='crm-image-popup crm-attachment'>
                  <img src="{$element.element_value.displayURL}"
                       height="{$element.element_value.imageThumbHeight}"
                       width="{$element.element_value.imageThumbWidth}">
                </a>
              {else}
                <a class="crm-attachment" href="{$element.element_value.fileURL}">{$element.element_value.fileName}</a>
              {/if}
              {if $element.element_value.deleteURL}
                <a href="#" class="crm-hover-button delete-attachment"
                   data-filename="{$element.element_value.fileName}"
                   data-args="{$element.element_value.deleteURLArgs}" title="{ts}Delete File{/ts}">
                  <span class="icon delete-icon"></span>
                </a>
              {/if}
            </span>
          </div>
        {/if}
      {elseif $element.html_type eq 'Autocomplete-Select'}
        {if $element.data_type eq 'ContactReference'}
          {assign var="element_name" value=$element.element_name}
          {include file="CRM/Custom/Form/ContactReference.tpl"}
        {/if}
      {/if}
    </td>
  </tr>
{/if}
