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
{* Master tpl for Advanced Search *}

<div class="crm-form-block crm-search-form-block">

  {include file="CRM/Contact/Form/Search/Intro.tpl"}

  <div class="crm-accordion-wrapper crm-advanced_search_form-accordion {if !empty($ssID) or $rows}collapsed{/if}">
    <div class="crm-accordion-header crm-master-accordion-header">
      {if !empty($ssID) or $rows}
        {if $savedSearch}
          {ts 1=$savedSearch.name}Edit %1 Smart Group Criteria{/ts}
        {else}
          {ts}Edit Search Criteria{/ts}
        {/if}
      {else}
        {if $savedSearch}
          {ts 1=$savedSearch.name}Edit %1 Smart Group Criteria{/ts}
        {else}
          {ts}Search Criteria{/ts}
        {/if}
      {/if}
      {help id='id-advanced-intro'}
    </div>
    <div class="crm-accordion-body">
      {include file="CRM/Contact/Form/Search/AdvancedCriteria.tpl"}
    </div>
  </div>
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
