{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{* The name "custom data group" is replaced by "custom data set"  *}
    <div class="help">
    {ts}Custom data is stored in custom fields. Custom fields are organized into logically related custom data sets (e.g. Volunteer Info). Use custom fields to collect and store custom data which are not included in the standard CiviCRM forms. You can create one or many sets of custom fields.{/ts} {docURL page="user/organising-your-data/creating-custom-fields"}
    </div>

    {if $rows}
    <div class="crm-content-block crm-block">
    <div id="custom_group">
     {strip}
   {* handle enable/disable actions*}
   {include file="CRM/common/enableDisableApi.tpl"}
      <table id="options" class="row-highlight">
        <thead>
          <tr>
            <th>{ts}ID{/ts}</th>
            <th>{ts}Set{/ts}</th>
            <th>{ts}Enabled?{/ts}</th>
            <th>{ts}Used For{/ts}</th>
            <th>{ts}Type{/ts}</th>
            <th>{ts}Order{/ts}</th>
            <th>{ts}Style{/ts}</th>
            <th><span class="sr-only">{ts}Actions{/ts}</span></th>
          </tr>
        </thead>
        <tbody>
        {foreach from=$rows item=row}
        <tr id="CustomGroup-{$row.id}" data-action="setvalue" class="crm-entity {cycle values="odd-row,even-row"} {$row.class}{if NOT $row.is_active} disabled{/if}">
          <td>{$row.id}</td>
          <td class="crmf-title crm-editable">{$row.title}</td>
          <td id="row_{$row.id}_status">{if $row.is_active eq 1} {ts}Yes{/ts} {else} {ts}No{/ts} {/if}</td>
          <td>{if $row.extends eq 'Contact'}{ts}All Contact Types{/ts}{else}{$row.extends_display}{/if}</td>
          <td>{if !empty($row.extends_entity_column_value)}{$row.extends_entity_column_value}{/if}</td>
          <td class="nowrap">{$row.weight|smarty:nodefaults}</td>
          <td>{$row.style_display}</td>
          <td>{$row.action|smarty:nodefaults|replace:'xx':$row.id}</td>
        </tr>
        {/foreach}
        </tbody>
      </table>

        <div class="action-link">
        {crmButton p='civicrm/admin/custom/group/edit' q="action=add&reset=1" id="newCustomDataGroup"  icon="plus-circle"}{ts}Add Set of Custom Fields{/ts}{/crmButton}
        </div>

        {/strip}
    </div>
    </div>
    {else}
       <div class="messages status no-popup">
       <img src="{$config->resourceBase}i/Inform.gif" alt="{ts escape='htmlattribute'}status{/ts}"/> &nbsp;
         {capture assign=crmURL}{crmURL p='civicrm/admin/custom/group/edit' q='action=add&reset=1'}{/capture}
         {ts 1=$crmURL}No custom data groups have been created yet. You can <a id="newCustomDataGroup" href='%1'>add one</a>.{/ts}
       </div>
    {/if}
