<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2018                                |
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
 * @copyright CiviCRM LLC (c) 2004-2018
 *
 */

/**
 * This class generates form components for Membership Type
 *
 */
class CRM_Member_Form_MembershipType extends CRM_Member_Form_MembershipConfig {

  use CRM_Core_Form_EntityFormTrait;

  /**
   * Fields for the entity to be assigned to the template.
   *
   * Fields may have keys
   *  - name (required to show in tpl from the array)
   *  - description (optional, will appear below the field)
   *  - not-auto-addable - this class will not attempt to add the field using addField.
   *    (this will be automatically set if the field does not have html in it's metadata
   *    or is not a core field on the form's entity).
   *  - help (option) add help to the field - e.g ['id' => 'id-source', 'file' => 'CRM/Contact/Form/Contact']]
   *  - template - use a field specific template to render this field
   *  - required
   *  - is_freeze (field should be frozen).
   *
   * @var array
   */
  protected $entityFields = [];

  /**
   * Set entity fields to be assigned to the form.
   */
  protected function setEntityFields() {
    $this->entityFields = [
      'name' => [
        'required' => 'TRUE',
        'name' => 'name',
        'description' => ts("e.g. 'Student', 'Senior', 'Honor Society'..."),
      ],
      'description' => [
        'name' => 'description',
        'description' => ts("Description of this membership type for internal use. May include eligibility, benefits, terms, etc."),
      ],
      'member_of_contact_id' => [
        'name' => 'member_of_contact_id',
        'description' => ts("Members assigned this membership type belong to which organization (e.g. this is for membership in 'Save the Whales - Northwest Chapter'). NOTE: This organization/group/chapter must exist as a CiviCRM Organization type contact."),
      ],
      'minimum_fee' => [
        'name' => 'minimum_fee',
        'description' => ts('Minimum fee required for this membership type. For free/complimentary memberships - set minimum fee to zero (0). NOTE: When using CiviCRM to process sales taxes this should be the tax exclusive amount.'),
        'formatter' => 'crmMoney',
      ],
      'financial_type_id' => [
        'name' => 'financial_type_id',
        'description' => ts('Select the financial type assigned to fees for this membership type (for example \'Membership Fees\'). This is required for all membership types - including free or complimentary memberships.'),
      ],
      'auto_renew' => [
        'name' => 'auto_renew',
        'options' => CRM_Core_SelectValues::memberAutoRenew(),
        'place_holder' => ts('You will need to select and configure a supported payment processor (currently Authorize.Net, PayPal Pro, or PayPal Website Standard) in order to offer automatically renewing memberships.'),
      ],
      'duration_interval' => [
        'name' => 'duration_interval',
      ],
      'duration_unit' => [
        'name' => 'duration_unit',
        'description' => ts('Duration of this membership (e.g. 30 days, 2 months, 5 years, 1 lifetime)'),
      ],
      'period_type' => [
        'name' => 'period_type',
        'description' => ts("Select 'rolling' if membership periods begin at date of signup. Select 'fixed' if membership periods begin on a set calendar date."),
        'help' => ['id' => 'period-type', 'file' => "CRM/Member/Page/MembershipType.hlp"],
      ],
      'fixed_period_start_day' => [
        'name' => 'fixed_period_start_day',
        'description' => ts("Month and day on which a <strong>fixed</strong> period membership or subscription begins. Example: A fixed period membership with Start Day set to Jan 01 means that membership periods would be 1/1/06 - 12/31/06 for anyone signing up during 2006."),
      ],
      'fixed_period_rollover_day' => [
        'name' => 'fixed_period_rollover_day',
        'description' => ts('Membership signups on or after this date cover the following calendar year as well. Example: If the rollover day is November 30, membership period for signups during December will cover the following year.'),
      ],
      'relationship_type_id' => [
        'name' => 'relationship_type_id',
      ],
      'max_related' => [
        'name' => 'max_related',
        'description' => ts('Maximum number of related memberships (leave blank for unlimited).'),
      ],
      'visibility' => [
        'name' => 'visibility',
        'description' => ts("Can this membership type be used for self-service signups ('Public'), or is it only for CiviCRM users with 'Edit Contributions' permission ('Admin')."),
      ],
      'weight' => [
        'name' => 'weight',
      ],
      'is_active' => [
        'name' => 'is_active',
      ],
    ];

    if (!CRM_Financial_BAO_PaymentProcessor::hasPaymentProcessorSupporting(array('Recurring'))) {
      $this->entityFields['auto_renew']['not-auto-addable'] = TRUE;
      $this->entityFields['auto_renew']['documentation_link'] = ['page' => 'user/contributions/payment-processors'];
    }
  }

