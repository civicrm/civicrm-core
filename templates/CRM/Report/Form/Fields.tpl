{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{if !$printOnly} {* NO print section starts *}
  {if $criteriaForm}
    <div class="crm-report-criteria"> {* criteria section starts *}
      <div id="mainTabContainer">
        {*tab navigation bar*}
        <ul>
          {foreach from=$tabs item='tab'}
            <li class="ui-corner-all">
              <a title="{$tab.title|escape}" href="#report-tab-{$tab.div_label}">{$tab.title}</a>
            </li>
          {/foreach}
          {if $instanceForm OR $instanceFormError}
            <li id="tab_settings" class="ui-corner-all">
              <a title="{ts}Title and Format{/ts}" href="#report-tab-format">{ts}Title and Format{/ts}</a>
            </li>
            <li class="ui-corner-all">
              <a title="{ts}Email Delivery{/ts}" href="#report-tab-email">{ts}Email Delivery{/ts}</a>
            </li>
            <li class="ui-corner-all">
              <a title="{ts}Access{/ts}" href="#report-tab-access">{ts}Access{/ts}</a>
            </li>
          {/if}
        </ul>

        {*criteria*}
        {include file="CRM/Report/Form/Criteria.tpl"}

        {*settings*}
        {if $instanceForm OR $instanceFormError}
          {include file="CRM/Report/Form/Tabs/Instance.tpl"}
        {/if}
      </div> {* end mainTabContainer *}

      <div class="crm-submit-buttons">
        {$form.buttons.html}
      </div>
    </div> {* criteria section ends *}
  {/if}

{literal}
  <script type="text/javascript">
    CRM.$(function($) {
      var tabSettings = {
        collapsible: true,
        active: {/literal}{if $rows}false{else}0{/if}{literal}
      };
      // If a tab contains an error, open it
      if ($('.civireport-criteria .crm-error', '#mainTabContainer').length) {
        tabSettings.active = $('.civireport-criteria').index($('.civireport-criteria:has(".crm-error")')[0]);
      }
      $("#mainTabContainer").tabs(tabSettings);
    });

  </script>
{/literal}

{/if} {* NO print section ends *}
