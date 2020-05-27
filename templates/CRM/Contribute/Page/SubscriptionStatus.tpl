{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
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
