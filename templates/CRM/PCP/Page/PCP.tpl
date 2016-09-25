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
<div class="help">
{ts}This screen shows all the Personal Campaign Pages created in the system and allows administrator to review them and change their status.{/ts} {help id="id-pcp-intro"}
</div>
{if $action ne 8}
{include file="CRM/PCP/Form/PCP/PCP.tpl"}
{else}
{include file="CRM/PCP/Form/PCP/Delete.tpl"}
{/if}

{if $rows}
<div id="ltype">
<p></p>
{include file="CRM/common/pager.tpl" location="top"}
{include file="CRM/common/pagerAToZ.tpl"}
{include file="CRM/common/jsortable.tpl"}
{strip}
<table id="options" class="display">
  <thead>
    <tr>
    <th>{ts}Page Title{/ts}</th>
    <th>{ts}Supporter{/ts}</th>
    <th>{ts}Contribution Page / Event{/ts}</th>
    <th>{ts}Starts{/ts}</th>
    <th>{ts}Ends{/ts}</th>
    <th>{ts}Status{/ts}</th>
    <th></th>
    </tr>
  </thead>
  <tbody>
  {foreach from=$rows item=row}
  <tr id="row_{$row.id}" class="{$row.class}">
    <td><a href="{crmURL p='civicrm/pcp/info' q="reset=1&id=`$row.id`" fe='true'}" title="{ts}View Personal Campaign Page{/ts}" target="_blank">{$row.title}</a></td>
    <td><a href="{crmURL p='civicrm/contact/view' q="reset=1&cid=`$row.supporter_id`"}" title="{ts}View contact record{/ts}">{$row.supporter}</a></td>
    <td><a href="{$row.page_url}" title="{ts}View page{/ts}" target="_blank">{$row.page_title}</td>
    <td>{$row.start_date|crmDate}</td>
    <td>{if $row.end_date}{$row.end_date|crmDate}{else}({ts}ongoing{/ts}){/if}</td>
    <td>{$row.status_id}</td>
    <td id={$row.id}>{$row.action|replace:'xx':$row.id}</td>
  </tr>
  {/foreach}
  </tbody>
</table>
{/strip}
</div>
{else}
<div class="messages status no-popup">
<div class="icon inform-icon"></div>
    {if $isSearch}
        {ts}There are no Personal Campaign Pages which match your search criteria.{/ts}
    {else}
        {ts}There are currently no Personal Campaign Pages.{/ts}
    {/if}
</div>
{/if}
