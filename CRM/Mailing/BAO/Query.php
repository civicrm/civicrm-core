<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2019                                |
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
 * @copyright CiviCRM LLC (c) 2004-2019
 */
class CRM_Mailing_BAO_Query {

  public static $_mailingFields = NULL;

  /**
   * @return array|null
   */
  public static function &getFields() {
    if (!self::$_mailingFields) {
      self::$_mailingFields = [];
      $_mailingFields['mailing_id'] = [
        'name' => 'mailing_id',
        'title' => ts('Mailing ID'),
        'where' => 'civicrm_mailing.id',
      ];
    }
    return self::$_mailingFields;
  }

  /**
   * If mailings are involved, add the specific Mailing fields
   *
   * @param $query
   */
  public static function select(&$query) {
    // if Mailing mode add mailing id
    if ($query->_mode & CRM_Contact_BAO_Query::MODE_MAILING) {
      $query->_select['mailing_id'] = "civicrm_mailing.id as mailing_id";
      $query->_element['mailing_id'] = 1;

      // base table is contact, so join recipients to it
      $query->_tables['civicrm_mailing_recipients'] = $query->_whereTables['civicrm_mailing_recipients']
        = " INNER JOIN civicrm_mailing_recipients ON civicrm_mailing_recipients.contact_id = contact_a.id ";

      $query->_tables['civicrm_mailing'] = $query->_whereTables['civicrm_mailing'] = 1;

      // get mailing name
      if (!empty($query->_returnProperties['mailing_name'])) {
        $query->_select['mailing_name'] = "civicrm_mailing.name as mailing_name";
        $query->_element['mailing_name'] = 1;
      }

      // get mailing subject
      if (!empty($query->_returnProperties['mailing_subject'])) {
        $query->_select['mailing_subject'] = "civicrm_mailing.subject as mailing_subject";
        $query->_element['mailing_subject'] = 1;
      }

      // get mailing status
      if (!empty($query->_returnProperties['mailing_job_status'])) {
        $query->_tables['civicrm_mailing_job'] = $query->_whereTables['civicrm_mailing_job']
          = " LEFT JOIN civicrm_mailing_job ON civicrm_mailing_job.mailing_id = civicrm_mailing.id AND civicrm_mailing_job.parent_id IS NULL AND civicrm_mailing_job.is_test != 1 ";
        $query->_select['mailing_job_status'] = "civicrm_mailing_job.status as mailing_job_status";
        $query->_element['mailing_job_status'] = 1;
      }

      // get email on hold
      if (!empty($query->_returnProperties['email_on_hold'])) {
        $query->_select['email_on_hold'] = "recipient_email.on_hold as email_on_hold";
        $query->_element['email_on_hold'] = 1;
        $query->_tables['recipient_email'] = $query->_whereTables['recipient_email'] = 1;
      }

      // get recipient email
      if (!empty($query->_returnProperties['email'])) {
        $query->_select['email'] = "recipient_email.email as email";
        $query->_element['email'] = 1;
        $query->_tables['recipient_email'] = $query->_whereTables['recipient_email'] = 1;
      }

      // get user opt out
      if (!empty($query->_returnProperties['contact_opt_out'])) {
        $query->_select['contact_opt_out'] = "contact_a.is_opt_out as contact_opt_out";
        $query->_element['contact_opt_out'] = 1;
      }

      // mailing job end date / completed date
      if (!empty($query->_returnProperties['mailing_job_end_date'])) {
        $query->_tables['civicrm_mailing_job'] = $query->_whereTables['civicrm_mailing_job']
          = " LEFT JOIN civicrm_mailing_job ON civicrm_mailing_job.mailing_id = civicrm_mailing.id AND civicrm_mailing_job.parent_id IS NULL AND civicrm_mailing_job.is_test != 1 ";
        $query->_select['mailing_job_end_date'] = "civicrm_mailing_job.end_date as mailing_job_end_date";
        $query->_element['mailing_job_end_date'] = 1;
      }

      if (!empty($query->_returnProperties['mailing_recipients_id'])) {
        $query->_select['mailing_recipients_id'] = " civicrm_mailing_recipients.id as mailing_recipients_id";
        $query->_element['mailing_recipients_id'] = 1;
      }
    }

    if (CRM_Utils_Array::value('mailing_campaign_id', $query->_returnProperties)) {
      $query->_select['mailing_campaign_id'] = 'civicrm_mailing.campaign_id as mailing_campaign_id';
      $query->_element['mailing_campaign_id'] = 1;
      $query->_tables['civicrm_campaign'] = 1;
    }
  }

