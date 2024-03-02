{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{* Search form and results for CiviCase componenet (Find Cases) *}
{if $notConfigured} {* Case types not present. Component is not configured for use. *}
    {include file="CRM/Case/Page/ConfigureError.tpl"}
{else}

<div class="crm-block crm-form-block crm-case-search-form-block">
<details class="crm-accordion-light crm-case_search-accordion" {if $rows}{else}open{/if}>
 <summary>
            {ts}Edit Search Criteria{/ts}
</summary>
 <div class="crm-accordion-body">
        {strip}
            <table class="form-layout">
            <tr class="crm-case-search-form-block-sort_name">
               <td colspan="2">
                   {$form.sort_name.label}&nbsp;&nbsp;{$form.sort_name.html|crmAddClass:'twenty'}
               </td>
              <td>{include file="CRM/common/formButtons.tpl" location="top"}</td>
            </tr>
            {include file="CRM/Case/Form/Search/Common.tpl"}

            <tr>
               <td colspan="3" class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</td>
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
        {include file="CRM/Case/Form/Search/EmptyResults.tpl"}
    </div>
    {/if}

    {if $rows}
  <div class="crm-results-block">
          {* Search request has returned 1 or more matching rows. Display results and collapse the search criteria fieldset. *}

          {* Search request has returned 1 or more matching rows. *}

             {* This section handles form elements for action task select and submit *}
             <div class="crm-search-tasks">
             {include file="CRM/common/searchResultTasks.tpl"}
             </div>

             {* This section displays the rows along and includes the paging controls *}
             <div class="crm-search-results">
             {include file="CRM/Case/Form/Selector.tpl" context="Search"}
             </div>
          {* END Actions/Results section *}
  </div>
    {/if}
</div>
{/if}
{/if}
