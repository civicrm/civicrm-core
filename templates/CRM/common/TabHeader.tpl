{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.3                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2013                                |
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

{* enclosed all tabs and its content in a block *}
{* include wysiwyg related files*}
{include file="CRM/common/wysiwyg.tpl" includeWysiwygEditor=true}

<div class="crm-block crm-content-block">
  {if $tabHeader and count($tabHeader) gt 1}
    <div id="mainTabContainer">
    <ul>
       {foreach from=$tabHeader key=tabName item=tabValue}
          <li id="tab_{$tabName}" class="crm-tab-button ui-corner-all {if !$tabValue.valid}disabled{/if}">
          {if $tabValue.link and $tabValue.active}
             <a href="{$tabValue.link}" title="{$tabValue.title}{if !$tabValue.valid} ({ts}disabled{/ts}){/if}">{$tabValue.title}</a>
          {else}
             <span {if !$tabValue.valid} title="{ts}disabled{/ts}"{/if}>{$tabValue.title}</span>
          {/if}
          </li>
       {/foreach}
    </ul>
    </div>
  {/if}
  <div class="clear"></div>
</div> {* crm-content-block ends here *}

<script type="text/javascript">
   var selectedTab = 'EventInfo';
   {if $selectedTab}selectedTab = "{$selectedTab}";{/if}
   var spinnerImage = '<img src="{$config->resourceBase}i/loading.gif" style="width:10px;height:10px"/>';
{literal}

cj( function() {
    var tabIndex = cj('#tab_' + selectedTab).prevAll().length
    cj("#mainTabContainer").tabs( {
        selected: tabIndex,
        spinner: spinnerImage,
        select: function(event, ui) {
            // we need to change the action of parent form, so that form submits to correct page
            var url = ui.tab.href;

            {/literal}{if $config->userSystem->is_drupal}{literal}
                var actionUrl = url.split( '?' );
                {/literal}{if $config->cleanURL}{literal}
                  var actualUrl = actionUrl[0];
                {/literal}{else}{literal}
                  var getParams = actionUrl[1].split( '&' );
                  var actualUrl = actionUrl[0] + '?' + getParams[0];
                {/literal}{/if}{literal}
            {/literal}{else}{literal}
                var actionUrl = url.split( '&' );
                var actualUrl = actionUrl[0] + '&' + actionUrl[1];
            {/literal}{/if}{literal}

            if ( !global_formNavigate ) {
              var message = '{/literal}{ts escape="js"}Are you sure you want to navigate away from this tab?{/ts}' + '\n\n' + '{ts escape="js"}You have unsaved changes.{/ts}' + '\n\n' + '{ts escape="js"}Press OK to continue, or Cancel to stay on the current tab.{/ts}{literal}';
              if ( !confirm( message ) ) {
                return false;
              } else {
                global_formNavigate = true;
              }
            }
            cj(this).parents("form").attr("action", actualUrl );

            return true;
        },
        load: function(event, ui) {
          if ((typeof(Drupal) != 'undefined') && Drupal.attachBehaviors) {
            Drupal.attachBehaviors(ui.panel);
          }
          cj(ui.panel).trigger('crmFormLoad');
          // FIXME - decouple scanProfileSelectors and TabHeader
          if (CRM.scanProfileSelectors) {
            CRM.scanProfileSelectors();
          }
        }
    });
});
{/literal}
</script>

