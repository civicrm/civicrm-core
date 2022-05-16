{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
<div class="help">
  {ts 1=$gLabel}The existing option choices for %1 group are listed below. You can add, edit or delete them from this screen.{/ts}
</div>
{if $action ne 1 and $action ne 2}
  <div class="action-link">
    <a href="{$newReport}"  id="new_{$gName}" class="button"><span><i class="crm-i fa-plus-circle" aria-hidden="true"></i> {ts 1=$gLabel}Register New %1{/ts}</span></a>
  </div>
  <div class="spacer"></div>
{/if}
{if $rows}
  <div id="optionList">
    {strip}
      {* handle enable/disable actions*}
      {include file="CRM/common/enableDisableApi.tpl"}
      <table id="options" class="row-highlight">
        <thead>
        <tr>
          <th>{ts}Label{/ts}</th>
          <th>{ts}URL{/ts}</th>
          <th>{ts}Description{/ts}</th>
          <th>{ts}Order{/ts}</th>
          {if !empty($showIsDefault)}
            <th>{ts}Default{/ts}</th>
          {/if}
          <th>{ts}Reserved{/ts}</th>
          <th>{ts}Enabled?{/ts}</th>
          <th>{ts}Component{/ts}</th>
          <th></th>
          <th class="hiddenElement"></th>
        </tr>
        </thead>
        {foreach from=$rows item=row}
          <tr id="option_value-{$row.id}" class="crm-entity {cycle values="odd-row,even-row"}{if !empty($row.class)}{$row.class}{/if}{if NOT $row.is_active} crm-report-optionList crm-report-optionList-status_disable disabled{else} crm-report-optionList crm-report-optionList-status_enable{/if}">
            <td class="crm-report-optionList-label crm-editable" data-field="label">{$row.label}</td>
            <td class="crm-report-optionList-value">{$row.value}</td>
            <td class="crm-report-optionList-description">{$row.description}</td>
            <td class="nowrap crm-report-optionList-order">{$row.weight|smarty:nodefaults}</td>
            {if !empty($showIsDefault)}
              <td class="crm-report-optionList-default_value">{$row.default_value}</td>
            {/if}
            <td class="crm-report-optionList-is_reserved">{if $row.is_reserved eq 1}{ts}Yes{/ts} {else} {ts}No{/ts} {/if}</td>
            <td class="crm-report-optionList-is_active">{if $row.is_active eq 1} {ts}Yes{/ts} {else} {ts}No{/ts} {/if}</td>
            <td class="crm-report-optionList-component_name">{$row.component_name}</td>
            <td class="crm-report-optionList-action">{$row.action}</td>
          </tr>
        {/foreach}
      </table>
    {/strip}

    {if $action ne 1 and $action ne 2}
      <div class="action-link">
        <a href="{$newReport}"  id="new_{$gName}" class="button"><span><i class="crm-i fa-plus-circle" aria-hidden="true"></i> {ts 1=$gLabel}Register New %1{/ts}</span></a>
      </div>
    {/if}
  </div>
{else}
  <div class="messages status no-popup">
    <img src="{$config->resourceBase}i/Inform.gif" alt="{ts}status{/ts}"/>&nbsp; {ts 1=$newReport}There are no option values entered. You can <a href="%1">add one</a>.{/ts}
  </div>
{/if}
