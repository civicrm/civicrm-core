<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2015                                |
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
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2015
 * $Id$
 *
 */

/**
 * This class is a temporary place to store default setting values
 * before they will be distributed in proper places (component configurations
 * and core configuration). The name is intentionally stupid so that it will be fixed
 * ASAP.
 *
 */
class CRM_Core_Config_Defaults {

  /**
   * Set the default values.
   * in an empty db, also called when setting component using GUI
   *
   * @param array $defaults
   *   Associated array of form elements.
   * @param bool $formMode
   *   this variable is set true for GUI
   *   mode (eg: Global setting >> Components)
   *
   */
  public static function setValues(&$defaults, $formMode = FALSE) {
  }

  public static function getCustomCssUrl($k = NULL) {
    return Civi::settings()->getUrl('customCSSURL', 'absolute');
  }

  public static function getCustomFileUploadDir($k = NULL) {
    $settings = Civi::settings();
    $value = $settings->getPath('customFileUploadDir');
    if (empty($value)) {
      $defaultFileStorage = CRM_Core_Config::singleton()->userSystem->getDefaultFileStorage();
      $value = $settings->filterPath($defaultFileStorage['path'] . "custom/");
    }
    $value = CRM_Utils_File::addTrailingSlash($value);
    CRM_Utils_File::createDir($value);
    CRM_Utils_File::restrictAccess($value);
    return $value;
  }


  public static function getCustomPhpPathDir($k = NULL) {
    return Civi::settings()->getPath('customPHPPathDir');
  }

  public static function getCustomTemplateDir($k = NULL) {
    return Civi::settings()->getPath('customTemplateDir');
  }

  public static function getExtensionsUrl($k = NULL) {
    return Civi::settings()->getUrl('extensionsURL', 'absolute');
  }

  public static function getExtensionsDir($k = NULL) {
    return Civi::settings()->getPath('extensionsDir');
  }

  public static function getImageUploadDir($k = NULL) {
    $settings = Civi::settings();
    $value = $settings->getPath('imageUploadDir');
    if (empty($value)) {
      $defaultFileStorage = CRM_Core_Config::singleton()->userSystem->getDefaultFileStorage();
      $value = $settings->filterPath($defaultFileStorage['path'] . "persist/contribute/");
    }
    $value = CRM_Utils_File::addTrailingSlash($value);
    CRM_Utils_File::createDir($value);
    return $value;
  }

  public static function getImageUploadUrl($k = NULL) {
    $settings = Civi::settings();
    $imageUploadURL = $settings->getUrl('imageUploadURL', 'absolute');
    if (empty($imageUploadURL)) {
      $defaultFileStorage = CRM_Core_Config::singleton()->userSystem->getDefaultFileStorage();
      $imageUploadURL = $settings->filterUrl($defaultFileStorage['url'] . 'persist/contribute/', 'absolute');
    }
    return $imageUploadURL;
  }

  public static function getUploadDir($k = NULL) {
    $settings = Civi::settings();
    $value = $settings->getPath('uploadDir');
    if (empty($value)) {
      $defaultFileStorage = CRM_Core_Config::singleton()->userSystem->getDefaultFileStorage();
      $value = $settings->filterPath($defaultFileStorage['path'] . "upload/");
    }
    $value = CRM_Utils_File::addTrailingSlash($value);
    CRM_Utils_File::createDir($value);
    CRM_Utils_File::restrictAccess($value);
    return $value;
  }

  public static function getUserFrameworkResourceUrl($k = NULL) {
    $settings = Civi::settings();
    $url = $settings->getUrl('userFrameworkResourceURL', 'absolute');
    if (empty($url)) {
      $config = CRM_Core_Config::singleton();
      $civiSource = $config->userSystem->getCiviSourceStorage();
      $url = $settings->filterUrl($civiSource['url'], 'absolute');
    }
    return $url;
  }

  public static function getResourceBase($k = NULL) {
    $settings = Civi::settings();
    $url = $settings->getUrl('userFrameworkResourceURL', 'relative');
    if (empty($url)) {
      $config = CRM_Core_Config::singleton();
      $civiSource = $config->userSystem->getCiviSourceStorage();
      $url = $settings->filterUrl($civiSource['url'], 'relative');
    }
    return $url;
  }

  public static function getDefaultCurrencySymbol($k = NULL) {
    $config = CRM_Core_Config::singleton();
    return $config->defaultCurrencySymbol(Civi::settings()->get('defaultCurrency'));
  }

  public static function setPath($key, $value) {
    Civi::settings()->setPath($key, $value);
  }

  public static function setUrl($key, $value) {
    Civi::settings()->setPath($key, $value);
  }

  public static function revert($key) {
    Civi::settings()->revert($key);
  }

}
