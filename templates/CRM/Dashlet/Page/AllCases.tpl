{*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2019                                |
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
{if $casePresent}
  {include file="CRM/Case/Form/CaseFilter.tpl" context="$context" list="all-cases" all="1"}
  <div class="form-item">
    {include file="CRM/Case/Page/DashboardSelector.tpl" context="$context" list="all-cases" all="1"}
  </div>
{else}
    <div class="messages status no-popup">
     {capture assign="findCasesURL"}{crmURL p='civicrm/case/search' q='reset=1'}{/capture}
     {ts 1=$findCasesURL}There are no Cases. Use <a href="%1">Find Cases</a> to expand your search.{/ts}
    </div>
{/if}