  /**
   * @param $query
   */
  public static function where(&$query) {
    $grouping = NULL;
    foreach (array_keys($query->_params) as $id) {
      if (empty($query->_params[$id][0])) {
        continue;
      }
      if (substr($query->_params[$id][0], 0, 8) == 'mailing_') {
        if ($query->_mode == CRM_Contact_BAO_Query::MODE_CONTACTS) {
          $query->_useDistinct = TRUE;
        }
        $grouping = $query->_params[$id][3];
        self::whereClauseSingle($query->_params[$id], $query);
      }
    }
  }

  /**
   * @param string $name
   * @param $mode
   * @param $side
   *
   * @return null|string
   */
  public static function from($name, $mode, $side) {
    $from = NULL;

    switch ($name) {
      case 'civicrm_mailing_recipients':
        $from = " $side JOIN civicrm_mailing_recipients ON civicrm_mailing_recipients.contact_id = contact_a.id";
        break;

      case 'civicrm_mailing_event_queue':
        // this is tightly binded so as to do a check WRT actual job recipients ('child' type jobs)
        $from = " INNER JOIN civicrm_mailing_event_queue ON
          civicrm_mailing_event_queue.contact_id = civicrm_mailing_recipients.contact_id
          AND civicrm_mailing_event_queue.job_id = civicrm_mailing_job.id AND civicrm_mailing_job.job_type = 'child'";
        break;

      case 'civicrm_mailing':
        $from = " $side JOIN civicrm_mailing ON civicrm_mailing.id = civicrm_mailing_recipients.mailing_id ";
        break;

      case 'civicrm_mailing_job':
        $from = " $side JOIN civicrm_mailing_job ON civicrm_mailing_job.mailing_id = civicrm_mailing.id AND civicrm_mailing_job.is_test != 1 ";
        break;

      case 'civicrm_mailing_event_bounce':
      case 'civicrm_mailing_event_delivered':
      case 'civicrm_mailing_event_opened':
      case 'civicrm_mailing_event_reply':
      case 'civicrm_mailing_event_unsubscribe':
      case 'civicrm_mailing_event_forward':
      case 'civicrm_mailing_event_trackable_url_open':
        $from = " $side JOIN $name ON $name.event_queue_id = civicrm_mailing_event_queue.id";
        break;

      case 'recipient_email':
        $from = " $side JOIN civicrm_email recipient_email ON recipient_email.id = civicrm_mailing_recipients.email_id";
        break;

      case 'civicrm_campaign':
        $from = " $side JOIN civicrm_campaign ON civicrm_campaign.id = civicrm_mailing.campaign_id";
        break;
    }

    return $from;
  }

  /**
   * @param $mode
   * @param bool $includeCustomFields
   *
   * @return array|null
   */
  public static function defaultReturnProperties(
    $mode,
    $includeCustomFields = TRUE
  ) {

    $properties = NULL;
    if ($mode & CRM_Contact_BAO_Query::MODE_MAILING) {
      $properties = [
        'mailing_id' => 1,
        'mailing_campaign_id' => 1,
        'mailing_name' => 1,
        'sort_name' => 1,
        'email' => 1,
        'mailing_subject' => 1,
        'email_on_hold' => 1,
        'contact_opt_out' => 1,
        'mailing_job_status' => 1,
        'mailing_job_end_date' => 1,
        'contact_type' => 1,
        'contact_sub_type' => 1,
        'mailing_recipients_id' => 1,
      ];
    }
    return $properties;
  }

