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
class CRM_Member_BAO_Query {

  /**
   * Get available fields.
   *
   * @return array
   */
  public static function &getFields() {
    $fields = CRM_Member_BAO_Membership::exportableFields();
    return $fields;
  }

  /**
   * If membership are involved, add the specific membership fields.
   *
   * @param CRM_Contact_BAO_Query $query
   */
  public static function select(&$query) {
    // if membership mode add membership id
    if ($query->_mode & CRM_Contact_BAO_Query::MODE_MEMBER ||
      CRM_Contact_BAO_Query::componentPresent($query->_returnProperties, 'membership_')
    ) {

      $query->_select['membership_id'] = "civicrm_membership.id as membership_id";
      $query->_element['membership_id'] = 1;
      $query->_tables['civicrm_membership'] = 1;
      $query->_whereTables['civicrm_membership'] = 1;

      //add membership type
      if (!empty($query->_returnProperties['membership_type'])) {
        $query->_select['membership_type'] = "civicrm_membership_type.name as membership_type";
        $query->_element['membership_type'] = 1;
        $query->_tables['civicrm_membership_type'] = 1;
        $query->_whereTables['civicrm_membership_type'] = 1;
      }

      //add join date
      if (!empty($query->_returnProperties['join_date'])) {
        $query->_select['join_date'] = "civicrm_membership.join_date as join_date";
        $query->_element['join_date'] = 1;
      }

      //add source
      if (!empty($query->_returnProperties['membership_source'])) {
        $query->_select['membership_source'] = "civicrm_membership.source as membership_source";
        $query->_element['membership_source'] = 1;
      }

      //add status
      if (!empty($query->_returnProperties['membership_status'])) {
        $query->_select['membership_status'] = "civicrm_membership_status.label as membership_status";
        $query->_element['membership_status'] = 1;
        $query->_tables['civicrm_membership_status'] = 1;
        $query->_whereTables['civicrm_membership_status'] = 1;
      }

      if (!empty($query->_returnProperties['membership_status_id'])) {
        $query->_select['status_id'] = "civicrm_membership_status.id as status_id";
        $query->_element['status_id'] = 1;
        $query->_tables['civicrm_membership_status'] = 1;
        $query->_whereTables['civicrm_membership_status'] = 1;
      }

      //add start date / end date
      if (!empty($query->_returnProperties['membership_start_date'])) {
        $query->_select['membership_start_date'] = "civicrm_membership.start_date as membership_start_date";
        $query->_element['membership_start_date'] = 1;
      }

      if (!empty($query->_returnProperties['membership_end_date'])) {
        $query->_select['membership_end_date'] = "civicrm_membership.end_date as  membership_end_date";
        $query->_element['membership_end_date'] = 1;
      }

      //add owner_membership_id
      if (!empty($query->_returnProperties['owner_membership_id'])) {
        $query->_select['owner_membership_id'] = "civicrm_membership.owner_membership_id as owner_membership_id";
        $query->_element['owner_membership_id'] = 1;
      }
      //add max_related
      if (!empty($query->_returnProperties['max_related'])) {
        $query->_select['max_related'] = "civicrm_membership.max_related as max_related";
        $query->_element['max_related'] = 1;
      }
      //add recur id w/o taking contribution table in join.
      if (!empty($query->_returnProperties['membership_recur_id'])) {
        $query->_select['membership_recur_id'] = "civicrm_membership.contribution_recur_id as membership_recur_id";
        $query->_element['membership_recur_id'] = 1;
      }

      //add campaign id.
      if (!empty($query->_returnProperties['member_campaign_id'])) {
        $query->_select['member_campaign_id'] = 'civicrm_membership.campaign_id as member_campaign_id';
        $query->_element['member_campaign_id'] = 1;
      }
    }
  }

