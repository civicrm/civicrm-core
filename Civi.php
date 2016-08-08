<?php

/**
 * Class Civi
 *
 * The "Civi" class provides a facade for accessing major subsystems,
 * such as the service-container and settings manager. It serves as a
 * bridge which allows procedural code to access important objects.
 *
 * General principles:
 *  - Each function provides access to a major subsystem.
 *  - Each function performs a simple lookup.
 *  - Each function returns an interface.
 *  - Whenever possible, interfaces should be well-known (e.g. based
 *    on a standard or well-regarded provider).
 */
class Civi {

  /**
   * A central location for static variable storage.
   *
   * @code
   * `Civi::$statics[__CLASS__]['foo'] = 'bar';
   * @endcode
   */
  public static $statics = array();

  /**
   * Get the service container.
   *
   * @return \Symfony\Component\DependencyInjection\ContainerInterface
   */
  public static function container() {
    return Civi\Core\Container::singleton();
  }

  /**
   * Fetch a service from the container.
   *
   * @param string $id
   *   The service ID.
   * @return mixed
   */
  public static function service($id) {
    return \Civi\Core\Container::singleton()->get($id);
  }

  /**
   * Reset all ephemeral system state, e.g. statics,
   * singletons, containers.
   */
  public static function reset() {
    self::$statics = array();
    Civi\Core\Container::singleton();
  }

  /**
   * @return CRM_Core_Resources
   */
  public static function resources() {
    return CRM_Core_Resources::singleton();
  }

}
