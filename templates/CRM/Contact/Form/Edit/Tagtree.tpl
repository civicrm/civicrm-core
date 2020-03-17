{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{* This tpl runs recursively to build each level of the tag tree *}
<ul class="tree-level-{$level}">
  {foreach from=$tree item="node" key="id"}
    <li id="tagli_{$id}">
      <input name="tag[{$id}]" id="tag_{$id}" class="form-checkbox" type="checkbox" value="1" {if $node.is_selectable EQ 0}disabled=""{/if} {if $form.tag.value.$id EQ 1}checked="checked"{/if}/>
      <span>
        <label for="tag_{$id}" id="tagLabel_{$id}" class="crm-tag-item" {if !empty($allTags.$id.color)}style="background-color: {$allTags.$id.color}; color: {$allTags.$id.color|colorContrast};"{/if} title="{$node.description|escape}">{$node.name}</label>
      </span>
      {if $node.children}
        {* Recurse... *}
        {include file="CRM/Contact/Form/Edit/Tagtree.tpl" tree=$node.children level=$level+1}
      {/if}
    </li>
  {/foreach}
</ul>
