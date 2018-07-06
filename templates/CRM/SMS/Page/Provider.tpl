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
{if $action eq 1 or $action eq 2 or $action eq 8}
   {include file="CRM/SMS/Form/Provider.tpl"}
{else}
  {capture assign=wikiLink}{docURL page="Setting up a SMS Provider for CiviSMS" text="(How to add a SMS Provider)" resource="wiki"}{/capture}
  <div class="help">
    {ts}You can configure one or more SMS Providers for your CiviCRM installation. To learn more about the procedure to install SMS extension and Provider, refer{/ts} {$wikiLink}
  </div>

<div class="crm-content-block crm-block">
  {if $rows}

  <div id="ltype">
    {strip}
        {* handle enable/disable actions*}
       {include file="CRM/common/enableDisableApi.tpl"}
        <table class="selector row-highlight">
        <tr class="columnheader">
            <th >{ts}Provider Details{/ts}</th>
            <th >{ts}Username{/ts}</th>
      <th >{ts}API Parameters{/ts}</th>
            <th >{ts}Action{/ts}</th>
        </tr>
        {foreach from=$rows item=row}
        <tr id="sms_provider-{$row.id}" class="crm-entity {cycle values="odd-row,even-row"} {$row.class}{if NOT $row.is_active} disabled{/if}">
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
  {else}
    <div class="messages status no-popup">
      <div class="icon inform-icon"></div>
        {ts}None found.{/ts}
     </div>

  {/if}
  <div class="action-link">
    {crmButton p='civicrm/admin/sms/provider' q="action=add&reset=1" icon="plus-circle"}{ts}Add SMS Provider{/ts}{/crmButton}
  </div>
</div>
{/if}
