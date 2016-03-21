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
{if $showBestResult }
    <span class="font-red">{ts}For best results, the Country field should precede the State-Province field in your Profile form. You can use the up and down arrows on field listing page for this profile to change the order of these fields or manually edit weight for Country/State-Province Field.{/ts}</span>
{/if}

{if $action eq 1 or $action eq 2 or $action eq 4 or $action eq 8 }
    {include file="CRM/UF/Form/Field.tpl"}
{elseif $action eq 1024 }
    {include file="CRM/UF/Form/Preview.tpl"}
{else}
<div class="crm-content-block">
    {if $ufField}
        <div id="field_page">
        {if not ($action eq 2 or $action eq 1)}
            <div class="action-link">
                {crmButton p="civicrm/admin/uf/group/field/add" q="reset=1&action=add&gid=$gid" icon="plus-circle"}{ts}Add Field{/ts}{/crmButton}{if !$isGroupReserved}{crmButton p="civicrm/admin/uf/group/update" q="action=update&id=`$gid`&reset=1&context=field" icon="wrench"}{ts}Edit Settings{/ts}{/crmButton}{/if}{crmButton p="civicrm/admin/uf/group" q="action=preview&id=`$gid`&reset=1&field=0&context=field" icon="television"}{ts}Preview (all fields){/ts}{/crmButton}{if !$skipCreate }{crmButton p="civicrm/profile/create" q="gid=$gid&reset=1" icon="play-circle"}{ts}Use (create mode){/ts}{/crmButton}{/if}
                <div class="clear"></div>
            </div>
        {/if}
        {strip}
        {* handle enable/disable actions*}
   {include file="CRM/common/enableDisableApi.tpl"}
        <table id="options" class="row-highlight">
            <thead>
            <tr>
                <th>{ts}Field Name{/ts}</th>
                {if in_array("Profile",$otherModules) or in_array("Search Profile",$otherModules) }
                <th>{ts}Visibility{/ts}</th>
                <th>{ts}Searchable?{/ts}</th>
                <th>{ts}In Selector?{/ts}</th>
                {/if}
                <th>{ts}Order{/ts}</th>
                <th>{ts}Required{/ts}</th>
                <th>{ts}View Only{/ts}</th>
                <th>{ts}Reserved{/ts}</th>
                <th></th>
            </tr>
            </thead>
            {foreach from=$ufField item=row}
            <tr id="UFField-{$row.id}" data-action="setvalue" class="crm-entity {cycle values="odd-row,even-row"} {$row.class}{if NOT $row.is_active} disabled{/if}">
                <td><span class="crmf-label crm-editable">{$row.label}</span>({$row.field_type})</td>
                {if in_array("Profile",$otherModules) or in_array("Search Profile",$otherModules) }
                <td class="crm-editable crmf-visibility" data-type="select">{$row.visibility_display}</td>
                <td class="crm-editable crmf-is_searchable" data-type="boolean">{if $row.is_searchable eq 1} {ts}Yes{/ts} {else} {ts}No{/ts} {/if}</td>
                <td class="crm-editable crmf-in_selector" data-type="boolean">{if $row.in_selector eq 1} {ts}Yes{/ts} {else} {ts}No{/ts} {/if}</td>
                {/if}
                <td class="nowrap">{$row.weight}</td>
                <td class="crm-editable crmf-is_required" data-type="boolean">{if $row.is_required eq 1} {ts}Yes{/ts} {else} {ts}No{/ts} {/if}</td>
                <td class="crm-editable crmf-is_view" data-type="boolean">{if $row.is_view eq 1} {ts}Yes{/ts} {else} {ts}No{/ts} {/if}</td>
                <td>{if $row.is_reserved     eq 1} {ts}Yes{/ts} {else} {ts}No{/ts} {/if}</td>
                <td>{$row.action|replace:'xx':$row.id}</td>
            </tr>
            {/foreach}
        </table>
        {/strip}
        {if not ($action eq 2 or $action eq 1)}
            <div class="action-link">
                {crmButton p="civicrm/admin/uf/group/field/add" q="reset=1&action=add&gid=$gid" icon="plus-circle"}{ts}Add Field{/ts}{/crmButton}{if !$isGroupReserved}{crmButton p="civicrm/admin/uf/group" q="action=update&id=`$gid`&reset=1&context=field" icon="wrench"}{ts}Edit Settings{/ts}{/crmButton}{/if}{crmButton p="civicrm/admin/uf/group" q="action=preview&id=`$gid`&reset=1&field=0&context=field" icon="television"}{ts}Preview (all fields){/ts}{/crmButton}{if !$skipCreate }{crmButton p="civicrm/profile/create" q="gid=$gid&reset=1" icon="play-circle"}{ts}Use (create mode){/ts}{/crmButton}{/if}
                <div class="clear"></div>
            </div>
        {/if}

        </div>
    {else}
        {if $action eq 16}
        {capture assign=crmURL}{crmURL p="civicrm/admin/uf/group/field/add" q="reset=1&action=add&gid=$gid"}{/capture}
        <div class="messages status no-popup crm-empty-table">
          <div class="icon inform-icon"></div>
          {ts}None found.{/ts}
        </div>
        <div class="action-link">
          {crmButton p="civicrm/admin/uf/group/field/add" q="reset=1&action=add&gid=$gid" icon="plus-circle"}{ts}Add Field{/ts}{/crmButton}
        </div>
        {/if}
    {/if}
</div>
{/if}
