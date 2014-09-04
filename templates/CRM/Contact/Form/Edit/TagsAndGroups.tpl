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
<style>
  .hit {ldelim}padding-left:10px;{rdelim}
  .tree li {ldelim}padding-left:10px;{rdelim}
  #Tag .tree .collapsable .hit {ldelim}background:url('{$config->resourceBase}i/menu-expanded.png') no-repeat left 8px;padding-left: 9px;cursor:pointer{rdelim}
  #Tag .tree .expandable .hit {ldelim}background:url('{$config->resourceBase}i/menu-collapsed.png') no-repeat left 6px;padding-left: 9px;cursor:pointer{rdelim}
  #Tag #tagtree .highlighted {ldelim}background-color:lightgrey;{rdelim}
</style>
{*crmScript ext=civicrm.jquery.plugins.jstree file=jquery.jstree.js*}
<script type="text/javascript">
  (function($){ldelim}
    var entityID={$entityID};
    var entityTable='{$entityTable}';
    {literal}
    $(function() {
      $("#tagtree input").removeAttr("disabled");
      //unobsctructive elements are there to provide the function to those not having javascript, no need for the others
      $(".unobstructive").hide();

      $("#tagtree ul input:checked").each(function(){
        $(this).parents("li").children(".jstree-icon").addClass('highlighted');
      });

      //load js tree.
      $("#tagtree").jstree({
        "plugins" : ["themes", "html_data"],
        "themes": {"url": CRM.config.resourceBase + 'packages/jquery/plugins/jstree/themes/default/style.css'}
      });
    });
  })(cj);
  {/literal}
</script>

{if $title}
<div class="crm-accordion-wrapper crm-tagGroup-accordion collapsed">
  <div class="crm-accordion-header">{$title}</div>
  <div class="crm-accordion-body" id="tagGroup">
{/if}
    <table class="form-layout-compressed{if $context EQ 'profile'} crm-profile-tagsandgroups{/if}">
      <tr>
       {if $groupElementType eq 'select'}
          <td><span class="label">{if $title}{$form.group.label}{/if}</span>
            {$form.group.html}
          </td>
      {/if}
      
	  <td width="70%"><span class="label">{if $title}{$form.$key.label}{/if}</span>
		<div id="tagtree">
			{include file="CRM/Tag/Form/Tagtree.tpl" level=1}
		</div>
	  </td>
    </tr>
    {if !$type || $type eq 'tag'}
      <tr><td>{include file="CRM/common/Tagset.tpl"}</td></tr>
    {/if}
  </table>
{if $title}
  </div>
</div><!-- /.crm-accordion-wrapper -->
{/if}