  /**
   * Deletion message to be assigned to the form.
   *
   * @var string
   */
  protected $deleteMessage;

  /**
   * Explicitly declare the entity api name.
   */
  public function getDefaultEntity() {
    return 'MembershipType';
  }

  /**
   * Set the delete message.
   *
   * We do this from the constructor in order to do a translation.
   */
  public function setDeleteMessage() {
    $this->deleteMessage = ts('WARNING: Deleting this option will result in the loss of all membership records of this type.') . ts('This may mean the loss of a substantial amount of data, and the action cannot be undone.') . ts('Do you want to continue?');
  }

  /**
   * Explicitly declare the form context.
   */
  public function getDefaultContext() {
    return 'create';
  }

  /**
   * Max number of contacts we will display for membership-organisation
   */
  const MAX_CONTACTS = 50;

  public function preProcess() {
    $this->_id = CRM_Utils_Request::retrieve('id', 'Positive', $this, FALSE, 0);
    $this->_BAOName = 'CRM_Member_BAO_MembershipType';
    $this->_action = CRM_Utils_Request::retrieve('action', 'String', $this, FALSE, 'add');
    $this->assign('action', $this->_action);

    $session = CRM_Core_Session::singleton();
    $url = CRM_Utils_System::url('civicrm/admin/member/membershipType', 'reset=1');
    $session->pushUserContext($url);

    $this->setPageTitle(ts('Membership Type'));
  }

  /**
   * Set default values for the form. MobileProvider that in edit/view mode
   * the default values are retrieved from the database
   *
   * @return array
   *   defaults
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
        $date = $defaults[$per];

        $defaults[$per] = array();
        if ($date > 31) {
          $date = ($date < 999) ? '0' . $date : $date;
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
   * Build the form object.
   *
   * @return void
   * @throws \CRM_Core_Exception
   * @throws \CiviCRM_API3_Exception
   */
  public function buildQuickForm() {
    self::buildQuickEntityForm();

    if ($this->_action & CRM_Core_Action::DELETE) {
      return;
    }
    // This is a temporary variable as we work towards moving over towards using the EntityField.tpl.
    // Fields in this array have been tested & in the tpl have been switched over to metadata.
    // Note this kinda 'works from the top' - ie. once we hit a field that needs some thought we need
    // to stop & make that one work.
    $this->assign('tpl_standardised_fields', ['name', 'description', 'member_of_contact_id', 'minimum_fee']);

    $this->addRule('name', ts('A membership type with this name already exists. Please select another name.'),
      'objectExists', array('CRM_Member_DAO_MembershipType', $this->_id)
    );
    $this->addRule('minimum_fee', ts('Please enter a monetary value for the Minimum Fee.'), 'money');

    $props = array('api' => array('params' => array('contact_type' => 'Organization')));
    $this->addEntityRef('member_of_contact_id', ts('Membership Organization'), $props, TRUE);

    //start day
    $this->add('date', 'fixed_period_start_day', ts('Fixed Period Start Day'),
      CRM_Core_SelectValues::date(NULL, 'M d'), FALSE
    );

    // Add Auto-renew options if we have a payment processor that supports recurring contributions
    $isAuthorize = FALSE;
    $options = array();
    if (CRM_Financial_BAO_PaymentProcessor::hasPaymentProcessorSupporting(array('Recurring'))) {
      $isAuthorize = TRUE;
      $options = CRM_Core_SelectValues::memberAutoRenew();
    }

    $this->addRadio('auto_renew', ts('Auto-renew Option'), $options);
    $this->assign('authorize', $isAuthorize);

    // rollover day
    $this->add('date', 'fixed_period_rollover_day', ts('Fixed Period Rollover Day'),
      CRM_Core_SelectValues::date(NULL, 'M d'), FALSE
    );
    $this->add('date', 'month_fixed_period_rollover_day', ts('Fixed Period Rollover Day'),
      CRM_Core_SelectValues::date(NULL, 'd'), FALSE
    );
    $this->add('select', 'financial_type_id', ts('Financial Type'),
      array('' => ts('- select -')) + CRM_Financial_BAO_FinancialType::getAvailableFinancialTypes($financialTypes, $this->_action), TRUE, array('class' => 'crm-select2')
    );

    $relTypeInd = CRM_Contact_BAO_Relationship::getContactRelationshipType(NULL, NULL, NULL, NULL, TRUE);
    if (is_array($relTypeInd)) {
      asort($relTypeInd);
    }
    $memberRel = $this->add('select', 'relationship_type_id', ts('Relationship Type'),
      $relTypeInd, FALSE, array('class' => 'crm-select2 huge', 'multiple' => 1));

    $this->addField('visibility', array('placeholder' => NULL, 'option_url' => NULL));

    $membershipRecords = FALSE;
    if ($this->_action & CRM_Core_Action::UPDATE) {
      $result = civicrm_api3("Membership", "get", array("membership_type_id" => $this->_id, "options" => array("limit" => 1)));
      $membershipRecords = ($result["count"] > 0);
      if ($membershipRecords) {
        $memberRel->freeze();
      }
    }

    $this->assign('membershipRecordsExists', $membershipRecords);

    $this->addFormRule(array('CRM_Member_Form_MembershipType', 'formRule'));

    $this->assign('membershipTypeId', $this->_id);

    if (CRM_Contribute_BAO_Contribution::checkContributeSettings('deferred_revenue_enabled')) {
      $deferredFinancialType = CRM_Financial_BAO_FinancialAccount::getDeferredFinancialType();
      $this->assign('deferredFinancialType', array_keys($deferredFinancialType));
    }
  }

