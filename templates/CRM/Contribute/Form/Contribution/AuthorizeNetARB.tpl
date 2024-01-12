{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
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
