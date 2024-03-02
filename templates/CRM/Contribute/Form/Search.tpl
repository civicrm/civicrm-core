{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{* Search form and results for Contributions *}
{assign var="showBlock" value="'searchForm'"}
{assign var="hideBlock" value="'searchForm_show'"}
<div class="crm-block crm-form-block crm-contribution-search-form-block">
  <details class="crm-accordion-light crm-contribution_search_form-accordion" {if $rows}{else}open{/if}>
      <summary>
          {ts}Edit Search Criteria{/ts}
       </summary>
      <div class="crm-accordion-body">
        {strip}
          <table class="form-layout">
            {include file="CRM/Contact/Form/Search/ContactSearchFields.tpl"}
            {include file="CRM/Contribute/Form/Search/Common.tpl"}
            <tr>
               <td colspan="2">{$form.buttons.html}</td>
            </tr>
            </table>
        {/strip}
      </div>
    </details>
</div><!-- /.crm-form-block -->
{if $rowsEmpty || $rows}
<div class="crm-content-block">
{if $rowsEmpty}
<div class="crm-results-block crm-results-block-empty">
    {include file="CRM/Contribute/Form/Search/EmptyResults.tpl"}
</div>
{/if}

{if $rows}
    <div class="crm-results-block">
    {* Search request has returned 1 or more matching rows. *}
        {* This section handles form elements for action task select and submit *}
        <div class="crm-search-tasks crm-event-search-tasks">
            {include file="CRM/common/searchResultTasks.tpl" context="Contribution"}
        </div>

        {* This section displays the rows along and includes the paging controls *}
  <div id="contributionSearch" class="crm-search-results">
        {include file="CRM/Contribute/Form/Selector.tpl" context="Search"}
  </div>
    {* END Actions/Results section *}
    </div>
{/if}

</div>
{/if}

