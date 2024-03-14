{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{* Master tpl for Advanced Search *}

<div class="crm-form-block crm-search-form-block">

  {include file="CRM/Contact/Form/Search/Intro.tpl"}

  <details class="crm-accordion-light crm-advanced_search_form-accordion" {if !$rows}open{/if} >
    <summary>
      {if !empty($savedSearch)}
        {ts 1=$savedSearch.name}Edit %1 Smart Group Criteria{/ts}
      {elseif !empty($ssID) or $rows}
        {ts}Edit Search Criteria{/ts}
      {else}
        {ts}Search Criteria{/ts}
      {/if}
      {help id='id-advanced-intro'}
    </summary>
    <div class="crm-accordion-body">
      {include file="CRM/Contact/Form/Search/AdvancedCriteria.tpl"}
    </div>
  </details>
</div>

{if $rowsEmpty}
<div class="crm-content-block">
  <div class="crm-results-block crm-results-block-empty">
    {include file="CRM/Contact/Form/Search/EmptyResults.tpl"}
  </div>
</div>
{/if}

{if $rows}
<div class="crm-content-block">
  <div class="crm-results-block">
    {* Search request has returned 1 or more matching rows. Display results and collapse the search criteria fieldset. *}

    {* This section handles form elements for action task select and submit *}
    <div class="crm-search-tasks">
      {if $taskFile}
        {if $taskContext}
          {include file=$taskFile context=$taskContext}
        {else}
          {include file=$taskFile}
        {/if}
      {else}
        {include file="CRM/Contact/Form/Search/ResultTasks.tpl"}
      {/if}
    </div>

    {* This section displays the rows along and includes the paging controls *}
    <div class="crm-search-results">
      {if $resultFile}
        {if $resultContext}
          {include file=$resultFile context=$resultContext}
        {else}
          {include file=$resultFile}
        {/if}
      {else}
        {include file="CRM/Contact/Form/Selector.tpl"}
      {/if}
    </div>

  {* END Actions/Results section *}
  </div>
</div>
{/if}
