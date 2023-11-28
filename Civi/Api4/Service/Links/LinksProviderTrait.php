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

namespace Civi\Api4\Service\Links;

trait LinksProviderTrait {

  /**
   * Given a set of links, find the one with the specified ui_action
   *
   * @param $links
   * @param $uiAction
   * @return int|null
   */
  protected static function getActionIndex($links, $uiAction): ?int {
    foreach ($links as $index => $link) {
      if (($link['ui_action'] ?? NULL) === $uiAction || str_contains($link['path'] ?? '', "action=$uiAction")) {
        return $index;
      }
    }
    return NULL;
  }

}
