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
{* this template is used for adding/editing tags  *}
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
    var entityID={$entityID},
      entityTable='{$entityTable}',
      $form = $('form.{$form.formClass}');
    {literal}
    CRM.updateContactSummaryTags = function() {
      var tags = [];
      $('#tagtree input:checkbox:checked+span label').each(function() {
        tags.push($(this).text());
      });
      $('input.crm-contact-tagset').each(function() {
        var setTags = _.pluck($(this).select2('data'), 'label');
        tags = tags.concat(setTags);
      });
      // contact summary tabs and search forms both listen for this event
      $($form).closest('.crm-ajax-container').trigger('crmFormSuccess', {tabCount: tags.length});
      // update summary tab
      $("#contact-summary #tags").html(tags.join(', '));
    };

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
        var tagid = this.id.replace("check_", "");
        var op = (this.checked) ? 'create' : 'delete';
        var api = CRM.api3('entity_tag', op, {entity_table: entityTable, entity_id: entityID, tag_id: tagid}, true);
        highlightSelected();
        CRM.updateContactSummaryTags();
      });

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

      {/literal}
      {if $permission neq 'edit'}
        {literal}
          $("#tagtree input").prop('disabled', true);
        {/literal}
      {/if}
      {literal}

      $(document).on('change', 'input.crm-contact-tagset', CRM.updateContactSummaryTags);
    });
  })(CRM.$, CRM._);
  {/literal}
</script>
<div id="Tag" class="view-content">
  <h3>{if !$hideContext}{ts}Tags{/ts}{/if}</h3>
  <div id="tagtree">
    {include file="CRM/Tag/Form/Tagtree.tpl" level=1}
  </div>
  <br />
{include file="CRM/common/Tagset.tpl"}
</div>
