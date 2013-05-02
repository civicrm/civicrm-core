{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.3                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2013                                |
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
{* this template is used for adding/editing tags  *}

<script type="text/javascript" src="{$config->resourceBase}packages/jquery/jquery.js"></script>
<script type="text/javascript" src="{$config->resourceBase}packages/jquery/plugins/jquery.treeview.min.js"></script>
<style>
.hit {ldelim}padding-left:10px;{rdelim}
.tree li {ldelim}padding-left:10px;{rdelim}
#crm-container #Tag ul#tagtree {ldelim}margin-left:-10px;{rdelim}
#crm-container td #Tag ul {ldelim}margin:0 0 0.5em;padding:0{rdelim}
#crm-container td #Tag li {ldelim}padding-bottom:0;margin:0 0 0 0.5em;{rdelim}

#Tag .tree .expandable .hit {ldelim}background:url('{$config->resourceBase}i/expandable.gif') no-repeat left 3px;cursor:pointer{rdelim}
#Tag .tree .collapsable .hit {ldelim}background:url('{$config->resourceBase}i/collapsable.gif') no-repeat left 3px;cursor:pointer{rdelim}
#Tag #tagtree .highlighted {ldelim}background-color:lightgrey;{rdelim}
</style>
<script type="text/javascript">
{literal}

jQuery(document).ready(function(){initTagTree()});
function initTagTree() {
  $("#tagtree").treeview({
    animated: "fast",
    collapsed: true,
    unique: true
          });
        $('#tagtree>li:odd').addClass('odd-row');
        $('#tagtree>li:even').addClass('even-row');
        $("#tagtree ul input:checked").each (function(){
          $(this).parents("li").children(".hit").addClass('highlighted');
        });
};
{/literal}
</script>
<ul id="tagtree" class="tree">
      {foreach from=$tree item="node" key="id"}
   <li id="tag_{$id}">
{if ! $node.children}<input name="tag[{$id}]" type="checkbox" />{/if}
{if $node.children}<input name="tag[{$id}]" id="check_{$id}" type="checkbox" />{/if}
<span {if $node.children}class="hit"{/if}>{$node.name}</span>
      {if $node.children}<ul>
      {foreach from=$node.children item="subnode" key="subid"}
   <li id="tag_{$subid}">
            <input id="check_{$subid}" name="tag[{$subid}]" type="checkbox" />
            <span {if $subnode.children}class="hit"{/if}>{$subnode.name}</span>
            {if $subnode.children}<ul>
         {foreach from=$subnode.children item="subsubnode" key="subsubid"}
      <li id="tag_{$subsubid}"><span><input id="check_{$subsubid}" name="tag[{$subsubid}]" type="checkbox" />{$subsubnode.name}</span></li>
         {/foreach}
      </ul>{/if}
   </li>
      {/foreach}
      </ul>{/if}
   </li>
   {/foreach}
</ul>
