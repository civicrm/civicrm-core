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
{* The name "custom data group" is replaced by "custom data set"  *}
{if $action eq 1 or $action eq 2 or $action eq 4}
    {include file="CRM/Custom/Form/Group.tpl"}
{elseif $action eq 1024}
    {include file="CRM/Custom/Form/Preview.tpl"}
{elseif $action eq 8}
    {include file="CRM/Custom/Form/DeleteGroup.tpl"}
{else}
    <div id="help">
    {ts}Custom data is stored in custom fields. Custom fields are organized into logically related custom data sets (e.g. Volunteer Info). Use custom fields to collect and store custom data which are not included in the standard CiviCRM forms. You can create one or many sets of custom fields.{/ts} {docURL page="user/organising-your-data/custom-fields"}
    </div>

    {if $rows}
    <div class="crm-content-block crm-block">
    <div id="custom_group">
     {strip}
   {* handle enable/disable actions*}
   {include file="CRM/common/enableDisable.tpl"}
   {include file="CRM/common/jsortable.tpl"}
      <table id="options" class="display">
        <thead>
          <tr>
            <th>{ts}Set{/ts}</th>
            <th>{ts}Enabled?{/ts}</th>
            <th>{ts}Used For{/ts}</th>
            <th>{ts}Type{/ts}</th>
            <th id="order" class="sortable">{ts}Order{/ts}</th>
            <th>{ts}Style{/ts}</th>
            <th></th>
            <th class='hiddenElement'></th>
          </tr>
        </thead>
        <tbody>
        {foreach from=$rows item=row}
        <tr id="CustomGroup-{$row.id}" class="crm-entity {cycle values="odd-row,even-row"} {$row.class}{if NOT $row.is_active} disabled{/if}">
          <td><span class="crmf-title crm-editable">{$row.title}</span></td>
          <td id="row_{$row.id}_status">{if $row.is_active eq 1} {ts}Yes{/ts} {else} {ts}No{/ts} {/if}</td>
          <td>{if $row.extends eq 'Contact'}{ts}All Contact Types{/ts}{else}{$row.extends_display}{/if}</td>
          <td>{$row.extends_entity_column_value}</td>
          <td class="nowrap">{$row.order}</td>
          <td>{$row.style_display}</td>
          <td>{$row.action|replace:'xx':$row.id}</td>
          <td class="order hiddenElement">{$row.weight}</td>
        </tr>
        {/foreach}
        </tbody>
      </table>

        {if NOT ($action eq 1 or $action eq 2) }
        <div class="action-link">
        <a href="{crmURL p='civicrm/admin/custom/group' q="action=add&reset=1"}" id="newCustomDataGroup" class="button"><span><div class="icon add-icon"></div>{ts}Add Set of Custom Fields{/ts}</span></a>
        </div>
        {/if}

        {/strip}
    </div>
    </div>
    {else}
       {if $action ne 1} {* When we are adding an item, we should not display this message *}
       <div class="messages status no-popup">
       <img src="{$config->resourceBase}i/Inform.gif" alt="{ts}status{/ts}"/> &nbsp;
         {capture assign=crmURL}{crmURL p='civicrm/admin/custom/group' q='action=add&reset=1'}{/capture}
         {ts 1=$crmURL}No custom data groups have been created yet. You can <a id="newCustomDataGroup" href='%1'>add one</a>.{/ts}
       </div>
       {/if}
    {/if}
{/if}
{include file="CRM/common/crmeditable.tpl"}

