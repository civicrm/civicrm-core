<div class="crm-content-block crm-block">
{if ($action eq 1) or ($action eq 2) or ($action eq 8)}
  {include file="CRM/Campaign/Form/SurveyType.tpl"}
{else}
{if $rows}
<div class="action-link">
  {crmButton p=$addSurveyType.0 q=$addSurveyType.1 icon="plus-circle"}{ts 1=$GName}Add %1{/ts}{/crmButton}
</div>

<div id={$gName}>
        {strip}
  {* handle enable/disable actions*}
  {include file="CRM/common/enableDisableApi.tpl"}
        <table class="row-highlight">
         <thead>
         <tr>
                 <th>
                   {ts}Label{/ts}
                 </th>
                 <th>
                   {ts}Value{/ts}
                 </th>
                 <th>{ts}Description{/ts}</th>
                 <th>{ts}Order{/ts}</th>
                 <th>{ts}Reserved{/ts}</th>
                 <th>{ts}Enabled?{/ts}</th>
                 <th></th>
                 </tr>
        </thead>
        {foreach from=$rows item=row}
        <tr id="option_value-{$row.id}" class="crm-entity crm-admin-options_{$row.id} {if NOT $row.is_active} disabled{/if}">
          <td class="crm-admin-options-label crm-editable" data-field="label">{$row.label}</td>
          <td class="crm-admin-options-value">{$row.value}</td>
          <td class="crm-admin-options-description">{if !empty($row.description)}{$row.description}{/if}</td>
          <td class="nowrap crm-admin-options-order">{$row.weight|smarty:nodefaults}</td>
          <td class="crm-admin-options-is_reserved">{if $row.is_reserved eq 1} {ts}Yes{/ts} {else} {ts}No{/ts} {/if}</td>
          <td class="crm-admin-options-is_active" id="row_{$row.id}_status">{if $row.is_active eq 1} {ts}Yes{/ts} {else} {ts}No{/ts} {/if}</td>
          <td>{$row.action|smarty:nodefaults|replace:'xx':$row.id}</td>
        </tr>
        {/foreach}
        </table>
        {/strip}
        <div class="action-link">
          {crmButton p=$addSurveyType.0 q=$addSurveyType.1 icon="plus-circle"}{ts 1=$GName}Add %1{/ts}{/crmButton}
        </div>

</div>
{else}
    <div class="messages status no-popup">
      {icon icon="fa-info-circle"}{/icon}
      {ts p=$addSurveyType.0 q=$addSurveyType.1}There are no survey types entered. You can <a href='%1'>add one</a>.{/ts}
    </div>
{/if}
{/if}
</div>
