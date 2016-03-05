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
{if !$printOnly} {* NO print section starts *}
  {if $criteriaForm}
    <div class="crm-report-criteria"> {* criteria section starts *}
      <div id="mainTabContainer">
        {*tab navigation bar*}
        <ul>
          {foreach from=$tabs item='tab'}
            <li class="ui-corner-all">
              <a title="{$tab.title}" href="#report-tab-{$tab.div_label}">{$tab.title}</a>
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

      {assign var=save value="_qf_"|cat:$form.formName|cat:"_submit_save"}
      {assign var=next value="_qf_"|cat:$form.formName|cat:"_submit_next"}
      <div class="crm-submit-buttons">
        {$form.buttons.html}
        {if $instanceForm}
          {$form.$save.html}
        {/if}
        {if $mode neq 'template' && $form.$next}
          {$form.$next.html}
        {/if}
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
