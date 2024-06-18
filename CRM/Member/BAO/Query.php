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
class CRM_Member_BAO_Query extends CRM_Core_BAO_Query {

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
      if (!empty($query->_returnProperties['membership_join_date'])) {
        $query->_select['membership_join_date'] = "civicrm_membership.join_date as membership_join_date";
        $query->_element['membership_join_date'] = 1;
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

      if (!empty($query->_returnProperties['membership_is_current_member'])) {
        $query->_select['is_current_member'] = "civicrm_membership_status.is_current_member as is_current_member";
        $query->_element['is_current_member'] = 1;
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
        if ($query->_mode == CRM_Contact_BAO_Query::MODE_CONTACTS) {
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
    if ($query->buildDateRangeQuery($values)) {
      // @todo - move this to Contact_Query in or near the call to
      // $this->buildRelativeDateQuery($values);
      return;
    }
    [$name, $op, $value, $grouping] = $values;
    $fields = self::getFields();

    $quoteValue = NULL;

    if (!empty($value) && !is_array($value)) {
      $quoteValue = "\"$value\"";
    }

    $fieldAliases = self::getLegacySupportedFields();

    $fieldName = $name = self::getFieldName($values);
    $qillName = $name;
    if (in_array($name, $fieldAliases)) {
      $qillName = array_search($name, $fieldAliases);
    }
    $pseudoExtraParam = [];
    $fieldSpec = CRM_Utils_Array::value($fieldName, $fields, []);
    $tableName = CRM_Utils_Array::value('table_name', $fieldSpec, 'civicrm_membership');
    $dataType = CRM_Utils_Type::typeToString(CRM_Utils_Array::value('type', $fieldSpec));
    if ($dataType === 'Timestamp' || $dataType === 'Date') {
      $title = empty($fieldSpec['unique_title']) ? $fieldSpec['title'] : $fieldSpec['unique_title'];
      $query->_tables['civicrm_membership'] = $query->_whereTables['civicrm_membership'] = 1;
      $query->dateQueryBuilder($values,
        $tableName, $fieldName, $fieldSpec['name'], $title
      );
      return;
    }

    switch ($name) {
      case 'member_join_date_low':
      case 'member_join_date_high':
        CRM_Core_Error::deprecatedWarning('member_join_date field is deprecated please use membership_join_date field instead');
        $fldName = str_replace(['_low', '_high'], '', $name);
        $query->dateQueryBuilder($values,
          'civicrm_membership', $fldName, 'join_date',
          'Member Since'
        );
        return;

      case 'member_start_date_low':
      case 'member_start_date_high':
        CRM_Core_Error::deprecatedWarning('member_start_date field is deprecated please use membership_start_date field instead');
        $fldName = str_replace(['_low', '_high'], '', $name);
        $query->dateQueryBuilder($values,
          'civicrm_membership', $fldName, 'start_date',
          'Start Date'
        );
        return;

      case 'member_end_date_low':
      case 'member_end_date_high':
        CRM_Core_Error::deprecatedWarning('member_end_date field is deprecated please use membership_end_date field instead');
        $fldName = str_replace(['_low', '_high'], '', $name);
        $query->dateQueryBuilder($values,
          'civicrm_membership', $fldName, 'end_date',
          'End Date'
        );
        return;

      case 'member_join_date':
        // This seeks to correct issue 5294. 'Old' smart groups will need
        // updating in due course as the selection criteion here doesn't show
        // any more. Without this patch, the smart group crashes.
        // It will enable one update 'offending' smart groups.
        CRM_Core_Error::deprecatedWarning('member_join_date field is deprecated please use membership_join_date field instead');
        $op = '>=';
        $date = CRM_Utils_Date::format($value);
        if ($date) {
          $query->_where[$grouping][] = "civicrm_membership.join_date {$op} {$date}";
          if (!is_array($value)) {
            $temp = $value;
            // Erase it.
            $value = NULL;
            $value[] = [];
            // Year.
            $value[0] = substr($temp, 0, 4);
            // Month.
            $value[1] = substr($temp, 4, 2);
            // Day.
            $value[2] = substr($temp, 6, 2);
          }
          $format = CRM_Utils_Date::customFormat(CRM_Utils_Date::format(array_reverse($value), '-'));
          $query->_qill[$grouping][] = ts('Member Since %2 %1', [1 => $format, 2 => $op]);
        }

        return;

      case 'member_source':
      case 'membership_source':
        $fieldSpec = $fields['membership_source'] ?? [];
        $query->handleWhereFromMetadata($fieldSpec, $name, $value, $op);
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
          $value = ['IN' => explode(',', $value)];
        }
      case 'membership_id':
        // CRM-18523 Updated to membership_id but kept member_id case for backwards compatibility
      case 'member_id':
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
        [$op, $value] = CRM_Contact_BAO_Query::buildQillForFieldValue('CRM_Member_DAO_Membership', $name, $value, $op);
        $query->_qill[$grouping][] = $qillName . ' ' . $op . ' ' . $value;
        $query->_tables['civicrm_membership'] = $query->_whereTables['civicrm_membership'] = 1;
        return;

      case 'membership_is_current_member':
        // We don't want to include all tests for sql OR CRM-7827
        $query->_where[$grouping][] = CRM_Contact_BAO_Query::buildClause("civicrm_membership_status.is_current_member", $op, $value, "Boolean");
        $query->_qill[$grouping][] = $value ? ts('Is a current member') : ts('Is a non-current member');
        $query->_tables['civicrm_membership_status'] = $query->_whereTables['civicrm_membership_status'] = 1;
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
        $where = $qill = [];
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
      $properties = [
        'contact_type' => 1,
        'contact_sub_type' => 1,
        'sort_name' => 1,
        'display_name' => 1,
        'membership_type' => 1,
        'member_is_test' => 1,
        'member_is_pay_later' => 1,
        'membership_join_date' => 1,
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
      ];

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
   * Get the metadata for fields to be included on the grant search form.
   *
   * @throws \CRM_Core_Exception
   */
  public static function getSearchFieldMetadata() {
    $fields = [
      'membership_join_date',
      'membership_start_date',
      'membership_end_date',
      'membership_type_id',
      'membership_status_id',
    ];
    $metadata = civicrm_api3('Membership', 'getfields', [])['values'];
    // We should really have a unique name in the url but to reduce risk of regression just hacking
    // here for now, since this is being done as an rc fix & the other is moderately risky.
    // https://lab.civicrm.org/dev/user-interface/-/issues/14
    $metadata['membership_status_id'] = $metadata['status_id'];
    // It can't be autoadded due to ^^.
    $metadata['membership_status_id']['is_pseudofield'] = TRUE;
    unset($metadata['status_id']);
    return array_intersect_key($metadata, array_flip($fields));
  }

  /**
   * Build the search form.
   *
   * @param CRM_Core_Form $form
   *
   * @throws \CRM_Core_Exception
   */
  public static function buildSearchForm(&$form) {
    $form->addSearchFieldMetadata(['Membership' => self::getSearchFieldMetadata()]);
    $form->addFormFieldsFromMetadata();
    $membershipStatus = CRM_Member_PseudoConstant::membershipStatus(NULL, NULL, 'label', FALSE, FALSE);
    $form->add('select', 'membership_status_id', ts('Membership Status'), $membershipStatus, FALSE, [
      'id' => 'membership_status_id',
      'multiple' => 'multiple',
      'class' => 'crm-select2',
    ]);

    $form->addElement('text', 'member_source', ts('Membership Source'));
    $form->add('number', 'membership_id', ts('Membership ID'), ['class' => 'four', 'min' => 1]);

    $form->addYesNo('membership_is_current_member', ts('Current Member?'), TRUE);
    $form->addYesNo('member_is_primary', ts('Primary Member?'), TRUE);
    $form->addYesNo('member_pay_later', ts('Pay Later?'), TRUE);

    $form->add('select', 'member_auto_renew',
      ts('Auto-Renew Subscription Status?'),
      [
        '1' => ts('- None -'),
        '2' => ts('In Progress'),
        '3' => ts('Failed'),
        '4' => ts('Cancelled'),
        '5' => ts('Ended'),
        '6' => ts('Any'),
      ],
      FALSE, ['class' => 'crm-select2', 'multiple' => 'multiple', 'placeholder' => ts('- any -')]
    );

    $form->addYesNo('member_test', ts('Membership is a Test?'), TRUE);
    $form->addYesNo('member_is_override', ts('Membership Status Is Overriden?'), TRUE);

    self::addCustomFormFields($form, ['Membership']);

    CRM_Campaign_BAO_Campaign::addCampaignInComponentSearch($form, 'member_campaign_id');

    $form->assign('validCiviMember', TRUE);
    $form->setDefaults(['member_test' => 0]);
  }

  /**
   * Add membership table.
   *
   * @param array $tables
   */
  public static function tableNames(&$tables) {
    if (!empty($tables['civicrm_membership_log']) || !empty($tables['civicrm_membership_status']) || !empty($tables['civicrm_membership_type'])) {
      $tables = array_merge(['civicrm_membership' => 1], $tables);
    }
  }

}
