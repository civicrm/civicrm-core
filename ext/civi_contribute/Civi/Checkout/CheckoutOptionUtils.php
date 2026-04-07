<?php

  /*
   +--------------------------------------------------------------------+
   | Copyright CiviCRM LLC. All rights reserved.                        |
   |                                                                    |
   | This work is published under the GNU AGPLv3 license with some      |
   | permitted exceptions and without any warranty. For full license    |
   | and copyright information, see https://civicrm.org/licensing       |
   +--------------------------------------------------------------------+
   */

namespace Civi\Checkout;

use CRM_Contribute_ExtensionUtil as E;

/**
 * Optional utils for implementing payment processor classes.
 * Think of it like the pick-n-mix version of PropertyBag -
 * you can use things if they are useful but you dont have to
 */
class CheckoutOptionUtils {

  /**
   * Map from api4 fields on the Contribution field to param names
   * commonly used by payment processors
   *
   * @return array
   */
  public static function getLegacyKeyMap(): array {
    return [
      'id' => 'contributionID',
      'contact_id' => 'contactID',
      'total_amount' => 'amount',
      'invoice_id' => 'invoiceID',
      'source' => 'source',
      'currency' => 'currency',
      // TODO? it might be nice to use the address record on the contribution
      // but this is not being saved at the moment. this was previously done
      // in the Contribution quickform layer. should it be moved to Order api?
      // or drop the duplication of contact billing address and contribution billing
      // address
      // 'address_id.street_address' => 'billingStreetAddress',
      // 'address_id.city' => 'billingCity',
      // 'address_id.postal_code' => 'billingPostalCode',
      // 'address_id.country_id.iso_code' => 'billingCountry',
      'contact_id.address_billing.street_address' => 'billingStreetAddress',
      'contact_id.address_billing.city' => 'billingCity',
      'contact_id.address_billing.postal_code' => 'billingPostalCode',
      'contact_id.address_billing.country_id.iso_code' => 'billingCountry',
    ];
  }

  public static function fillContributionDefaults(array $contribution): array {
    $defaults = [
      'description' => E::ts('CiviCRM Contribution'),
      'source' => E::ts('CiviCRM Contribution'),
    ];

    foreach ($defaults as $key => $value) {
      if (empty($contribution[$key])) {
        $contribution[$key] = $value;
      }
    }

    return $contribution;
  }

  public static function fetchRequiredParams(int $contributionId, array $api4Keys = [], array $legacyKeys = [], array $knownValues = []): array {
    $fieldsToFetch = [];

    foreach ($api4Keys as $api4Key) {
      if (isset($knownValues[$api4Key])) {
        continue;
      }
      $fieldsToFetch[] = $api4Key;
    }

    $legacyKeyMap = self::getLegacyKeyMap();

    foreach ($legacyKeys as $legacyKey) {
      $sourceFields = array_keys(array_filter($legacyKeyMap, fn ($key) => $key === $legacyKey));

      if (!$sourceFields) {
        throw new \CRM_Core_Exception("Sorry - no api4 source key is known for requested legacy key: {$legacyKey}");
      }

      $fieldsToFetch = array_merge($fieldsToFetch, $sourceFields);
    }

    $values = \Civi\Api4\Contribution::get(FALSE)
      ->addWhere('id', '=', $contributionId)
      ->addSelect(...$fieldsToFetch)
      ->execute()
      ->first();

    // rekey using legacy keys if requested
    foreach ($legacyKeys as $legacyKey) {
      $sourceFields = array_keys(array_filter($legacyKeyMap, fn ($key) => $key === $legacyKey));
      foreach ($sourceFields as $sourceField) {
        // in case there are multiple source keys, take the
        // first non-empty value
        if ($values[$sourceField]) {
          $values[$legacyKey] = $values[$sourceField];
          continue;
        }
      }
    }

    // We need to pass the currency to the payment processor,
    //   use the default if not passed a default from Afform
    if (empty($values['currency'])) {
      $values['currency'] = \CRM_Core_Config::singleton()->defaultCurrency;
    }

    return $values;
  }

  public static function fetchLineItems(int $contributionId): array {
    $lineItems = (array) \Civi\Api4\LineItem::get(FALSE)
      ->addWhere('contribution_id', '=', $contributionId)
      ->addSelect('*', 'price_field_id:label', 'price_field_value_id:label')
      ->execute();
    return $lineItems;
  }

  public static function mapQuickformFieldMetadata(array $allFields): array {
    return array_map(function ($field) {
      if ($field['htmlType'] === 'select') {
        $field['options'] = array_map(fn ($key) => ['id' => $key, 'label' => $field['attributes'][$key]], array_keys($field['attributes']));
      }
      unset($field['attributes'], $field['extra']);
      return $field;
    }, $allFields);
  }

  public static function getPaymentProcessorPairs(array $paymentProcessorTypeNames): array {
    $all = \Civi\Api4\PaymentProcessor::get(FALSE)
      // note the payment_processor_type_id doesn't actually matter when it comes to
      // CheckoutOptions - as long as the credentials are valid
      ->addWhere('payment_processor_type_id:name', 'IN', $paymentProcessorTypeNames)
      ->addWhere('is_test', 'IN', [TRUE, FALSE])
      ->execute();

    $pairs = [];

    foreach ($all as $processor) {
      $pairs[$processor['name']][$processor['is_test'] ? 'test' : 'live'] = $processor;
    }

    return $pairs;
  }

}
