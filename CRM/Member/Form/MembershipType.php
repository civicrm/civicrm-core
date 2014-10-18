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
 * This class generates form components for Membership Type
 *
 */
class CRM_Member_Form_MembershipType extends CRM_Member_Form {

  /**
   * max number of contacts we will display for membership-organisation
   */
  CONST MAX_CONTACTS = 50;

  function preProcess() {
    $this->_id = CRM_Utils_Request::retrieve('id', 'Positive', $this, FALSE, 0 );
    $this->_BAOName = 'CRM_Member_BAO_MembershipType';
    $this->_action = CRM_Utils_Request::retrieve('action', 'String', $this, FALSE, 'add');
    $this->assign('action', $this->_action);

    $session = CRM_Core_Session::singleton();
    $url = CRM_Utils_System::url('civicrm/admin/member/membershipType', 'reset=1');
    $session->pushUserContext($url);

    $this->setPageTitle(ts('Membership Type'));
  }

  /**
   * This function sets the default values for the form. MobileProvider that in edit/view mode
   * the default values are retrieved from the database
   *
   * @access public
   *
   * @return void
   */
  public function setDefaultValues() {
    $defaults = parent::setDefaultValues();

    //finding default weight to be put
    if (!isset($defaults['weight']) || (!$defaults['weight'])) {
      $defaults['weight'] = CRM_Utils_Weight::getDefaultWeight('CRM_Member_DAO_MembershipType');
    }
    //setting default relationshipType
    if (isset($defaults['relationship_type_id'])) {
      //$defaults['relationship_type_id'] = $defaults['relationship_type_id'].'_a_b';
      // Set values for relation type select box
      $relTypeIds = explode(CRM_Core_DAO::VALUE_SEPARATOR, $defaults['relationship_type_id']);
      $relDirections = explode(CRM_Core_DAO::VALUE_SEPARATOR, $defaults['relationship_direction']);
      $defaults['relationship_type_id'] = array();
      foreach ($relTypeIds as $key => $value) {
        $defaults['relationship_type_id'][] = $value . '_' . $relDirections[$key];
      }
    }


    //setting default fixed_period_start_day & fixed_period_rollover_day
    $periods = array('fixed_period_start_day', 'fixed_period_rollover_day');
    foreach ($periods as $per) {
      if (isset($defaults[$per])) {
        $date            = $defaults[$per];

        $defaults[$per] = array();
        if ($date > 31) {
          $date                = ($date < 999) ? '0' . $date : $date;
          $defaults[$per]['M'] = substr($date, 0, 2);
          $defaults[$per]['d'] = substr($date, 2, 3);
        }
        else {
          //special case when only day is rollover and duration is month
          $defaults['month_fixed_period_rollover_day']['d'] = $date;
        }
      }
    }

    return $defaults;
  }

