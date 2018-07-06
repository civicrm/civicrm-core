{*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2018                                |
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
{* Search form and results for Event Participants *}
{assign var="showBlock" value="'searchForm'"}
{assign var="hideBlock" value="'searchForm_show'"}
<div class="crm-block crm-form-block crm-event-search-form-block">
<div class="crm-accordion-wrapper crm-advanced_search_form-accordion {if !empty($ssID) or $rows}collapsed{/if}">
 <div class="crm-accordion-header crm-master-accordion-header">
        {ts}Edit Search Criteria{/ts}
  </div>
 <div class="crm-accordion-body">
<div id="searchForm">
    {strip}
        <table class="form-layout">
        {include file="CRM/Contact/Form/Search/ContactSearchFields.tpl"}

        {include file="CRM/Event/Form/Search/Common.tpl"}

        <tr>
           <td colspan="2">{include file="CRM/common/formButtons.tpl"}</td>
        </tr>
        </table>
    {/strip}
</div>
</div>
</div>
</div>
{if $rowsEmpty|| $rows}
<div class="crm-block crm-content-block">
{if $rowsEmpty}
  <div class="crm-results-block crm-results-block-empty">
    {include file="CRM/Event/Form/Search/EmptyResults.tpl"}
  </div>
{/if}

{if $rows}
<div class="crm-results-block">
    {* Search request has returned 1 or more matching rows. *}
        {* This section handles form elements for action task select and submit *}
        <div class="crm-search-tasks crm-event-search-tasks">
           {include file="CRM/common/searchResultTasks.tpl" context='Event'}
      </div>
        {* This section displays the rows along and includes the paging controls *}
      <div id='participantSearch' class="crm-event-search-results">
          {include file="CRM/Event/Form/Selector.tpl" context="Search"}
        </div>
    {* END Actions/Results section *}
</div>
{/if}

</div>
{/if}
