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
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */

use Civi\Api4\Utils\ReflectionUtils;

/**
 * Base class for admin forms.
 */
class CRM_Admin_Form extends CRM_Core_Form {

  /**
   * The id of the object being edited / created
   *
   * @var int
   */
  public $_id;

  /**
   * The default values for form fields.
   *
   * @var array
   */
  protected $_values;

  /**
   * The name of the BAO object for this form.
   *
   * @var CRM_Core_DAO|string
   */
  protected $_BAOName;

  /**
   * Whether to use the legacy `retrieve` method or APIv4 to load values.
   * @var string
   */
  protected $retrieveMethod = 'retrieve';

  /**
   * Explicitly declare the form context.
   */
  public function getDefaultContext() {
    return 'create';
  }

  /**
   * Note: This type of form was traditionally embedded in a page, with values like _id and _action
   * being `set()` by the page controller.
   * Nowadays the preferred approach is to place these forms at their own url.
   * This function can handle either scenario. It will retrieve `id` either from a value stored by the page controller
   * if embedded, or from the url if standalone.
   */
  public function preProcess() {
    Civi::resources()->addStyleFile('civicrm', 'css/admin.css');
    Civi::resources()->addScriptFile('civicrm', 'js/jquery/jquery.crmIconPicker.js');

    // Lookup id from URL or stored value in controller
    $this->_id = CRM_Utils_Request::retrieve('id', 'Positive', $this);

    // If embedded in a page, this will have been assigned
    $this->_BAOName = $this->get('BAOName');
    // Otherwise, look it up from the api entity name
    if (!$this->_BAOName) {
      $this->_BAOName = CRM_Core_DAO_AllCoreTables::getBAOClassName(CRM_Core_DAO_AllCoreTables::getDAONameForEntity($this->getDefaultEntity()));
    }
    $this->retrieveValues();
    $this->setPageTitle($this->_BAOName::getEntityTitle());
    // Once form is submitted, user should be redirected back to the "browse" page.
    if (isset($this->_BAOName::getEntityPaths()['browse'])) {
      $this->pushUrlToUserContext($this->_BAOName::getEntityPaths()['browse']);
    }
  }

  /**
   * Set default values for the form. Note that in edit/view mode
   * the default values are retrieved from the database
   *
   * @return array
   */
  public function setDefaultValues() {
    // Fetch defaults from the db if not already retrieved
    if (empty($this->_values)) {
      $this->retrieveValues();
    }
    $defaults = $this->_values;

    // Allow defaults to be set from the url
    if (empty($this->_id) && $this->_action & CRM_Core_Action::ADD) {
      foreach ($_GET as $key => $val) {
        if ($this->elementExists($key)) {
          $defaults[$key] = $val;
        }
      }
    }

    if ($this->_action == CRM_Core_Action::DELETE && isset($defaults['name'])) {
      $this->assign('delName', $defaults['name']);
    }

    // Field is_active should default to TRUE (if there is no such field, this value will be ignored)
    $defaults['is_active'] = ($this->_id) ? $defaults['is_active'] ?? 1 : 1;
    if (!empty($defaults['parent_id'])) {
      $this->assign('is_parent', TRUE);
    }
    return $defaults;
  }

  /**
   * Add standard buttons.
   */
  public function buildQuickForm() {
    if ($this->_action & CRM_Core_Action::VIEW || $this->_action & CRM_Core_Action::PREVIEW) {
      $this->addButtons([
        [
          'type' => 'cancel',
          'name' => ts('Done'),
          'isDefault' => TRUE,
        ],
      ]);
    }
    else {
      $this->addButtons([
        [
          'type' => 'next',
          'name' => $this->_action & CRM_Core_Action::DELETE ? ts('Delete') : ts('Save'),
          'isDefault' => TRUE,
        ],
        [
          'type' => 'cancel',
          'name' => ts('Cancel'),
        ],
      ]);
    }
  }

  /**
   * Retrieve entity from the database using legacy retrieve method (default) or APIv4.
   *
   * @return array
   */
  protected function retrieveValues(): array {
    $this->_values = [];
    if (isset($this->_id) && CRM_Utils_Rule::positiveInteger($this->_id)) {
      if ($this->retrieveMethod === 'retrieve') {
        $params = ['id' => $this->_id];
        if (!empty(ReflectionUtils::getCodeDocs((new \ReflectionMethod($this->_BAOName, 'retrieve')), 'Method')['deprecated'])) {
          CRM_Core_DAO::commonRetrieve($this->_BAOName, $params, $this->_values);
        }
        else {
          // Are there still some out there?
          $this->_BAOName::retrieve($params, $this->_values);
        }
      }
      elseif ($this->retrieveMethod === 'api4') {
        $this->_values = civicrm_api4($this->getDefaultEntity(), 'get', [
          'where' => [['id', '=', $this->_id]],
        ])->single();
      }
      else {
        throw new CRM_Core_Exception("Unknown retrieve method '$this->retrieveMethod' in " . get_class($this));
      }
    }
    return $this->_values;
  }

}
