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

use Civi\Api4\Generic\Result;

/**
 * @inheritDoc
 */
class Get extends \Civi\Api4\Generic\DAOGetAction {

  /**
   * @inheritDoc
   */
  protected function getObjects(Result $result) {
    parent::getObjects($result);

    $locale = $this->getLanguage();
    if ($locale && $locale != \CRM_Core_I18n::getLocale()) {
      \CRM_Core_I18n::singleton()->setLocale($locale);
      $localeToRestore = $locale;
    }
    foreach ($result as &$sd) {
      foreach ($sd['settings']['columns'] as &$column) {
        if (!empty($column['label'])) {
          $column['label'] = _ts(trim($column['label']));
        }
      }
    }
    if ($localeToRestore) {
      \CRM_Core_I18n::singleton()->setLocale($localeToRestore);
    }
  }

}