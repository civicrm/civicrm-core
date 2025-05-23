{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{if empty($printOnly)} {* NO print section starts *}
  {if !empty($criteriaForm)}
    <div class="crm-report-criteria"> {* criteria section starts *}
      <div id="mainTabContainer">
        {*tab navigation bar*}
        <ul role="tablist">
          {foreach from=$tabs item='tab'}
            <li class="ui-corner-all" role="tab">
              <a title="{$tab.title|escape}" href="#report-tab-{$tab.div_label}">{$tab.title}</a>
            </li>
          {/foreach}
          {if !empty($instanceForm) OR !empty($instanceFormError)}
            <li id="tab_settings" class="ui-corner-all" role="tab">
              <a title="{ts escape='htmlattribute'}Title and Format{/ts}" href="#report-tab-format">{ts}Title and Format{/ts}</a>
            </li>
            <li class="ui-corner-all" role="tab">
              <a title="{ts escape='htmlattribute'}Email Delivery{/ts}" href="#report-tab-email">{ts}Email Delivery{/ts}</a>
            </li>
            <li class="ui-corner-all" role="tab">
              <a title="{ts escape='htmlattribute'}Access{/ts}" href="#report-tab-access">{ts}Access{/ts}</a>
            </li>
          {/if}
        </ul>

        {*criteria*}
        {include file="CRM/Report/Form/Criteria.tpl"}

        {*settings*}
        {if !empty($instanceForm) OR !empty($instanceFormError)}
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
        active: {/literal}{if !empty($rows)}false{else}0{/if}{literal}
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
