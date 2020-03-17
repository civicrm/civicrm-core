{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{if $casePresent}
  {include file="CRM/Case/Form/CaseFilter.tpl" context="$context" list="my-cases" all="0"}
  <div class="form-item">
    {include file="CRM/Case/Page/DashboardSelector.tpl" context="$context" list="my-cases" all="0"}
  </div>
{else}
    <div class="messages status no-popup">
     {capture assign="findCasesURL"}{crmURL p='civicrm/case/search' q='reset=1'}{/capture}
     {ts 1=$findCasesURL}There are no open cases with activities scheduled in the next two weeks. Use <a href="%1">Find Cases</a> to expand your search.{/ts}
    </div>
{/if}
