{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{if $action eq 1 or $action eq 2 or $action eq 8}
   {include file="CRM/SMS/Form/Provider.tpl"}
{else}
  <div class="help">
    {ts}You can configure one or more SMS Providers for your CiviCRM installation.{/ts} {docURL page="user/sms-text-messaging/set-up"}
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
        <tr id="sms_provider-{$row.id}" class="crm-entity {cycle values="odd-row,even-row"}{if !empty($row.class)} {$row.class}{/if}{if NOT $row.is_active} disabled{/if}">
            <td class="crm-provider-name"><strong>{$row.name}</strong> ({$row.title})<br/>
                {ts}API Type:{/ts} {$row.api_type}<br/>
                {ts}API Url:{/ts} {$row.api_url}<br/>
            </td>
            <td class="crm-provider-username">{$row.username}
        </td>
            <td class="crm-api-params">{if $row.api_params eq null}<em>{ts}no parameters{/ts}</em>{else}<pre>{$row.api_params}</pre>{/if}</td>

          <td>{$row.action|smarty:nodefaults|replace:'xx':$row.id}</td>
        </tr>
        {/foreach}
        </table>
    {/strip}
  </div>
  {else}
    <div class="messages status no-popup">
      {icon icon="fa-info-circle"}{/icon}
        {ts}None found.{/ts}
     </div>

  {/if}
  <div class="action-link">
    {crmButton p='civicrm/admin/sms/provider' q="action=add&reset=1" icon="plus-circle"}{ts}Add SMS Provider{/ts}{/crmButton}
  </div>
</div>
{/if}
