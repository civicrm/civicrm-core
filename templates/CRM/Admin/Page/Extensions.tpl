{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}

{if $action eq 1 or $action eq 2 or $action eq 8 or $action eq 32 or $action eq 64}
    {include file="CRM/Admin/Form/Extensions.tpl"}
{else}
  <div class="crm-content-block crm-block">
    {if $action ne 1 and $action ne 2}
        {include file="CRM/Admin/Page/Extensions/Refresh.tpl"}
    {/if}

    {if $extDbUpgrades}
      <div class="messages warning">
        <p>{ts 1=$extDbUpgradeUrl}Your extensions require database updates. Please <a href="%1">execute the updates</a>.{/ts}</p>
      </div>
    {/if}

    {include file="CRM/Admin/Page/Extensions/About.tpl"}

    {include file="CRM/common/enableDisableApi.tpl"}
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
  </div>

    {include file="CRM/common/TabHeader.tpl" defaultTab="summary"}

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
