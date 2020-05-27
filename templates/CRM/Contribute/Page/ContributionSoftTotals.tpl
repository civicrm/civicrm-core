{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{* Display soft credit totals for a contact or search result-set *}

<tr>
  {if $softCreditTotals.amount}
    <th class="contriTotalLeft right">{ts}Total Soft Credit Amount{/ts} &ndash; {$softCreditTotals.amount}</th>
    <th class="right"> &nbsp; {ts}# Completed Soft Credits{/ts} &ndash; {$softCreditTotals.count}</th>
    <th class="right contriTotalRight"> &nbsp; {ts}Avg Soft Credit Amount{/ts} &ndash; {$softCreditTotals.avg}</th>
  {/if}
  {if $softCreditTotals.cancel.amount}
    <th class="disabled right contriTotalRight"> &nbsp; {ts}Cancelled/Refunded{/ts} &ndash; {$softCreditTotals.cancel.amount}</th>
  {/if}
</tr>