  /**
   * Function to build the form
   *
   * @return void
   * @access public
   */
  public function buildQuickForm() {
    parent::buildQuickForm();

    if ($this->_action & CRM_Core_Action::DELETE) {
      return;
    }

    $this->applyFilter('__ALL__', 'trim');
    $this->add('text', 'name', ts('Name'), CRM_Core_DAO::getAttribute('CRM_Member_DAO_MembershipType', 'name'), TRUE);

    $this->addRule('name', ts('A membership type with this name already exists. Please select another name.'),
      'objectExists', array('CRM_Member_DAO_MembershipType', $this->_id)
    );
    $this->add('text', 'description', ts('Description'),
      CRM_Core_DAO::getAttribute('CRM_Member_DAO_MembershipType', 'description')
    );
    $this->add('text', 'minimum_fee', ts('Minimum Fee'),
      CRM_Core_DAO::getAttribute('CRM_Member_DAO_MembershipType', 'minimum_fee')
    );
    $this->addRule('minimum_fee', ts('Please enter a monetary value for the Minimum Fee.'), 'money');

    $this->addSelect('duration_unit', array(), TRUE);

    //period type
    $this->addSelect('period_type', array(), TRUE);

    $this->add('text', 'duration_interval', ts('Duration Interval'),
      CRM_Core_DAO::getAttribute('CRM_Member_DAO_MembershipType', 'duration_interval')
    );

    $props = array('api' => array('params' => array('contact_type' => 'Organization')));
    $this->addEntityRef('member_of_contact_id', ts('Membership Organization'), $props, TRUE);

    //start day
    $this->add('date', 'fixed_period_start_day', ts('Fixed Period Start Day'),
      CRM_Core_SelectValues::date(NULL, 'M d'), FALSE
    );

    //Auto-renew Option
    $paymentProcessor  = CRM_Core_PseudoConstant::paymentProcessor(FALSE, FALSE, 'is_recur = 1');
    $isAuthorize       = FALSE;
    $options           = array();
    if (is_array($paymentProcessor) && !empty($paymentProcessor)) {
      $isAuthorize = TRUE;
      $options = array(ts('No auto-renew option'), ts('Give option, but not required'), ts('Auto-renew required '));
    }

    $this->addRadio('auto_renew', ts('Auto-renew Option'), $options);
    $this->assign('authorize', $isAuthorize);

    //rollover day
    $this->add('date', 'fixed_period_rollover_day', ts('Fixed Period Rollover Day'),
      CRM_Core_SelectValues::date(NULL, 'M d'), FALSE
    );
    $this->add('date', 'month_fixed_period_rollover_day', ts('Fixed Period Rollover Day'),
      CRM_Core_SelectValues::date(NULL, 'd'), FALSE
    );

    $this->add('select', 'financial_type_id', ts( 'Financial Type' ),
      array('' => ts('- select -')) + CRM_Contribute_PseudoConstant::financialType(), TRUE, array('class' => 'crm-select2')
    );

    $relTypeInd = CRM_Contact_BAO_Relationship::getContactRelationshipType(NULL, NULL, NULL, NULL, TRUE);
    if (is_array($relTypeInd)) {
      asort($relTypeInd);
    }
    $memberRel = &$this->add('select', 'relationship_type_id', ts('Relationship Type'),
      array('' => ts('- select -')) + $relTypeInd);
    $memberRel->setMultiple(TRUE);

    $this->addSelect('visibility', array('placeholder' => NULL, 'option_url' => NULL));

    $this->add('text', 'weight', ts('Order'),
      CRM_Core_DAO::getAttribute('CRM_Member_DAO_MembershipType', 'weight')
    );
    $this->add('checkbox', 'is_active', ts('Enabled?'));

    $membershipRecords = FALSE;
    if ($this->_action & CRM_Core_Action::UPDATE) {
      $membershipType = new CRM_Member_BAO_Membership();
      $membershipType->membership_type_id = $this->_id;
      if ($membershipType->find(TRUE)) {
        $membershipRecords = TRUE;
        $memberRel->freeze();
      }
    }

    $this->assign('membershipRecordsExists', $membershipRecords);

    $this->add('text', 'max_related', ts('Max related'),
        CRM_Core_DAO::getAttribute('CRM_Member_DAO_MembershipType', 'max_related')
    );

    $this->addFormRule(array('CRM_Member_Form_MembershipType', 'formRule'));

    $this->assign('membershipTypeId', $this->_id);
  }

