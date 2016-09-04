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
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2016
 */

/**
 * Class CRM_Utils_Cache_SerializeCache
 */
class CRM_Utils_Cache_SerializeCache implements CRM_Utils_Cache_Interface {

  /**
   * The cache storage container, an array by default, stored in a file under templates
   */
  private $_cache;

  /**
   * Constructor.
   *
   * @param array $config
   *   An array of configuration params.
   *
   * @return \CRM_Utils_Cache_SerializeCache
   */
  public function __construct($config) {
    $this->_cache = array();
  }

  /**
   * @param $key
   *
   * @return string
   */
  public function fileName($key) {
    if (strlen($key) > 50) {
      return CIVICRM_TEMPLATE_COMPILEDIR . "CRM_" . md5($key) . ".php";
    }
    return CIVICRM_TEMPLATE_COMPILEDIR . $key . ".php";
  }

  /**
   * @param string $key
   *
   * @return mixed
   */
  public function get($key) {
    if (array_key_exists($key, $this->_cache)) {
      return $this->_cache[$key];
    }

    if (!file_exists($this->fileName($key))) {
      return;
    }
    $this->_cache[$key] = unserialize(substr(file_get_contents($this->fileName($key)), 8));
    return $this->_cache[$key];
  }

  /**
   * @param string $key
   * @param mixed $value
   */
  public function set($key, &$value) {
    if (file_exists($this->fileName($key))) {
      return;
    }
    $this->_cache[$key] = $value;
    file_put_contents($this->fileName($key), "<?php //" . serialize($value));
  }

  /**
   * @param string $key
   */
  public function delete($key) {
    if (file_exists($this->fileName($key))) {
      unlink($this->fileName($key));
    }
    unset($this->_cache[$key]);
  }

  /**
   * @param null $key
   */
  public function flush($key = NULL) {
    $prefix = "CRM_";
    if (!$handle = opendir(CIVICRM_TEMPLATE_COMPILEDIR)) {
      return; // die? Error?
    }
    while (FALSE !== ($entry = readdir($handle))) {
      if (substr($entry, 0, 4) == $prefix) {
        unlink(CIVICRM_TEMPLATE_COMPILEDIR . $entry);
      }
    }
    closedir($handle);
    unset($this->_cache);
    $this->_cache = array();
  }

}
