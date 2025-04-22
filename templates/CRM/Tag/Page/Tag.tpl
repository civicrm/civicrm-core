{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
<div class="crm-content-block">
  <div class="help">
    {ts 1=$usedForStr}Tags are a convenient way to categorize data (%1).{/ts}
    {crmPermission has='administer Tagsets'}
      <br />
      {ts}Create predefined tags in the main tree, or click the <strong>+</strong> to add a set for free tagging.{/ts}
    {/crmPermission}
    {docURL page="user/organising-your-data/groups-and-tags"}
  </div>

  <div id="mainTabContainer">
    <ul role="tablist">
      <li role="tab" class="ui-corner-all crm-tab-button" title="{ts escape='htmlattribute'}Main Tag List{/ts}">
        <a href="#tree"><i class="crm-i fa-tags" aria-hidden="true"></i> {ts}Tag Tree{/ts}</a>
      </li>
      {foreach from=$tagsets item=set}
        <li role="tab" class="ui-corner-all crm-tab-button {if ($set.is_reserved)}is-reserved{/if}" title="{ts escape='htmlattribute' 1=$set.used_for_label_str}Tag Set for %1{/ts}">
          <a href="#tagset-{$set.id}">{$set.label}</a>
        </li>
      {/foreach}
      {crmPermission has='administer Tagsets'}
        <li role="tab" class="ui-corner-all crm-tab-button" title="{ts escape='htmlattribute'}Add Tag Set{/ts}">
          <a href="#new-tagset"><i class="crm-i fa-plus" aria-hidden="true"></i></a>
        </li>
      {/crmPermission}
    </ul>
    <div id="tree" role="tabpanel">
      <div class="help">
        {ts}Organize the tag hierarchy by clicking and dragging. Shift-click to select multiple tags to merge/move/delete.{/ts}
      </div>
      <input class="crm-form-text big" name="filter_tag_tree" placeholder="{ts escape='htmlattribute'}Filter List{/ts}" allowclear="1"/>
      <a class="crm-hover-button crm-clear-link" style="visibility:hidden;" title="{ts escape='htmlattribute'}Clear{/ts}"><i class="crm-i fa-times" aria-hidden="true"></i></a>
    </div>
    {foreach from=$tagsets item=set}
      <div id="tagset-{$set.id}" role="tabpanel">
      </div>
    {/foreach}
    <div id="new-tagset" role="tabpanel">
    </div>
  </div>
</div>
{crmAPI entity="Contact" action="getsingle" var="user" return='display_name' id="user_contact_id"}
{literal}
<script type="text/javascript">
  (function($, _) {
    $(function($) {
      var $window = $(window),
        renderedTabs = ['tree'],
        tagSets = {/literal}{$tagsets|@json_encode}{literal},
        user = {/literal}{$user|@json_encode}{literal},
        usedFor = {/literal}{$usedFor|@json_encode}{literal},
        menuHeight = $('#civicrm-menu').height() + 15,
        noneSelectedTpl = _.template($('#noneSelectedTpl').html()),
        oneSelectedTpl = _.template($('#oneSelectedTpl').html()),
        moreSelectedTpl = _.template($('#moreSelectedTpl').html()),
        tagsetHeaderTpl = _.template($('#tagsetHeaderTpl').html());

      function formatTagSet(info) {
        info.date = CRM.utils.formatDate(info.created_date);
        info.used_for_label = [];
        if (undefined !== info.used_for) {
          _.each(info.used_for.split(','), function(item) {
            info.used_for_label.push(usedFor[item]);
          });
        }
      }

      _.each(tagSets, formatTagSet);

      function renderTree($panel) {
        var plugins,
          selected = [],
          tagset = $panel.attr('id').split('-')[1] || 0;

        function hasChildren(id) {
          var $node = $('.tag-tree', $panel).jstree(true).get_node(id, true);
          return !$node.hasClass('jstree-leaf');
        }

        function changeColor() {
          var color = $(this).val().toLowerCase(),
            id = $(this).closest('.crm-entity').data('id'),
            node = $('.tag-tree', $panel).jstree(true).get_node(id);
          if (color === '#ffffff') {
            node.a_attr.style = '';
          } else {
            node.a_attr.style = 'background-color: ' + color + '; color: ' + CRM.utils.colorContrast(color) + ';';
          }
          node.data.color = color;
          $('.tag-tree', $panel).jstree(true).redraw(true);
          CRM.api3('Tag', 'create', {id: id, color: color}, true);
        }

        function changeSelection(e, data) {
          var tplParams = {
            tagset: tagset,
            tagsetCount: _.keys(tagSets).length,
            adminReserved: CRM.checkPerm('administer reserved tags')
          },
            tree = $('.tag-tree', $panel).jstree(true),
            $infoBox = $('.tag-info', $panel);
          selected = data.selected;
          if (!data.selected || !data.selected.length) {
            tplParams.is_reserved = tagset ? tagSets[tagset].is_reserved == 1 : false;
            tplParams.length = $('.tag-tree li', $panel).length;
            tplParams.adminTagsets = CRM.checkPerm('administer Tagsets');
            $infoBox.html(noneSelectedTpl(tplParams));
          } else if (data.selected.length === 1) {
            tplParams.usedFor = usedFor;
            tplParams.hasChildren = hasChildren(data.node.id);
            $infoBox.html(oneSelectedTpl($.extend({}, data.node, tplParams)));
          } else {
            tplParams.items = data.selected;
            tplParams.hasChildren = tplParams.reserved = tplParams.usages = 0;
            _.each(data.selected, function(id) {
              var node = tree.get_node(id);
              tplParams.usages += node.data.usages;
              tplParams.reserved += node.data.is_reserved;
              tplParams.hasChildren += hasChildren(id) ? 1 : 0;
            });
            $infoBox.html(moreSelectedTpl(tplParams));
          }
          $infoBox.trigger('crmLoad');
        }

        function clearSelection(e) {
          e.preventDefault();
          $('.tag-tree', $panel).jstree(true).deselect_all();
        }

        function changeUsedFor() {
          var vals = $('input[name=used_for]:checked', $panel).map(function(i, el) {
            return $(el).val();
          }).get(),
            id = $(this).closest('.crm-entity').data('id');
          if (vals.length) {
            CRM.api3('Tag', 'create', {id: id, used_for: vals}, true);
            var node = $('.tag-tree', $panel).jstree(true).get_node(id);
            node.data.used_for = vals;
          }
        }

        function moveTag(e, data) {
          if (data.parent != data.old_parent) {
            CRM.api3('Tag', 'create', {id: data.node.id, parent_id: data.parent.replace('#', '')}, true);
          }
        }

        function deleteTagset() {
          $('#mainTabContainer').tabs('option', 'active', 0);
          $panel.off().remove();
          $("a[href='#tagset-" + tagset + "']").parent().remove();
          $('#mainTabContainer').tabs('refresh');
        }

        function updateTagset(info) {
          tagSets[tagset].description = info.description;
          tagSets[tagset].label = info.label;
          tagSets[tagset].used_for = info.used_for;
          tagSets[tagset].is_reserved = info.is_reserved;
          formatTagSet(tagSets[tagset]);
          addTagsetHeader();
          $(".tag-tree", $panel).jstree("search", '');
        }

        function addTagsetHeader() {
          $('.tagset-header', $panel).remove();
          $panel.prepend(tagsetHeaderTpl(tagSets[tagset]));
          $("a[href='#tagset-" + tagset + "']").text(tagSets[tagset].label)
            .parent().toggleClass('is-reserved', tagSets[tagset].is_reserved == 1)
            .attr('title', ts('{/literal}{ts escape='js' 1='%1'}Tag Set for %1{/ts}{literal}', {'1': tagSets[tagset].used_for_label.join(', ')}));
        }

        if (tagset) {
          addTagsetHeader();
        }

        function moveTagDialog(e) {
          e.preventDefault();
          var sets = [{key: '0', value: '{/literal}{ts escape='js'}Main Tag Tree{/ts}{literal}'}];
          _.each(tagSets, function(tagSet) {
            sets.push({key: tagSet.id, value: tagSet.label});
          });
          CRM.confirm({
            title: '{/literal}{ts escape='js'}Move to Tagset{/ts}{literal}',
            message: '<label for="select-tagset">{/literal}{ts escape='js'}Select Tagset{/ts}{literal}: '
              + '<select id="select-tagset" class="crm-select2 big">'
              + CRM.utils.renderOptions(sets, tagset)
              + '</select>'
          })
            .on('crmConfirm:yes', function() {
              var chosen = parseInt($('#select-tagset').val());
              if (parseInt(tagset) !== chosen) {
                var apiCalls = [];
                _.each(selected, function(id) {
                  apiCalls.push(['Tag', 'create', {id: id, parent_id: chosen || ''}]);
                });
                $('#mainTabContainer').block();
                CRM.api3(apiCalls, true)
                  .done(function() {
                    $('.tag-tree', $panel).jstree(true).refresh();
                    $('#mainTabContainer').unblock();
                    var $otherPanel = $(chosen ? '#tagset-' + chosen : '#tree');
                    if ($('.tag-tree', $otherPanel).length) {
                      $('.tag-tree', $otherPanel).jstree(true).refresh();
                    }
                  });
              }
            });
        }

        function isDraggable(nodes, event) {
          var draggable = true;
          _.each(nodes, function(node) {
            if (node.data.is_reserved && !CRM.checkPerm('administer reserved tags')) {
              draggable = false;
            }
          });
          return draggable;
        }

        $panel
          .append('<div class="tag-tree-wrapper"><div class="tag-tree"></div><div class="tag-info"></div></div>')
          .on('change', 'input[type=color]', changeColor)
          .on('change', 'input[name=used_for]', changeUsedFor)
          .on('click', '.clear-tag-selection', clearSelection)
          .on('click', '.move-tag-button', moveTagDialog)
          .on('click', '.used-for-toggle', function() {
            $(this).attr('style', 'display: none !important;').next().show();
          })
          .on('click', 'a.crm-clear-link', function() {
            $('.tag-tree', $panel).jstree(true).refresh();
          })
          .on('crmPopupFormSuccess crmFormSuccess', function(e, cts, data) {
            if ($(e.target).hasClass('tagset-action-delete')) {
              deleteTagset();
            } else if ($(e.target).hasClass('tagset-action-update')) {
              updateTagset(data.tag);
            } else {
              $('.tag-tree', $panel).jstree(true).refresh();
            }
          });

        plugins = ['wholerow', 'changed', 'search'];
        if (!tagset) {
          // Allow drag-n-drop nesting of the tag tree
          plugins.push('dnd');
        }

        $('.tag-tree', $panel)
          .on('changed.jstree loaded.jstree', changeSelection)
          .on('move_node.jstree', moveTag)
          .on('search.jstree', function() {
            $(this).unblock();
          })
          .jstree({
            core: {
              data: {
                url: CRM.url('civicrm/ajax/tagTree'),
                data: function(node) {
                  return {parent_id: node.id === '#' ? tagset : node.id};
                }
              },
              themes: {icons: false},
              check_callback: true
            },
            'search': {
              'ajax' : {
                url : CRM.url('civicrm/ajax/tagTree')
              },
              'show_only_matches': true
            },
            plugins: plugins,
            dnd: {
              is_draggable: isDraggable,
              copy: false
            }
          });

        $('input[name=filter_tag_tree]', $panel).on('keyup change', function(e) {
          var element = $(this);
          var searchString = element.val();
          if (e.type == 'change') {
            if (window.searchedString === searchString) {
              if (searchString === '') {
                $('.tag-tree', $panel).jstree("clear_search");
                $('.tag-tree', $panel).jstree("refresh", true, true);
              }
              else {
                $('.tag-tree', $panel).block();
                $(".tag-tree", $panel).jstree("search", searchString);
                delete window.searchedString;
              }
            }
          }
          else {
            if (this.timer) clearTimeout(this.timer);
            this.timer = setTimeout(function() {
              if (_.isEmpty(window.searchedString) || window.searchedString !== searchString) {
                window.searchedString = searchString;
                element.trigger('change');
              }
            }, 1000);
          }
        });
      }

      function newTagset() {
        CRM.loadForm(CRM.url('civicrm/tag/edit', {action: 'add', tagset: 1}))
          .on('crmFormSuccess', function(e, data) {
            tagSets[data.tag.id] = data.tag;
            tagSets[data.tag.id].display_name = user.display_name;
            formatTagSet(tagSets[data.tag.id]);
            $("#new-tagset").before('<div id="tagset-' + data.tag.id + '">');
            $("a[href='#new-tagset']").parent().before('<li class="ui-corner-all crm-tab-button"><a href="#tagset-' + data.tag.id + '">' + data.tag.label + '</a></li>');
            $('#mainTabContainer').tabs('refresh');
            $('#mainTabContainer').tabs('option', 'active', -2);
          });
      }

      $('#mainTabContainer')
        .tabs()
        .on("tabsbeforeactivate", function (event, ui) {
          var id = $(ui.newPanel).attr('id');
          if (id === 'new-tagset') {
            event.preventDefault();
            newTagset();
            return false;
          }
          if ($.inArray(id, renderedTabs) < 0) {
            renderedTabs.push(id);
            renderTree(ui.newPanel);
          }
        });

      renderTree($('#tree'));

      // Prevent the info box from scrolling offscreen
      $window.on('scroll resize', function () {
        var $wrapper = $('.tag-tree-wrapper:visible'),
          pos = $wrapper.offset(),
          $box = $('.tag-info:visible');
        if ($window.scrollTop() + menuHeight > pos.top) {
          $box.css({
            position: 'fixed',
            top: menuHeight,
            right: parseInt($window.width() - (pos.left + $wrapper.width())),
            width: parseInt($wrapper.width() * .40)
          });
        } else {
          $box.removeAttr('style');
        }
      });

    });
  })(CRM.$, CRM._);
</script>
<style type="text/css">
  div.tag-tree-wrapper {
    position: relative;
    min-height: 250px;
  }
  div.tag-tree {
    width: 59%;
  }
  div.tag-info {
    width: 40%;
    position: absolute;
    top: 5px;
    right: 0;
    min-height: 100px;
    border: 1px solid #aaa;
    border-radius: 4px;
    box-shadow: 0 0 4px #e3e3e3;
    padding: 1em;
    box-sizing: border-box;
    background: white;
  }
  div.tag-info .clear-tag-selection {
    position: absolute;
    top: 10px;
    right: 12px;
    color: inherit;
    opacity: .5;
  }
  div.tag-info .clear-tag-selection:hover,
  div.tag-info .clear-tag-selection:active {
    opacity: 1;
  }
  .tag-tree-wrapper .tag-tree a.crm-tag-item {
    border-radius: 3px;
    margin: 2px 0;
    height: 20px;
    line-height: 20px;
    font-size: 12px;
    padding: 0 3px;
  }
  #tree a.crm-tag-item {
    cursor: move;
  }
  li.is-reserved > a:after {
    content: ' *';
  }
  {/literal}{crmPermission not='administer reserved tags'}{literal}
    #tree li.is-reserved > a.crm-tag-item {
      cursor: not-allowed;
    }
    li.is-reserved > a:after {
      color: #8A1F11;
    }
  {/literal}{/crmPermission}{literal}
  .tag-tree-wrapper ul {
    margin: 0;
    padding: 0;
  }
  div.tag-info h4 .crm-editable {
    min-width: 60%;
    padding: .2em;
  }
  div.tag-info .crm-editable-enabled {
    min-width: 5em;
  }
  div.tag-info .crm-editable-enabled[data-field=description] {
    min-width: 60%;
  }
  div.tag-info input[type=color] {
    cursor: pointer;
  }
  div.tag-info input[disabled] {
    cursor: default;
  }
  div.tag-info .tdl {
    font-weight: bold;
    color: #999;
  }
  div.tag-info hr {
    margin: .2em 0;
  }
  div.tag-info .crm-submit-buttons {
    margin: 10px 0 0;
  }
