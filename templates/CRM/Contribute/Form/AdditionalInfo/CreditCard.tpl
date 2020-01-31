{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{* this template is used for adding Credit Cart and billing details *}
<div id="id-creditCard" class="section-shown">
    {include file='CRM/Core/BillingBlockWrapper.tpl'}
</div>

{include file="CRM/Contribute/Form/AdditionalInfo/Payment.tpl"}
