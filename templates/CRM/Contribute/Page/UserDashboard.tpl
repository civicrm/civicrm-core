{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{crmRegion name="crm-contribute-userdashboard-pre"}
{/crmRegion}
<div class="view-content">
    {if $contribute_rows}
        {strip}
            <table class="selector">
                <tr class="columnheader">
                    <th>{ts}Total Amount{/ts}</th>
                    <th>{ts}Financial Type{/ts}</th>
                    <th>{ts}Contribution Date{/ts}</th>
                    <th>{ts}Receipt Sent{/ts}</th>
                    <th>{ts}Balance{/ts}</th>
                    <th>{ts}Status{/ts}</th>
                    {if $isIncludeInvoiceLinks}
                      <th></th>
                    {/if}
                    <th></th>
                </tr>

                {foreach from=$contribute_rows item=row}
                    <tr id='rowid{$row.id}'
                        class="{cycle values="odd-row,even-row"}{if !empty($row.cancel_date)} disabled{/if}">
                        <td>{$row.total_amount|crmMoney:$row.currency} {if !empty($row.amount_level) && !is_array($row.amount_level)} - {$row.amount_level|escape|smarty:nodefaults} {/if}
                            {if !empty($row.contribution_recur_id)}
                                <br/>
                                {ts}(Recurring Contribution){/ts}
                            {/if}
                        </td>
                        <td>{$row.financial_type|escape|smarty:nodefaults}</td>
                        <td>{$row.receive_date|truncate:10:''|crmDate}</td>
                        <td>{$row.receipt_date|truncate:10:''|crmDate}</td>
                        <td>{$row.balance_amount|crmMoney:$row.currency}</td>
                        <td>{$row.contribution_status|escape|smarty:nodefaults}</td>
                        {if $isIncludeInvoiceLinks}
                          <td>
                            {* @todo Instead of this tpl handling assign actions as an array attached the row, iterate through - will better accomodate extension overrides and competition for scarce real estate on this page*}
                            {assign var='id' value=$row.id}
                            {assign var='contact_id' value=$row.contact_id}
                            {assign var='urlParams' value="reset=1&id=$id&cid=$contact_id"}
                            {if $canViewMyInvoicesOrAccessCiviContribute}
                                <a class="button no-popup nowrap"
                                   href="{crmURL p='civicrm/contribute/invoice' q=$urlParams}">
                                    <i class="crm-i fa-download" aria-hidden="true"></i>
                                    {if empty($row.contribution_status_name) || (!empty($row.contribution_status_name) && $row.contribution_status_name != 'Refunded' && $row.contribution_status_name != 'Cancelled')}
                                        <span>{ts}Download Invoice{/ts}</span>
                                    {else}
                                        <span>{ts}Download Invoice and Credit Note{/ts}</span>
                                    {/if}
                                </a>
                            {/if}
                          </td>
                        {/if}
                        {if !empty($row.buttons)}
                        <td>
                        {foreach from=$row.buttons item=button}
                          <a class="{$button.class}" href="{$button.url}"><span class='nowrap'>{$button.label}</span></a>
                        {/foreach}
                        </td>
                        {/if}
                    </tr>
                {/foreach}
            </table>
        {/strip}
        {if !empty($contributionSummary.total) and $contributionSummary.total.count gt 12}
            {ts}Contact us for information about contributions prior to those listed above.{/ts}
        {/if}
    {else}
        <div class="messages status no-popup">
            {icon icon="fa-info-circle"}{/icon}
            {ts}There are no contributions on record for you.{/ts}
        </div>
    {/if}

    {if !empty($soft_credit_contributions)}
        {strip}
            <div class="help">
                {ts}Contributions made in your honor{/ts}:
            </div>
            <table class="selector">
                <tr class="columnheader">
                    <th>{ts}Contributor{/ts}</th>
                    <th>{ts}Amount{/ts}</th>
                    <th>{ts}Type{/ts}</th>
                    <th>{ts}Financial Type{/ts}</th>
                    <th>{ts}Contribution Date{/ts}</th>
                    <th>{ts}Receipt Sent{/ts}</th>
                    <th>{ts}Status{/ts}</th>
                </tr>
                {foreach from=$soft_credit_contributions item=row}
                    <tr id='rowid{$row.contact_id}' class="{cycle values="odd-row,even-row"}">
                        <td><a href="{crmURL p="civicrm/contact/view" q="reset=1&cid=`$row.contact_id`"}"
                               id="view_contact">{$row.display_name|escape|smarty:nodefaults}</a></td>
                        <td>{$row.total_amount|crmMoney:$row.currency}</td>
                        <td>{$row.soft_credit_type|escape|smarty:nodefaults}</td>
                        <td>{$row.financial_type|escape|smarty:nodefaults}</td>
                        <td>{$row.receive_date|truncate:10:''|crmDate}</td>
                        <td>{$row.receipt_date|truncate:10:''|crmDate}</td>
                        <td>{$row.contribution_status|escape|smarty:nodefaults}</td>
                    </tr>
                {/foreach}
            </table>
        {/strip}
    {/if}

        {if !empty($recurRows)}
            {strip}
                <div><label>{ts}Recurring Contribution(s){/ts}</label></div>
                <table class="selector">
                    <tr class="columnheader">
                        <th>{ts}Terms:{/ts}</th>
                        <th>{ts}Status{/ts}</th>
                        <th>{ts}Installments{/ts}</th>
                        <th>{ts}Created{/ts}</th>
                        <th></th>
                    </tr>
                    {foreach from=$recurRows item=row}
                        <tr class="{cycle values="odd-row,even-row"}">
                            <td><label>{$row.amount|crmMoney}</label>
                                every {$row.frequency_interval} {$row.frequency_unit} for {$row.installments} installments
                            </td>
                            <td>{$row.recur_status|escape|smarty:nodefaults}</td>
                            <td>{if $row.completed}<a href="{$row.link}">{$row.completed}
                                    /{$row.installments}</a>
                                {else}0/{$row.installments} {/if}</td>
                            <td>{$row.create_date|crmDate}</td>
                            <td>{$row.action|replace:'xx':$row.id}</td>
                        </tr>
                    {/foreach}
                </table>
            {/strip}
        {/if}

</div>
{crmRegion name="crm-contribute-userdashboard-post"}
{/crmRegion}
