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
 * This interface defines methods that need to be implemented
 * for a component to introduce itself to the system.
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */
abstract class CRM_Core_Component_Info {

  /**
   * Name of the class (minus component namespace path)
   * of the component invocation class'es name.
   */
  const COMPONENT_INVOKE_CLASS = 'Invoke';

  /**
   * Name of the class (minus component namespace path)
   * of the component BAO Query class'es name.
   */
  const COMPONENT_BAO_QUERY_CLASS = 'BAO_Query';

  /**
   * Name of the class (minus component namespace path)
   * of the component user dashboard plugin.
   */
  const COMPONENT_USERDASHBOARD_CLASS = 'Page_UserDashboard';

  /**
   * Name of the class (minus component namespace path)
   * of the component tab offered to contact record view.
   */
  const COMPONENT_TAB_CLASS = 'Page_Tab';

  /**
   * Name of the class (minus component namespace path)
   * of the component tab offered to contact record view.
   */
  const COMPONENT_ADVSEARCHPANE_CLASS = 'Form_Search_AdvancedSearchPane';

  /**
   * Name of the directory (assumed in component directory)
   * where xml resources used by this component live.
   */
  const COMPONENT_XML_RESOURCES = 'xml';

  /**
   * Name of the directory (assumed in xml resources path)
   * containing component menu definition XML file names.
   */
  const COMPONENT_MENU_XML = 'Menu';

  /**
   * Component settings as key/value pairs.
   *
   * @var array{name: string, translatedName: string, title: string, search: bool, showActivitiesInCore: bool, url: string}
   */
  public $info;

  /**
   * Component keyword.
   *
   * @var string
   */
  protected $keyword;

  /**
   * Component Name.
   *
   * @var string
   */
  public $name;

  /**
   * Component namespace.
   * e.g. CRM_Contribute.
   *
   * @var string
   */
  public $namespace;

  /**
   * Component ID
   * @var int
   */
  public $componentID;

  /**
   * @param string $name
   *   Name of the component.
   * @param string $namespace
   *   Namespace prefix for component's files.
   * @param int $componentID
   */
  public function __construct($name, $namespace, $componentID) {
    $this->name = $name;
    $this->namespace = $namespace;
    $this->componentID = $componentID;
    $this->info = $this->getInfo();
    $this->info['url'] = $this->getKeyword();
  }

  /**
   * Name of the module-extension coupled with this component
   * @return string
   */
  public function getExtensionName(): string {
    return CRM_Utils_String::convertStringToSnakeCase($this->name);
  }

  /**
   * Provides base information about the component.
   * Needs to be implemented in component's information
   * class.
   *
   * @return array
   *   collection of required component settings
   */
  abstract public function getInfo();

  /**
   * Provides permissions that are unwise for Anonymous Roles to have.
   *
   * @return array
   *   list of permissions
   * @see CRM_Component_Info::getPermissions
   */
  public function getAnonymousPermissionWarnings() {
    return [];
  }

  /**
   * Defines permissions that are used by component.
   *
   * @return array
   */
  abstract public function getPermissions();

  /**
   * Provides information about user dashboard element.
   * offered by this component.
   *
   * @return array|null
   *   collection of required dashboard settings,
   *                    null if no element offered
   */
  abstract public function getUserDashboardElement();

  /**
   * Provides information about user dashboard element.
   * offered by this component.
   *
   * @return array|null
   *   collection of required dashboard settings,
   *                    null if no element offered
   */
  abstract public function registerTab();

  /**
   * Get icon font class representing this component.
   *
   * @return string
   */
  public function getIcon() {
    return 'crm-i fa-puzzle-piece';
  }

  /**
   * Provides information about advanced search pane
   * offered by this component.
   *
   * @return array|null
   *   collection of required pane settings,
   *                    null if no element offered
   */
  abstract public function registerAdvancedSearchPane();

  /**
   * Provides potential activity types that this
   * component might want to register in activity history.
   * Needs to be implemented in component's information
   * class.
   *
   * @return array|null
   *   collection of activity types
   */
  abstract public function getActivityTypes();

  /**
   * Provides information whether given component is currently
   * marked as enabled in configuration.
   *
   * @return bool
   *   true if component is enabled, false if not
   */
  public function isEnabled() {
    return CRM_Core_Component::isEnabled($this->info['name']);
  }

  /**
   * Provides component's invocation object.
   *
   * @return mixed
   *   component's invocation object
   */
  public function getInvokeObject() {
    return $this->_instantiate(self::COMPONENT_INVOKE_CLASS);
  }

  /**
   * Provides component's BAO Query object.
   *
   * @return mixed
   *   component's BAO Query object
   */
  public function getBAOQueryObject() {
    return $this->_instantiate(self::COMPONENT_BAO_QUERY_CLASS);
  }

  /**
   * Builds advanced search form's component specific pane.
   *
   * @param CRM_Core_Form $form
   */
  public function buildAdvancedSearchPaneForm(&$form) {
    $bao = $this->getBAOQueryObject();
    $bao->buildSearchForm($form);
  }

  /**
   * Provides component's user dashboard page object.
   *
   * @return mixed
   *   component's User Dashboard applet object
   */
  public function getUserDashboardObject() {
    return $this->_instantiate(self::COMPONENT_USERDASHBOARD_CLASS);
  }

  /**
   * Provides component's contact record tab object.
   *
   * @return mixed
   *   component's contact record tab object
   */
  public function getTabObject() {
    return $this->_instantiate(self::COMPONENT_TAB_CLASS);
  }

  /**
   * Provides component's advanced search pane's template path.
   *
   * @return string
   *   component's advanced search pane's template path
   */
  public function getAdvancedSearchPaneTemplatePath() {
    $fullpath = $this->namespace . '_' . self::COMPONENT_ADVSEARCHPANE_CLASS;
    return str_replace('_', DIRECTORY_SEPARATOR, $fullpath . '.tpl');
  }

  /**
   * Provides information whether given component uses system wide search.
   *
   * @return bool
   *   true if component needs search integration
   */
  public function usesSearch() {
    return (bool) $this->info['search'];
  }

  /**
   * Provides the xml menu files.
   *
   * @return array
   *   array of menu files
   */
  public function menuFiles() {
    return CRM_Utils_File::getFilesByExtension($this->_getMenuXMLPath(), 'xml');
  }

  /**
   * Simple "keyword" getter.
   * FIXME: It should be protected so the keyword is not
   * FIXME: accessed from beyond component infrastructure.
   *
   * @return string
   *   component keyword
   */
  public function getKeyword() {
    return $this->keyword;
  }

  /**
   * Helper for figuring out menu XML file location.
   *
   * @return mixed
   *   component's element as class instance
   */
  private function _getMenuXMLPath() {
    global $civicrm_root;
    $fullpath = $this->namespace . '_' . self::COMPONENT_XML_RESOURCES . '_' . self::COMPONENT_MENU_XML;
    return CRM_Utils_File::addTrailingSlash($civicrm_root . DIRECTORY_SEPARATOR . str_replace('_', DIRECTORY_SEPARATOR, $fullpath));
  }

  /**
   * Helper for instantiating component's elements.
   *
   * @param string $cl
   *
   * @return mixed
   *   component's element as class instance
   */
  private function _instantiate($cl) {
    $className = $this->namespace . '_' . $cl;
    return new $className();
  }

}
