<?php

/**
 * An "upgrader" is a class that handles the DB install+upgrade lifecycle
 * for an extension.
 */
interface CRM_Extension_Upgrader_Interface {

  /**
   * @param array $params
   *   - string $key: Long form name ('org.example.myext')
   */
  public function init(array $params);

  /**
   * Notify the upgrader about a key lifecycle event, such as installation or uninstallation.
   *
   * Each event corresponds to a hook, such as `hook_civicrm_install` or `hook_civicrm_upgrade`.
   *
   * @param string $event
   *   One of the following: 'install', 'onPostInstall', 'enable', 'disable', 'uninstall', 'upgrade'
   * @param array $params
   *   Any data that would ordinarily be provided via the equivalent hook.
   *
   * @return mixed
   */
  public function notify(string $event, array $params = []);

}