  /**
   * Validation.
   *
   * @param array $params
   *   (ref.) an assoc array of name/value pairs.
   *
   * @return bool|array
   *   mixed true or array of errors
   */
  public static function formRule($params) {
    $errors = array();

    if (!$params['name']) {
      $errors['name'] = ts('Please enter a membership type name.');
    }

    if (($params['minimum_fee'] > 0) && !$params['financial_type_id']) {
      $errors['financial_type_id'] = ts('Please enter the financial Type.');
    }

    if (empty($params['duration_interval']) and $params['duration_unit'] != 'lifetime') {
      $errors['duration_interval'] = ts('Please enter a duration interval.');
    }

    if (in_array(CRM_Utils_Array::value('auto_renew', $params), array(
      1,
      2,
    ))) {
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
   * Process the form submission.
   *
   * @return void
   */
  public function postProcess() {
    if ($this->_action & CRM_Core_Action::DELETE) {
      try {
        CRM_Member_BAO_MembershipType::del($this->_id);
      }
      catch (CRM_Core_Exception $e) {
        CRM_Core_Error::statusBounce($e->getMessage(), NULL, ts('Membership Type Not Deleted'));
      }
      CRM_Core_Session::setStatus(ts('Selected membership type has been deleted.'), ts('Record Deleted'), 'success');
    }
    else {
      $params = $this->exportValues();

      if ($params['minimum_fee']) {
        $params['minimum_fee'] = CRM_Utils_Rule::cleanMoney($params['minimum_fee']);
      }

      $hasRelTypeVal = FALSE;
      if (!CRM_Utils_System::isNull($params['relationship_type_id'])) {
        // To insert relation ids and directions with value separator
        $relTypeDirs = $params['relationship_type_id'];
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
        $params['relationship_type_id'] = $params['relationship_direction'] = $params['max_related'] = 'null';
      }

      if ($params['duration_unit'] == 'lifetime' &&
        empty($params['duration_interval'])
      ) {
        $params['duration_interval'] = 1;
      }

      $periods = array('fixed_period_start_day', 'fixed_period_rollover_day');
      foreach ($periods as $period) {
        if (!empty($params[$period]['M']) && !empty($params[$period]['d'])) {
          $mon = $params[$period]['M'];
          $dat = $params[$period]['d'];
          $mon = ($mon < 10) ? '0' . $mon : $mon;
          $dat = ($dat < 10) ? '0' . $dat : $dat;
          $params[$period] = $mon . $dat;
        }
        elseif ($period == 'fixed_period_rollover_day' && !empty($params['month_fixed_period_rollover_day'])) {
          $params['fixed_period_rollover_day'] = $params['month_fixed_period_rollover_day']['d'];
          unset($params['month_fixed_period_rollover_day']);
        }
        else {
          $params[$period] = 'null';
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
        $params['id'] = $this->_id;
      }

      $membershipTypeResult = civicrm_api3('MembershipType', 'create', $params);
      $membershipTypeName = $membershipTypeResult['values'][$membershipTypeResult['id']]['name'];

      CRM_Core_Session::setStatus(ts("The membership type '%1' has been saved.",
        array(1 => $membershipTypeName)
      ), ts('Saved'), 'success');
      $session = CRM_Core_Session::singleton();
      $buttonName = $this->controller->getButtonName();
      if ($buttonName == $this->getButtonName('upload', 'new')) {
        $session->replaceUserContext(
          CRM_Utils_System::url('civicrm/admin/member/membershipType/add', 'action=add&reset=1')
        );
      }
    }
  }

  /**
   * @param int $previousID
   * @param int $priceSetId
   * @param int $membershipTypeId
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
