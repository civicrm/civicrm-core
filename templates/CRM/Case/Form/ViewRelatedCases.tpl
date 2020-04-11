{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{* Ajax-loaded list of related cases *}
<table class="report">
  <tr class="columnheader">
    <th>{ts}Client Name{/ts}</th>
    <th>{ts}Case Type{/ts}</th>
    <th></th>
  </tr>

  {foreach from=$relatedCases item=row key=caseId}
    <tr>
      <td class="crm-case-caseview-client_name label">{$row.client_name}</td>
      <td class="crm-case-caseview-case_type label">{$row.case_type}</td>
      <td class="label">{$row.links}</td>
    </tr>
  {/foreach}
</table>