  /**
   * @param $values
   * @param $query
   */
  public static function whereClauseSingle(&$values, &$query) {
    list($name, $op, $value, $grouping, $wildcard) = $values;

    switch ($name) {
      case 'mailing_id':
        $selectedMailings = array_flip($value);
        $value = "(" . implode(',', $value) . ")";
        $op = 'IN';
        $query->_where[$grouping][] = "civicrm_mailing.id $op $value";

        $mailings = CRM_Mailing_BAO_Mailing::getMailingsList();
        foreach ($selectedMailings as $id => $dnc) {
          $selectedMailings[$id] = $mailings[$id];
        }
        $selectedMailings = implode(' or ', $selectedMailings);

        $query->_qill[$grouping][] = "Mailing Name $op \"$selectedMailings\"";
        $query->_tables['civicrm_mailing'] = $query->_whereTables['civicrm_mailing'] = 1;
        $query->_tables['civicrm_mailing_recipients'] = $query->_whereTables['civicrm_mailing_recipients'] = 1;
        return;

      case 'mailing_name':
        $value = addslashes($value);
        if ($wildcard) {
          $value = "%$value%";
          $op = 'LIKE';
        }

        $query->_where[$grouping][] = "civicrm_mailing.name $op '$value'";
        $query->_qill[$grouping][] = "Mailing Namename $op \"$value\"";
        $query->_tables['civicrm_mailing'] = $query->_whereTables['civicrm_mailing'] = 1;
        $query->_tables['civicrm_mailing_recipients'] = $query->_whereTables['civicrm_mailing_recipients'] = 1;
        return;

      case 'mailing_date':
      case 'mailing_date_low':
      case 'mailing_date_high':
        // process to / from date
        $query->_tables['civicrm_mailing'] = $query->_whereTables['civicrm_mailing'] = 1;
        $query->_tables['civicrm_mailing_event_queue'] = $query->_whereTables['civicrm_mailing_event_queue'] = 1;
        $query->_tables['civicrm_mailing_job'] = $query->_whereTables['civicrm_mailing_job'] = 1;
        $query->_tables['civicrm_mailing_recipients'] = $query->_whereTables['civicrm_mailing_recipients'] = 1;
        $query->dateQueryBuilder($values,
          'civicrm_mailing_job', 'mailing_date', 'start_date', 'Mailing Delivery Date'
        );
        return;

      case 'mailing_delivery_status':
        $options = CRM_Mailing_PseudoConstant::yesNoOptions('delivered');

        list($name, $op, $value, $grouping, $wildcard) = $values;
        if ($value == 'Y') {
          self::mailingEventQueryBuilder($query, $values,
            'civicrm_mailing_event_delivered',
            'mailing_delivery_status',
            ts('Mailing Delivery'),
            $options
          );
        }
        elseif ($value == 'N') {
          $options['Y'] = $options['N'];
          $values = [$name, $op, 'Y', $grouping, $wildcard];
          self::mailingEventQueryBuilder($query, $values,
            'civicrm_mailing_event_bounce',
            'mailing_delivery_status',
            ts('Mailing Delivery'),
            $options
          );
        }
        return;

      case 'mailing_bounce_types':
        $op = 'IN';
        $values = [$name, $op, $value, $grouping, $wildcard];
        self::mailingEventQueryBuilder($query, $values,
          'civicrm_mailing_event_bounce',
          'bounce_type_id',
          ts('Bounce type(s)'),
          CRM_Core_PseudoConstant::get('CRM_Mailing_Event_DAO_Bounce', 'bounce_type_id', [
            'keyColumn' => 'id',
            'labelColumn' => 'name',
          ])
        );
        return;

      case 'mailing_open_status':
        self::mailingEventQueryBuilder($query, $values,
          'civicrm_mailing_event_opened', 'mailing_open_status', ts('Mailing: Trackable Opens'), CRM_Mailing_PseudoConstant::yesNoOptions('open')
        );
        return;

      case 'mailing_click_status':
        self::mailingEventQueryBuilder($query, $values,
          'civicrm_mailing_event_trackable_url_open', 'mailing_click_status', ts('Mailing: Trackable URL Clicks'), CRM_Mailing_PseudoConstant::yesNoOptions('click')
        );
        return;

      case 'mailing_reply_status':
        self::mailingEventQueryBuilder($query, $values,
          'civicrm_mailing_event_reply', 'mailing_reply_status', ts('Mailing: Trackable Replies'), CRM_Mailing_PseudoConstant::yesNoOptions('reply')
        );
        return;

      case 'mailing_optout':
        $valueTitle = [1 => ts('Opt-out Requests')];
        // include opt-out events only
        $query->_where[$grouping][] = "civicrm_mailing_event_unsubscribe.org_unsubscribe = 1";
        self::mailingEventQueryBuilder($query, $values,
          'civicrm_mailing_event_unsubscribe', 'mailing_unsubscribe',
          ts('Mailing: '), $valueTitle
        );
        return;

      case 'mailing_unsubscribe':
        $valueTitle = [1 => ts('Unsubscribe Requests')];
        // exclude opt-out events
        $query->_where[$grouping][] = "civicrm_mailing_event_unsubscribe.org_unsubscribe = 0";
        self::mailingEventQueryBuilder($query, $values,
          'civicrm_mailing_event_unsubscribe', 'mailing_unsubscribe',
          ts('Mailing: '), $valueTitle
        );
        return;

      case 'mailing_forward':
        $valueTitle = ['Y' => ts('Forwards')];
        // since its a checkbox
        $values[2] = 'Y';
        self::mailingEventQueryBuilder($query, $values,
          'civicrm_mailing_event_forward', 'mailing_forward',
          ts('Mailing: '), $valueTitle
        );
        return;

      case 'mailing_job_status':
        if (!empty($value)) {
          if ($value != 'Scheduled' && $value != 'Canceled') {
            $query->_tables['civicrm_mailing_event_queue'] = $query->_whereTables['civicrm_mailing_event_queue'] = 1;
          }
          $query->_tables['civicrm_mailing'] = $query->_whereTables['civicrm_mailing'] = 1;
          $query->_tables['civicrm_mailing_job'] = $query->_whereTables['civicrm_mailing_job'] = 1;
          $query->_tables['civicrm_mailing_recipients'] = $query->_whereTables['civicrm_mailing_recipients'] = 1;

          $query->_where[$grouping][] = " civicrm_mailing_job.status = '{$value}' ";
          $query->_qill[$grouping][] = "Mailing Job Status IS \"$value\"";
        }
        return;

      case 'mailing_campaign_id':
        $name = 'campaign_id';
        $query->_where[$grouping][] = CRM_Contact_BAO_Query::buildClause("civicrm_mailing.$name", $op, $value, 'Integer');
        list($op, $value) = CRM_Contact_BAO_Query::buildQillForFieldValue('CRM_Mailing_DAO_Mailing', $name, $value, $op);
        $query->_qill[$grouping][] = ts('Campaign %1 %2', [1 => $op, 2 => $value]);
        $query->_tables['civicrm_mailing'] = $query->_whereTables['civicrm_mailing'] = 1;
        $query->_tables['civicrm_mailing_recipients'] = $query->_whereTables['civicrm_mailing_recipients'] = 1;
        return;
    }
  }

