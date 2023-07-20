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
    {ts}Payment Processor configurations for all payment processors that can be used in this installation of CiviCRM.{/ts}
</div>

{if $action eq 1 or $action eq 2 or $action eq 8}
   {include file="CRM/Admin/Form/PaymentProcessorType.tpl"}
{else}

{if $rows}
<div id="ltype">
<p></p>
    <div class="form-item">
        {strip}
  {include file="CRM/common/enableDisableApi.tpl"}
        <table cellpadding="0" cellspacing="0" border="0">
        <tr class="columnheader">
            <th>{ts}Name{/ts}</th>
            <th>{ts}Title{/ts}</th>
            <th>{ts}Description{/ts}</th>
            <th>{ts}Enabled?{/ts}</th>
          <th>{ts}Default?{/ts}</th>
            <th></th>
        </tr>
        {foreach from=$rows item=row}
        <tr id="paymentProcessorType-{$row.id}" class="{cycle values="odd-row,even-row"} {$row.class} crm-entity {if NOT $row.is_active} disabled{/if}">
          <td class="crm-paymentProcessorType-name">{$row.name}</td>
          <td class="crm-paymentProcessorType-title crm-editable" data-field="title">{$row.title}</td>
            <td class="crm-paymentProcessorType-description">{$row.description}</td>
          <td id="row_{$row.id}_status" class="crm-paymentProcessorType-is_active">{if $row.is_active eq 1} {ts}Yes{/ts} {else} {ts}No{/ts} {/if}</td>
            <td class="crm-paymentProcessorType-is_default">{icon condition=$row.is_default}{ts}Default{/ts}{/icon}&nbsp;</td>
          <td>{$row.action}</td>
        </tr>
        {/foreach}
        </table>
        {/strip}

        {if $action ne 1 and $action ne 2}
        <div class="action-link">
          <a class="button" href="{crmURL q="action=add&reset=1"}" id="newPaymentProcessor"><i class="crm-i fa-plus-circle" aria-hidden="true"></i> {ts}New Payment Processor{/ts}</a>
          {crmButton p="civicrm/admin" q="reset=1" class="cancel" icon="times"}{ts}Done{/ts}{/crmButton}
        </div>
        {/if}
    </div>
</div>
{elseif $action ne 1}
    <div class="messages status no-popup">
      <img src="{$config->resourceBase}i/Inform.gif" alt="{ts}status{/ts}"/>
      {ts}None found.{/ts}
    </div>
{/if}
{/if}
