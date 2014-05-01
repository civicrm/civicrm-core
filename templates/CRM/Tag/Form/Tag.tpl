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
  #tagtree .highlighted > label {
    background-color: #FEFD7B;
  }
</style>
<script type="text/javascript">
  (function($, _){{/literal}
    var entityID={$entityID};
    var entityTable='{$entityTable}';
    {literal}
    CRM.updateContactSummaryTags = function() {
      var tags = [];
      $('#tagtree input:checkbox:checked+label').each(function() {
        tags.push($(this).text());
      });
      $('input.crm-contact-tagset').each(function() {
        var setTags = _.pluck($(this).select2('data'), 'label');
        tags = tags.concat(setTags);
      });
      // contact summary tabs and search forms both listen for this event
      $('#Tag').closest('.crm-ajax-container').trigger('crmFormSuccess', {tabCount: tags.length});
      // update summary tab
      $("#contact-summary #tags").html(tags.join(', '));
    };

    $(function() {
      $("#tagtree ul input:checked").each (function(){
        $(this).closest("li").addClass('highlighted');
      });

      $("#tagtree input").change(function(){
        var tagid = this.id.replace("check_", "");
        var op = (this.checked) ? 'create' : 'delete';
        var api = CRM.api3('entity_tag', op, {entity_table: entityTable, entity_id: entityID, tag_id: tagid}, true);
        $(this).closest("li").toggleClass('highlighted');
        CRM.updateContactSummaryTags();
      });

      //load js tree.
      $("#tagtree").jstree({
        "plugins" : ["themes", "html_data"],
        "themes": {"url": CRM.config.resourceBase + 'packages/jquery/plugins/jstree/themes/default/style.css'}
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
    <ul class="tree">
    {foreach from=$tree item="node" key="id"}
      <li id="tag_{$id}">
        {if ! $node.children}<input name="tagList[{$id}]" id="check_{$id}" type="checkbox" {if $tagged[$id]}checked="checked"{/if}/>{/if}
        {if $node.children}<input name="tagList[{$id}]" id="check_{$id}" type="checkbox" {if $tagged[$id]}checked="checked"{/if}/>{/if}
        {if $node.children} <span class="hit"></span> {/if} <label for="check_{$id}" id="tagLabel_{$id}">{$node.name}</label>
        {if $node.children}
          <ul>
            {foreach from=$node.children item="subnode" key="subid"}
              <li id="tag_{$subid}">
                <input id="check_{$subid}" name="tagList[{$subid}]" type="checkbox" {if $tagged[$subid]}checked="checked"{/if}/>
                {if $subnode.children} <span class="hit"></span> {/if} <label for="check_{$subid}" id="tagLabel_{$subid}">{$subnode.name}</label>
                {if $subnode.children}
                  <ul>
                    {foreach from=$subnode.children item="subsubnode" key="subsubid"}
                      <li id="tag_{$subsubid}">
                        <input id="check_{$subsubid}" name="tagList[{$subsubid}]" type="checkbox" {if $tagged[$subsubid]}checked="checked"{/if}/>
                        <label for="check_{$subsubid}" id="tagLabel_{$subsubid}">{$subsubnode.name}</label>
                      </li>
                    {/foreach}
                  </ul>
                {/if}
              </li>
            {/foreach}
          </ul>
        {/if}
      </li>
    {/foreach}
    </ul>
  </div>
  <br />
{include file="CRM/common/Tagset.tpl"}
</div>