  /**
   * Add all the elements shared between Mailing search and advnaced search.
   *
   *
   * @param CRM_Core_Form $form
   */
  public static function buildSearchForm(&$form) {
    // mailing selectors
    $mailings = CRM_Mailing_BAO_Mailing::getMailingsList();

    if (!empty($mailings)) {
      $form->add('select', 'mailing_id', ts('Mailing Name(s)'), $mailings, FALSE,
        ['id' => 'mailing_id', 'multiple' => 'multiple', 'class' => 'crm-select2']
      );
    }

    CRM_Core_Form_Date::buildDateRange($form, 'mailing_date', 1, '_low', '_high', ts('From'), FALSE);
    $form->addElement('hidden', 'mailing_date_range_error');
    $form->addFormRule(['CRM_Mailing_BAO_Query', 'formRule'], $form);

    $mailingJobStatuses = [
      '' => ts('- select -'),
      'Complete' => 'Complete',
      'Scheduled' => 'Scheduled',
      'Running' => 'Running',
      'Canceled' => 'Canceled',
    ];
    $form->addElement('select', 'mailing_job_status', ts('Mailing Job Status'), $mailingJobStatuses, FALSE);

    $mailingBounceTypes = CRM_Core_PseudoConstant::get(
      'CRM_Mailing_Event_DAO_Bounce', 'bounce_type_id',
      ['keyColumn' => 'id', 'labelColumn' => 'name']
    );
    $form->add('select', 'mailing_bounce_types', ts('Bounce Types'), $mailingBounceTypes, FALSE,
      ['id' => 'mailing_bounce_types', 'multiple' => 'multiple', 'class' => 'crm-select2']
    );

    // event filters
    $form->addRadio('mailing_delivery_status', ts('Delivery Status'), CRM_Mailing_PseudoConstant::yesNoOptions('delivered'), ['allowClear' => TRUE]);
    $form->addRadio('mailing_open_status', ts('Trackable Opens'), CRM_Mailing_PseudoConstant::yesNoOptions('open'), ['allowClear' => TRUE]);
    $form->addRadio('mailing_click_status', ts('Trackable URLs'), CRM_Mailing_PseudoConstant::yesNoOptions('click'), ['allowClear' => TRUE]);
    $form->addRadio('mailing_reply_status', ts('Trackable Replies'), CRM_Mailing_PseudoConstant::yesNoOptions('reply'), ['allowClear' => TRUE]);

    $form->add('checkbox', 'mailing_unsubscribe', ts('Unsubscribe Requests'));
    $form->add('checkbox', 'mailing_optout', ts('Opt-out Requests'));
    $form->add('checkbox', 'mailing_forward', ts('Forwards'));
    // Campaign select field
    CRM_Campaign_BAO_Campaign::addCampaignInComponentSearch($form, 'mailing_campaign_id');

    $form->assign('validCiviMailing', TRUE);
  }

