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

namespace Civi\Schema\Entity;

use Civi\Schema\SqlEntityMetadata;

class AddressMetadata extends SqlEntityMetadata {

  public function getOptions(string $fieldName, array $values = [], bool $includeDisabled = FALSE, bool $checkPermissions = FALSE, ?int $userId = NULL): ?array {
    $options = parent::getOptions($fieldName, $values, $includeDisabled, $checkPermissions, $userId);
    if ($fieldName == 'country_id') {
      // The general idea is call the function that does all the stuff, but it
      // wants a different format, so we convert, then merge back in the
      // original format data.
      $map = [];
      $originalReindexed = [];
      foreach ($options as $opt) {
        $map[$opt['id']] = $opt['label'];
        // It's way more efficient later to be able to get the original by id.
        // It's currently indexed sequentially.
        $originalReindexed[$opt['id']] = $opt;
      }

      // At the moment it's unsorted. The pre-entity output used the db for
      // sorting. We don't know what all the local mysql settings are set to,
      // but the strings right now are still all en_US, and it will get
      // re-sorted in a minute according to locale if the civi locale is
      // different anyway, so use en_US.
      // If we just use regular asort() here, then e.g. Åland Islands is wrong.
      $collator = new \Collator('en_US.utf8');
      $collator->asort($map);

      // Do all the things
      $map = \CRM_Core_BAO_Country::_defaultContactCountries($map);

      // Now merge the format it wants back in
      $newOptions = [];
      foreach ($map as $id => $possiblyTranslatedLabel) {
        // We may have translated the label, so we want that label.
        $newOptions[] = array_merge($originalReindexed[$id], ['label' => $possiblyTranslatedLabel]);
      }
      $options = $newOptions;
    }
    return $options;
  }

}
