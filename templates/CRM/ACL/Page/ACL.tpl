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
  {include file="CRM/ACL/Form/ACL.tpl"}

{else}
  <div class="crm-block crm-content-block">
    {include file="CRM/ACL/Header.tpl" step=3}

    {if $rows}
      <div id="ltype">
        {strip}
        {* handle enable/disable actions*}
          {include file="CRM/common/enableDisableApi.tpl"}
          {include file="CRM/common/jsortable.tpl"}
          <table id="options" class="display">
            <thead>
            <tr class="columnheader">
              <th>{ts}Role{/ts}</th>
              <th>{ts}Operation{/ts}</th>
              <th>{ts}Type of Data{/ts}</th>
              <th>{ts}Which Data{/ts}</th>
              <th>{ts}Description{/ts}</th>
              <th>{ts}Enabled?{/ts}</th>
              <th>{ts}Mode{/ts}</th>
              <th id="sortable">{ts}Priority{/ts}</th>
              <th></th>
            </tr>
            </thead>
            <tbody>
            {foreach from=$rows item=row key=aclID}
              <tr id="acl-{$aclID}" class="{cycle values="odd-row,even-row"} {$row.class} crm-acl crm-entity {if NOT $row.is_active} disabled{/if}">
                <td class="crm-acl-entity">{$row.entity}</td>
                <td class="crm-acl-operation" >{$row.operation}</td>
                <td class="crm-acl-object_name">{$row.object_name}</td>
                <td class="crm-acl-object" >{$row.object}</td>
                <td class="crm-acl-name crm-editable" data-field="name">{$row.name}</td>
                <td class="crm-acl-is_active" id="row_{$aclID}_status">{if $row.is_active eq 1} {ts}Yes{/ts} {else} {ts}No{/ts} {/if}</td>
                <td class="crm-acl-deny" id="row_{$aclID}_deny">{if $row.deny}{ts}Deny{/ts}{else}{ts}Allow{/ts}{/if}</td>
                <td class="crm-acl-priority" id="row_{$aclID}_priority">{$row.priority}</td>
                <td>{$row.action|replace:'xx':$aclID}</td>
              </tr>
            {/foreach}
            </tbody>
          </table>
        {/strip}

        {if $action ne 1 and $action ne 2}
          <div class="action-link">
            {crmButton p="civicrm/acl/edit" q="action=add&reset=1" id="newACL" icon="plus-circle"}{ts}Add ACL{/ts}{/crmButton}
          </div>
        {/if}
      </div>
    {else}
      <div class="messages status no-popup">
        <img src="{$config->resourceBase}i/Inform.gif" alt="{ts}status{/ts}"/>
        {capture assign=crmURL}{crmURL q="action=add&reset=1"}{/capture}
        {ts 1=$crmURL}There are no ACLs entered. You can <a href='%1'>add one</a>.{/ts}
      </div>
    {/if}
  </div>
{/if}
