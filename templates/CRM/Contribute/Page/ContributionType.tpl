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
   {include file="CRM/Contribute/Form/ContributionType.tpl"}
{else}
    <div class="help">
        <p>{ts}Financial types are used to categorize contributions for reporting and accounting purposes. These are also referred to as <strong>Funds</strong>. You may set up as many types as needed. Each type can carry an accounting code which can be used to map contributions to codes in your accounting system. Commonly used financial types are: Donation, Campaign Contribution, Membership Dues...{/ts}</p>
    </div>

{if $rows}
<div id="ltype">
<p></p>
    <div class="form-item">
        {strip}
  {* handle enable/disable actions*}
   {include file="CRM/common/enableDisableApi.tpl"}
        <table cellpadding="0" cellspacing="0" border="0">
           <thead class="sticky">
            <th>{ts}Name{/ts}</th>
            <th>{ts}Description{/ts}</th>
            <th>{ts}Acctg Code{/ts}</th>
            <th>{ts}Deductible?{/ts}</th>
            <th>{ts}Reserved?{/ts}</th>
            <th>{ts}Enabled?{/ts}</th>
            <th></th>
          </thead>
         {foreach from=$rows item=row}
        <tr id="contribution_type-{$row.id}" class="crm-entity {cycle values="odd-row,even-row"}{if !empty($row.class)} {$row.class}{/if}{if NOT $row.is_active} disabled{/if}">
          <td>{$row.name}</td>
          <td>{$row.description}</td>
              <td>{$row.accounting_code}</td>
          <td>{if $row.is_deductible eq 1} {ts}Yes{/ts} {else} {ts}No{/ts} {/if}</td>
          <td>{if $row.is_reserved eq 1} {ts}Yes{/ts} {else} {ts}No{/ts} {/if}</td>
          <td id="row_{$row.id}_status">{if $row.is_active eq 1} {ts}Yes{/ts} {else} {ts}No{/ts} {/if}</td>
          <td>{$row.action|smarty:nodefaults|replace:'xx':$row.id}</td>
        </tr>
        {/foreach}
         </table>
        {/strip}

    </div>
</div>
{else}
    <div class="messages status no-popup">
        {icon icon="fa-info-circle"}{/icon}
      {ts}None found.{/ts}
    </div>
{/if}
  {if $action ne 1 and $action ne 2}
    <div class="action-link">
      {crmButton q="action=add&reset=1" id="newContributionType"  icon="plus-circle"}{ts}Add Financial Type{/ts}{/crmButton}
      {crmButton p="civicrm/admin" q="reset=1" class="cancel" icon="times"}{ts}Done{/ts}{/crmButton}
    </div>
  {/if}
{/if}