</style>
{/literal}

<script type="text/template" id="noneSelectedTpl">
  <% if (length) {ldelim} %>
    <h4>{ts}None Selected{/ts}</h4>
    <hr />
    <p>{ts}Select one or more tags for details.{/ts}</p>
  <% {rdelim} else {ldelim} %>
    <h4>{ts}Empty Tag Set{/ts}</h4>
    <hr />
    <p>{ts}No tags have been created in this set.{/ts}</p>
  <% {rdelim} %>
  <div class="crm-submit-buttons">
    <a href="{crmURL p="civicrm/tag/edit" q="action=add&parent_id="}<%= tagset || '' %>" class="button crm-popup">
      <span><i class="crm-i fa-plus" aria-hidden="true"></i> {ts}Add Tag{/ts}</span>
    </a>
    <% if(tagset && adminTagsets) {ldelim} %>
      <a href="{crmURL p="civicrm/tag/edit" q="action=update&id="}<%= tagset %>" class="button crm-popup tagset-action-update">
        <span><i class="crm-i fa-pencil" aria-hidden="true"></i> {ts}Edit Set{/ts}</span>
      </a>
    <% {rdelim} %>
    <% if(tagset && !length && adminTagsets && (!is_reserved || adminReserved)) {ldelim} %>
      <a href="{crmURL p="civicrm/tag/edit" q="action=delete&id="}<%= tagset %>" class="button crm-popup small-popup tagset-action-delete">
        <span><i class="crm-i fa-trash" aria-hidden="true"></i> {ts}Delete Set{/ts}</span>
      </a>
    <% {rdelim} %>
  </div>
