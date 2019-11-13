{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
<div class="advanced-search-fields basic-fields form-layout">
  {foreach from=$basicSearchFields item=fieldSpec}
    {assign var=field value=$form[$fieldSpec.name]}
    {if $field}
      <div class="search-field {$fieldSpec.class|escape}">
        {if $fieldSpec.template}
          {include file=$fieldSpec.template}
        {else}
          {$field.label}
          {if $fieldSpec.help}
            {assign var=help value=$fieldSpec.help}
            {capture assign=helpFile}{if $fieldSpec.help}{$fieldSpec.help}{else}''{/if}{/capture}
            {help id=$help.id file=$help.file}
          {/if}
          <br />
          {$field.html}
          {if $fieldSpec.description}
            <div class="description font-italic">
              {$fieldSpec.description}
            </div>
          {/if}
        {/if}
      </div>
    {elseif $fieldSpec.is_custom}
      {include file=$fieldSpec.template}
    {/if}
  {/foreach}
</div>
