{*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2018                                |
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
{if $action eq 1 or $action eq 2 or $action eq 8}
  {include file="CRM/Admin/Form/Navigation.tpl"}
{else}
  <div class="help">
    {ts}Customize the CiviCRM navigation menu bar for your users here.{/ts} {help id="id-navigation"}
  </div>

  <div class="crm-block crm-content-block">
    <div id="new-menu-item">
      {crmButton p="civicrm/admin/menu" q="action=add&reset=1" id="newMenuItem" icon="crm-i fa-plus-circle" style="margin-left: 6px;"}{ts}Add Menu Item{/ts}{/crmButton}&nbsp;&nbsp;&nbsp;&nbsp;
        <span id="reset-menu" class="status" style="display:none">
        {capture assign=rebuildURL}{crmURL p='civicrm/admin/menu' q="reset=1"}{/capture}
          {ts 1=$rebuildURL}<a href='%1' title="Reload page"><strong>Click here</strong></a> to reload the page and see your changes in the menu bar above.{/ts}
        </span><br/><br/>
    </div>
    <div class="spacer"></div>
    <div style="padding-left: 25px;"><div class="crm-logo-sm"></div></div>
    <div id="navigation-tree" class="navigation-tree" style="height:auto; border-collapse:separate; background-color:#FFFFFF;"></div>
    <div class="spacer"></div>
    <div>
      <a href="#" class="nav-reset crm-hover-button">
        {* TODO: fa-broom would be better, but not implemented yet. https://github.com/FortAwesome/Font-Awesome/issues/239 *}
        <i class="crm-i fa-undo"></i> {ts}Cleanup reports menu{/ts}
      </a>
    </div>
    <div class="spacer"></div>
  </div>
  {literal}
  <script type="text/javascript">
    CRM.$(function($) {
      $("#navigation-tree").jstree({
        plugins: ["dnd", "contextmenu"],
        core: {
          data: function(tree, callBack) {
            CRM.api3('Navigation', 'get', {
              domain_id: {/literal}{$config->domainID()}{literal},
              options: {limit: 0, sort: 'weight'},
              return: ['label', 'parent_id', 'icon'],
              name: {'!=': 'Home'},
              sequential: 1
            }).done(function(data) {
              var items = [];
              $.each(data.values, function(key, value) {
                items.push({
                  id: value.id,
                  text: value.label,
                  icon: value.icon || false,
                  parent: value.parent_id || '#'
                });
              });
              callBack(items);
            });
          },
          progressive_render: true,
          check_callback: true
        },
        dnd: {
          copy: false
        },
        contextmenu: {
          items: function (node, callBack) {
            var items = {
              add: {
                label: "{/literal}{ts escape='js'}Add{/ts}{literal}",
                icon: 'crm-i fa-plus',
                action: editForm
              },
              edit: {
                label: "{/literal}{ts escape='js'}Edit{/ts}{literal}",
                icon: 'crm-i fa-pencil',
                action: editForm
              },
              delete: {
                label: "{/literal}{ts escape='js'}Delete{/ts}{literal}",
                icon: 'crm-i fa-trash',
                action: function (menu) {
                  var nodeID = menu.reference.attr('id').replace('_anchor', ''),
                    node = $("#navigation-tree").jstree(true).get_node(nodeID),
                    menuName = node.text;
                  var deleteMsg = {/literal}"{ts escape='js'}Are you sure you want to delete this menu item:{/ts} " + '"'{literal} + menuName + {/literal}'"? {ts escape='js'}This action cannot be undone.{/ts}'{literal};
                  if (node.children.length) {
                    deleteMsg += {/literal}"<br /><br />" + ts('{ts escape='js' 1='<strong>%1</strong>'}%1 sub-menu items will also be deleted.{/ts}'{literal}, {1: node.children.length});
                  }
                  CRM.confirm({message: deleteMsg})
                    .on('crmConfirm:yes', function() {
                      CRM.api3('Navigation', 'delete', {id: nodeID}, true);
                      $("#navigation-tree").jstree(true).delete_node(menu.reference.closest('li'));
                      $("#reset-menu").show();
                    });
                }
              }
            };
            callBack(items);
          }
        }
      }).on("move_node.jstree", function (e, data) {
        var nodeID = data.node.id;
        var refID = data.parent === '#' ? '' : data.parent;
        var ps = data.position;
        var postURL = {/literal}"{crmURL p='civicrm/ajax/menutree' h=0 q='key='}{crmKey name='civicrm/ajax/menutree'}"{literal};
        CRM.status({}, $.get( postURL + '&type=move&id=' +  nodeID + '&ref_id=' + refID + '&ps='+ps));
        $("#reset-menu").show();
      });

      function editForm(menu) {
        var nodeID = menu.reference.attr('id').replace('_anchor', ''),
          action = menu.item.icon === 'crm-i fa-pencil' ? 'update' : 'add',
          args = {reset: 1, action: action};
        if (action === 'add') {
          args.parent_id = nodeID;
        } else {
          args.id = nodeID;
        }
        CRM.loadForm(CRM.url('civicrm/admin/menu', args)).on('crmFormSuccess', function() {
          $("#navigation-tree").jstree(true).refresh();
          $("#reset-menu").show();
        });
      }

      $('#new-menu-item a.button')
        .on('click', CRM.popup)
        .on('crmPopupFormSuccess', function() {
          $("#navigation-tree").jstree(true).refresh();
          $("#reset-menu").show();
        });

      $('a.nav-reset').on('click', function(e) {
        e.preventDefault();
        CRM.confirm({
          title: $(this).text(),
          message: '{/literal}{ts escape='js'}This will add links for all currently active reports to the "Reports" menu under the relevant component. If you have added report instances to other menus, they will be moved to "Reports".  Are you sure?{/ts}{literal}'
        })
          .on('crmConfirm:yes', function() {
            $('#crm-container').block();
            CRM.api3('Navigation', 'reset', {'for': 'report'}, true)
              .done(function() {
                $('#crm-container').unblock();
                $("#navigation-tree").jstree(true).refresh();
                $("#reset-menu").show();
              })
          });
      });
    });
</script>
{/literal}
{/if}
