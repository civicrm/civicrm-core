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
{if !$printOnly} {* NO print section starts *}
{if $criteriaForm}
<div class="crm-report-criteria"> {* criteria section starts *}
      <div id="mainTabContainer">
        {*tab navigation bar*}
        <ul class="ui-tabs-nav ui-helper-reset ui-helper-clearfix ui-widget-header ui-corner-all">
          {if $colGroups}
            <li class="ui-state-default ui-corner-all">
              <a title="Columns" href="#report-tab-col-groups">{ts}Columns{/ts}</a>
            </li>
          {/if}
          {if $groupByElements}
            <li class="ui-state-default ui-corner-all">
              <a title="Group By" href="#report-tab-group-by-elements">{ts}Grouping{/ts}</a>
            </li>
          {/if}
          {if $orderByOptions}
            <li class="ui-state-default ui-corner-all">
              <a title="Order By" href="#report-tab-order-by-elements">{ts}Sorting{/ts}</a>
            </li>
          {/if}
          {if $form.options.html}
            <li class="ui-state-default ui-corner-all">
              <a title="Other Options" href="#report-tab-other-options">{ts}Options{/ts}</a>
            </li>
          {/if}
          {if $filters}
            <li class="ui-state-default ui-corner-all">
              <a title="Filters" href="#report-tab-set-filters">{ts}Filters{/ts}</a>
            </li>
          {/if}
          {if $instanceForm OR $instanceFormError}
            <li id="tab_settings" class="ui-state-default ui-corner-all">
              <a title="Settings" href="#report-tab-settings">{ts}Display{/ts}</a>
            </li>
            <li class="ui-state-default ui-corner-all">
              <a title="Email" href="#report-tab-email">{ts}Email{/ts}</a>
            </li>
            <li class="ui-state-default ui-corner-all">
              <a title="Other" href="#report-tab-other">{ts}Access{/ts}</a>
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
                            {$form.$save.html}
                            {if $mode neq 'template' && $form.$next}
                                {$form.$next.html}
                            {/if}
                        </div>
</div> {* criteria section ends *}
   {/if}

{literal}
<script type="text/javascript">
CRM.$(function($) {
  $("#mainTabContainer").tabs({
    collapsible: true,
    active: {/literal}{if $rows}false{else}true{/if}{literal}
  });
});

</script>
{/literal}

{/if} {* NO print section ends *}
