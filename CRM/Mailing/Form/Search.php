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
class CRM_Mailing_Form_Search extends CRM_Core_Form_Search {

  /**
   * Get the default entity being queried.
   *
   * @return string
   */
  public function getDefaultEntity() {
    return 'Mailing';
  }

  public function preProcess() {
    parent::preProcess();
  }

  public function buildQuickForm() {
    $parent = $this->controller->getParent();
    $nameTextLabel = ($parent->_sms) ? ts('SMS Name') : ts('Mailing Name');

    $this->add('text', 'mailing_name', $nameTextLabel,
      CRM_Core_DAO::getAttribute('CRM_Mailing_DAO_Mailing', 'title')
    );

    $dateFieldLabel = ($parent->_sms) ? ts('SMS Date') : ts('Mailing Date');
    $this->addDatePickerRange('mailing', $dateFieldLabel);

    $this->add('text', 'sort_name', ts('Created or Sent by'),
      CRM_Core_DAO::getAttribute('CRM_Contact_DAO_Contact', 'sort_name')
    );

    CRM_Campaign_BAO_Campaign::addCampaignInComponentSearch($this);

    // CRM-15434 - Fix mailing search by status in non-English languages
    $statusVals = CRM_Core_SelectValues::getMailingJobStatus();
    foreach ($statusVals as $statusId => $statusName) {
      $this->addElement('checkbox', "mailing_status[$statusId]", NULL, $statusName);
    }
    $this->addElement('checkbox', 'status_unscheduled', NULL, ts('Draft / Unscheduled'));
    $this->addYesNo('is_archived', ts('Mailing is Archived'), TRUE);

    // Search by language, if multi-lingual
    $enabledLanguages = CRM_Core_I18n::languages(TRUE);

    if (count($enabledLanguages) > 1) {
      $this->addElement('select', 'language', ts('Language'), ['' => ts('- all languages -')] + $enabledLanguages, ['class' => 'crm-select2']);
    }

    if ($parent->_sms) {
      $this->addElement('hidden', 'sms', $parent->_sms);
    }
    $this->add('hidden', 'hidden_find_mailings', 1);

    $this->addButtons([
      [
        'type' => 'refresh',
        'name' => ts('Search'),
        'isDefault' => TRUE,
      ],
    ]);
  }

  /**
   * @return array
   */
  public function setDefaultValues() {
    $defaults = $statusVals = [];
    $parent = $this->controller->getParent();

    if ($parent->get('unscheduled')) {
      $defaults['status_unscheduled'] = 1;
    }
    if ($parent->get('scheduled')) {
      $statusVals = array_keys(CRM_Core_SelectValues::getMailingJobStatus());
      $defaults['is_archived'] = 0;
    }
    if ($parent->get('archived')) {
      $defaults['is_archived'] = 1;
    }
    foreach ($statusVals as $status) {
      $defaults['mailing_status'][$status] = 1;
    }

    if ($parent->_sms) {
      $defaults['sms'] = 1;
    }
    return $defaults;
  }

  public function postProcess() {
    $params = $this->controller->exportValues($this->_name);

    if (!empty($params['mailing_relative'])) {
      list($params['mailing_low'], $params['mailing_high']) = CRM_Utils_Date::getFromTo($params['mailing_relative'], $params['mailing_low'], $params['mailing_high']);
      unset($params['mailing_relative']);
    }
    elseif (!empty($params['mailing_high'])) {
      $params['mailing_high'] .= ' ' . '23:59:59';
    }

    $parent = $this->controller->getParent();
    if (!empty($params)) {
      $fields = [
        'mailing_name',
        'mailing_low',
        'mailing_high',
        'sort_name',
        'campaign_id',
        'mailing_status',
        'sms',
        'status_unscheduled',
        'is_archived',
        'language',
        'hidden_find_mailings',
      ];
      foreach ($fields as $field) {
        if (isset($params[$field]) &&
          !CRM_Utils_System::isNull($params[$field])
        ) {
          $parent->set($field, $params[$field]);
        }
        else {
          $parent->set($field, NULL);
        }
      }
    }
  }

}
