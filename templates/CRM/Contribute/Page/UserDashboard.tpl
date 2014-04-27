{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.5                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2014                                |
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
<div class="view-content">
    {if $contribute_rows}
        {strip}

        <table class="selector">
            <tr class="columnheader">
                <th>{ts}Total Amount{/ts}</th>
                <th>{ts}Financial Type{/ts}</th>
                <th>{ts}Received date{/ts}</th>
                <th>{ts}Receipt Sent{/ts}</th>
                <th>{ts}Status{/ts}</th>
            </tr>

            {foreach from=$contribute_rows item=row}
                <tr id='rowid{$row.contribution_id}' class="{cycle values="odd-row,even-row"}{if $row.cancel_date} disabled{/if}">
                    <td>{$row.total_amount|crmMoney:$row.currency} {if $row.amount_level } - {$row.amount_level} {/if}
                        {if $row.contribution_recur_id}
                            <br /> {ts}(Recurring Contribution){/ts}
                        {/if}
                    </td>
                    <td>{$row.financial_type}</td>
                    <td>{$row.receive_date|truncate:10:''|crmDate}</td>
                    <td>{$row.receipt_date|truncate:10:''|crmDate}</td>
                    <td>{$row.contribution_status}</td>
                </tr>
            {/foreach}
        </table>
        {/strip}
        {if $contributionSummary.total.count gt 12}
            {ts}Contact us for information about contributions prior to those listed above.{/ts}
        {/if}
    {else}
        <div class="messages status no-popup">
           <div class="icon inform-icon"></div>
                    {ts}There are no contributions on record for you.{/ts}
        </div>
    {/if}


    {if $honor}
        {if $honorRows}
            {strip}
            <div id="help">
                {ts}Contributions made in your honor{/ts}:
            </div>
            <table class="selector">
                <tr class="columnheader">
                    <th >{ts}Contributor{/ts}</th>
                    <th>{ts}Amount{/ts}</th>
                    <th>{ts}Type{/ts}</th>
                    <th>{ts}Financial Type{/ts}</th>
                    <th>{ts}Received date{/ts}</th>
                    <th>{ts}Receipt Sent{/ts}</th>
                    <th>{ts}Status{/ts}</th>
                </tr>
                {foreach from=$honorRows item=row}
                    <tr id='rowid{$row.honorId}' class="{cycle values="odd-row,even-row"}">
                        <td><a href="{crmURL p="civicrm/contact/view" q="reset=1&cid=`$row.honorId`"}" id="view_contact">{$row.display_name}</a></td>
                        <td>{$row.amount}</td>
                        <td>{$row.honor_type}</td>
                        <td>{$row.type}</td>
                        <td>{$row.receive_date|truncate:10:''|crmDate}</td>
                        <td>{$row.receipt_date|truncate:10:''|crmDate}</td>
                        <td>{$row.contribution_status}</td>
                    </tr>
                {/foreach}
            </table>
            {/strip}
        {/if}
    {/if}

    {if $recur}
        {if $recurRows}
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
                {foreach from=$recurRows item=row key=id}
                    <tr class="{cycle values="odd-row,even-row"}">
                        <td><label>{$recurRows.$id.amount|crmMoney}</label>  every {$recurRows.$id.frequency_interval} {$recurRows.$id.frequency_unit} for {$recurRows.$id.installments} installments</td>
                        <td>{$recurRows.$id.recur_status}</td>
                        <td>{if $recurRows.$id.completed}<a href="{$recurRows.$id.link}">{$recurRows.$id.completed}/{$recurRows.$id.installments}</a>
                            {else}0/{$recurRows.$id.installments} {/if}</td>
                       <td>{$recurRows.$id.create_date|crmDate}</td>
                       <td>{$recurRows.$id.action|replace:'xx':$recurRows.id}</td>
                    </tr>
                {/foreach}
            </table>
            {/strip}
        {/if}
    {/if}
</div>

