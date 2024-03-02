{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}

<div class="crm-block crm-form-block crm-search-form-block">
  <details class="crm-accordion-light crm-member_search_form-accordion" {if $rows}{else}open{/if}>
   <summary>
      {ts}Edit Search Criteria{/ts}
    </summary>
  <div class="crm-accordion-body">
      {strip}
        <div class="help">
            {ts}Use this form to find Grant(s) by Contact name, Grant Status, Grant Type, Total Amount , etc .{/ts}
        </div>
        <table class="form-layout">
            <tr>
               <td colspan="3">
                    {$form.sort_name.label}&nbsp;&nbsp;{$form.sort_name.html}&nbsp;&nbsp;&nbsp;{$form.buttons.html}<br />
               </td>
            </tr>

        {include file="CRM/Grant/Form/Search/Common.tpl"}

        </table>
        {/strip}
    </div>
  </details>
</div><!-- /.crm-form-block -->

<div class="crm-content-block">
{if $rowsEmpty}
    <div class="crm-results-block crm-results-block-empty">
        {include file="CRM/Grant/Form/Search/EmptyResults.tpl"}
    </div>
{/if}

{if $rows}
    {* Search request has returned 1 or more matching rows. Display results and collapse the search criteria fieldset. *}
  <div class="crm-results-block">
        {* This section handles form elements for action task select and submit *}
        <div class="crm-search-tasks">
            {include file="CRM/common/searchResultTasks.tpl"}
        </div>
        {* This section displays the rows along and includes the paging controls *}
        <div class="crm-search-results">
            {include file="CRM/Grant/Form/Selector.tpl" context="Search"}
       </div>
    </div><!-- /.crm-results-block -->
{/if}
</div><!-- /.crm-content-block -->
