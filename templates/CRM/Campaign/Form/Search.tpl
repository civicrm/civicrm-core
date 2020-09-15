{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{* Search form and results for voters *}
{include file='CRM/Campaign/Form/Search/Common.tpl' context='search'}

{if $rowsEmpty || $rows}
<div class="crm-content-block crm-search-form-block">
{if $rowsEmpty}
<div class="crm-content-block">
  <div class="crm-results-block crm-results-block-empty">
    {include file="CRM/Campaign/Form/Search/EmptyResults.tpl"}
  </div>
</div>
{/if}

{if $rows}
<div class="crm-content-block">
  <div class="crm-results-block">
    {* Search request has returned 1 or more matching rows. Display results and collapse the search criteria fieldset. *}
    {assign var="showBlock" value="'searchForm_show'"}
    {assign var="hideBlock" value="'searchForm'"}

    {* Search request has returned 1 or more matching rows. *}
    <fieldset>
      <div class="crm-search-tasks">
       {* This section handles form elements for action task select and submit *}
       {include file="CRM/common/searchResultTasks.tpl" context="Campaign"}
      </div>
      <div class="crm-search-results">
       {* This section displays the rows along and includes the paging controls *}
       {include file="CRM/Campaign/Form/Selector.tpl" context="Search"}
      </div>
    </fieldset>
    {* END Actions/Results section *}
  </div>
</div>
{/if}
</div>
{/if}
