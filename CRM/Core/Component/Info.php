<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.5                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2014                                |
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
 * This interface defines methods that need to be implemented
 * for a component to introduce itself to the system.
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2014
 * $Id$
 *
 */

abstract class CRM_Core_Component_Info {

  /*
     * Name of the class (minus component namespace path)
     * of the component invocation class'es name.
     */
  CONST COMPONENT_INVOKE_CLASS = 'Invoke';

  /*
     * Name of the class (minus component namespace path)
     * of the component configuration class'es name.
     */
  CONST COMPONENT_CONFIG_CLASS = 'Config';

  /*
     * Name of the class (minus component namespace path)
     * of the component BAO Query class'es name.
     */
  CONST COMPONENT_BAO_QUERY_CLASS = 'BAO_Query';

  /*
     * Name of the class (minus component namespace path)
     * of the component user dashboard plugin.
     */
  CONST COMPONENT_USERDASHBOARD_CLASS = 'Page_UserDashboard';

  /*
     * Name of the class (minus component namespace path)
     * of the component tab offered to contact record view.
     */
  CONST COMPONENT_TAB_CLASS = 'Page_Tab';

  /*
     * Name of the class (minus component namespace path)
     * of the component tab offered to contact record view.
     */
  CONST COMPONENT_ADVSEARCHPANE_CLASS = 'Form_Search_AdvancedSearchPane';

  /*
     * Name of the directory (assumed in component directory)
     * where xml resources used by this component live.
     */
  CONST COMPONENT_XML_RESOURCES = 'xml';

  /*
     * Name of the directory (assumed in xml resources path)
     * containing component menu definition XML file names.
     */
  CONST COMPONENT_MENU_XML = 'Menu';

  /*
   * Stores component information.
   * @var array component settings as key/value pairs
   */
  public $info;

  /*
   * Stores component keyword
   * @var string name of component keyword
   */
  protected $keyword;

  /*
   * Class constructor, sets name and namespace (those are stored
   * in the component registry (database) and no need to duplicate
   * them here, as well as populates the info variable.
   *
   * @param string $name name of the component
   * @param string $namespace namespace prefix for component's files
   * @access public
   *
   */
  /**
   * @param $name
   * @param $namespace
   * @param $componentID
   */
  public function __construct($name, $namespace, $componentID) {
    $this->name        = $name;
    $this->namespace   = $namespace;
    $this->componentID = $componentID;
    $this->info        = $this->getInfo();
    $this->info['url'] = $this->getKeyword();
  }

  /**
   * EXPERIMENTAL: Get a list of AngularJS modules
   *
   * @return array list of modules; same format as CRM_Utils_Hook::angularModules(&$angularModules)
   * @see CRM_Utils_Hook::angularModules
   */
  public function getAngularModules() {
    return array();
  }

  /**
   * Provides base information about the component.
   * Needs to be implemented in component's information
   * class.
   *
   * @return array collection of required component settings
   * @access public
   *
   */
  abstract public function getInfo();

  /**
   * Get a list of entities to register via API
   *
   * @return array list of entities; same format as CRM_Utils_Hook::managedEntities(&$entities)
   * @see CRM_Utils_Hook::managedEntities
   */
  public function getManagedEntities() {
    return array();
  }

  /**
   * Provides permissions that are unwise for Anonymous Roles to have
   *
   * @return array list of permissions
   * @see CRM_Component_Info::getPermissions
   */
  public function getAnonymousPermissionWarnings() {
    return array();
  }

  /**
   * Provides permissions that are used by component.
   * Needs to be implemented in component's information
   * class.
   *
   * NOTE: if using conditionally permission return,
   * implementation of $getAllUnconditionally is required.
   *
   * @param bool $getAllUnconditionally
   *
   * @return array|null collection of permissions, null if none
   * @access public
   */
  abstract public function getPermissions($getAllUnconditionally = FALSE);

  /**
   * Determine how many other records refer to a given record
   *
   * @param CRM_Core_DAO $dao the item for which we want a reference count
   * @return array each item in the array is an array with keys:
   *   - name: string, eg "sql:civicrm_email:contact_id"
   *   - type: string, eg "sql"
   *   - count: int, eg "5" if there are 5 email addresses that refer to $dao
   */
  public function getReferenceCounts($dao) {
    return array();
  }

  /**
   * Provides information about user dashboard element
   * offered by this component.
   *
   * @return array|null collection of required dashboard settings,
   *                    null if no element offered
   * @access public
   *
   */
  abstract public function getUserDashboardElement();

