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
    <li>
      <a id="tag_{$id}" class="{if !$node.is_selectable || $permission neq 'edit'}jstree-disabled{/if} {if $tagged[$id]}jstree-clicked{/if}">
        <span class="crm-tag-item" {if array_key_exists($id, $allTags) && !empty($allTags.$id.color)}style="background-color: {$allTags.$id.color}; color: {$allTags.$id.color|colorContrast};"{/if} title="{$node.description|escape}">
          {$node.label}
        </span>
      </a>
      {if $node.children}
        {* Recurse... *}
        {include file="CRM/Tag/Form/Tagtree.tpl" tree=$node.children level=$level+1}
      {/if}
    </li>
  {/foreach}
</ul>
