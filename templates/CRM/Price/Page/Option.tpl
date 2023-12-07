{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{if ($action eq 1 or $action eq 2 or $action eq 4 or $action eq 8  and !$usedBy) and !$isReserved}
  {include file="CRM/Price/Form/Option.tpl"}

{elseif $usedBy}
  <div class='spacer'></div>
  <div id="price_set_used_by" class="messages status no-popup">
    {icon icon="fa-info-circle"}{/icon}
    {if $action eq 8}
      {ts 1=$usedPriceSetTitle}Unable to delete the '%1' Price Field Option - it is currently in use by one or more active events  or contribution pages or contributions.{/ts}
    {/if}

    {if $usedBy.civicrm_event or $usedBy.civicrm_contribution_page}
      {include file="CRM/Price/Page/table.tpl"}
    {/if}

  </div>
{else}


  {if $customOption}

    <div id="field_page">
      <p></p>
      {strip}
        {* handle enable/disable actions*}
        {include file="CRM/common/enableDisableApi.tpl"}
        <table id="options" class="row-highlight">
          <thead>
          <tr>
            <th>{ts}Option Label{/ts}</th>
            <th>{ts}Option Amount{/ts}</th>
            <th>{ts}Non-deductible Amount{/ts}</th>
            <th>{ts}Pre Help{/ts}</th>
            <th>{ts}Post Help{/ts}</th>
            {if $isEvent}
              <th>{ts}Participant Count{/ts}</th>
              <th>{ts}Maximum{/ts}</th>
            {/if}
            <th>{ts}Default{/ts}</th>
            <th>{ts}Financial Type{/ts}</th>
            <th>{ts}Order{/ts}</th>
            {if $getTaxDetails}
              <th>{ts}Tax Label{/ts}</th>
              <th>{ts}Tax Amount{/ts}</th>
            {/if}
            <th>{ts}Enabled?{/ts}</th>
            <th></th>
          </tr>
          </thead>
          <tbody>
          {foreach from=$customOption item=row}
            <tr id="price_field_value-{$row.id}" class="crm-entity {cycle values="odd-row,even-row"}{if !empty($row.class)} {$row.class}{/if}{if NOT $row.is_active} disabled{/if}">
              <td class="crm-price-option-label crm-editable" data-field="label">{$row.label|escape}</td>
              <td class="crm-price-option-value">{$row.amount|crmMoney}</td>
              <td class="crm-price-option-non-deductible-amount">{$row.non_deductible_amount|crmMoney}</td>
              <td class="crm-price-option-pre-help">{$row.help_pre|escape}</td>
              <td class="crm-price-option-post-help">{$row.help_post|escape}</td>
              {if $isEvent}
                <td class="crm-price-option-count">{$row.count}</td>
                <td class="crm-price-option-max">{$row.max_value}</td>
              {/if}
              <td class="crm-price-option-is_default">{icon condition=$row.is_default}{ts}Default{/ts}{/icon}</td>
              <td class="nowrap crm-price-option-financial-type-id">{$row.financial_type_id}</td>
              <td class="nowrap crm-price-option-order">{$row.weight|smarty:nodefaults}</td>
              {if $getTaxDetails}
                <td>{if $row.tax_rate != ''}
                      {$taxTerm} ({$row.tax_rate}%)
                    {/if}
                </td>
                <td>{$row.tax_amount|crmMoney}</td>
              {/if}
              <td id="row_{$row.id}_status" class="crm-price-option-is_active">{if $row.is_active eq 1} {ts}Yes{/ts} {else} {ts}No{/ts} {/if}</td>
              <td>{$row.action|smarty:nodefaults|replace:'xx':$row.id}</td>
            </tr>
          {/foreach}
          </tbody>
        </table>
      {/strip}
    </div>

  {else}
    {if $action eq 16}
      <div class="messages status no-popup">
        <img src="{$config->resourceBase}i/Inform.gif" alt="{ts}status{/ts}"/>
        {ts}None found.{/ts}
      </div>
    {/if}
  {/if}
  {if $addMoreFields && !$isReserved}
    <div class="action-link">
      {crmButton p="civicrm/admin/price/field/option/edit" q="reset=1&action=add&fid=$fid&sid=$sid" icon="plus-circle"}{ts 1=$fieldTitle}New Option for '%1'{/ts}{/crmButton}
      {crmButton p="civicrm/admin/price/field/option/edit" q="reset=1&sid=$sid" class="cancel" icon="times"}{ts}Done{/ts}{/crmButton}
    </div>
  {/if}
{/if}
