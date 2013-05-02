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
{if $action eq 1 or $action eq 2 or $action eq 4 or $action eq 8}
    {include file="CRM/Custom/Form/Option.tpl"}
{/if}

{if $customOption}
    {if $reusedNames}
        <div class="message status">
            <div class="icon inform-icon"></div> &nbsp; {ts 1=$reusedNames}These Multiple Choice Options are shared by the following custom fields: %1{/ts}
        </div>
    {/if}

    <div id="field_page">
      <p></p>
      <div class="form-item">
        {strip}
        {* handle enable/disable actions*}
         {include file="CRM/common/enableDisable.tpl"}
        <table class="selector">
          <tr class="columnheader">
            <th>{ts}Label{/ts}</th>
            <th>{ts}Value{/ts}</th>
            <th>{ts}Default{/ts}</th>
            <th>{ts}Order{/ts}</th>
            <th>{ts}Enabled?{/ts}</th>
            <th>&nbsp;</th>
          </tr>
          {foreach from=$customOption item=row key=id}
            <tr id="OptionValue-{$id}"class="crm-entity {cycle values="odd-row,even-row"} {$row.class} crm-custom_option {if NOT $row.is_active} disabled{/if}">
              <td><span class="crm-custom_option-label crm-editable crmf-label">{$row.label}</span></td>
              <td><span class="crm-custom_option-value disabled-crm-editable" data-field="value" data-action="update">{$row.value}</span></td>
              <td class="crm-custom_option-default_value crmf-value">{$row.default_value}</td>
              <td class="nowrap crm-custom_option-weight crmf-weight">{$row.weight}</td>
              <td id="row_{$id}_status" class="crm-custom_option-is_active crmf-is_active">{if $row.is_active eq 1} {ts}Yes{/ts} {else} {ts}No{/ts} {/if}</td>
              <td>{$row.action|replace:'xx':$id}</td>
            </tr>
          {/foreach}
          </table>
        {/strip}

        <div class="action-link">
            <a href="{crmURL q="reset=1&action=add&fid=$fid&gid=$gid"}" class="button"><span><div class="icon add-icon"></div> {ts 1=$fieldTitle}Add Option for '%1'{/ts}</span></a>
        </div>
      </div>
    </div>

{else}
    {if $action eq 16}
        <div class="messages status no-popup">
           <img src="{$config->resourceBase}i/Inform.gif" alt="{ts}status{/ts}"/>
           {capture assign=crmURL}{crmURL p='civicrm/admin/custom/group/field/option' q="action=add&fid=$fid&gid=$gid"}{/capture}{ts 1=$fieldTitle 2=$crmURL}There are no multiple choice options for the custom field '%1', <a href='%2'>add one</a>.{/ts}
        </div>
    {/if}
{/if}

{include file="CRM/common/crmeditable.tpl"}

