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

/**
 * The extension manager handles installing, disabling enabling, and
 * uninstalling extensions.
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */
interface CRM_Extension_Manager_Interface {

  /**
   * Perform type-specific installation logic (before marking the
   * extension as installed or clearing the caches).
   *
   * @param CRM_Extension_Info $info
   */
  public function onPreInstall(CRM_Extension_Info $info);

  /**
   * Perform type-specific installation logic (after marking the
   * extension as installed but before clearing the caches).
   *
   * @param CRM_Extension_Info $info
   */
  public function onPostInstall(CRM_Extension_Info $info);

  /**
   * Perform type-specific installation logic (after marking the
   * extension as installed and clearing the caches).
   *
   * @param CRM_Extension_Info $info
   */
  public function onPostPostInstall(CRM_Extension_Info $info);

  /**
   * @param CRM_Extension_Info $info
   */
  public function onPreEnable(CRM_Extension_Info $info);

  /**
   * @param CRM_Extension_Info $info
   */
  public function onPostEnable(CRM_Extension_Info $info);

  /**
   * Perform type-specific removal logic (before updating the extension
   * row in the "civicrm_extension" table).
   *
   * @param CRM_Extension_Info $info
   *   May be generated from xml or DB (which is lossy).
   * @see CRM_Extension_Manager::createInfoFromDB
   */
  public function onPreDisable(CRM_Extension_Info $info);

  /**
   * Perform type-specific removal logic (after updating the extension
   * row in the "civicrm_extension" table).
   *
   * @param CRM_Extension_Info $info
   *   May be generated from xml or DB (which is lossy).
   * @see CRM_Extension_Manager::createInfoFromDB
   */
  public function onPostDisable(CRM_Extension_Info $info);

  /**
   * Perform type-specific removal logic (before removing the extension
   * row in the "civicrm_extension" table).
   *
   * @param CRM_Extension_Info $info
   *   May be generated from xml or DB (which is lossy).
   * @see CRM_Extension_Manager::createInfoFromDB
   */
  public function onPreUninstall(CRM_Extension_Info $info);

  /**
   * Perform type-specific removal logic (after removing the extension
   * row in the "civicrm_extension" table).
   *
   * @param CRM_Extension_Info $info
   *   May be generated from xml or DB (which is lossy).
   * @see CRM_Extension_Manager::createInfoFromDB
   */
  public function onPostUninstall(CRM_Extension_Info $info);

  /**
   * @param CRM_Extension_Info $oldInfo
   * @param CRM_Extension_Info $newInfo
   */
  public function onPreReplace(CRM_Extension_Info $oldInfo, CRM_Extension_Info $newInfo);

  /**
   * @param CRM_Extension_Info $oldInfo
   * @param CRM_Extension_Info $newInfo
   */
  public function onPostReplace(CRM_Extension_Info $oldInfo, CRM_Extension_Info $newInfo);

}
