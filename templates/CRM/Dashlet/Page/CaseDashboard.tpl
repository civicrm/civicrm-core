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

<div id="case_dashboard_dashlet" class="form-item">

{capture assign=newCaseURL}{crmURL p="civicrm/case/add" q="action=add&context=standalone&reset=1"}{/capture}

<div class="float-right">
  <table class="form-layout-compressed">
   {if $newClient}
    <tr>
      <td>
        <a href="{$newCaseURL}" class="button">
          <span><i class="crm-i fa-plus-circle"></i> {ts}New Case{/ts}</span>
        </a>
      </td>
    </tr>
   {/if}
   {if $myCases}
    <tr>
      <td class="right">
        <a href="{crmURL p="civicrm/case" q="reset=1&all=1"}"><span>&raquo; {ts}Show ALL Cases with Upcoming Activities{/ts}</span></a>
      </td>
    </tr>
   {else}
    <tr>
      <td class="right">
        <a href="{crmURL p="civicrm/case" q="reset=1&all=0"}"><span>&raquo; {ts}Show My Cases with Upcoming Activities{/ts}</span></a>
      </td>
    </tr>
   {/if}
   <tr>
     <td class="right">
       <a href="{crmURL p="civicrm/case/search" q="reset=1&case_owner=1&force=1"}"><span>&raquo; {ts}Show My Cases{/ts}</span></a>
     </td>
   </tr>
  </table>
</div>

<h3>{ts}Summary of Involvement{/ts}</h3>

<table class="report">
  <tr class="columnheader">
    <th>&nbsp;</th>
    {foreach from=$casesSummary.headers item=header}
    <th scope="col" class="right" style="padding-right: 10px;"><a href="{$header.url}">{$header.status}</a></th>
    {/foreach}
  </tr>
  {foreach from=$casesSummary.rows item=row key=caseType}
   <tr>
   <th><strong>{$caseType}</strong></th>
   {foreach from=$casesSummary.headers item=header}
    {assign var="caseStatus" value=$header.status}
    <td class="label">
    {if $row.$caseStatus}
    <a href="{$row.$caseStatus.url}">{$row.$caseStatus.count}</a>
    {else}
     0
    {/if}
    </td>
   {/foreach}
  </tr>
  {/foreach}
</table>

</div>
