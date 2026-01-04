{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{*

***** IMPORTANT *************
Quickbooks allows almost NO variation in formatting. If editing this file pay
close attention to whitespace, empty lines, and invisible characters. In
particular, since the coding standard means sometimes people's editors replace
tabs with spaces, always use the $tabchar var below instead of an actual tab
character. And watch out for editors that add spaces/tabs at the end of lines.
(I'm wondering if it's better not to use smarty for this.)

****************** BE CAREFUL !!!!!!!!!! ***********************

*}
{if !empty($accounts)}
!ACCNT{$tabchar}NAME{$tabchar}REFNUM{$tabchar}TIMESTAMP{$tabchar}ACCNTTYPE{$tabchar}OBAMOUNT{$tabchar}DESC{$tabchar}ACCNUM
{* I don't think we need these fields - SCD  BANKNUM  EXTRA  HIDDEN  DELCOUNT  USEID  WKPAPERREF

*}
{foreach from=$accounts key=account_id item=acct}
ACCNT{$tabchar}{$acct.name}{$tabchar}{$tabchar}{$tabchar}{$acct.type}{$tabchar}{$tabchar}{$acct.description}{$tabchar}{$acct.account_code}
{/foreach}
{/if}
{if !empty($contacts)}
!CUST{$tabchar}NAME{$tabchar}REFNUM{$tabchar}TIMESTAMP{$tabchar}BADDR1{$tabchar}BADDR2{$tabchar}BADDR3{$tabchar}BADDR4{$tabchar}BADDR5{$tabchar}SADDR1{$tabchar}SADDR2{$tabchar}SADDR3{$tabchar}SADDR4{$tabchar}SADDR5{$tabchar}PHONE1{$tabchar}PHONE2{$tabchar}FAXNUM{$tabchar}EMAIL{$tabchar}NOTE{$tabchar}CONT1{$tabchar}CONT2{$tabchar}CTYPE{$tabchar}TERMS{$tabchar}TAXABLE{$tabchar}SALESTAXCODE{$tabchar}LIMIT{$tabchar}RESALENUM{$tabchar}REP{$tabchar}TAXITEM{$tabchar}NOTEPAD{$tabchar}SALUTATION{$tabchar}COMPANYNAME{$tabchar}FIRSTNAME{$tabchar}MIDINIT{$tabchar}LASTNAME{$tabchar}CUSTFLD1{$tabchar}CUSTFLD2{$tabchar}CUSTFLD3{$tabchar}CUSTFLD4{$tabchar}CUSTFLD5{$tabchar}CUSTFLD6{$tabchar}CUSTFLD7{$tabchar}CUSTFLD8{$tabchar}CUSTFLD9{$tabchar}CUSTFLD10{$tabchar}CUSTFLD11{$tabchar}CUSTFLD12{$tabchar}CUSTFLD13{$tabchar}CUSTFLD14{$tabchar}CUSTFLD15{$tabchar}JOBDESC{$tabchar}JOBTYPE{$tabchar}JOBSTATUS{$tabchar}JOBSTART{$tabchar}JOBPROJEND{$tabchar}JOBEND{$tabchar}HIDDEN{$tabchar}DELCOUNT{$tabchar}PRICELEVEL
{foreach from=$contacts key=contact_id item=contact}
CUST{$tabchar}{$contact.name}{$tabchar}{$tabchar}{$tabchar}{$tabchar}{$tabchar}{$tabchar}{$tabchar}{$tabchar}{$tabchar}{$tabchar}{$tabchar}{$tabchar}{$tabchar}{$tabchar}{$tabchar}{$tabchar}{$tabchar}{$tabchar}{$tabchar}{$tabchar}{$tabchar}{$tabchar}{$tabchar}{$tabchar}{$tabchar}{$tabchar}{$tabchar}{$tabchar}{$tabchar}{$tabchar}{$tabchar}{$contact.first_name}{$tabchar}{$tabchar}{$contact.last_name}{$tabchar}{$tabchar}{$tabchar}{$tabchar}{$tabchar}{$tabchar}{$tabchar}{$tabchar}{$tabchar}{$tabchar}{$tabchar}{$tabchar}{$tabchar}{$tabchar}{$tabchar}{$tabchar}{$tabchar}{$tabchar}{$tabchar}{$tabchar}{$tabchar}{$tabchar}{$tabchar}{$tabchar}
{/foreach}
{/if}
{if !empty($journalEntries)}
!TRNS{$tabchar}TRNSID{$tabchar}TRNSTYPE{$tabchar}DATE{$tabchar}ACCNT{$tabchar}NAME{$tabchar}CLASS{$tabchar}AMOUNT{$tabchar}DOCNUM{$tabchar}MEMO{$tabchar}PAYMETH
!SPL{$tabchar}SPLID{$tabchar}TRNSTYPE{$tabchar}DATE{$tabchar}ACCNT{$tabchar}NAME{$tabchar}CLASS{$tabchar}AMOUNT{$tabchar}DOCNUM{$tabchar}MEMO{$tabchar}PAYMETH
!ENDTRNS
{foreach from=$journalEntries key=id item=je}
TRNS{$tabchar}{$je.to_account.trxn_id}{$tabchar}GENERAL JOURNAL{$tabchar}{$je.to_account.trxn_date}{$tabchar}{$je.to_account.account_name}{$tabchar}{$je.to_account.contact_name}{$tabchar}{$tabchar}{$je.to_account.amount}{$tabchar}{$je.to_account.check_number}{$tabchar}{$tabchar}{$je.to_account.payment_instrument}
{foreach from=$je.splits key=spl_id item=spl}
SPL{$tabchar}{$spl.spl_id}{$tabchar}GENERAL JOURNAL{$tabchar}{$spl.trxn_date}{$tabchar}{$spl.account_name}{$tabchar}{$spl.contact_name}{$tabchar}{$tabchar}{$spl.amount}{$tabchar}{$spl.check_number}{$tabchar}{$spl.description}{$tabchar}{$spl.payment_instrument}
{/foreach}
ENDTRNS
{/foreach}
{/if}