</script>

<script type="text/template" id="oneSelectedTpl">
  <div class="crm-entity" data-entity="Tag" data-id="<%= id %>">
    <h4>
      <input type="color" value="<%= data.color %>" <% if (!data.is_reserved || adminReserved) {ldelim} %>title="{ts escape='htmlattribute'}Select color{/ts}" <% {rdelim} else {ldelim} %>disabled<% {rdelim} %> />
      <span class="<% if (!data.is_reserved || adminReserved) {ldelim} %>crm-editable<% {rdelim} %>" data-field="label"><%- text %></span>
    </h4>
    <hr />
    <div><span class="tdl">{ts}Description:{/ts}</span>
      <span class="<% if (!data.is_reserved || adminReserved) {ldelim} %>crm-editable<% {rdelim} %>" data-field="description"><%- data.description %></span>
    </div>
    <div><span class="tdl">{ts}Selectable:{/ts}</span>
      <span class="<% if (!data.is_reserved || adminReserved) {ldelim} %>crm-editable<% {rdelim} %>" data-field="is_selectable" data-type="select"><% if (data.is_selectable) {ldelim} %> {ts}Yes{/ts} <% {rdelim} else {ldelim} %> {ts}No{/ts} <% {rdelim} %></span>
    </div>
    <div><span class="tdl">{ts}Reserved:{/ts}</span>
      <span class="<% if (adminReserved) {ldelim} %>crm-editable<% {rdelim} %>" data-field="is_reserved" data-type="select"><% if (data.is_reserved) {ldelim} %> {ts}Yes{/ts} <% {rdelim} else {ldelim} %> {ts}No{/ts} <% {rdelim} %></span>
    </div>
    <% if (parent === '#' && !tagset) {ldelim} %>
      <div>
        <span class="tdl">{ts}Used For:{/ts}</span>
        {literal}
          <span class="<% if (!data.is_reserved || adminReserved) { %>crm-editable-enabled used-for-toggle<% } %>">
            <% if (!data.used_for.length) { %><i class="crm-i fa-pencil crm-editable-placeholder" aria-hidden="true"></i><% } %>
            <% _.forEach(data.used_for, function(key, i) { %><%- (i ? ', ' : '') + usedFor[key] %><% }) %>
          </span>
          <span style="display: none">
          <% _.forEach(usedFor, function(label, key) { %>
            <span style="white-space: nowrap">
              <input type="checkbox" name="used_for" value="<%= key %>" id="<%= id + '_used_for_' + key %>" <% if (data.used_for.indexOf(key) > -1) { %>checked<% } %> />
              <label for="<%= id + '_used_for_' + key %>"><%- label %></label>
            </span>
          <% }) %>
          </span>
        {/literal}
      </div>
    <% {rdelim} %>
    <div><span class="tdl">{ts}Usage Count:{/ts}</span> <%= data.usages %></div>
    <a class="clear-tag-selection" href="#" title="{ts escape='htmlattribute'}Clear selection{/ts}"><i class="crm-i fa-ban" aria-hidden="true"></i></a>
  </div>
  <div class="crm-submit-buttons">
    <% if(!tagset) {ldelim} %>
      <a href="{crmURL p="civicrm/tag/edit" q="action=add&parent_id="}<%= id %>" class="button crm-popup" title="{ts escape='htmlattribute'}Create new tag under this one{/ts}">
        <span><i class="crm-i fa-plus" aria-hidden="true"></i> {ts}Add Child{/ts}</span>
      </a>
    <% {rdelim} %>
    <a href="{crmURL p="civicrm/tag/edit" q="action=add&clone_from="}<%= id %>" class="button crm-popup" title="{ts escape='htmlattribute'}Duplicate this tag{/ts}">
      <span><i class="crm-i fa-copy" aria-hidden="true"></i> {ts}Clone Tag{/ts}</span>
    </a>
    <% if(!data.is_reserved || adminReserved) {ldelim} %>
      <% if(tagsetCount) {ldelim} %>
        <a href="#move" class="button move-tag-button" title="{ts escape='htmlattribute'}Move to a different tagset{/ts}">
          <span><i class="crm-i fa-share-square-o" aria-hidden="true"></i> {ts}Move Tag{/ts}</span>
        </a>
      <% {rdelim} %>
      <% if(!hasChildren) {ldelim} %>
        <a href="{crmURL p="civicrm/tag/edit" q="action=delete&id="}<%= id %>" class="button crm-popup small-popup">
          <span><i class="crm-i fa-trash" aria-hidden="true"></i> {ts}Delete{/ts}</span>
        </a>
      <% {rdelim} %>
    <% {rdelim} %>
  </div>
