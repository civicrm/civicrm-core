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

namespace Civi\Api4\Action\CustomValue;

/**
 * Get fields for a custom group.
 */
class GetFields extends \Civi\Api4\Generic\DAOGetFieldsAction {
  use \Civi\Api4\Generic\Traits\CustomValueActionTrait;

  protected function getRecords() {
    $fields = $this->_itemsToGet('name');
    /** @var \Civi\Api4\Service\Spec\SpecGatherer $gatherer */
    $gatherer = \Civi::container()->get('spec_gatherer');
    $spec = $gatherer->getSpec('Custom_' . $this->getCustomGroup(), $this->getAction(), TRUE, $this->values);
    return $this->specToArray($spec->getFields($fields));
  }

}