  /**
   * @param $row
   * @param int $id
   */
  public static function searchAction(&$row, $id) {
  }

  /**
   * @param $tables
   */
  public static function tableNames(&$tables) {
  }

  /**
   * Filter query results based on which contacts do (not) have a particular mailing event in their history.
   *
   * @param $query
   * @param $values
   * @param string $tableName
   * @param string $fieldName
   * @param $fieldTitle
   *
   * @param $valueTitles
   */
  public static function mailingEventQueryBuilder(&$query, &$values, $tableName, $fieldName, $fieldTitle, &$valueTitles) {
    list($name, $op, $value, $grouping, $wildcard) = $values;

    if (empty($value) || $value == 'A') {
      // don't do any filtering
      return;
    }

    if ($value == 'Y') {
      $query->_where[$grouping][] = $tableName . ".id is not null ";
    }
    elseif ($value == 'N') {
      $query->_where[$grouping][] = $tableName . ".id is null ";
    }

    if (is_array($value)) {
      $query->_where[$grouping][] = "$tableName.$fieldName $op (" . implode(',', $value) . ")";
      $query->_qill[$grouping][] = "$fieldTitle $op " . implode(', ', array_intersect_key($valueTitles, array_flip($value)));
    }
    else {
      $query->_qill[$grouping][] = $fieldTitle . ' - ' . $valueTitles[$value];
    }

    $query->_tables['civicrm_mailing'] = $query->_whereTables['civicrm_mailing'] = 1;
    $query->_tables['civicrm_mailing_job'] = $query->_whereTables['civicrm_mailing_job'] = 1;
    $query->_tables['civicrm_mailing_event_queue'] = $query->_whereTables['civicrm_mailing_event_queue'] = 1;
    $query->_tables['civicrm_mailing_recipients'] = $query->_whereTables['civicrm_mailing_recipients'] = 1;
    $query->_tables[$tableName] = $query->_whereTables[$tableName] = 1;
  }

  /**
   * Check if the values in the date range are in correct chronological order.
   *
   * @param array $fields
   * @param array $files
   * @param CRM_Core_Form $form
   *
   * @return bool|array
   */
  public static function formRule($fields, $files, $form) {
    $errors = [];

    if (empty($fields['mailing_date_high']) || empty($fields['mailing_date_low'])) {
      return TRUE;
    }

    CRM_Utils_Rule::validDateRange($fields, 'mailing_date', $errors, ts('Mailing Date'));

    return empty($errors) ? TRUE : $errors;
  }

}
