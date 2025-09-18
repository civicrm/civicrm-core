{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}

{if $action eq 1 or $action eq 2 or $action eq 4 or $action eq 8}
    {include file="CRM/UF/Form/Field.tpl"}
{elseif $action eq 1024}
    {include file="CRM/UF/Form/Preview.tpl"}
{else}
<div class="crm-content-block">
  {if $ufField}
    {if not ($action eq 2 or $action eq 1)}
      <div class="action-link">
        {crmButton p="civicrm/admin/uf/group/field/add" q="reset=1&action=add&gid=$gid" icon="plus-circle"}{ts}Add Field{/ts}{/crmButton}
        {if !$isGroupReserved}{crmButton p="civicrm/admin/uf/group" q="action=update&id=`$gid`&reset=1&context=field" icon="wrench"}{ts}Edit Settings{/ts}{/crmButton}{/if}
        {crmButton p="civicrm/admin/uf/group" q="action=preview&id=`$gid`&reset=1&field=0&context=field" icon="television"}{ts}Preview{/ts}{/crmButton}
        {if !$skipCreate}{crmButton p="civicrm/profile/create" q="gid=$gid&reset=1" icon="play-circle"}{ts}Use (create mode){/ts}{/crmButton}{/if}
        <div class="clear"></div>
      </div>
    {/if}
    <div id="field_page">
      {if $uf_group_type_extra}
        <p>
          {capture assign='helpTitle'}{ts}Used in Forms{/ts}{/capture}
          {$helpTitle} {help id='used-for-extra' title=$helpTitle file="CRM/UF/Form/Group.hlp"}<br>
          {$uf_group_type_extra}
        </p>
      {/if}
      {strip}
      {* handle enable/disable actions*}
      {include file="CRM/common/enableDisableApi.tpl"}
        <table id="options" class="row-highlight">
            <thead>
            <tr>
                <th>{ts}Field Name{/ts}</th>
                {if $legacyprofiles and (in_array("Profile",$otherModules) or in_array("Search Profile",$otherModules))}
                <th>{ts}Visibility{/ts}</th>
                <th>{ts}Searchable{/ts}</th>
                <th>{ts}Results Column{/ts}</th>
                {/if}
                <th>{ts}Order{/ts}</th>
                <th>{ts}Required{/ts}</th>
                <th>{ts}View Only{/ts}</th>
                <th>{ts}Reserved{/ts}</th>
                <th></th>
            </tr>
            </thead>
            {foreach from=$ufField item=row}
            <tr id="UFField-{$row.id}" data-action="setvalue" class="crm-entity {cycle values="odd-row,even-row"}{if !empty($row.class)} {$row.class}{/if}{if NOT $row.is_active} disabled{/if}">
                <td><span class="crmf-label crm-editable">{$row.label}</span>({$row.field_type})</td>
                {if $legacyprofiles and (in_array("Profile",$otherModules) or in_array("Search Profile",$otherModules))}
                <td class="crm-editable crmf-visibility" data-type="select">{$row.visibility_display}</td>
                <td class="crm-editable crmf-is_searchable" data-type="boolean">{if $row.is_searchable eq 1} {ts}Yes{/ts} {else} {ts}No{/ts} {/if}</td>
                <td class="crm-editable crmf-in_selector" data-type="boolean">{if $row.in_selector eq 1} {ts}Yes{/ts} {else} {ts}No{/ts} {/if}</td>
                {/if}
                <td class="nowrap">{$row.weight nofilter}</td>
                <td class="crm-editable crmf-is_required" data-type="boolean">{if $row.is_required eq 1} {ts}Yes{/ts} {else} {ts}No{/ts} {/if}</td>
                <td class="crm-editable crmf-is_view" data-type="boolean">{if $row.is_view eq 1} {ts}Yes{/ts} {else} {ts}No{/ts} {/if}</td>
                <td>{if $row.is_reserved     eq 1} {ts}Yes{/ts} {else} {ts}No{/ts} {/if}</td>
                <td>{$row.action|smarty:nodefaults|replace:'xx':$row.id}</td>
            </tr>
            {/foreach}
        </table>
      {/strip}
      </div>
    {else}
      {if $action eq 16}
      {capture assign=crmURL}{crmURL p="civicrm/admin/uf/group/field/add" q="reset=1&action=add&gid=$gid"}{/capture}
      <div class="messages status no-popup crm-empty-table">
        {icon icon="fa-info-circle"}{/icon}
        {ts}None found.{/ts}
      </div>
      <div class="action-link">
        {crmButton p="civicrm/admin/uf/group/field/add" q="reset=1&action=add&gid=$gid" icon="plus-circle"}{ts}Add Field{/ts}{/crmButton}
      </div>
    {/if}
  {/if}
</div>
{/if}
