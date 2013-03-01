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
{* CiviMember DashBoard (launch page) *}
<h3>{ts}Membership Summary{/ts} {help id="id-member-intro"}</h3>
<table class="report">
    <tr class="columnheader-dark">
      <th scope="col" rowspan="2">{ts}Members by Type{/ts}</th>
        {if $preMonth}
      <th scope="col" colspan="3">{$premonth} &ndash; {ts}(Last Month){/ts}</th>
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
                <td class="label"><a href="{$row.premonth.new.url}" title="view details">{$row.premonth.new.count}</a></td>
                <td class="label"><a href="{$row.premonth.renew.url}" title="view details">{$row.premonth.renew.count}</a>
    </td>
                <td class="label">
        <a href="{$row.premonth.total.url}" title="view details">{$row.premonth.total.count}</a>&nbsp;
        [ <a href="{$row.premonth_owner.premonth_owner.url}" title="view details">
        {$row.premonth_owner.premonth_owner.count}</a> ]
    </td>
            {/if}

      <td class="label"><a href="{$row.month.new.url}" title="view details">{$row.month.new.count}</a></td>
            <td class="label"><a href="{$row.month.renew.url}" title="view details">{$row.month.renew.count}</a></td>
            <td class="label"><a href="{$row.month.total.url}" title="view details">{$row.month.total.count}</a>&nbsp;
          [ <a href="{$row.month_owner.month_owner.url}" title="view details">
    {$row.month_owner.month_owner.count}</a> ]
      </td>

            <td class="label"><a href="{$row.year.new.url}" title="view details">{$row.year.new.count}</a></td>
            <td class="label"><a href="{$row.year.renew.url}" title="view details">{$row.year.renew.count}</a></td>
            <td class="label"><a href="{$row.year.total.url}" title="view details">{$row.year.total.count}</a>&nbsp;
        [ <a href="{$row.year_owner.year_owner.url}" title="view details">{$row.year_owner.year_owner.count}</a> ]
      </td>

            <td class="label">
                {if $isCurrent}
        <a href="{$row.current.total.url}" title="view details">{$row.current.total.count}</a>&nbsp;
                    [ <a href="{$row.current_owner.current_owner.url}" title="view details">
        {$row.current_owner.current_owner.count}</a> ]
                {else}
                    <a href="{$row.total.total.url}" title="view details">{$row.total.total.count}</a>&nbsp;
                    [ <a href="{$row.total_owner.total_owner.url}" title="view details">
        {$row.total_owner.total_owner.count}</a> ]
                {/if}
            </td> {* member/search?reset=1&force=1&membership_type_id=1&current=1 *}
        </tr>
    {/foreach}

    <tr class="columnfooter">
        <td><strong>{ts}Totals (all types){/ts}</strong></td>
        {if $preMonth}
           <td class="label">
       <a href="{$totalCount.premonth.new.url}" title="view details">{$totalCount.premonth.new.count}</a></td>
           <td class="label">
       <a href="{$totalCount.premonth.renew.url}" title="view details">{$totalCount.premonth.renew.count}</a></td>
           <td class="label">
       <a href="{$totalCount.premonth.total.url}" title="view details">{$totalCount.premonth.total.count}</a>
       [ <a href="{$totalCount.premonth_owner.premonth_owner.url}" title="view details">{$totalCount.premonth_owner.premonth_owner.count}</a> ]
     </td>
        {/if}

        <td class="label">
    <a href="{$totalCount.month.new.url}" title="view details">{$totalCount.month.new.count}</a></td>
        <td class="label">
    <a href="{$totalCount.month.renew.url}" title="view details">{$totalCount.month.renew.count}</a></td>
        <td class="label">
    <a href="{$totalCount.month.total.url}" title="view details">{$totalCount.month.total.count}</a>
    [ <a href="{$totalCount.month_owner.month_owner.url}" title="view details">
    {$totalCount.month_owner.month_owner.count}</a> ]
  </td>
        <td class="label">
    <a href="{$totalCount.year.new.url}" title="view details">{$totalCount.year.new.count}</a></td>
        <td class="label">
    <a href="{$totalCount.year.renew.url}" title="view details">{$totalCount.year.renew.count}</a></td>
        <td class="label">
    <a href="{$totalCount.year.total.url}" title="view details">{$totalCount.year.total.count}</a>
    [ <a href="{$totalCount.year_owner.year_owner.url}" title="view details">{$totalCount.year_owner.year_owner.count}
    </a> ]
  </td>

        <td class="label">
            {if $isCurrent}
    <a href="{$row.total.total.url}" title="view details">{$totalCount.current.total.count}</a>&nbsp;
    [ <a href="{$row.total_owner.total_owner.url}" title="view details">
    {$totalCount.current_owner.current_owner.count}</a> ]

            {else}
    <a href="{$totalCount.total.url}" title="view details">{$totalCount.total.total.count}</a>&nbsp;
    [ <a href="{$totalCount.total_owner.total_owner.url}" title="view details">
    {$totalCount.total_owner.total_owner.count}</a> ]

            {/if}
        </td> {* member/search?reset=1&force=1&current=1 *}
    </tr>
    <tr><td colspan='11'>
      Primary member counts (those who "own" the membership rather than receiving via relationship) are in [brackets].
    </td></tr>
</table>

<div class="spacer"></div>

{if $rows}
{* if $pager->_totalItems *}
    <h3>{ts}Recent Memberships{/ts}</h3>
    <div class="form-item">
        { include file="CRM/Member/Form/Selector.tpl" context="dashboard" }
    </div>
{* /if *}
{/if}
