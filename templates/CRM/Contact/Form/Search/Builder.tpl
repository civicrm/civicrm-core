{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{* Search Builder *}

<div class="crm-form-block crm-search-form-block">
  <div class="crm-accordion-wrapper crm-search_builder-accordion {if $rows and !$showSearchForm}collapsed{/if}">
    <div class="crm-accordion-header crm-master-accordion-header">
      {ts}Search Criteria{/ts} {help id='builder-intro'}
    </div>
    <div class="crm-accordion-body">
      <div id="searchForm">
        {* Table for adding search criteria. *}
        {include file="CRM/Contact/Form/Search/table.tpl"}
        <div class="clear"></div>
        <div id="crm-submit-buttons" class="crm-submit-buttons">
          {$form.buttons.html}
        </div>
      </div>
    </div><!-- /.crm-accordion-body -->
  </div><!-- /.crm-accordion-wrapper -->
</div><!-- /.crm-form-block -->
{if $rowsEmpty || $rows}
<div class="crm-content-block">
{if $rowsEmpty}
  <div class="crm-results-block crm-results-block-empty">
    {include file="CRM/Contact/Form/Search/EmptyResults.tpl"}
  </div>
{/if}

{if $rows}
  <div class="crm-results-block">
       {* This section handles form elements for action task select and submit *}
       <div class="crm-search-tasks">
       {include file="CRM/Contact/Form/Search/ResultTasks.tpl"}
       </div>

       {* This section displays the rows along and includes the paging controls *}
       <div class="crm-search-results">
       {include file="CRM/Contact/Form/Selector.tpl"}
      </div>

    </div>
    {* END Actions/Results section *}

{/if}
</div>
{/if}
{$initHideBoxes}
{include file="CRM/Form/validate.tpl"}
