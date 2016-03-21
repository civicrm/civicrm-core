<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2016                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
 */

/**
 * The extension manager handles installing, disabling enabling, and
 * uninstalling extensions.
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2016
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
