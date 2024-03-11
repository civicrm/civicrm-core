{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{* Search form and results for Event Participants *}
<div class="crm-form-block crm-search-form-block">
<details class="crm-accordion-light crm-advanced_search_form-accordion" {if $rowsEmpty or $rows}{else}open{/if}>
 <summary>
        {ts}Edit Search Criteria{/ts}
 </summary>
 <div class="crm-accordion-body">

<div id="searchForm">
    {strip}
        <table class="form-layout">
        {include file="CRM/Contact/Form/Search/ContactSearchFields.tpl"}
        {include file="CRM/Pledge/Form/Search/Common.tpl"}

        <tr>
           <td colspan="2">{$form.buttons.html}</td>
        </tr>
        </table>
    {/strip}
  </div>
</div>
</details>
</div>

{if $rowsEmpty || $rows}

<div class="crm-content-block">

{if $rowsEmpty}
  <div class="crm-results-block crm-results-block-empty">
  {include file="CRM/Pledge/Form/Search/EmptyResults.tpl"}
  </div>
{/if}

{if $rows}
  <div class="crm-results-block">

    {* Search request has returned 1 or more matching rows. *}

       {* This section handles form elements for action task select and submit *}
       <div class="crm-search-tasks">
       {include file="CRM/common/searchResultTasks.tpl"}
    </div>
       {* This section displays the rows along and includes the paging controls *}
     <div class="crm-search-results">
       {include file="CRM/Pledge/Form/Selector.tpl" context="Search"}
       </div>
    {* END Actions/Results section *}
</div>
{/if}
</div>
{/if}
