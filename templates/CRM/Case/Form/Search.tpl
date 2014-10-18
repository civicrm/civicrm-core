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
{* Search form and results for CiviCase componenet (Find Cases) *}
{if $notConfigured} {* Case types not present. Component is not configured for use. *}
    {include file="CRM/Case/Page/ConfigureError.tpl"}
{else}

<div class="crm-block crm-form-block crm-case-search-form-block">
<div class="crm-accordion-wrapper crm-case_search-accordion {if $rows}collapsed{/if}">
 <div class="crm-accordion-header crm-master-accordion-header">
            {ts}Edit Search Criteria{/ts}
</div><!-- /.crm-accordion-header -->
 <div class="crm-accordion-body">
        {strip}
            <table class="form-layout">
            <tr class="crm-case-search-form-block-sort_name">
               <td class="font-size12pt" colspan="3">
                   {$form.sort_name.label}&nbsp;&nbsp;{$form.sort_name.html|crmAddClass:'twenty'}&nbsp;&nbsp;&nbsp;{$form.buttons.html}
               </td>
            </tr>
            {include file="CRM/Case/Form/Search/Common.tpl"}

            <tr>
               <td colspan="2" class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</td>
            </tr>
            </table>
        {/strip}
</div><!-- /.crm-accordion-body -->
</div><!-- /.crm-accordion-wrapper -->
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

{include file="CRM/Case/Form/ActivityChangeStatusJs.tpl"}
