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
  public function clear(): Router {
    \CRM_Core_Menu::clear();
    return $this;
  }

  /**
   * Rebuild the routing table.
   *
   * This is costly so please consider using `clear` instead
   */
  public function rebuild(): Router {
    \CRM_Core_Menu::rebuild();
    return $this;
  }

  /**
   * Get route for a path from the routing table.
   *
   * @param string $path e.g. civicrm/mailing/subscribe
   *
   * @return ?array best matching route, or NULL
   */
  public function get(string $path): ?array {
    return \CRM_Core_Menu::get($path);
  }

}
