{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2015                                |
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
  <style type="text/css">
    #navigation-tree li {
      font-weight: normal;
    }
    #navigation-tree > ul > li {
      font-weight: bold;
    }
  </style>
  <script type="text/javascript">
    CRM.$(function($) {
      $("#navigation-tree").jstree({
        plugins: [ "themes", "json_data", "dnd","ui", "crrm","contextmenu" ],
        json_data: {
          ajax:{
            dataType: "json",
            url: {/literal}"{crmURL p='civicrm/ajax/menu' h=0 q='key='}{crmKey name='civicrm/ajax/menu'}"{literal}
          },
          progressive_render: true
        },
        themes: {
          "theme": 'classic',
          "dots": true,
          "icons": false,
          "url": CRM.config.resourceBase + 'packages/jquery/plugins/jstree/themes/classic/style.css'
        },
        rules: {
          droppable: [ "tree-drop" ],
          multiple: true,
          deletable: "all",
          draggable: "all"
        },
        crrm: {
          move: {
            check_move: function(m) {
              var homeMenuId = {/literal}"{$homeMenuId}"{literal};
              if ( $( m.r[0] ).attr('id').replace("node_","") == homeMenuId ||
                $( m.o[0] ).attr('id').replace("node_","") == homeMenuId ) {
                return false;
              } else {
                return true;
              }
            }
          }
        },
        contextmenu: {
          items: {
            create: false,
            ccp: {
              label : "{/literal}{ts escape='js'}Edit{/ts}{literal}",
              visible: function (node, obj) { if(node.length != 1) return false;
                return obj.check("renameable", node); },
              action: function (node, obj) {
                var nid = $(node).prop('id');
                var nodeID = nid.substr( 5 );
                var editURL = {/literal}"{crmURL p='civicrm/admin/menu' h=0 q='action=update&reset=1&id='}"{literal} + nodeID;
                CRM.loadForm(editURL).on('crmFormSuccess', function() {
                  $("#navigation-tree").jstree('refresh');
                  $("#reset-menu").show( );
                });
              },
              submenu: false
            }
          }
        }

      }).bind("rename.jstree", function ( e,node ) {
        var nodeID  = node.rslt.obj.attr('id').replace("node_","");
        var newName = node.rslt.new_name;
        var postURL = {/literal}"{crmURL p='civicrm/ajax/menutree' h=0 q='key='}{crmKey name='civicrm/ajax/menutree'}"{literal};
        $.get( postURL + '&type=rename&id=' + nodeID + '&data=' + newName,
          function (data) {
            $("#reset-menu").show( );
          }
        );

      }).bind("remove.jstree", function( e,node ) {
        var menuName  = node.rslt.obj.find('a').first( ).text( );
        var nodeID  = node.rslt.obj.attr('id').replace("node_","");

        // don't allow deleting of home
        var homeMenuId = {/literal}"{$homeMenuId}"{literal};
        if ( nodeID == homeMenuId ) {
          var cannotDeleteMsg = {/literal}"{ts escape='js'}You cannot delete this menu item:{/ts}" + " "{literal} + menuName;
          CRM.alert( cannotDeleteMsg, {/literal}"{ts escape='js'}Cannot Delete{/ts}"{literal} );
          $("#navigation-tree").jstree('refresh');
          return false;
        }
        var deleteMsg = {/literal}"{ts escape='js'}Are you sure you want to delete this menu item:{/ts}" + " "{literal} + menuName + {/literal}" ? {ts}This action cannot be undone.{/ts}"{literal};
        var isDelete  = confirm( deleteMsg );
        if ( isDelete ) {
          var postURL = {/literal}"{crmURL p='civicrm/ajax/menutree' h=0 q='key='}{crmKey name='civicrm/ajax/menutree'}"{literal};
          $.get( postURL + '&type=delete&id=' + nodeID,
            function (data) {
              $("#reset-menu").show( );
            }
          );
        } else {
          $("#navigation-tree").jstree('refresh');
        }

      }).bind("move_node.jstree", function ( e,node ) {
        node.rslt.o.each(function (i) {
          var nodeID = node.rslt.o.attr('id').replace("node_","");
          var refID  = node.rslt.np.attr('id').replace("node_","");
          if (isNaN( refID ) ){ refID =''; }
          var ps = node.rslt.cp+i;
          var postURL = {/literal}"{crmURL p='civicrm/ajax/menutree' h=0 q='key='}{crmKey name='civicrm/ajax/menutree'}"{literal};
          $.get( postURL + '&type=move&id=' +  nodeID + '&ref_id=' + refID + '&ps='+ps,
            function (data) {
              $("#reset-menu").show( );
            });
        });
      });
      $('#new-menu-item a.button')
        .on('click', CRM.popup)
        .on('crmPopupFormSuccess', function() {
          $("#navigation-tree").jstree('refresh');
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
                $("#navigation-tree").jstree('refresh');
                $("#reset-menu").show();
              })
          });
      });
    });
</script>
{/literal}
{/if}
