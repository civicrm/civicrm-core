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
use Civi\Token\TokenRow;
use Civi\Token\Event\TokenValueEvent;

/**
 * Class CRM_Contribute_ContributionProductTokens
 *
 * Generate "contribution_product.*" tokens.
 */
class CRM_Contribute_ContributionProductTokens extends CRM_Core_EntityTokens {

  /**
   * Get the entity name for api v4 calls.
   *
   * @return string
   */
  protected function getApiEntityName(): string {
    return 'ContributionProduct';
  }

  /**
   * Get Related Entity tokens.
   *
   * @return array[]
   */
  protected function getRelatedTokens(): array {
    $tokens = [];
    // Check to make sure CiviContribute is enabled, just in case it remains registered. Eventually this will be moved to the CiviContribute extension
    // and this check can hopefully be removed (as long as caching on enable / disable doesn't explode our brains and / or crash the site).
    if (!array_key_exists('Contribution', \Civi::service('action_object_provider')->getEntities())) {
      return $tokens;
    }
    $tokens += $this->getRelatedTokensForEntity('Product', 'product_id', ['name', 'sku']);
    return $tokens;
  }

  /**
   * @param \Civi\Token\TokenRow $row
   * @param string $field
   * @return string|int
   */
  protected function getFieldValue(TokenRow $row, string $field) {
    if ($field === 'product_option:label') {
      // Per PR comments - this should almost certainly have been option values
      // https://github.com/civicrm/civicrm-core/pull/29691
      // However, given the choice made at the time it would require undoing all that
      // and re-doing to expose the values 'well' as tokens. So here we imitate what the api
      // would ideally do (contribution_product.product_option:name, contribution_product.product_option:label}
      // Note that to do this conversion we need to know the product_id - which we do in
      // this token context, lest so in a generic api situation.
      // This has test cover..
      $productOption = $this->getFieldValue($row, 'product_option');
      $productOptions = $this->getFieldValue($row, 'product_id.options');
      return is_array($productOptions) ? $productOptions[$productOption] : '';
    }
    return parent::getFieldValue($row, $field);
  }

  protected function getBespokeTokens(): array {
    return [
      'product_option:label' => [
        'title' => ts('Product Option'),
        'name' => 'product_option:label',
        'type' => 'calculated',
        'options' => NULL,
        'data_type' => 'string',
      ],
    ];
  }

  /**
   * Get the fields required to prefetch the entity.
   *
   * @param \Civi\Token\Event\TokenValueEvent $e
   *
   * @return array
   * @throws \CRM_Core_Exception
   */
  public function getPrefetchFields(TokenValueEvent $e): array {
    $return = parent::getPrefetchFields($e);
    // Api doesn't actually handle this field - flow on from decision not to make it an
    // option value.
    foreach ($return as $index => $value) {
      if ($value === 'product_option:label') {
        $return[$index] = 'product_id.options';
      }
    }
    return $return;
  }

}
