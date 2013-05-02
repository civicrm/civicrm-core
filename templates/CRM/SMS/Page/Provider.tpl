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
{capture assign=wikiLink}{docURL page="Setting up a SMS Provider for CiviSMS" text="(How to add a SMS Provider)" resource="wiki"}{/capture}
<div id="help">
    {ts}You can configure one or more SMS Providers for your CiviCRM installation. To learn more about the procedure to install SMS extension and Provider, refer{/ts} {$wikiLink}
</div>
{if $action eq 1 or $action eq 2 or $action eq 8}
   {include file="CRM/SMS/Form/Provider.tpl"}
{else}

  {if $rows}

      {if $action ne 1 and $action ne 2}
        <div class="action-link">
          <a href="{crmURL q="action=add&reset=1"}" id="newProvider" class="button"><span><div class="icon add-icon"></div>{ts}Add New Provider{/ts}</span></a>
       </div>
      {/if}

<div id="ltype">
    {strip}
        {* handle enable/disable actions*}
       {include file="CRM/common/enableDisable.tpl"}
        <br/><table class="selector">
        <tr class="columnheader">
            <th >{ts}Provider Details{/ts}</th>
            <th >{ts}Username{/ts}</th>
      <th >{ts}API Parameters{/ts}</th>
            <th >{ts}Action{/ts}</th>
        </tr>
        {foreach from=$rows item=row}
        <tr id="row_{$row.id}" class="crm-provider {cycle values="odd-row,even-row"} {$row.class}{if NOT $row.is_active} disabled{/if}">
            <td class="crm-provider-name"><strong>{$row.name}</strong> ({$row.title})<br/>
                {ts}API Type:{/ts} {$row.api_type}<br/>
                {ts}API Url:{/ts} {$row.api_url}<br/>
            </td>
            <td class="crm-provider-username">{$row.username}
        </td>
            <td class="crm-api-params">{if $row.api_params eq null}<em>{ts}no parameters{/ts}</em>{else}<pre>{$row.api_params}</pre>{/if}</td>

          <td>{$row.action|replace:'xx':$row.id}</td>
        </tr>
        {/foreach}
        </table>
    {/strip}
</div>
{elseif $action ne 1}
    <div class="messages status no-popup">
      <div class="icon inform-icon"></div>
        {ts}There are no providers configured.{/ts}
     </div>
     <div class="action-link">
       <a href="{crmURL p='civicrm/admin/sms/provider' q="action=add&reset=1"}" class="button"><span><div class="icon add-icon"></div>{ts}Add SMS Provider{/ts}</span></a>
     </div>

{/if}
{/if}