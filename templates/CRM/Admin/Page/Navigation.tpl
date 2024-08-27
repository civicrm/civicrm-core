{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{if $action eq 1 or $action eq 2 or $action eq 8}
  {include file="CRM/Admin/Form/Navigation.tpl"}
{else}
  <div class="help">
    {capture assign="displayPrefUrl"}href="{crmURL p='civicrm/admin/setting/preferences/display' q='reset=1'}"{/capture}
    {capture assign="searchPrefUrl"}href="{crmURL p='civicrm/admin/setting/search' q='reset=1'}"{/capture}
    <p>{ts}Customize the CiviCRM navigation menu bar for your users here.{/ts} {help id="id-navigation"}</p>
    <p>{ts 1=$displayPrefUrl}The menu color and position can be adjusted on the <a %1>Display Preferences</a> screen.{/ts}</p>
    <p>{ts 1=$searchPrefUrl}Quicksearch options can be edited on the <a %1>Search Preferences</a> screen.{/ts}</p>
  </div>

  <div class="crm-block crm-content-block">
    <div id="new-menu-item">
      {crmButton p="civicrm/admin/menu" q="action=add&reset=1" id="newMenuItem" icon="plus-circle" style="margin-left: 6px;"}{ts}Add Menu Item{/ts}{/crmButton}
    </div>
    <div class="spacer"></div>
    <div style="padding-left: 48px;"><img src="{$config->resourceBase}i/logo_sm.png" /></div>
    <div id="navigation-tree" class="navigation-tree"></div>
    <div class="spacer"></div>
    <div>
      <a href="#" class="nav-reset crm-hover-button">
        <i class="crm-i fa-broom" aria-hidden="true"></i> {ts}Cleanup reports menu{/ts}
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
            }).then(function(data) {
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
                      CRM.api3('Navigation', 'delete', {id: nodeID}, true).then(refreshMenubar);
                      $("#navigation-tree").jstree(true).delete_node(menu.reference.closest('li'));
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
        CRM.status({}, $.get( postURL + '&type=move&id=' +  nodeID + '&ref_id=' + refID + '&ps='+ps).then(refreshMenubar));
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
          refreshMenubar();
        });
      }

      $('#new-menu-item a.button')
        .on('click', CRM.popup)
        .on('crmPopupFormSuccess', function() {
          $("#navigation-tree").jstree(true).refresh();
          refreshMenubar();
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
              .then(function() {
                $('#crm-container').unblock();
                $("#navigation-tree").jstree(true).refresh();
                refreshMenubar();
              });
          });
      });

      // Force-refresh the menubar by resetting the cache code
      function refreshMenubar() {
        CRM.menubar.destroy();
        CRM.menubar.cacheCode = Math.random();
        CRM.menubar.initialize();
      }
    });
</script>
{/literal}
{/if}
