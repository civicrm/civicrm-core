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
    {include file="CRM/Admin/Form/Mapping.tpl"}
{else}

    <div class="help">
        {ts}Saved mappings allow you to easily run the same import or export job multiple times. Mappings are created and updated as part of an Import or Export task. This screen allows you to rename or delete existing mappings.{/ts}
    </div>
    <div class="crm-content-block crm-block">
    {if $rows}
    <div id="mapping">
        <div class="form-item">
            {strip}
            <table cellpadding="0" cellspacing="0" border="0">
            <tr class="columnheader">
              <th>{ts}Name{/ts}</th>
              <th>{ts}Description{/ts}</th>
                    <th>{ts}Mapping Type{/ts}</th>
              <th></th>
            </tr>
            {foreach from=$rows item=row}
            <tr class="{cycle values="odd-row,even-row"}{if !empty($row.class)} {$row.class}{/if} crm-mapping">
                <td class="crm-mapping-name">{$row.name}</td>
                <td class="crm-mapping-description">{$row.description}</td>
                <td class="crm-mapping-mapping_type">{$row.mapping_type}</td>
                <td>{$row.action|smarty:nodefaults|replace:'xx':$row.id}</td>
            </tr>
            {/foreach}
            </table>
            {/strip}
        </div>
    </div>
    {else}
        <div class="messages status no-popup">
            <img src="{$config->resourceBase}i/Inform.gif" alt="{ts}status{/ts}"/>
            {ts}There are currently no saved import or export mappings. You create saved mappings as part of an Import or Export task.{/ts}
        </div>
    {/if}
    </div>
{/if}
