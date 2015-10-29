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

<table id="dedupeExceptions" class="display crm-sortable">
  <thead>
    <tr class="columnheader">
      <th>{ts}Contact 1{/ts}</th>
      <th>{ts}Contact 2 (Duplicate){/ts}</th>
      <th data-orderable="false"></th>
    </tr>
  </thead>
  <tbody>
    {foreach from=$dedupeExceptions item=exception key=id}
      <tr id="dupeRow_{$id}" class="{cycle values="odd-row,even-row"}">
        <td>{$exception.main.name}</td>
        <td>{$exception.other.name}</td>
        <td><a id='duplicateContacts' href="#" title={ts}Remove Exception{/ts} onClick="processDupes( {$exception.main.id}, {$exception.other.id}, 'nondupe-dupe', 'dedupe-exception' );return false;">&raquo; {ts}Remove Exception{/ts}</a></td>
      </tr>
    {/foreach}
  </tbody>
</table>
<div class="clear"><br /></div>
<div class="action-link">
  {crmButton p="civicrm/contact/deduperules" q="reset=1" icon="times"}{ts}Done{/ts}{/crmButton}
</div>

{* process the dupe contacts *}
{include file="CRM/common/dedupe.tpl"}