  /**
   * Generate where clause.
   *
   * @param CRM_Contact_BAO_Query $query
   */
  public static function where(&$query) {
    foreach (array_keys($query->_params) as $id) {
      if (empty($query->_params[$id][0])) {
        continue;
      }
      if (substr($query->_params[$id][0], 0, 7) == 'member_' || substr($query->_params[$id][0], 0, 11) == 'membership_') {
        if ($query->_mode == CRM_Contact_BAO_QUERY::MODE_CONTACTS) {
          $query->_useDistinct = TRUE;
        }
        self::whereClauseSingle($query->_params[$id], $query);
      }
    }
  }

  /**
   * Generate where for a single parameter.
   *
   * @param array $values
   * @param CRM_Contact_BAO_Query $query
   */
  public static function whereClauseSingle(&$values, &$query) {
    list($name, $op, $value, $grouping) = $values;
    switch ($name) {
      case 'member_join_date_low':
      case 'member_join_date_high':
        $query->dateQueryBuilder($values,
          'civicrm_membership', 'member_join_date', 'join_date',
          'Member Since'
        );
        return;

      case 'member_start_date_low':
      case 'member_start_date_high':
        $query->dateQueryBuilder($values,
          'civicrm_membership', 'member_start_date', 'start_date',
          'Start Date'
        );
        return;

      case 'member_end_date_low':
      case 'member_end_date_high':
        $query->dateQueryBuilder($values,
          'civicrm_membership', 'member_end_date', 'end_date',
          'End Date'
        );
        return;

      case 'member_join_date':
        $op = '>=';
        $date = CRM_Utils_Date::format($value);
        if ($date) {
          $query->_where[$grouping][] = "civicrm_membership.join_date {$op} {$date}";
          $format = CRM_Utils_Date::customFormat(CRM_Utils_Date::format(array_reverse($value), '-'));
          $query->_qill[$grouping][] = ts('Member Since %2 %1', array(1 => $format, 2 => $op));
        }

        return;

      case 'member_source':
      case 'membership_source':
        $strtolower = function_exists('mb_strtolower') ? 'mb_strtolower' : 'strtolower';
        $value = $strtolower(CRM_Core_DAO::escapeString(trim($value)));

        $query->_where[$grouping][] = "civicrm_membership.source $op '{$value}'";
        $query->_qill[$grouping][] = ts('Source %2 %1', array(1 => $value, 2 => $op));
        $query->_tables['civicrm_membership'] = $query->_whereTables['civicrm_membership'] = 1;
        return;

      // CRM-17011 These 2 variants appear in some smart groups saved at some time prior to 4.6.6.
      case 'member_status_id':
      case 'member_membership_type_id':
        if (is_array($value)) {
          $op = 'IN';
          $value = array_keys($value);
        }
      case 'membership_status':
      case 'membership_status_id':
      case 'membership_type':
      case 'membership_type_id':
        // CRM-17075 we are specifically handling the possibility we are dealing with the entity reference field
        // for membership_type_id here (although status would be handled if converted). The unhandled pathway at the moment
        // is from groupContactCache::load and this is a small fix to get the entity reference field to work.
        // However, it would seem the larger fix would be to NOT invoke the form formValues for
        // the load function. The where clause and the whereTables are saved so they should suffice to generate the query
        // to get a contact list. But, better to deal with in 4.8 now...
        if (is_string($value) && strpos($value, ',') && $op == '=') {
          $value = array('IN' => explode(',', $value));
        }
      case 'membership_id':
      case 'member_id': // CRM-18523 Updated to membership_id but kept member_id case for backwards compatibility
      case 'member_campaign_id':

        if (strpos($name, 'status') !== FALSE) {
          $name = 'status_id';
          $qillName = ts('Membership Status');
        }
        elseif ($name == 'membership_id' || $name == 'member_id') {
          $name = 'id';
          $qillName = ts('Membership ID');
        }
        elseif ($name == 'member_campaign_id') {
          $name = 'campaign_id';
          $qillName = ts('Campaign');
        }
        else {
          $name = 'membership_type_id';
          $qillName = ts('Membership Type');
        }
        $query->_where[$grouping][] = CRM_Contact_BAO_Query::buildClause("civicrm_membership.$name",
          $op,
          $value,
          "Integer"
        );
        list($op, $value) = CRM_Contact_BAO_Query::buildQillForFieldValue('CRM_Member_DAO_Membership', $name, $value, $op);
        $query->_qill[$grouping][] = $qillName . ' ' . $op . ' ' . $value;
        $query->_tables['civicrm_membership'] = $query->_whereTables['civicrm_membership'] = 1;
        return;

      case 'member_test':
        // We don't want to include all tests for sql OR CRM-7827
        if (!$value || $query->getOperator() != 'OR') {
          $query->_where[$grouping][] = CRM_Contact_BAO_Query::buildClause("civicrm_membership.is_test", $op, $value, "Boolean");
          if ($value) {
            $query->_qill[$grouping][] = ts('Membership is a Test');
          }
        }
        $query->_tables['civicrm_membership'] = $query->_whereTables['civicrm_membership'] = 1;
        return;

      case 'member_auto_renew':
        $op = '=';
        $where = $qill = array();
        foreach ($value as $val) {
          if ($val == 1) {
            $where[] = ' civicrm_membership.contribution_recur_id IS NULL';
            $qill[] = ts('Membership is NOT Auto-Renew');
          }
          elseif ($val == 2) {
            $where[] = ' civicrm_membership.contribution_recur_id IS NOT NULL AND ' . CRM_Contact_BAO_Query::buildClause(
                'ccr.contribution_status_id',
                $op,
                array_search(
                  'In Progress',
                  CRM_Contribute_PseudoConstant::contributionStatus(NULL, 'name')
                ),
                'Integer'
              );
            $qill[] = ts('Membership is Auto-Renew and In Progress');
          }
          elseif ($val == 3) {
            $where[] = ' civicrm_membership.contribution_recur_id IS NOT NULL AND ' .
              CRM_Contact_BAO_Query::buildClause(
                'ccr.contribution_status_id',
                $op,
                array_search(
                  'Failed',
                  CRM_Contribute_PseudoConstant::contributionStatus(NULL, 'name')
                ),
                'Integer'
              );
            $qill[] = ts('Membership is Auto-Renew and Failed');
          }
          elseif ($val == 4) {
            $where[] = ' civicrm_membership.contribution_recur_id IS NOT NULL AND ' .
              CRM_Contact_BAO_Query::buildClause(
                'ccr.contribution_status_id',
                $op,
                array_search(
                  'Cancelled',
                  CRM_Contribute_PseudoConstant::contributionStatus(NULL, 'name')
                ),
                'Integer'
              );
            $qill[] = ts('Membership is Auto-Renew and Cancelled');
          }
          elseif ($val == 5) {
            $where[] = ' civicrm_membership.contribution_recur_id IS NOT NULL AND ccr.end_date IS NOT NULL AND ccr.end_date < NOW()';
            $qill[] = ts('Membership is Auto-Renew and Ended');
          }
          elseif ($val == 6) {
            $where[] = ' civicrm_membership.contribution_recur_id IS NOT NULL';
            $qill[] = ts('Membership is Auto-Renew');
          }
        }
        if (!empty($where)) {
          $query->_where[$grouping][] = implode(' OR ', $where);
          $query->_qill[$grouping][] = implode(' OR ', $qill);
        }

        $query->_tables['civicrm_membership'] = $query->_whereTables['civicrm_membership'] = 1;
        return;

      case 'member_pay_later':
        $query->_where[$grouping][] = CRM_Contact_BAO_Query::buildClause("civicrm_membership.is_pay_later",
          $op,
          $value,
          "Integer"
        );
        if ($value) {
          $query->_qill[$grouping][] = ts("Membership is Pay Later");
        }
        else {
          $query->_qill[$grouping][] = ts("Membership is NOT Pay Later");
        }
        $query->_tables['civicrm_membership'] = $query->_whereTables['civicrm_membership'] = 1;
        return;

      case 'member_is_primary':
        if ($value) {
          $query->_where[$grouping][] = " civicrm_membership.owner_membership_id IS NULL";
          $query->_qill[$grouping][] = ts("Primary Members Only");
        }
        else {
          $query->_where[$grouping][] = " civicrm_membership.owner_membership_id IS NOT NULL";
          $query->_qill[$grouping][] = ts("Related Members Only");
        }
        $query->_tables['civicrm_membership'] = $query->_whereTables['civicrm_membership'] = 1;
        return;

      case 'member_is_override':
        $query->_where[$grouping][] = CRM_Contact_BAO_Query::buildClause("civicrm_membership.is_override", $op, $value, "Boolean");
        $query->_qill[$grouping][] = $value ? ts("Membership Status Is Overriden") : ts("Membership Status Is NOT Overriden");
        $query->_tables['civicrm_membership'] = $query->_whereTables['civicrm_membership'] = 1;
        return;
    }
  }

