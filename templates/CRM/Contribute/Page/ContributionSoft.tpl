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
{if $softCreditRows}
{strip}
<table class="form-layout-compressed">
    <tr>
        <th class="contriTotalLeft">{ts}Total Soft Credits{/ts} - {$softCreditTotals.amount|crmMoney:$softCreditTotals.currency}</th>
        <th class="right" width="10px"> &nbsp; </th>
        <th class="right contriTotalRight"> &nbsp; {ts}Avg Soft Credits{/ts} - {$softCreditTotals.avg|crmMoney:$softCreditTotals.currency}</th>
    </tr>
</table> 
<p></p>

<table class="selector">
    <tr class="columnheader">
        <th scope="col">{ts}Contributor{/ts}</th> 
        <th scope="col">{ts}Amount{/ts}</th>
        <th scope="col">{ts}Type{/ts}</th>
        <th scope="col" class="sorting_desc">{ts}Received{/ts}</th>
        <th scope="col">{ts}Status{/ts}</th>
        <th scope="col">{ts}Personal Campaign Page?{/ts}</th>
        <th></th>
    </tr>
    {foreach from=$softCreditRows item=row}
        <tr id='rowid{$row.id}' class="{cycle values="odd-row,even-row"}">
            <td><a href="{crmURL p="civicrm/contact/view" q="reset=1&cid=`$row.contributor_id`"}" id="view_contact" title="{ts}View contributor contact record{/ts}">{$row.contributor_name}</a></td>
            <td>{$row.amount|crmMoney:$row.currency}</td>
            <td>{$row.financial_type}</td>
            <td>{$row.receive_date|truncate:10:''|crmDate}</td>
            <td>{$row.contribution_status}</td>
            <td>{if $row.pcp_id}<a href="{crmURL p="civicrm/pcp/info" q="reset=1&id=`$row.pcp_id`"}" title="{ts}View Personal Campaign Page{/ts}">{$row.pcp_title}</a>{else}{ts}(n/a){/ts}{/if}</td>
            <td><a href="{crmURL p="civicrm/contact/view/contribution" q="reset=1&id=`$row.contribution_id`&cid=`$contactId`&action=view&context=contribution&selectedChild=contribute"}" title="{ts}View related contribution{/ts}">{ts}View{/ts}</a></td>
        </tr>
    {/foreach}
</table>
{/strip}
{/if}
