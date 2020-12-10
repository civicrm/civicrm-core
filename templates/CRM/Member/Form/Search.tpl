{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
<div class="crm-form-block crm-search-form-block">
  <div class="crm-accordion-wrapper crm-member_search_form-accordion {if $rows}collapsed{/if}">
   <div class="crm-accordion-header crm-master-accordion-header">
      {ts}Edit Search Criteria{/ts}
    </div><!-- /.crm-accordion-header -->
  <div class="crm-accordion-body">
  {strip}
       <table class="form-layout">
          {include file="CRM/Contact/Form/Search/ContactSearchFields.tpl"}
          {include file="CRM/Member/Form/Search/Common.tpl"}

          <tr>
              <td colspan="2">{include file="CRM/common/formButtons.tpl"}</td>
          </tr>
      </table>
  {/strip}
   </div><!-- /.crm-accordion-body -->
  </div><!-- /.crm-accordion-wrapper -->
</div><!-- /.crm-form-block -->
<div class="crm-content-block">
  {if $rowsEmpty}
    <div class="crm-results-block crm-results-block-empty">
      {include file="CRM/Member/Form/Search/EmptyResults.tpl"}
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
             <div id ="memberSearch" class="crm-search-results">
             {include file="CRM/Member/Form/Selector.tpl" context="Search"}
             </div>
      {* END Actions/Results section *}
  </div>
  {/if}
</div>
