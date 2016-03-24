{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2016                                |
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
        <div id="crm-submit-buttons">
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
