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

/**
 * This class generates form components for Navigation.
 */
class CRM_Admin_Form_Navigation extends CRM_Admin_Form {

  /**
   * The parent id of the navigation menu.
   * @var int
   */
  protected $_currentParentID = NULL;

  /**
   * @var bool
   */
  public $submitOnce = TRUE;

  public function getDefaultEntity(): string {
    return 'Navigation';
  }

  /**
   * Build the form object.
   */
  public function buildQuickForm() {
    parent::buildQuickForm();

    if (isset($this->_id)) {
      $params = ['id' => $this->_id];
      $navDao = CRM_Core_BAO_Navigation::retrieve($params, $this->_defaults);
    }

    if ($this->_action & CRM_Core_Action::DELETE) {
      $childCount = CRM_Core_BAO_Navigation::getChildCount($this->_id);
      $this->assign('label', $navDao->label ?: $navDao->url);
      $this->assign('childCount', $childCount);
      return;
    }

    $this->applyFilter('__ALL__', 'trim');
    $this->add('text',
      'label',
      ts('Title'),
      CRM_Core_DAO::getAttribute('CRM_Core_DAO_Navigation', 'label'),
      TRUE
    );

    $this->add('text', 'url', ts('Url'), CRM_Core_DAO::getAttribute('CRM_Core_DAO_Navigation', 'url'));

    $this->add('text', 'icon', ts('Icon'), ['class' => 'crm-icon-picker', 'title' => ts('Choose Icon'), 'allowClear' => TRUE]);

    $getPerms = (array) \Civi\Api4\Permission::get(0)
      ->addWhere('group', 'IN', ['civicrm', 'cms', 'const'])
      ->setOrderBy(['title' => 'ASC'])
      ->execute();
    $permissions = [];
    foreach ($getPerms as $perm) {
      $permissions[] = ['id' => $perm['name'], 'text' => $perm['title'], 'description' => $perm['description'] ?? ''];
    }
    $this->add('select2', 'permission', ts('Permission'), $permissions, FALSE,
      ['placeholder' => ts('Unrestricted'), 'class' => 'huge', 'multiple' => TRUE]
    );

    $operators = ['AND' => ts('AND'), 'OR' => ts('OR')];
    $this->add('select', 'permission_operator', NULL, $operators);

    //make separator location configurable
    $separator = CRM_Core_SelectValues::navigationMenuSeparator();
    $this->add('select', 'has_separator', ts('Separator'), $separator, FALSE, ['class' => 'crm-select2']);

    $active = $this->add('advcheckbox', 'is_active', ts('Enabled'));

    if (($this->_defaults['name'] ?? NULL) == 'Home') {
      $active->freeze();
    }
    else {
      $parentMenu = CRM_Core_BAO_Navigation::getNavigationList();

      if (isset($this->_id)) {
        unset($parentMenu[$this->_id]);
      }

      // also unset home.
      $homeMenuId = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_Navigation', 'Home', 'id', 'name');
      unset($parentMenu[$homeMenuId]);

      $this->add('select', 'parent_id', ts('Parent'), ['' => ts('Top level')] + $parentMenu, FALSE, ['class' => 'crm-select2 huge']);
    }
  }

  /**
   * @return array
   */
  public function setDefaultValues() {
    $defaults = parent::setDefaultValues();

    if ($this->_action & CRM_Core_Action::DELETE) {
      return $defaults;
    }

    if (isset($this->_id)) {
      //Take parent id in object variable to calculate the menu
      //weight if menu parent id changed
      $this->_currentParentID = $this->_defaults['parent_id'] ?? NULL;
    }
    else {
      $defaults['permission'] = "access CiviCRM";
    }

    // its ok if there is no element called is_active
    $defaults['is_active'] = ($this->_id) ? $this->_defaults['is_active'] : 1;

    if (!empty($defaults['icon'])) {
      $defaults['icon'] = trim(str_replace('crm-i', '', $defaults['icon']));
    }

    return $defaults;
  }

  /**
   * Process the form submission.
   */
  public function postProcess() {
    // get the submitted form values.
    $params = $this->controller->exportValues($this->_name);

    if ($this->_action & CRM_Core_Action::DELETE) {
      CRM_Core_BAO_Navigation::deleteRecords([['id' => $this->_id]]);
      $childCount = $this->getTemplateVars('childCount');
      $msg = ts('One menu item permanently deleted.', [
        'plural' => '%count menu items permanently deleted.',
        'count' => $childCount + 1,
      ]);
      CRM_Core_Session::setStatus($msg, ts('Deleted'), 'success');
      return;
    }

    if (isset($this->_id)) {
      $params['id'] = $this->_id;
      $params['current_parent_id'] = $this->_currentParentID;
    }

    if (!empty($params['icon'])) {
      $params['icon'] = 'crm-i ' . $params['icon'];
    }

    $navigation = CRM_Core_BAO_Navigation::add($params);

    // also reset navigation
    CRM_Core_BAO_Navigation::resetNavigation();

    CRM_Core_Session::setStatus(ts('Menu \'%1\' has been saved.',
      [1 => $navigation->label]
    ), ts('Saved'), 'success');
  }

}
