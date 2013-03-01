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
{if $action eq 1 or $action eq 2 or $action eq 8}
    {include file="CRM/Admin/Form/Mapping.tpl"}
{else}
    <div id="help">
        {ts}Saved mappings allow you to easily run the same import or export job multiple times. Mappings are created and updated as part of an Import or Export task. This screen allows you to rename or delete existing mappings.{/ts}
    </div>
    {if $rows}
    <div id="mapping">
    <p></p>
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
            <tr class="{cycle values="odd-row,even-row"} {$row.class} crm-mapping">
                <td class="crm-mapping-name">{$row.name}</td>
                <td class="crm-mapping-description">{$row.description}</td>
                <td class="crm-mapping-mapping_type">{$row.mapping_type}</td>
                <td>{$row.action|replace:'xx':$row.id}</td>
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
{/if}