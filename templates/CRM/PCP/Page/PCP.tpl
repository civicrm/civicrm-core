{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
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
    <td id={$row.id}>{$row.action|smarty:nodefaults|replace:'xx':$row.id}</td>
  </tr>
  {/foreach}
  </tbody>
</table>
{/strip}
</div>
{else}
<div class="messages status no-popup">
{icon icon="fa-info-circle"}{/icon}
    {if $isSearch}
        {ts}There are no Personal Campaign Pages which match your search criteria.{/ts}
    {else}
        {ts}There are currently no Personal Campaign Pages.{/ts}
    {/if}
</div>
{/if}
