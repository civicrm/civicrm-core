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
      $map = [];
      foreach ($options as $opt) {
        $map[$opt['id']] = $opt['label'];
      }
      // This sorting isn't identical to the pre-entity output because it used
      // db rules whereas this is php, e.g. Åland Islands
      asort($map);
      $map = \CRM_Core_BAO_Country::_defaultContactCountries($map);
      // Now merge the format it wants back in
      $newOptions = [];
      foreach ($map as $id => $country) {
        // There must be a better algorithm. Maybe reindex the original first so can just pull by key?
        foreach ($options as $opt) {
          if ($opt['id'] == $id) {
            $newOptions[] = $opt;
            break;
          }
        }
      }
      $options = $newOptions;
    }
    return $options;
  }

}
