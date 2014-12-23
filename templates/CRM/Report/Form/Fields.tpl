{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.6                                                |
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
{if !$printOnly} {* NO print section starts *}
  {if $criteriaForm}
    <div class="crm-report-criteria"> {* criteria section starts *}
      <div id="mainTabContainer">
        {*tab navigation bar*}
        <ul>
          {if $colGroups}
            <li class="ui-corner-all">
              <a title="{ts}Columns{/ts}" href="#report-tab-col-groups">{ts}Columns{/ts}</a>
            </li>
          {/if}
          {if $groupByElements}
            <li class="ui-corner-all">
              <a title="{ts}Grouping{/ts}" href="#report-tab-group-by-elements">{ts}Grouping{/ts}</a>
            </li>
          {/if}
          {if $orderByOptions}
            <li class="ui-corner-all">
              <a title="{ts}Sorting{/ts}" href="#report-tab-order-by-elements">{ts}Sorting{/ts}</a>
            </li>
          {/if}
          {if $otherOptions}
            <li class="ui-corner-all">
              <a title="{ts}Display Options{/ts}" href="#report-tab-other-options">{ts}Display{/ts}</a>
            </li>
          {/if}
          {if $filters}
            <li class="ui-corner-all">
              <a title="{ts}Filters{/ts}" href="#report-tab-set-filters">{ts}Filters{/ts}</a>
            </li>
          {/if}
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
          {include file="CRM/Report/Form/Instance.tpl"}
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
