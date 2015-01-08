{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.5                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2014                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
*}
{* This tpl runs recursively to build each level of the tag tree *}
<ul class="tree-level-{$level}">
  {foreach from=$tree item="node" key="id"}
    <li id="tag_{$id}">
      <input name="tagList[{$id}]" id="check_{$id}" type="checkbox" {if $tagged[$id]}checked="checked"{/if}/>
      <span>
        <label for="check_{$id}" id="tagLabel_{$id}">{$node.name}</label>
        {if $node.description}{help id=$id title=$node.name file="CRM/Tag/Form/Tagtree"}{/if}
      </span>
      {if $node.children}
        {* Recurse... *}
        {include file="CRM/Tag/Form/Tagtree.tpl" tree=$node.children level=$level+1}
      {/if}
    </li>
  {/foreach}
</ul>