  /**
   * Generate from clause.
   *
   * @param string $name
   * @param int $mode
   * @param string $side
   *
   * @return string
   */
  public static function from($name, $mode, $side) {
    $from = NULL;
    switch ($name) {
      case 'civicrm_membership':
        $from = " $side JOIN civicrm_membership ON civicrm_membership.contact_id = contact_a.id ";
        $from .= " $side JOIN civicrm_contribution_recur ccr ON ( civicrm_membership.contribution_recur_id = ccr.id )";
        break;

      case 'civicrm_membership_type':
        if ($mode & CRM_Contact_BAO_Query::MODE_MEMBER) {
          $from = " INNER JOIN civicrm_membership_type ON civicrm_membership.membership_type_id = civicrm_membership_type.id ";
        }
        else {
          $from = " $side JOIN civicrm_membership_type ON civicrm_membership.membership_type_id = civicrm_membership_type.id ";
        }
        break;

      case 'civicrm_membership_status':
        if ($mode & CRM_Contact_BAO_Query::MODE_MEMBER) {
          $from = " INNER JOIN civicrm_membership_status ON civicrm_membership.status_id = civicrm_membership_status.id ";
        }
        else {
          $from = " $side JOIN civicrm_membership_status ON civicrm_membership.status_id = civicrm_membership_status.id ";
        }
        break;

      case 'civicrm_membership_payment':
        $from = " $side JOIN civicrm_membership_payment ON civicrm_membership_payment.membership_id = civicrm_membership.id ";
        break;
    }
    return $from;
  }

