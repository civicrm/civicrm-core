{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2017                                |
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

{crmRegion name="payment-edit-block"}
   <div id="payment-edit-section" class="crm-section billing_mode-section">
     {foreach from=$paymentFields item=paymentField}
       {assign var='name' value=$paymentField.name}
       <div class="crm-container {$name}-section">
         <div class="label">{$form.$name.label}
           {if $requiredPaymentFields.$name}<span class="crm-marker" title="{ts}This field is required.{/ts}">*</span>{/if}
         </div>
         <div class="content">{if $name eq 'total_amount'}{$currency}&nbsp;&nbsp;{/if}{$form.$name.html}
           {if $name == 'credit_card_type'}
             <div class="crm-credit_card_type-icons"></div>
           {/if}
         </div>
         <div class="clear"></div>
       </div>
     {/foreach}
   </div>
{/crmRegion}
<div class="crm-submit-buttons">
  {include file="CRM/common/formButtons.tpl" location="bottom"}
</div>
