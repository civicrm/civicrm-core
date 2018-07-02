{*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2018                                |
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

{if $result eq true}
  {if $task eq 'billing'}
     {if $billingName}
     <div class="crm-group billing_name_address-group">
       <div class="header-dark">
         {ts}Billing Name and Address{/ts}
       </div>
       <div class="crm-section no-label billing_name-section">
       <div class="content">{$billingName}</div>
         <div class="clear"></div>
       </div>
       <div class="crm-section no-label billing_address-section">
         <div class="content">{$address|nl2br}</div>
         <div class="clear"></div>
       </div>
     </div>
     {/if}

     {if $credit_card_number}
     <div class="crm-group credit_card-group">
       <div class="header-dark">
         {ts}Credit Card Information{/ts}
       </div>
       <div class="crm-section no-label credit_card_details-section">
         <div class="content">{$credit_card_type}</div>
         <div class="content">{$credit_card_number}</div>
         <div class="content">{ts}Expires{/ts}: {$credit_card_exp_date|truncate:7:''|crmDate}</div>
         <div class="clear"></div>
       </div>
     </div>
     {/if}
  {/if}
{/if}
