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
              <th id="sortable">{ts}Role{/ts}</th>
              <th>{ts}Operation{/ts}</th>
              <th>{ts}Type of Data{/ts}</th>
              <th>{ts}Which Data{/ts}</th>
              <th>{ts}Description{/ts}</th>
              <th>{ts}Enabled?{/ts}</th>
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
                <td>{$row.action|replace:'xx':$aclID}</td>
              </tr>
            {/foreach}
            </tbody>
          </table>
        {/strip}

        {if $action ne 1 and $action ne 2}
          <div class="action-link">
            {crmButton q="action=add&reset=1" id="newACL" icon="plus-circle"}{ts}Add ACL{/ts}{/crmButton}
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