  /**
   * Get default return properties.
   *
   * @param string $mode
   * @param bool $includeCustomFields
   *
   * @return array|null
   */
  public static function defaultReturnProperties(
    $mode,
    $includeCustomFields = TRUE
  ) {
    $properties = NULL;
    if ($mode & CRM_Contact_BAO_Query::MODE_MEMBER) {
      $properties = array(
        'contact_type' => 1,
        'contact_sub_type' => 1,
        'sort_name' => 1,
        'display_name' => 1,
        'membership_type' => 1,
        'member_is_test' => 1,
        'member_is_pay_later' => 1,
        'join_date' => 1,
        'membership_start_date' => 1,
        'membership_end_date' => 1,
        'membership_source' => 1,
        'membership_status' => 1,
        'membership_id' => 1,
        'owner_membership_id' => 1,
        'max_related' => 1,
        'membership_recur_id' => 1,
        'member_campaign_id' => 1,
        'member_is_override' => 1,
        'member_auto_renew' => 1,
      );

      if ($includeCustomFields) {
        // also get all the custom membership properties
        $fields = CRM_Core_BAO_CustomField::getFieldsForImport('Membership');
        if (!empty($fields)) {
          foreach ($fields as $name => $dontCare) {
            $properties[$name] = 1;
          }
        }
      }
    }

    return $properties;
  }

