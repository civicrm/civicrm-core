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
<div class="form-item"> 
<fieldset><legend>{ts}Item Information{/ts}</legend>
    <table class="form-layout-compressed">
         <tr><td class="label">{ts}Auction{/ts}</td><td>{$auctionTitle}</td></tr>
         <tr><td class="label">{ts}Donor{/ts}</td><td>{$donorName}</td></tr>
         <tr><td class="label">{$form.title.label}</td><td>{$form.title.html}</td></tr>
         <tr><td class="label">{$form.description.label}</td><td>{$form.description.html}</td></tr>
         <tr><td class="label">{$form.url.label}</td><td>{$form.url.html}</td></tr>
         <tr><td class="label">{$form.auction_item_type_id.label}</td><td>{$form.auction_item_type_id.html}</td></tr>

         <tr><td class="label">{$form.quantity.label}</td><td>{$form.quantity.html|crmReplace:class:four}<br />
         <tr><td class="label">{$form.retail_value.label}</td><td>{$form.retail_value.html|crmReplace:class:four}<br />
         <tr><td class="label">{$form.min_bid_value.label}</td><td>{$form.min_bid_value.html|crmReplace:class:four}<br />
         <tr><td class="label">{$form.min_bid_increment.label}</td><td>{$form.min_bid_increment.html|crmReplace:class:four}<br />
         <tr><td class="label">{$form.buy_now_value.label}</td><td>{$form.buy_now_value.html|crmReplace:class:four}<br />

         <tr><td>&nbsp;</td><td>{$form.is_group.html} {$form.is_group.label}<br />
         <tr><td>&nbsp;</td><td>{$form.is_active.html} {$form.is_active.label}</td></tr> 
        <tr><td>&nbsp;</td><td>&nbsp;</td></tr>
    </table>

{include file="CRM/Form/attachment.tpl" context="pcpCampaign"}

    <dl>    
       <dt></dt><dd class="html-adjust">{$form.buttons.html}</dd>   
    </dl> 
</fieldset>     
</div>