  /**
   * Provides information about user dashboard element
   * offered by this component.
   *
   * @return array|null collection of required dashboard settings,
   *                    null if no element offered
   * @access public
   *
   */
  abstract public function registerTab();

  /**
   * Provides information about advanced search pane
   * offered by this component.
   *
   * @return array|null collection of required pane settings,
   *                    null if no element offered
   * @access public
   *
   */
  abstract public function registerAdvancedSearchPane();

  /**
   * Provides potential activity types that this
   * component might want to register in activity history.
   * Needs to be implemented in component's information
   * class.
   *
   * @return array|null collection of activity types
   * @access public
   *
   */
  abstract public function getActivityTypes();

  /**
   * Provides information whether given component is currently
   * marked as enabled in configuration.
   *
   * @return boolean true if component is enabled, false if not
   * @access public
   *
   */
  public function isEnabled() {
    $config = CRM_Core_Config::singleton();
    if (in_array($this->info['name'], $config->enableComponents)) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Provides component's configuration object.
   *
   * @return mixed component's configuration object
   * @access public
   *
   */
  public function getConfigObject() {
    return $this->_instantiate(self::COMPONENT_CONFIG_CLASS);
  }

  /**
   * Provides component's menu definition object.
   *
   * @return mixed component's menu definition object
   * @access public
   *
   */
  public function getMenuObject() {
    return $this->_instantiate(self::COMPONENT_MENU_CLASS);
  }

  /**
   * Provides component's invocation object.
   *
   * @return mixed component's invocation object
   * @access public
   *
   */
  public function getInvokeObject() {
    return $this->_instantiate(self::COMPONENT_INVOKE_CLASS);
  }

  /**
   * Provides component's BAO Query object.
   *
   * @return mixed component's BAO Query object
   * @access public
   *
   */
  public function getBAOQueryObject() {
    return $this->_instantiate(self::COMPONENT_BAO_QUERY_CLASS);
  }

  /**
   * Builds advanced search form's component specific pane.
   *
   * @access public
   *
   */
  public function buildAdvancedSearchPaneForm(&$form) {
    $bao = $this->getBAOQueryObject();
    $bao->buildSearchForm($form);
  }

  /**
   * Provides component's user dashboard page object.
   *
   * @return mixed component's User Dashboard applet object
   * @access public
   *
   */
  public function getUserDashboardObject() {
    return $this->_instantiate(self::COMPONENT_USERDASHBOARD_CLASS);
  }

  /**
   * Provides component's contact record tab object.
   *
   * @return mixed component's contact record tab object
   * @access public
   *
   */
  public function getTabObject() {
    return $this->_instantiate(self::COMPONENT_TAB_CLASS);
  }

  /**
   * Provides component's advanced search pane's template path.
   *
   * @return string component's advanced search pane's template path
   * @access public
   *
   */
  public function getAdvancedSearchPaneTemplatePath() {
    $fullpath = $this->namespace . '_' . self::COMPONENT_ADVSEARCHPANE_CLASS;
    return str_replace('_', DIRECTORY_SEPARATOR, $fullpath . '.tpl');
  }

  /**
   * Provides information whether given component uses system wide search.
   *
   * @return boolean true if component needs search integration
   * @access public
   *
   */
  public function usesSearch() {
    return $this->info['search'] ? TRUE : FALSE;
  }

  /**
   * Provides the xml menu files
   *
   * @return array array of menu files
   * @access public
   *
   */
  public function menuFiles() {
    return CRM_Utils_File::getFilesByExtension($this->_getMenuXMLPath(), 'xml');
  }

  /**
   * Simple "keyword" getter.
   * FIXME: It should be protected so the keyword is not
   * FIXME: accessed from beyond component infrastructure.
   *
   * @return string component keyword
   * @access public
   *
   */
  public function getKeyword() {
    return $this->keyword;
  }

  /**
   * Helper for figuring out menu XML file location.
   *
   * @return mixed component's element as class instance
   * @access private
   *
   */
  private function _getMenuXMLPath() {
    global $civicrm_root;
    $fullpath = $this->namespace . '_' . self::COMPONENT_XML_RESOURCES . '_' . self::COMPONENT_MENU_XML;
    return CRM_Utils_File::addTrailingSlash($civicrm_root . DIRECTORY_SEPARATOR . str_replace('_', DIRECTORY_SEPARATOR, $fullpath));
  }

  /**
   * Helper for instantiating component's elements.
   *
   * @param $cl
   *
   * @return mixed component's element as class instance
   * @access private
   */
  private function _instantiate($cl) {
    $className = $this->namespace . '_' . $cl;
    require_once (str_replace('_', DIRECTORY_SEPARATOR, $className) . '.php');
    return new $className();
  }
}

