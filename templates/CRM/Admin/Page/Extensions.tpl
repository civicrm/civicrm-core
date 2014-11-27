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

{if $action eq 1 or $action eq 2 or $action eq 8 or $action eq 32 or $action eq 64}
    {include file="CRM/Admin/Form/Extensions.tpl"}
{else}
    {if $action ne 1 and $action ne 2}
        {include file="CRM/Admin/Page/Extensions/Refresh.tpl"}
    {/if}

    {if $extDbUpgrades}
      <div class="messages warning">
        <p>{ts 1=$extDbUpgradeUrl}Your extensions require database updates. Please <a href="%1">execute the updates</a>.{/ts}
      </div>
    {/if}

    {include file="CRM/Admin/Page/Extensions/About.tpl"}

    {include file="CRM/common/enableDisableApi.tpl"}
    {include file="CRM/common/crmeditable.tpl"}
    {include file="CRM/common/jsortable.tpl"}

    <div id="mainTabContainer" class="ui-tabs ui-widget ui-widget-content ui-corner-all">
        <ul class="crm-extensions-tabs-list">
            <li id="tab_summary" class="crm-tab-button">
              <a href="#extensions-main" title="{ts}Extensions{/ts}">
              <span> </span> {ts}Extensions{/ts}
              <em>&nbsp;</em>
              </a>
            </li>
            <li id="tab_addnew" class="crm-tab-button">
              <a href="#extensions-addnew" title="{ts}Add New{/ts}">
              <span> </span> {ts}Add New{/ts}
              <em>&nbsp;</em>
              </a>
            </li>
        </ul>

        <div id="extensions-main" class="ui-tabs-panel ui-widget-content ui-corner-bottom">
            {include file="CRM/Admin/Page/Extensions/Main.tpl"}
        </div>
        <div id="extensions-addnew" class="ui-tabs-panel ui-widget-content ui-corner-bottom">
            {if $extAddNewEnabled}
                {if $extAddNewReqs}
                    {include file="CRM/Admin/Page/Extensions/AddNewReq.tpl"}
                {else}
                    {include file="CRM/Admin/Page/Extensions/AddNew.tpl"}
                {/if}
            {else}
                {ts}The system administrator has disabled this feature.{/ts}
            {/if}
        </div>

        <div class="clear"></div>
    </div>

    {if $action ne 1 and $action ne 2}
        {include file="CRM/Admin/Page/Extensions/Refresh.tpl"}
    {/if}

    {* Expand/Collapse *}
    {literal}
    <script type="text/javascript">
      CRM.$(function($) {
          $('.collapsed').click( function( ) {
              var currentObj = $( this );
              if ( currentObj.hasClass( 'expanded') ) {
                  currentObj.removeClass( 'expanded' );
                  currentObj.parent( ).parent( ).next( ).hide( );
              } else {
                  currentObj.addClass( 'expanded' );
                  currentObj.parent( ).parent( ).next( ).show( );
              }

              return false;
          });
      });
    </script>
    {/literal}

    {* Tab management *}
    <script type="text/javascript">
    var selectedTab  = 'summary';
    {if $selectedChild}selectedTab = "{$selectedChild}";{/if}

    {literal}

    CRM.$(function($) {
      var tabIndex = $('#tab_' + selectedTab).prevAll().length;
      $("#mainTabContainer").tabs({active: tabIndex});
      $(".crm-tab-button").addClass("ui-corner-bottom");
    });
    {/literal}
    </script>

    {* Refresh buttons *}
    {literal}
    <script type="text/javascript">
    CRM.$(function($) {
      $('.crm-extensions-refresh').click(function(event){
        event.stopPropagation();
        CRM.alert('', '{/literal}{ts escape="js"}Refreshing...{/ts}{literal}', 'crm-msg-loading', {expires: 0});
        CRM.api('Extension', 'refresh', {}, {
          'callBack' : function(result){
            if (result.is_error) {
              CRM.alert(result.error_message, '{/literal}{ts escape="js"}Refresh Error{/ts}{literal}', 'error');
            } else {
              window.location.reload();
            }
          }
        });
        return false;
      }); // .click
    }); // onload
    </script>
    {/literal}
{/if}
