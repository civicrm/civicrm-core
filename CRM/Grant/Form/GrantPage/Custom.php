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
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2014
 * $Id$
 *
 */

/**
 * form to process actions on the group aspect of Custom Data
 */
class CRM_Grant_Form_GrantPage_Custom extends CRM_Grant_Form_GrantPage {

  /**
   * Function to actually build the form
   *
   * @return void
   * @access public
   */
  public function buildQuickForm() {
    //GEP-11
    $this->_isLast = TRUE;

    // Register 'contact_1' model
    $entities = array();
    $entities[] = array('entity_name' => 'contact_1', 'entity_type' => 'IndividualModel');
    $allowCoreTypes = array_merge(array('Contact', 'Individual'), CRM_Contact_BAO_ContactType::subTypes('Individual'));
    $allowSubTypes = array();

    // Register 'grant_1'
    $allowCoreTypes[] = 'Grant';
    $entities[] = array('entity_name' => 'grant_1', 'entity_type' => 'GrantModel');

    $this->addProfileSelector('custom_pre_id', ts('Include Profile') . '<br />' . ts('(top of page)'), $allowCoreTypes, $allowSubTypes, $entities);
    $this->addProfileSelector('custom_post_id', ts('Include Profile') . '<br />' . ts('(bottom of page)'), $allowCoreTypes, $allowSubTypes, $entities);

    $this->addFormRule(array('CRM_Grant_Form_GrantPage_Custom', 'formRule'), $this->_id);

    parent::buildQuickForm();
  }

  /**
   * This function sets the default values for the form. Note that in edit/view mode
   * the default values are retrieved from the database
   *
   * @access public
   *
   * @return void
   */
  function setDefaultValues() {
    $defaults = parent::setDefaultValues();

    if ($this->_id) {
      $title = CRM_Core_DAO::getFieldValue('CRM_Grant_DAO_GrantApplicationPage', $this->_id, 'title');
      CRM_Utils_System::setTitle(ts('Include Profiles (%1)', array(1 => $title)));
    }


    $ufJoinParams = array(
      'module' => 'CiviGrant',
      'entity_table' => 'civicrm_grant_app_page',
      'entity_id' => $this->_id,
    );

    list($defaults['custom_pre_id'],
      $second) = CRM_Core_BAO_UFJoin::getUFGroupIds($ufJoinParams);
    $defaults['custom_post_id'] = $second ? array_shift($second) : '';

    return $defaults;
  }

  /**
   * Process the form
   *
   * @return void
   * @access public
   */
  public function postProcess() {
    // get the submitted form values.
    $params = $this->controller->exportValues($this->_name);

    if ($this->_action & CRM_Core_Action::UPDATE) {
      $params['id'] = $this->_id;
    }

    $transaction = new CRM_Core_Transaction();

    // also update uf join table
    $ufJoinParams = array(
      'is_active' => 1,
      'module' => 'CiviGrant',
      'entity_table' => 'civicrm_grant_app_page',
      'entity_id' => $this->_id,
    );

    // first delete all past entries
    CRM_Core_BAO_UFJoin::deleteAll($ufJoinParams);

    if (!empty($params['custom_pre_id'])) {
      $ufJoinParams['weight'] = 1;
      $ufJoinParams['uf_group_id'] = $params['custom_pre_id'];
      CRM_Core_BAO_UFJoin::create($ufJoinParams);
    }

    unset($ufJoinParams['id']);

    if (!empty($params['custom_post_id'])) {
      $ufJoinParams['weight'] = 2;
      $ufJoinParams['uf_group_id'] = $params['custom_post_id'];
      CRM_Core_BAO_UFJoin::create($ufJoinParams);
    }

    $transaction->commit();
    parent::endPostProcess();
  }

  /**
   * Return a descriptive name for the page, used in wizard header
   *
   * @return string
   * @access public
   */
  public function getTitle() {
    return ts('Include Profiles');
  }

 /**
   * global form rule
   *
   * @param array $fields  the input form values
   *
   * @return true if no errors, else array of errors
   * @access public
   * @static
   */
  static function formRule($fields, $files, $grantApplicationPageId) {
    $errors = array();
    $preProfileType = $postProfileType = NULL;

    return empty($errors) ? TRUE : $errors;
  }
}

