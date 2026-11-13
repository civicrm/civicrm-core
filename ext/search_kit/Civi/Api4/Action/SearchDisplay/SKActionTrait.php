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

namespace Civi\Api4\Action\SearchDisplay;

use Civi\Api4\Generic\Traits\DAOActionTrait;
use Civi\Search\Translator;

trait SKActionTrait {
  use DAOActionTrait;

  /**
   * Add i18n extraction to the original trait
   */
  protected function writeObjects($items) {
    $result = parent::writeObjects($items);
    Translator::updateSearchDisplaySources($result);
    return $result;
  }

}
