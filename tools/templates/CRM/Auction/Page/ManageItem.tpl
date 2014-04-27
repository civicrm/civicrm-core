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
<div class="crm-suubmit-buttons">
<a accesskey="N" href="{$newItemURL}" id="newAddItem" class="button"><span><div class="icon add-icon"></div>{ts}Add Item{/ts}</span></a>
<a accesskey="P" href="{$previewItemURL}" id="previewItem" class="button"><span><div class="icon preview-icon"></div>{ts}Preview Items{/ts}</span></a>
</div>
{include file="CRM/Auction/Form/SearchItem.tpl"}

{if $rows}
    <div id=item_status_id>
        {strip}
        {include file="CRM/common/pager.tpl" location="top"}
        {include file="CRM/common/pagerAToZ.tpl}    
        <table class="selector">
         <tr class="columnheader">
            <th>{ts}Donor{/ts}</th>
            <th>{ts}Item{/ts}</th>
            <th>{ts}Description{/ts}</th>
            <th>{ts}Auction Type{/ts}</th>
            <th>{ts}Quantity{/ts}</th>
            <th>{ts}Retail Value{/ts}</th>
            <th>{ts}Buy Now Value{/ts}</th>
            <th>{ts}Min Bid Value{/ts}</th>
            <th>{ts}Min Bid Increment{/ts}</th>
            <th>{ts}Approved?{/ts}</th>
	    <th></th>
         </tr>
        {foreach from=$rows item=row}
          <tr class="{cycle values="odd-row,even-row"} {$row.class}{if NOT $row.is_active} disabled{/if}">
            <td>{$row.donorName}</td>
            <td>{$row.title}&nbsp;&nbsp;({ts}ID:{/ts} {$row.id})</td>
            <td>{$row.description}</td>
            <td>{$row.auction_type}</td>
            <td>{$row.quantity}</td>
            <td>{$row.retail_value}</td>
            <td>{$row.buy_now_value}</td>
            <td>{$row.min_bid_value}</td>
            <td>{$row.min_bid_increment}</td>
	    <td>{if $row.is_active eq 1} {ts}Yes{/ts} {else} {ts}No{/ts} {/if}</td>
	    <td>{$row.action}</td>
          </tr>
        {/foreach}    
        </table>
        {include file="CRM/common/pager.tpl" location="bottom"}
        {/strip}
      
    </div>
{else}
   {if $isSearch eq 1}
    <div class="status messages">
        <dl>
            <dt><div class="icon inform-icon"></div></dt>
            {capture assign=browseURL}{crmURL p='civicrm/auction/manage' q="reset=1"}{/capture}
            <dd>
                {ts}No available Auctions match your search criteria. Suggestions:{/ts}
                <div class="spacer"></div>
                <ul>
                <li>{ts}Check your spelling.{/ts}</li>
                <li>{ts}Try a different spelling or use fewer letters.{/ts}</li>
                <li>{ts}Make sure you have enough privileges in the access control system.{/ts}</li>
                </ul>
                {ts 1=$browseURL}Or you can <a href='%1'>browse all available Current Auctions</a>.{/ts}
            </dd>
        </dl>
    </div>
   {else}
    <div class="messages status">
    <dl>
        <dt><div class="icon inform-icon"></div></dt>
        <dd>{ts 1=$newAuctionURL}There are no auctions created yet. You can <a href='%1'>add one</a>.{/ts}</dd>
        </dl>
    </div>    
   {/if}
{/if}
