{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{* this template is used for adding/editing tags  *}
{crmStyle file='bower_components/jstree/dist/themes/default/style.min.css'}
{literal}
<script type="text/javascript">
  (function($, _){{/literal}
    var entityID={$entityID},
      entityTable='{$entityTable}',
      $form = $('form.{$form.formClass}');
    {literal}

    $(function() {

      // Display tags on the contact summary
      function updateContactSummaryTags() {
        var tags = [],
          selected = $("#tagtree").jstree(true).get_selected(true);
        $.each(selected, function(k, item) {
          var $tag = $(item.text);
          tags.push('<span class="crm-tag-item" style="' + $tag.attr('style') + '" title="' + ($.parseHTML($tag.attr('title')) || '') + '">' + $tag.text() + '</span>');
        });
        $('input.crm-contact-tagset').each(function() {
          $.each($(this).select2('data'), function (i, tag) {
            tags.push('<span class="crm-tag-item" title="' + ($.parseHTML(tag.description.text) || '') + '"' + (tag.color ? 'style="color: ' + CRM.utils.colorContrast(tag.color) + '; background-color: ' + tag.color + ';"' : '') + '>' + tag.label + '</span>');
          });
        });
        // contact summary tabs and search forms both listen for this event
        $($form).closest('.crm-ajax-container').trigger('crmFormSuccess', {tabCount: tags.length});
        // update summary tab
        $("#contact-summary #tags").html(tags.join(' '));
      }

      // Load js tree.
      CRM.loadScript(CRM.config.resourceBase + 'bower_components/jstree/dist/jstree.min.js').done(function() {
        $("#tagtree").jstree({
          plugins : ['search', 'wholerow', 'checkbox'],
          core: {
            animation: 100,
            themes: {
              "theme": 'classic',
              "dots": false,
              "icons": false
            }
          },
          'search': {
            'case_insensitive' : true,
            'show_only_matches': true
          },
          checkbox: {
            three_state: false
          }
        })
          .on('select_node.jstree deselect_node.jstree', function(e, selected) {
            var id = selected.node.a_attr.id.replace('tag_', ''),
              op = e.type === 'select_node' ? 'create' : 'delete';
            CRM.api3('entity_tag', op, {entity_table: entityTable, entity_id: entityID, tag_id: id}, true);
            updateContactSummaryTags();
          });
      });

      $(document).on('change', 'input.crm-contact-tagset', updateContactSummaryTags);

      $('input[name=filter_tag_tree]', '#Tag').on('keyup change', function() {
        $("#tagtree").jstree(true).search($(this).val());
      });
    });
  })(CRM.$, CRM._);
  {/literal}
</script>
<div id="Tag" class="view-content">
  <table class="">
    <thead>
      <tr>
        <th>{ts}Tag Tree{/ts}</th>
        {if $tagsetInfo.contact}<th>{ts}Tag Sets{/ts}</th>{/if}
      </tr>
    </thead>
    <tbody>
      <tr>
        <td>
          <input class="crm-form-text big" name="filter_tag_tree" placeholder="{ts escape='htmlattribute'}Filter List{/ts}" allowclear="1"/>
          <a class="crm-hover-button crm-clear-link" style="visibility:hidden;" title="{ts escape='htmlattribute'}Clear{/ts}"><i class="crm-i fa-times" aria-hidden="true"></i></a>
          <div id="tagtree">
            {include file="CRM/Tag/Form/Tagtree.tpl" level=1}
          </div>
        </td>
        {if $tagsetInfo.contact}
        <td>
          {include file="CRM/common/Tagset.tpl"}
        </td>
        {/if}
      </tr>
    </tbody>
  </table>
</div>
