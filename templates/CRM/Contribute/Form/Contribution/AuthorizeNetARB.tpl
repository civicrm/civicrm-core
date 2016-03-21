{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2016                                |
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
{if $subscriptionType eq 'cancel'}
<?xml version="1.0" encoding="utf-8"?>
<ARBCancelSubscriptionRequest xmlns="AnetApi/xml/v1/schema/AnetApiSchema.xsd">
  <merchantAuthentication>
    <name>{$apiLogin}</name>
    <transactionKey>{$paymentKey}</transactionKey>
  </merchantAuthentication>
  <subscriptionId>{$subscriptionId}</subscriptionId>
</ARBCancelSubscriptionRequest>
{elseif $subscriptionType eq 'updateBilling'}
<?xml version="1.0" encoding="utf-8"?>
<ARBUpdateSubscriptionRequest xmlns="AnetApi/xml/v1/schema/AnetApiSchema.xsd">
  <merchantAuthentication>
    <name>{$apiLogin}</name>
    <transactionKey>{$paymentKey}</transactionKey>
  </merchantAuthentication>
  <subscriptionId>{$subscriptionId}</subscriptionId>
  <subscription>
    <payment>
      <creditCard>
        <cardNumber>{$cardNumber}</cardNumber>
        <expirationDate>{$expirationDate}</expirationDate>
      </creditCard>
    </payment>
    <billTo>
      <firstName>{$billingFirstName}</firstName>
      <lastName>{$billingLastName}</lastName>
      <address>{$billingAddress}</address>
      <city>{$billingCity}</city>
      <state>{$billingState}</state>
      <zip>{$billingZip}</zip>
      <country>{$billingCountry}</country>
    </billTo>
  </subscription>
</ARBUpdateSubscriptionRequest>
{elseif $subscriptionType eq 'update'}
<?xml version="1.0" encoding="utf-8"?>
<ARBUpdateSubscriptionRequest xmlns="AnetApi/xml/v1/schema/AnetApiSchema.xsd">
  <merchantAuthentication>
    <name>{$apiLogin}</name>
    <transactionKey>{$paymentKey}</transactionKey>
  </merchantAuthentication>
<subscriptionId>{$subscriptionId}</subscriptionId>
  <subscription>
    <paymentSchedule>
    <totalOccurrences>{$totalOccurrences}</totalOccurrences>
    </paymentSchedule>
    <amount>{$amount}</amount>
   </subscription>
</ARBUpdateSubscriptionRequest>
{else}
<?xml version="1.0" encoding="utf-8"?>
<ARBCreateSubscriptionRequest xmlns="AnetApi/xml/v1/schema/AnetApiSchema.xsd">
  <merchantAuthentication>
    <name>{$apiLogin}</name>
    <transactionKey>{$paymentKey}</transactionKey>
  </merchantAuthentication>
  <refId>{$refId}</refId>
  <subscription>
    {if $name}<name>{$name|truncate:50}</name>{/if}
    <paymentSchedule>
      <interval>
        <length>{$intervalLength}</length>
        <unit>{$intervalUnit}</unit>
      </interval>
      <startDate>{$startDate}</startDate>
      <totalOccurrences>{$totalOccurrences}</totalOccurrences>
    </paymentSchedule>
    <amount>{$amount}</amount>
    <payment>
      <creditCard>
        <cardNumber>{$cardNumber}</cardNumber>
        <expirationDate>{$expirationDate}</expirationDate>
      </creditCard>
    </payment>
   {if $invoiceNumber}
   <order>
     <invoiceNumber>{$invoiceNumber}</invoiceNumber>
     {if $name}<description>{$name}</description>{/if}
   </order>
   {/if}
    <customer>
      <id>{$contactID}</id>
      <email>{$email}</email>
    </customer>
    <billTo>
      <firstName>{$billingFirstName}</firstName>
      <lastName>{$billingLastName}</lastName>
      <address>{$billingAddress}</address>
      <city>{$billingCity}</city>
      <state>{$billingState}</state>
      <zip>{$billingZip}</zip>
      <country>{$billingCountry}</country>
    </billTo>
  </subscription>
</ARBCreateSubscriptionRequest>
{/if}
