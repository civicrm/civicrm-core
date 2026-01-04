{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{* CiviMember DashBoard (launch page) *}
{if $membershipSummary}
  <h3>{ts}Membership Summary{/ts} {help id="id-member-intro"}</h3>
  <table class="report">
    <tr class="columnheader-dark">
      <th scope="col" rowspan="2">{ts}Members by Type{/ts}</th>
        {if $preMonth}
      <th scope="col" colspan="3">{$premonth} {ts}(Last Month){/ts}</th>
        {/if}
        <th scope="col" colspan="3">{$month}{if $isCurrent}{ts} (MTD){/ts}{/if}</th>
        <th scope="col" colspan="3">
            {if $year eq $currentYear}
    {$year} {ts}(YTD){/ts}
            {else}
    {$year} {ts 1=$month}through %1{/ts}
            {/if}
        </th>
      <th scope="col" rowspan="2">
        {if $isCurrent}
          {ts}Current #{/ts}
        {else}
          {ts 1=$month 2=$year}Members as of %1 %2{/ts}
        {/if}
      </th>
    </tr>

    <tr class="columnheader-dark">
        {if $preMonth}
            <th scope="col">{ts}New{/ts}</th><th scope="col">{ts}Renew{/ts}</th><th scope="col">{ts}Total{/ts}</th>
        {/if}
        <th scope="col">{ts}New{/ts}</th><th scope="col">{ts}Renew{/ts}</th><th scope="col">{ts}Total{/ts}</th>
        <th scope="col">{ts}New{/ts}</th><th scope="col">{ts}Renew{/ts}</th><th scope="col">{ts}Total{/ts}</th>
    </tr>

    {foreach from=$membershipSummary item=row}
        <tr>
            <td><strong>{$row.month.total.name}</strong></td>
          {if $preMonth}
            <td class="label crm-grid-cell">
              {if $row.premonth.new.url}<a href="{$row.premonth.new.url}" title="{ts escape='htmlattribute'}View details{/ts}">{$row.premonth.new.count}</a>
              {else}{$row.premonth.new.count}{/if}
            </td>
            <td class="label crm-grid-cell">
              {if $row.premonth.renew.url}<a href="{$row.premonth.renew.url}" title="{ts escape='htmlattribute'}View details{/ts}">{$row.premonth.renew.count}</a>
              {else}{$row.premonth.renew.count}{/if}
            </td>
            <td class="label crm-grid-cell">
              {if $row.premonth.total.url}
                <a href="{$row.premonth.total.url}" title="{ts escape='htmlattribute'}View details{/ts}">{$row.premonth.total.count}</a>
              {else}
                {$row.premonth.total.count}
              {/if}&nbsp;[
              {if $row.premonth_owner.premonth_owner.url}
                <a href="{$row.premonth_owner.premonth_owner.url}" title="{ts escape='htmlattribute'}View details{/ts}">{$row.premonth_owner.premonth_owner.count}</a>
              {else}
                {$row.premonth_owner.premonth_owner.count}
              {/if}]
            </td>
          {/if}

            <td class="label crm-grid-cell">
              {if $row.month.new.url}<a href="{$row.month.new.url}" title="{ts escape='htmlattribute'}View details{/ts}">{$row.month.new.count}</a>
              {else}{$row.month.new.count}{/if}
            </td>
            <td class="label crm-grid-cell">
              {if $row.month.renew.url}<a href="{$row.month.renew.url}" title="{ts escape='htmlattribute'}View details{/ts}">{$row.month.renew.count}</a>
              {else}{$row.month.renew.count}{/if}
            </td>
            <td class="label crm-grid-cell">
              {if $row.month.total.url}
                <a href="{$row.month.total.url}" title="{ts escape='htmlattribute'}View details{/ts}">{$row.month.total.count}</a>
              {else}
                {$row.month.total.count}
              {/if}&nbsp;[
              {if $row.month_owner.month_owner.url}
                <a href="{$row.month_owner.month_owner.url}" title="{ts escape='htmlattribute'}View details{/ts}">{$row.month_owner.month_owner.count}</a>
              {else}
                {$row.month_owner.month_owner.count}
              {/if}]
            </td>

            <td class="label crm-grid-cell">
              {if $row.year.new.url}<a href="{$row.year.new.url}" title="{ts escape='htmlattribute'}View details{/ts}">{$row.year.new.count}</a>
              {else}{$row.year.new.count}{/if}
            </td>
            <td class="label crm-grid-cell">
              {if $row.year.renew.url}<a href="{$row.year.renew.url}" title="{ts escape='htmlattribute'}View details{/ts}">{$row.year.renew.count}</a>
              {else}{$row.year.renew.count}{/if}
            </td>
            <td class="label crm-grid-cell">
              {if $row.year.total.url}
                <a href="{$row.year.total.url}" title="{ts escape='htmlattribute'}View details{/ts}">{$row.year.total.count}</a>
              {else}
                {$row.year.total.count}
              {/if}&nbsp;[
              {if $row.year_owner.year_owner.url}
                <a href="{$row.year_owner.year_owner.url}" title="{ts escape='htmlattribute'}View details{/ts}">{$row.year_owner.year_owner.count}</a>
              {else}
                {$row.year_owner.year_owner.count}
              {/if}]
            </td>

            <td class="label crm-grid-cell">
              {if $isCurrent}
                {if $row.current.total.url}
                  <a href="{$row.current.total.url}" title="{ts escape='htmlattribute'}View details{/ts}">{$row.current.total.count}</a>
                {else}
                  {$row.current.total.count}
                {/if}&nbsp;[
                {if $row.current_owner.current_owner.url}
                  <a href="{$row.current_owner.current_owner.url}" title="{ts escape='htmlattribute'}View details{/ts}">{$row.current_owner.current_owner.count}</a>
                {else}
                  {$row.current_owner.current_owner.count}
                {/if} ]
              {else}
                {if $row.total.total.url}
                  <a href="{$row.total.total.url}" title="{ts escape='htmlattribute'}View details{/ts}">{$row.total.total.count}</a>
                {else}
                  {$row.total.total.count}
                {/if}&nbsp;[
                {if $row.total_owner.total_owner.url}
                  <a href="{$row.total_owner.total_owner.url}" title="{ts escape='htmlattribute'}View details{/ts}">{$row.total_owner.total_owner.count}</a>
                {else}
                  {$row.total_owner.total_owner.count}
                {/if} ]
              {/if}
            </td> {* member/search?reset=1&force=1&membership_type_id=1&current=1 *}
        </tr>
    {/foreach}

    <tr class="columnfooter">
        <td><strong>{ts}Totals (all types){/ts}</strong></td>
        {if $preMonth}
            <td class="label crm-grid-cell">
              {if $totalCount.premonth.new.url}<a href="{$totalCount.premonth.new.url}" title="{ts escape='htmlattribute'}View details{/ts}">{$totalCount.premonth.new.count}</a>
              {else}{$totalCount.premonth.new.count}{/if}
            </td>
            <td class="label crm-grid-cell">
              {if $totalCount.premonth.renew.url}<a href="{$totalCount.premonth.renew.url}" title="{ts escape='htmlattribute'}View details{/ts}">{$totalCount.premonth.renew.count}</a>
              {else}{$totalCount.premonth.renew.count}{/if}
            </td>
            <td class="label crm-grid-cell">
              {if $totalCount.premonth.total.url}
                <a href="{$totalCount.premonth.total.url}" title="{ts escape='htmlattribute'}View details{/ts}">{$totalCount.premonth.total.count}</a>
              {else}
                {$totalCount.premonth.total.count}
              {/if}&nbsp;[
              {if $totalCount.premonth_owner.premonth_owner.url}
                <a href="{$totalCount.premonth_owner.premonth_owner.url}" title="{ts escape='htmlattribute'}View details{/ts}">{$totalCount.premonth_owner.premonth_owner.count}</a>
              {else}
                {$totalCount.premonth_owner.premonth_owner.count}
              {/if}]
            </td>
        {/if}

            <td class="label crm-grid-cell">
              {if $totalCount.month.new.url}<a href="{$totalCount.month.new.url}" title="{ts escape='htmlattribute'}View details{/ts}">{$totalCount.month.new.count}</a>
              {else}{$totalCount.month.new.count}{/if}
            </td>
            <td class="label crm-grid-cell">
              {if $totalCount.month.renew.url}<a href="{$totalCount.month.renew.url}" title="{ts escape='htmlattribute'}View details{/ts}">{$totalCount.month.renew.count}</a>
              {else}{$totalCount.month.renew.count}{/if}
            </td>
            <td class="label crm-grid-cell">
              {if $totalCount.month.total.url}
                <a href="{$totalCount.month.total.url}" title="{ts escape='htmlattribute'}View details{/ts}">{$totalCount.month.total.count}</a>
              {else}
                {$totalCount.month.total.count}
              {/if}&nbsp;[
              {if $totalCount.month_owner.month_owner.url}
                <a href="{$totalCount.month_owner.month_owner.url}" title="{ts escape='htmlattribute'}View details{/ts}">{$totalCount.month_owner.month_owner.count}</a>
              {else}
                {$totalCount.month_owner.month_owner.count}
              {/if}]
            </td>

            <td class="label crm-grid-cell">
              {if $totalCount.year.new.url}<a href="{$totalCount.year.new.url}" title="{ts escape='htmlattribute'}View details{/ts}">{$totalCount.year.new.count}</a>
              {else}{$totalCount.year.new.count}{/if}
            </td>
            <td class="label crm-grid-cell">
              {if $totalCount.year.renew.url}<a href="{$totalCount.year.renew.url}" title="{ts escape='htmlattribute'}View details{/ts}">{$totalCount.year.renew.count}</a>
              {else}{$totalCount.year.renew.count}{/if}
            </td>
            <td class="label crm-grid-cell">
              {if $totalCount.year.total.url}
                <a href="{$totalCount.year.total.url}" title="{ts escape='htmlattribute'}View details{/ts}">{$totalCount.year.total.count}</a>
              {else}
                {$totalCount.year.total.count}
              {/if}&nbsp;[
              {if $totalCount.year_owner.year_owner.url}
                <a href="{$totalCount.year_owner.year_owner.url}" title="{ts escape='htmlattribute'}View details{/ts}">{$totalCount.year_owner.year_owner.count}</a>
              {else}
                {$totalCount.year_owner.year_owner.count}
              {/if}]
            </td>

            <td class="label crm-grid-cell">
              {if $isCurrent}
                {if $row.total.total.url}
                  <a href="{$row.total.total.url}" title="{ts escape='htmlattribute'}View details{/ts}">{$totalCount.current.total.count}</a>
                {else}
                  {$totalCount.current.total.count}
                {/if}&nbsp;[
                {if $row.total_owner.total_owner.url}
                  <a href="{$row.total_owner.total_owner.url}" title="{ts escape='htmlattribute'}View details{/ts}">{$totalCount.current_owner.current_owner.count}</a>
                {else}
                  {$totalCount.current_owner.current_owner.count}
                {/if} ]
              {else}
                {if $totalCount.total.url}
                  <a href="{$totalCount.total.url}" title="{ts escape='htmlattribute'}View details{/ts}">{$totalCount.total.total.count}</a>
                {else}
                  {$totalCount.total.total.count}
                {/if}&nbsp;[
                {if $totalCount.total_owner.total_owner.url}
                  <a href="{$totalCount.total_owner.total_owner.url}" title="{ts escape='htmlattribute'}View details{/ts}">{$totalCount.total_owner.total_owner.count}</a>
                {else}
                  {$totalCount.total_owner.total_owner.count}
                {/if} ]
              {/if}
            </td>

    </tr>
    <tr><td colspan='11'>
      {ts}Primary member counts (those who "own" the membership rather than receiving via relationship) are in [brackets].{/ts}
    </td></tr>
  </table>
{/if}
<div class="spacer"></div>

{if $rows}
{* if $pager->_totalItems *}
  <h3>{ts}Recent Memberships{/ts}</h3>
  <div class="form-item">
    {include file="CRM/Member/Form/Selector.tpl" context="dashboard"}
  </div>
{* /if *}
{/if}
