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

class RelationshipCacheMetadata extends SqlEntityMetadata {

  public function getCustomFields(array $customGroupFilters = []): array {
    // Include relationship custom fields
    $customGroupFilters += ['extends' => 'Relationship'];
    return parent::getCustomFields($customGroupFilters);
  }

}
