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
    {if $field && !in_array($fieldSpec.name, array('first_name', 'last_name'))}
      <div class="search-field {if !empty($fieldSpec.class)}{$fieldSpec.class|escape}{/if}">
        {if !empty($fieldSpec.template)}
          {include file=$fieldSpec.template}
        {else}
          {$field.label}
          {if !empty($fieldSpec.help)}
            {assign var=help value=$fieldSpec.help}
            {help id=$help.id file=$help.file}
          {/if}
          <br />
          {$field.html}
          {if !empty($fieldSpec.description)}
            <div class="description font-italic">
              {$fieldSpec.description}
            </div>
          {/if}
        {/if}
      </div>
    {elseif !empty($fieldSpec.is_custom)}
      {include file=$fieldSpec.template}
    {/if}
  {/foreach}
  {if !empty($form.deleted_contacts)}
    <div class="search-field">
      {$form.deleted_contacts.html} {$form.deleted_contacts.label}
    </div>
  {/if}
</div>
