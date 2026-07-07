<?php

namespace Civi\Core;

use Civi\Core\Service\AutoService;

/**
 * @service civi.router
 */
class Router extends AutoService {

  /**
   * Clear the routing table.
   *
   * It will be rebuilt on next access
   */
  public function clear(): void {
    \CRM_Core_Menu::clear();
  }

  /**
   * Rebuild the routing table.
   *
   * This is costly so please consider using `clear` instead
   */
  public function rebuild(): void {
    \CRM_Core_Menu::store();
  }

  /**
   * Get route for a path from the routing table.
   *
   * @param string $path e.g. civicrm/mailing/subscribe
   *
   * @return array
   */
  public function get(string $path): array {
    return \CRM_Core_Menu::get($path);
  }

}
