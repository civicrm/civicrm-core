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

namespace Civi\Token;

/**
 * Trait for registering and evaluating APIv4-style custom field tokens.
 */
trait Api4CustomTokenTrait {

  /**
   * Add custom field tokens in APIv4 format (Group.Field) rather than legacy custom_N.
   *
   * @param array $tokensMetadata
   * @param array $field
   * @param array $exposedFields
   * @param string $prefix
   */
  protected function addFieldToTokenMetadata(array &$tokensMetadata, array $field, array $exposedFields, string $prefix = ''): void {
    if ($field['type'] === 'Custom') {
      $field['audience'] ??= 'user';
      $tokenName = $field['name'];

      $parts = explode(': ', $field['label'], 2);
      $field['title'] = isset($parts[1]) ? "{$parts[1]} :: {$parts[0]}" : $field['label'];
      $tokensMetadata[$tokenName] = $field;

      if (!empty($field['options']) || !empty($field['suffixes'])) {
        $tokensMetadata[$tokenName . ':label'] = array_merge($field, [
          'name' => $tokenName . ':label',
          'title' => $field['title'],
        ]);
        $tokensMetadata[$tokenName . ':name'] = array_merge($field, [
          'name' => $tokenName . ':name',
          'title' => \ts('Machine name') . ': ' . $field['title'],
          'audience' => 'sysadmin',
        ]);
      }

      $fkEntity = $field['fk_entity'] ?? ($field['data_type'] === 'ContactReference' ? 'Contact' : NULL);
      if ($fkEntity) {
        $relatedTokens = $this->getRelatedTokensForEntity($fkEntity, $tokenName, ['*']);
        foreach ($relatedTokens as $relTokenName => $relTokenSpec) {
          $tokensMetadata[$relTokenName] = $relTokenSpec;
        }
      }
      return;
    }

    parent::addFieldToTokenMetadata($tokensMetadata, $field, $exposedFields, $prefix);
  }

}
