{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}

{* Main template for basic search (Find Contacts) *}
{include file="CRM/Contact/Form/Search/Intro.tpl"}
<div class="crm-form-block crm-search-form-block">
{* This section handles form elements for search criteria *}
  <div id="searchForm">
    {include file="CRM/Contact/Form/Search/BasicCriteria.tpl"}
  </div>
</div>
<div class="crm-content-block">
{if $rowsEmpty}
  <div class="crm-results-block crm-results-block-empty">
    {include file="CRM/Contact/Form/Search/EmptyResults.tpl"}
  </div>
{elseif $rows}
  <div class="crm-results-block">
    {* Search request has returned 1 or more matching rows. *}
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
{else}
  <div class="spacer">&nbsp;</div>
{/if}
</div>
{*include file="CRM/common/searchJs.tpl"*}
