{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{if $action neq 8}
  {include file="CRM/ACL/Header.tpl" step=2}
{/if}

{if $action eq 1 or $action eq 2 or $action eq 8}
   {include file="CRM/ACL/Form/EntityRole.tpl"}
{/if}

<div class="crm-block crm-content-block">
{if $rows}
<div id="ltype">
    {strip}
  {* handle enable/disable actions*}
   {include file="CRM/common/enableDisableApi.tpl"}
    {include file="CRM/common/jsortable.tpl"}
    <table id="options" class="display">
        <thead>
        <tr class="columnheader">
            <th id="sortable">{ts}ACL Role{/ts}</th>
            <th>{ts}Assigned to{/ts}</th>
            <th>{ts}Enabled?{/ts}</th>
            <th></th>
        </tr>
        </thead>
        <tbody>
        {foreach from=$rows item=row}
          <tr id="acl_role-{$row.id}" class="{cycle values="odd-row,even-row"} {$row.class} crm-acl_entity_role crm-entity {if NOT $row.is_active} disabled{/if}">
            <td class="crm-acl_entity_role-acl_role">{$row.acl_role}</td>
            <td class="crm-acl_entity_role-entity">{$row.entity}</td>
            <td class="crm-acl_entity_role-is_active" id="row_{$row.id}_status">{if $row.is_active eq 1} {ts}Yes{/ts} {else} {ts}No{/ts} {/if}</td>
            <td>{$row.action|smarty:nodefaults|replace:'xx':$row.id}</td>
          </tr>
        {/foreach}
        </tbody>
    </table>
    {/strip}

        {if $action ne 1 and $action ne 2}
      <div class="crm-submit-buttons">
            {crmButton q="action=add&reset=1" id="newACL"  icon="plus-circle"}{ts}Add Role Assignment{/ts}{/crmButton}
        </div>
        {/if}
</div>
{elseif $action ne 1 and $action ne 2 and $action ne 8}
    <div class="messages status no-popup">
         <img src="{$config->resourceBase}i/Inform.gif" alt="{ts}status{/ts}"/>
        {capture assign=crmURL}{crmURL q="action=add&reset=1"}{/capture}
        {ts 1=$crmURL}There are no Role Assignments. You can <a href='%1'>add one</a> now.{/ts}
    </div>
{/if}
</div>
