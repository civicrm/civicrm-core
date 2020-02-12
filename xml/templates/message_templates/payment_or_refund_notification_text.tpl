{if $emailGreeting}{$emailGreeting},
{/if}

{if $isRefund}
{ts}A refund has been issued based on changes in your registration selections.{/ts}
{else}
{ts}Below you will find a receipt for this payment.{/ts}
{/if}
{if $paymentsComplete}
{ts}Thank you for completing this payment.{/ts}
{/if}

{if $isRefund}
===============================================================================

{ts}Refund Details{/ts}

===============================================================================
{ts}This Refund Amount{/ts}: {$refundAmount|crmMoney}
------------------------------------------------------------------------------------

{else}
===============================================================================

{ts}Payment Details{/ts}

===============================================================================
{ts}This Payment Amount{/ts}: {$paymentAmount|crmMoney}
------------------------------------------------------------------------------------
{/if}
{if $receive_date}
{ts}Transaction Date{/ts}: {$receive_date|crmDate}
{/if}
{if $trxn_id}
{ts}Transaction #{/ts}: {$trxn_id}
{/if}
{if $paidBy}
{ts}Paid By{/ts}: {$paidBy}
{/if}
{if $checkNumber}
{ts}Check Number{/ts}: {$checkNumber}
{/if}

===============================================================================

{ts}Contribution Details{/ts}

===============================================================================
{ts}Total Fee{/ts}: {$totalAmount|crmMoney}
{ts}Total Paid{/ts}: {$totalPaid|crmMoney}
{ts}Balance Owed{/ts}: {$amountOwed|crmMoney} {* This will be zero after final payment. *}


{if $billingName || $address}

===============================================================================

{ts}Billing Name and Address{/ts}

===============================================================================

{$billingName}
{$address}
{/if}

{if $credit_card_number}
===========================================================
{ts}Credit Card Information{/ts}

===============================================================================

{$credit_card_type}
{$credit_card_number}
{ts}Expires{/ts}: {$credit_card_exp_date|truncate:7:''|crmDate}
{/if}
{if $component eq 'event'}
===============================================================================

{ts}Event Information and Location{/ts}

===============================================================================

{$event.event_title}
{$event.event_start_date|crmDate}{if $event.event_end_date}-{if $event.event_end_date|date_format:"%Y%m%d" == $event.event_start_date|date_format:"%Y%m%d"}{$event.event_end_date|crmDate:0:1}{else}{$event.event_end_date|crmDate}{/if}{/if}

{if $event.participant_role}
{ts}Participant Role{/ts}: {$event.participant_role}
{/if}

{if $isShowLocation}
{$location.address.1.display|strip_tags:false}
{/if}{*End of isShowLocation condition*}

{if $location.phone.1.phone || $location.email.1.email}

{ts}Event Contacts:{/ts}
{foreach from=$location.phone item=phone}
{if $phone.phone}

{if $phone.phone_type}{$phone.phone_type_display}{else}{ts}Phone{/ts}{/if}: {$phone.phone}{/if} {if $phone.phone_ext} {ts}ext.{/ts} {$phone.phone_ext}{/if}
{/foreach}
{foreach from=$location.email item=eventEmail}
{if $eventEmail.email}

{ts}Email{/ts}: {$eventEmail.email}{/if}{/foreach}
{/if}
{/if}