</script>

<script type="text/template" id="moreSelectedTpl">
  <h4>{ts 1="<%= items.length %>"}%1 Tags Selected{/ts}</h4>
  <hr />
    <% if (reserved) {ldelim} %>
      <p>* {ts 1="<%= reserved %>"}%1 reserved.{/ts}</p>
    <% {rdelim} %>
  <p><span class="tdl">{ts}Total Usage:{/ts}</span> <%= usages %></p>
  <a class="clear-tag-selection" href="#" title="{ts escape='htmlattribute'}Clear selection{/ts}"><i class="crm-i fa-ban" aria-hidden="true"></i></a>
  <div class="crm-submit-buttons">
    <% if(!reserved || adminReserved) {ldelim} %>
      <a href="{crmURL p="civicrm/tag/merge" q="id="}<%= items.join() %>" class="button crm-popup small-popup" title="{ts escape='htmlattribute'}Combine tags into one{/ts}">
        <span><i class="crm-i fa-compress" aria-hidden="true"></i> {ts}Merge Tags{/ts}</span>
      </a>
      <% if(tagsetCount) {ldelim} %>
        <a href="#move" class="button move-tag-button" title="{ts escape='htmlattribute'}Move to a different tagset{/ts}">
          <span><i class="crm-i fa-share-square-o" aria-hidden="true"></i> {ts}Move Tags{/ts}</span>
        </a>
      <% {rdelim} %>
      <% if(!hasChildren) {ldelim} %>
        <a href="{crmURL p="civicrm/tag/edit" q="action=delete&id="}<%= items.join() %>" class="button crm-popup small-popup">
          <span><i class="crm-i fa-trash" aria-hidden="true"></i> {ts}Delete All{/ts}</span>
        </a>
      <% {rdelim} %>
    <% {rdelim} %>
  </div>
</script>

<script type="text/template" id="tagsetHeaderTpl">
  <div class="tagset-header">
    <div class="help">
      <% if(is_reserved == 1) {ldelim} %><strong>{ts}Reserved{/ts}</strong><% {rdelim} %>
      <% if(undefined === display_name) {ldelim} var display_name = null; {rdelim} %>
      {ts 1="<%= used_for_label.join(', ') %>" 2="<%= date %>" 3="<%= display_name %>"}Tag Set for %1 (created %2 by %3).{/ts}
      <% if(typeof description === 'string' && description.length && description !== 'null') {ldelim} %><p><em><%- description %></em></p><% {rdelim} %>
    </div>
    <input class="crm-form-text big" name="filter_tag_tree" placeholder="{ts escape='htmlattribute'}Filter List{/ts}" allowclear="1"/>
    <a class="crm-hover-button crm-clear-link" style="visibility:hidden;" title="{ts escape='htmlattribute'}Clear{/ts}"><i class="crm-i fa-times" aria-hidden="true"></i></a>
  </div>
</script>
