{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}

{* wrapper for the billing block including the div to make the block swappable & the js to make that happen
This allows the billing block to change when the card type changes *}
<div id="billing-payment-block">
  {include file="CRM/Core/BillingBlock.tpl"}
</div>
{include file="CRM/common/paymentBlock.tpl"}
