{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2016                                |
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
{literal}
<style>
  #tagtree .highlighted > span {
    background-color: #fefca6;
  }
  #tagtree .helpicon ins {
    display: none;
  }
  #tagtree ins.jstree-icon {
    cursor: pointer;
  }
</style>
<script type="text/javascript">
  (function($, _){{/literal}
    var entityID='{$entityID}',
      entityTable='{$entityTable}',
      $form = $('form.{$form.formClass}');
    {literal}

    $(function() {
      function highlightSelected() {
        $("ul input:not(:checked)", '#tagtree').each(function () {
          $(this).closest("li").removeClass('highlighted');
        });
        $("ul input:checked", '#tagtree').each(function () {
          $(this).parents("li[id^=tag]").addClass('highlighted');
        });
      }
      highlightSelected();

      $("#tagtree input").change(function(){
        highlightSelected();
      });

      var childTag = "{/literal}{$loadjsTree}{literal}";
      if (childTag) {
        //load js tree.
        $("#tagtree").jstree({
          plugins : ["themes", "html_data"],
          themes: {
            "theme": 'classic',
            "dots": false,
            "icons": false,
            "url": CRM.config.resourceBase + 'packages/jquery/plugins/jstree/themes/classic/style.css'
          }
        });
      }
	  {/literal}
      {if !empty($permission) && $permission neq 'edit'}
        {literal}
          $("#tagtree input").prop('disabled', true);
        {/literal}
      {/if}
      {literal}
    });
  })(CRM.$, CRM._);
  {/literal}
</script>

{if $title}
<div class="crm-accordion-wrapper crm-tagGroup-accordion collapsed">
  <div class="crm-accordion-header">{$title}</div>
  <div class="crm-accordion-body" id="tagGroup">
{/if}
    <table class="form-layout-compressed{if $context EQ 'profile'} crm-profile-tagsandgroups{/if}">
      <tr>
        {if !$type || $type eq 'group'}
          <td>
            {if $groupElementType eq 'select'}
              <span class="label">{if $title}{$form.group.label}{/if}</span>
            {/if}
            {$form.group.html}
          </td>
        {/if}
        {if (!$type || $type eq 'tag') && $tree}
          <td width="70%">{if $title}<span class="label">{$form.tag.label}</span>{/if}
            <div id="tagtree">
              {include file="CRM/Contact/Form/Edit/Tagtree.tpl" level=1}
            </div>
          </td>
          <tr><td>{include file="CRM/common/Tagset.tpl"}</td></tr>
        {/if}
      </tr>
    </table>
{if $title}
  </div>
</div><!-- /.crm-accordion-wrapper -->
{/if}
