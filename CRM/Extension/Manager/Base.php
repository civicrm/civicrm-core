<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.3                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2013                                |
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
 * @copyright CiviCRM LLC (c) 2004-2013
 * $Id$
 *
 */
class CRM_Extension_Manager_Base implements CRM_Extension_Manager_Interface {

  /**
   * @var bool hether to automatically uninstall and install during 'replace'
   */
  public $autoReplace;

  /**
   * @param bool $autoReplace whether to automatically uninstall and install during 'replace'
   */
  public function __construct($autoReplace = FALSE) {
    $this->autoReplace = $autoReplace;
  }

  /**
   * {@inheritdoc}
   */
  public function onPreInstall(CRM_Extension_Info $info) {
  }

  /**
   * {@inheritdoc}
   */
  public function onPostInstall(CRM_Extension_Info $info) {
  }

  /**
   * {@inheritdoc}
   */
  public function onPreEnable(CRM_Extension_Info $info) {
  }

  /**
   * {@inheritdoc}
   */
  public function onPostEnable(CRM_Extension_Info $info) {
  }

  /**
   * {@inheritdoc}
   */
  public function onPreDisable(CRM_Extension_Info $info) {
  }

  /**
   * {@inheritdoc}
   */
  public function onPostDisable(CRM_Extension_Info $info) {
  }

  /**
   * {@inheritdoc}
   */
  public function onPreUninstall(CRM_Extension_Info $info) {
  }

  /**
   * {@inheritdoc}
   */
  public function onPostUninstall(CRM_Extension_Info $info) {
  }

  /**
   * {@inheritdoc}
   */
  public function onPreReplace(CRM_Extension_Info $oldInfo, CRM_Extension_Info $newInfo) {
    if ($this->autoReplace) {
      $this->onPreUninstall($oldInfo);
      $this->onPostUninstall($oldInfo);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function onPostReplace(CRM_Extension_Info $oldInfo, CRM_Extension_Info $newInfo) {
    if ($this->autoReplace) {
      $this->onPreInstall($oldInfo);
      $this->onPostInstall($oldInfo);
    }
  }
}