  /**
   * Function for validation
   *
   * @param array $params (ref.) an assoc array of name/value pairs
   *
   * @return mixed true or array of errors
   * @access public
   * @static
   */
  static function formRule($params) {
    $errors = array();

    if (!$params['name']) {
      $errors['name'] = ts('Please enter a membership type name.');
    }

    if (($params['minimum_fee'] > 0 ) && !$params['financial_type_id'] ) {
      $errors['financial_type_id'] = ts('Please enter the financial type.');
    }

    if (empty($params['duration_interval']) and $params['duration_unit'] != 'lifetime') {
      $errors['duration_interval'] = ts('Please enter a duration interval.');
    }

    if (in_array(CRM_Utils_Array::value('auto_renew', $params), array(
      1, 2))) {
      if (($params['duration_interval'] > 1 && $params['duration_unit'] == 'year') ||
        ($params['duration_interval'] > 12 && $params['duration_unit'] == 'month')
      ) {
        $errors['duration_unit'] = ts('Automatic renewals are not supported by the currently available payment processors when the membership duration is greater than 1 year / 12 months.');
      }
    }

    if ($params['period_type'] == 'fixed' &&
      $params['duration_unit'] == 'day'
    ) {
      $errors['period_type'] = ts('Period type should be Rolling when duration unit is Day');
    }

    if (($params['period_type'] == 'fixed') &&
      ($params['duration_unit'] == 'year')
    ) {
      $periods = array('fixed_period_start_day', 'fixed_period_rollover_day');
      foreach ($periods as $period) {
        $month = $params[$period]['M'];
        $date = $params[$period]['d'];
        if (!$month || !$date) {
          switch ($period) {
            case 'fixed_period_start_day':
              $errors[$period] = ts('Please enter a valid fixed period start day');
              break;

            case 'fixed_period_rollover_day':
              $errors[$period] = ts('Please enter a valid fixed period rollover day');
              break;
          }
        }
      }
    }

    if ($params['fixed_period_start_day'] && !empty($params['fixed_period_start_day'])) {
      $params['fixed_period_start_day']['Y'] = date('Y');
      if (!CRM_Utils_Rule::qfDate($params['fixed_period_start_day'])) {
        $errors['fixed_period_start_day'] = ts('Please enter valid Fixed Period Start Day');
      }
    }

    if ($params['fixed_period_rollover_day'] && !empty($params['fixed_period_rollover_day'])) {
      $params['fixed_period_rollover_day']['Y'] = date('Y');
      if (!CRM_Utils_Rule::qfDate($params['fixed_period_rollover_day'])) {
        $errors['fixed_period_rollover_day'] = ts('Please enter valid Fixed Period Rollover Day');
      }
    }

    return empty($errors) ? TRUE : $errors;
  }

