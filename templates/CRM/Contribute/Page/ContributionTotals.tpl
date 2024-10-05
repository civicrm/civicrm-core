{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{*Table displays contribution totals for a contact or search result-set *}
{if !empty($annual.count) OR $contributionSummary.total.count OR $contributionSummary.cancel.count OR $contributionSummary.soft_credit.count}
    <table class="form-layout-compressed">

    {if !empty($annual.count)}
        <tr>
            <th class="contriTotalLeft right">{ts}Current Fiscal Year-to-Date{/ts} &ndash; {$annual.amount nofilter}</th>
            <th class="right"> &nbsp; {ts}# Completed Contributions{/ts} &ndash; {$annual.count nofilter}</th>
            <th class="right contriTotalRight"> &nbsp; {ts}Avg Amount{/ts} &ndash; {$annual.avg nofilter}</th>
            {if $contributionSummary.cancel.amount}
                <td>&nbsp;</td>
            {/if}
        </tr>
    {/if}

    {if $contributionSummary}
      <tr>
          {if $contributionSummary.total.amount}
            <th class="contriTotalLeft right">{ts}Total{/ts} &ndash; {$contributionSummary.total.amount nofilter}</th>
            <th class="right"> &nbsp; {ts}# Completed{/ts} &ndash; {$contributionSummary.total.count nofilter}</th>
            <th class="right contriTotalRight"> &nbsp; {ts}Avg{/ts} &ndash; {$contributionSummary.total.avg nofilter}</th>
          {/if}
          {if $contributionSummary.cancel.amount}
            <th class="disabled right contriTotalRight"> &nbsp; {ts}Cancelled/Refunded{/ts} &ndash; {$contributionSummary.cancel.amount nofilter}</th>
          {/if}
      </tr>
      {if $contributionSummary.soft_credit.count}
        {include file="CRM/Contribute/Page/ContributionSoftTotals.tpl" softCreditTotals=$contributionSummary.soft_credit}
      {/if}
    {/if}

    </table>
{/if}
