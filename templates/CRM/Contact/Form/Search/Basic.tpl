{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2015                                |
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
