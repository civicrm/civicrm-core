{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.3                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2013                                |
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
{if $overlayProfile }
<table class="crm-table-group-summary">
<tr><td>{$displayName}</td></tr>
<tr><td>
{assign var="count" value="0"}
{assign var="totalRows" value=$row|@count}
<div class="crm-summary-col-0">
{foreach from=$profileFields item=field key=rowName}
  {if $count gt $totalRows/2}
    </div>
    </td><td>
    <div class="crm-summary-col-1">
    {assign var="count" value="1"}
  {/if}
  <div class="crm-section {$rowName}-section">
    <div class="label">
        {$field.label}
    </div>
     <div class="content">
        {$field.value}
     </div>
     <div class="clear"></div>
  </div>
  {assign var="count" value=`$count+1`}
{/foreach}
</div>
</td></tr>
</table>
{* fields array is not empty *}
{/if}