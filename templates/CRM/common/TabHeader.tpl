{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.4                                                |
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
{literal}
  cj(function($) {
    var tabSettings = {};
    {/literal}{if $selectedTab}
    var selectedTab = "{$selectedTab}";
    tabSettings.active = $('#tab_' + selectedTab).prevAll().length;
    {/if}{literal}
    $("#mainTabContainer")
      .on('tabsbeforeactivate',
        function(e, ui) {
          // Warn of unsaved changes - requires formNavigate.tpl to be included in each tab
          if (!global_formNavigate) {
            var message = '{/literal}{ts escape="js" 1='%1'}Your changes in the <em>%1</em> tab have not been saved.{/ts}{literal}';
            CRM.alert(ts(message, {1: ui.oldTab.text()}), '{/literal}{ts escape="js"}Unsaved Changes{/ts}{literal}', 'warning');
            global_formNavigate = true;
          }
        })
      .on('tabsbeforeload',
        function(e, ui) {
          // Use civicrm ajax wrappers rather than the default $.load
          if (!ui.tab.data("loaded")) {
            CRM.loadPage($('a', ui.tab).attr('href'), {
              target: ui.panel
            })
          }
          ui.tab.data("loaded", true);
          e.preventDefault();
        })
      .tabs(tabSettings);
});
{/literal}
</script>