  /**
   * Function to process the form
   *
   * @access public
   *
   * @return void
   */
  public function postProcess() {
    if ($this->_action & CRM_Core_Action::DELETE) {
      try{
      CRM_Member_BAO_MembershipType::del($this->_id);
      }
      catch(CRM_Core_Exception $e) {
        CRM_Core_Error::statusBounce($e->getMessage(), NULL, ts('Membership Type Not Deleted'));
      }
      CRM_Core_Session::setStatus(ts('Selected membership type has been deleted.'), ts('Record Deleted'), 'success');
    }
    else {
      $buttonName = $this->controller->getButtonName();
      $submitted = $this->controller->exportValues($this->_name);

      $fields = array(
        'name',
        'weight',
        'is_active',
        'member_of_contact_id',
        'visibility',
        'period_type',
        'minimum_fee',
        'description',
        'auto_renew',
        'duration_unit',
        'duration_interval',
        'financial_type_id',
        'fixed_period_start_day',
        'fixed_period_rollover_day',
        'month_fixed_period_rollover_day',
        'max_related'
      );

      $params = $ids = array();
      foreach ($fields as $fld) {
        $params[$fld] = CRM_Utils_Array::value($fld, $submitted, 'NULL');
      }

      //clean money.
      if ($params['minimum_fee']) {
        $params['minimum_fee'] = CRM_Utils_Rule::cleanMoney($params['minimum_fee']);
      }

      $hasRelTypeVal = FALSE;
      if (!CRM_Utils_System::isNull($submitted['relationship_type_id'])) {
        // To insert relation ids and directions with value separator
        $relTypeDirs = $submitted['relationship_type_id'];
        $relIds = $relDirection = array();
        foreach ($relTypeDirs as $key => $value) {
          $relationId = explode('_', $value);
          if (count($relationId) == 3 &&
            is_numeric($relationId[0])
          ) {
            $relIds[] = $relationId[0];
            $relDirection[] = $relationId[1] . '_' . $relationId[2];
          }
        }
        if (!empty($relIds)) {
          $hasRelTypeVal = TRUE;
          $params['relationship_type_id'] = implode(CRM_Core_DAO::VALUE_SEPARATOR, $relIds);
          $params['relationship_direction'] = implode(CRM_Core_DAO::VALUE_SEPARATOR, $relDirection);
        }
      }
      if (!$hasRelTypeVal) {
        $params['relationship_type_id'] = $params['relationship_direction'] = $params['max_related'] = 'NULL';
      }

      if ($params['duration_unit'] == 'lifetime' &&
        empty($params['duration_interval'])
      ) {
        $params['duration_interval'] = 1;
      }

      $periods = array('fixed_period_start_day', 'fixed_period_rollover_day');
      foreach ($periods as $per) {
        if (!empty($params[$per]['M']) && !empty($params[$per]['d'])) {
          $mon          = $params[$per]['M'];
          $dat          = $params[$per]['d'];
          $mon          = ($mon < 10) ? '0' . $mon : $mon;
          $dat          = ($dat < 10) ? '0' . $dat : $dat;
          $params[$per] = $mon . $dat;
        }
        else if($per == 'fixed_period_rollover_day' && !empty($params['month_fixed_period_rollover_day'])){
          $params['fixed_period_rollover_day'] = $params['month_fixed_period_rollover_day']['d'];
          unset($params['month_fixed_period_rollover_day']);
        }
        else {
          $params[$per] = 'NULL';
        }
      }
      $oldWeight = NULL;

      if ($this->_id) {
        $oldWeight = CRM_Core_DAO::getFieldValue('CRM_Member_DAO_MembershipType',
          $this->_id, 'weight', 'id'
        );
      }
      $params['weight'] = CRM_Utils_Weight::updateOtherWeights('CRM_Member_DAO_MembershipType',
        $oldWeight, $params['weight']
      );

      if ($this->_action & CRM_Core_Action::UPDATE) {
        $ids['membershipType'] = $this->_id;
      }

      $membershipType = CRM_Member_BAO_MembershipType::add($params, $ids);

      CRM_Core_Session::setStatus(ts('The membership type \'%1\' has been saved.',
          array(1 => $membershipType->name)
        ), ts('Saved'), 'success');
      $session = CRM_Core_Session::singleton();
      if ($buttonName == $this->getButtonName('upload', 'new')) {
        $session->replaceUserContext(
          CRM_Utils_System::url('civicrm/admin/member/membershipType/add', 'action=add&reset=1')
        );
      }
    }
  }

  /**
   * @param $previousID
   * @param $priceSetId
   * @param $membershipTypeId
   * @param $optionsIds
   */
  public static function checkPreviousPriceField($previousID, $priceSetId, $membershipTypeId, &$optionsIds) {
    if ($previousID) {
      $editedFieldParams = array(
        'price_set_id ' => $priceSetId,
        'name' => $previousID,
      );
      $editedResults = array();
      CRM_Price_BAO_PriceField::retrieve($editedFieldParams, $editedResults);
      if (!empty($editedResults)) {
        $editedFieldParams = array(
          'price_field_id' => $editedResults['id'],
          'membership_type_id' => $membershipTypeId,
        );
        $editedResults = array();
        CRM_Price_BAO_PriceFieldValue::retrieve($editedFieldParams, $editedResults);
        $optionsIds['option_id'][1] = CRM_Utils_Array::value('id', $editedResults);
      }
    }
  }
}

