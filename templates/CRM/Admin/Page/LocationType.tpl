{*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2019                                |
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
   {include file="CRM/Admin/Form/LocationType.tpl"}
{else}
  <div class="help">
    {ts}Location types provide convenient labels to differentiate contacts' location(s). Administrators may define as many additional types as appropriate for your constituents (examples might be Main Office, School, Vacation Home...).{/ts}
  </div>

<div class="crm-block crm-content-block">
  {if $rows}
  <div id="ltype">
    {strip}
  {* handle enable/disable actions*}
   {include file="CRM/common/enableDisableApi.tpl"}
    {include file="CRM/common/jsortable.tpl"}
    <table id="options" class="display">
    <thead>
      <tr>
          <th id="sortable">{ts}Name{/ts}</th>
          <th>{ts}Display Name{/ts}</th>
          <th>{ts}vCard{/ts}</th>
          <th id="nosort">{ts}Description{/ts}</th>
          <th>{ts}Enabled?{/ts}</th>
          <th>{ts}Default?{/ts}</th>
          <th></th>
      </tr>
    </thead>
    {foreach from=$rows item=row}
    <tr id="location_type-{$row.id}"  data-action="setvalue" class="{cycle values="odd-row,even-row"} {$row.class} crm-entity {if NOT $row.is_active} disabled{/if}">
        <td class="crmf-name">{$row.name}</td>
        <td class="crmf-display_name crm-editable">{$row.display_name}</td>
        <td class="crmf-vcard_name crm-editable">{$row.vcard_name}</td>
        <td class="crmf-description crm-editable">{$row.description}</td>
        <td id="row_{$row.id}_status" class="crmf-is_active">{if $row.is_active eq 1} {ts}Yes{/ts} {else} {ts}No{/ts} {/if}</td>
        <td class="crmf-is_default" >{if $row.is_default eq 1}<img src="{$config->resourceBase}i/check.gif" alt="{ts}Default{/ts}" />{/if}&nbsp;</td>
        <td>{$row.action|replace:'xx':$row.id}</td>
    </tr>
    {/foreach}
    </table>
    {/strip}
  </div>
  {else}
    <div class="messages status no-popup">
        <img src="{$config->resourceBase}i/Inform.gif" alt="{ts}status{/ts}"/>
        {ts}None found.{/ts}
    </div>
  {/if}
  <div class="action-link">
    {crmButton q="action=add&reset=1" id="newLocationType" icon="plus-circle"}{ts}Add Option{/ts}{/crmButton}
    {crmButton p="civicrm/admin" q="reset=1" class="cancel" icon="times"}{ts}Done{/ts}{/crmButton}
  </div>
</div>
{/if}