  /**
   * Build the search form.
   *
   * @param CRM_Core_Form $form
   */
  public static function buildSearchForm(&$form) {
    $membershipStatus = CRM_Member_PseudoConstant::membershipStatus(NULL, NULL, 'label', FALSE, FALSE);
    $form->add('select', 'membership_status_id', ts('Membership Status'), $membershipStatus, FALSE, array(
      'id' => 'membership_status_id',
      'multiple' => 'multiple',
      'class' => 'crm-select2',
    ));

    $form->addEntityRef('membership_type_id', ts('Membership Type'), array(
      'entity' => 'MembershipType',
      'multiple' => TRUE,
      'placeholder' => ts('- any -'),
      'select' => array('minimumInputLength' => 0),
    ));

    $form->addElement('text', 'member_source', ts('Source'));

    CRM_Core_Form_Date::buildDateRange($form, 'member_join_date', 1, '_low', '_high', ts('From'), FALSE);
    $form->addElement('hidden', 'member_join_date_range_error');

    CRM_Core_Form_Date::buildDateRange($form, 'member_start_date', 1, '_low', '_high', ts('From'), FALSE);
    $form->addElement('hidden', 'member_start_date_range_error');

    CRM_Core_Form_Date::buildDateRange($form, 'member_end_date', 1, '_low', '_high', ts('From'), FALSE);
    $form->addElement('hidden', 'member_end_date_range_error');

    $form->addFormRule(array('CRM_Member_BAO_Query', 'formRule'), $form);

    $form->addYesNo('member_is_primary', ts('Primary Member?'), TRUE);
    $form->addYesNo('member_pay_later', ts('Pay Later?'), TRUE);

    $form->add('select', 'member_auto_renew',
      ts('Auto-Renew Subscription Status?'),
      array(
        '1' => ts('- None -'),
        '2' => ts('In Progress'),
        '3' => ts('Failed'),
        '4' => ts('Cancelled'),
        '5' => ts('Ended'),
        '6' => ts('Any'),
      ),
      FALSE, array('class' => 'crm-select2', 'multiple' => 'multiple', 'placeholder' => ts('- any -'))
    );

    $form->addYesNo('member_test', ts('Membership is a Test?'), TRUE);
    $form->addYesNo('member_is_override', ts('Membership Status Is Overriden?'), TRUE);

    // add all the custom  searchable fields
    $extends = array('Membership');
    $groupDetails = CRM_Core_BAO_CustomGroup::getGroupDetail(NULL, TRUE, $extends);
    if ($groupDetails) {
      $form->assign('membershipGroupTree', $groupDetails);
      foreach ($groupDetails as $group) {
        foreach ($group['fields'] as $field) {
          $fieldId = $field['id'];
          $elementName = 'custom_' . $fieldId;
          CRM_Core_BAO_CustomField::addQuickFormElement($form, $elementName, $fieldId, FALSE, TRUE);
        }
      }
    }

    CRM_Campaign_BAO_Campaign::addCampaignInComponentSearch($form, 'member_campaign_id');

    $form->assign('validCiviMember', TRUE);
    $form->setDefaults(array('member_test' => 0));
  }

  /**
   * Possibly un-required function.
   *
   * @param array $row
   * @param int $id
   */
  public static function searchAction(&$row, $id) {
  }

  /**
   * Add membership table.
   *
   * @param array $tables
   */
  public static function tableNames(&$tables) {
    if (!empty($tables['civicrm_membership_log']) || !empty($tables['civicrm_membership_status']) || CRM_Utils_Array::value('civicrm_membership_type', $tables)) {
      $tables = array_merge(array('civicrm_membership' => 1), $tables);
    }
  }

  /**
   * Custom form rules.
   *
   * @param array $fields
   * @param array $files
   * @param CRM_Core_Form $form
   *
   * @return bool|array
   */
  public static function formRule($fields, $files, $form) {
    $errors = array();

    if ((empty($fields['member_join_date_low']) || empty($fields['member_join_date_high'])) && (empty($fields['member_start_date_low']) || empty($fields['member_start_date_high'])) && (empty($fields['member_end_date_low']) || empty($fields['member_end_date_high']))) {
      return TRUE;
    }

    CRM_Utils_Rule::validDateRange($fields, 'member_join_date', $errors, ts('Member Since'));
    CRM_Utils_Rule::validDateRange($fields, 'member_start_date', $errors, ts('Start Date'));
    CRM_Utils_Rule::validDateRange($fields, 'member_end_date', $errors, ts('End Date'));

    return empty($errors) ? TRUE : $errors;
  }

}
