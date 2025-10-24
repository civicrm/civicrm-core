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
  
  protected function getObjects(Result $result) {
    parent::getObjects($result);
    if (!\CRM_Core_I18n::isMultiLingual()) return;

    $locale = $this->getLanguage();
    if ($locale && $locale != \CRM_Core_I18n::getLocale()) {
      \CRM_Core_I18n::singleton()->setLocale($locale);
      $localeToRestore = $locale;
    }
    foreach ($result as &$sd) {
      self::ts($sd, 'label');
      foreach ($sd['settings']['columns'] as &$column) {
        self::ts($column, 'label');
        if ($column['links']) {
          foreach ($column['links'] as $idx => &$link) {
            self::ts($link, 'text');
          }
        }
      }
      if ($sd['settings']['toolbar']) {
        foreach ($sd['settings']['toolbar'] as $idx => &$link) {
          self::ts($link, 'text');
        }
      }
    }
    if ($localeToRestore) {
      \CRM_Core_I18n::singleton()->setLocale($localeToRestore);
    }
  }

  static private function ts(&$item, $keyName) {
    if (!empty($item[$keyName])) {
      $item[$keyName] = _ts(trim($item[$keyName]));
    }
  }

}