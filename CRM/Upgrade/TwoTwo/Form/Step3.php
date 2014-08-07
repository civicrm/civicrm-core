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
class CRM_Upgrade_TwoTwo_Form_Step3 extends CRM_Upgrade_Form {
  /**
   * @param $errorMessage
   *
   * @return bool
   */
  function verifyPreDBState(&$errorMessage) {
    $errorMessage = ts('Pre-condition failed for upgrade step %1.', array(1 => '3'));
    return $this->checkVersion('2.1.102');
  }

  function upgrade() {
    //1.upgared the domain from email address.
    self::upgradeDomainFromEmail();

    //2.preserve mailer preferences.
    self::mailerPreferences();

    $this->setVersion('2.1.103');
  }

  /**
   * @param $errorMessage
   *
   * @return bool
   */
  function verifyPostDBState(&$errorMessage) {
    // check if Option Group & Option Values tables exists
    if (!CRM_Core_DAO::checkTableExists('civicrm_option_group') ||
      !CRM_Core_DAO::checkTableExists('civicrm_option_value')
    ) {
      $errorMessage .= '  option group or option value table is missing.';
      return FALSE;
    }
    // check fields which MUST be present civicrm_option_group & civicrm_option_value
    if (!CRM_Core_DAO::checkFieldExists('civicrm_option_group', 'id') ||
      !CRM_Core_DAO::checkFieldExists('civicrm_option_group', 'name') ||
      !CRM_Core_DAO::checkFieldExists('civicrm_option_group', 'label') ||
      !CRM_Core_DAO::checkFieldExists('civicrm_option_group', 'description') ||
      !CRM_Core_DAO::checkFieldExists('civicrm_option_group', 'is_reserved') ||
      !CRM_Core_DAO::checkFieldExists('civicrm_option_group', 'is_active')
    ) {
      $errorMessage .= ' Few important fields were found missing in civicrm_option_group table.';
      return FALSE;
    }
    if (!CRM_Core_DAO::checkFieldExists('civicrm_option_value', 'id') ||
      !CRM_Core_DAO::checkFieldExists('civicrm_option_value', 'option_group_id') ||
      !CRM_Core_DAO::checkFieldExists('civicrm_option_value', 'name') ||
      !CRM_Core_DAO::checkFieldExists('civicrm_option_value', 'label') ||
      !CRM_Core_DAO::checkFieldExists('civicrm_option_value', 'description') ||
      !CRM_Core_DAO::checkFieldExists('civicrm_option_value', 'component_id') ||
      !CRM_Core_DAO::checkFieldExists('civicrm_option_value', 'is_active')
    ) {
      $errorMessage .= ' Few important fields were found missing in civicrm_option_value table.';
      return FALSE;
    }
    $errorMessage = ts('Post-condition failed for upgrade step %1.', array(1 => '2'));

    return $this->checkVersion('2.1.103');
  }

  /**
   * @return string
   */
  function getTitle() {
    return ts('CiviCRM 2.2 Upgrade: Step Three (Option Group And Values)');
  }

  /**
   * @return string
   */
  function getTemplateMessage() {
    return '<p>' . ts('Step Three will upgrade the Option Group And Values in your database.') . '</p>';
  }

  /**
   * @return string
   */
  function getButtonTitle() {
    return ts('Upgrade & Continue');
  }

  /**
   * This function preserve the civicrm_domain.email_name and civicrm_domain.email_address
   * as a default option value into "from_email_address" option group
   * and drop these columns from civicrm_domain table.
   * @access public
   *
   * @return void
   */
  function upgradeDomainFromEmail() {
    $query = "
SELECT id
  FROM civicrm_option_group
 WHERE name = 'from_Email_address'";

    $fmaGroup = CRM_Core_DAO::executeQuery($query, CRM_Core_DAO::$_nullArray);
    $fmaGroupId = NULL;
    if ($fmaGroup->fetch()) {
      $fmaGroupId = $fmaGroup->id;
    }
    else {
      //insert 'from_mailing_address' option group.
      $query = "
INSERT INTO civicrm_option_group ( name, description, is_reserved, is_active )
VALUES ('from_email_address', 'From Email Address', 0, 1)";

      CRM_Core_DAO::executeQuery($query, CRM_Core_DAO::$_nullArray);

      //get the group id.
      $query = "
SELECT id
  FROM civicrm_option_group
 WHERE name = 'from_email_address'";
      $dao = CRM_Core_DAO::executeQuery($query, CRM_Core_DAO::$_nullArray);
      if ($dao->fetch()) {
        $fmaGroupId = $dao->id;
      }
    }

    if ($fmaGroupId) {
      //get domain from email address and name as default value.
      $domain = CRM_Core_BAO_Domain::getDomain();
      $domain->selectAdd();
      $domain->selectAdd('email_name', 'email_address');
      $domain->find(TRUE);

      $formEmailAddress = '"' . $domain->email_name . '"<' . $domain->email_address . '>';

      //first check given domain email address exist in option
      //value, if yes make it as domain email address by making
      //it as default from email address..

      //get the existing from email address.

      $optionValues = array();
      $grpParams['name'] = 'from_email_address';
      CRM_Core_OptionValue::getValues($grpParams, $optionValues);

      $maxVal = $maxWt = 1;
      $insertEmailAddress = TRUE;

      if (!empty($optionValues)) {

        //make existing is_default = 0
        $query = "
UPDATE  civicrm_option_value
   SET  is_default = 0
 WHERE  option_group_id = %1";

        $params = array(1 => array($fmaGroupId, 'Integer'));
        CRM_Core_DAO::executeQuery($query, $params);

        //if domain from name and email exist as name or label in option value
        //table need to preserve that name and label  and take care that label
        //and name both remain unique in db.

        $labelValues = $nameValues = array();
        foreach ($optionValues as $id => $value) {
          if ($value['label'] == $formEmailAddress) {
            $labelValues = $value;
          }
          elseif ($value['name'] == $formEmailAddress) {
            $nameValues = $value;
          }
        }

        //as we consider label so label should preserve.
        $updateValues = array();
        if (!empty($labelValues)) {
          $updateValues = $labelValues;
        }

        //if matching name found need to preserve it.
        if (!empty($nameValues)) {

          //copy domain from email address as label.
          if (empty($updateValues)) {
            $updateValues = $nameValues;
            $updateValues['label'] = $formEmailAddress;
          }
          else {
            //since name is also imp so preserve it
            //as name for domain email address record.
            $updateValues['name'] = $nameValues['name'];

            //name is unique so drop name value record.
            //since we transfer this name to found label record.
            CRM_Core_BAO_OptionValue::del($nameValues['id']);
          }
        }

        if (!empty($updateValues)) {
          $insertEmailAddress = FALSE;
          //update label/name found record w/ manupulated values.
          $updateValues['is_active'] = $updateValues['is_default'] = 1;
          $optionValue = new CRM_Core_DAO_OptionValue();
          $optionValue->copyValues($updateValues);
          $optionValue->save();
        }

        //get the max value and wt.
        if ($insertEmailAddress) {
          $query = "
SELECT   max(ROUND(civicrm_option_value.value)) as maxVal,
         max(civicrm_option_value.weight) as maxWt
    FROM civicrm_option_value, civicrm_option_group
   WHERE civicrm_option_group.name = 'from_Email_address'
     AND civicrm_option_value.option_group_id = civicrm_option_group.id
GROUP BY civicrm_option_group.id";

          $dao = CRM_Core_DAO::executeQuery($query, CRM_Core_DAO::$_nullArray);
          if ($dao->fetch()) {
            $maxWt += $dao->maxWt;
            $maxVal += $dao->maxVal;
          }
        }
      }

      if ($insertEmailAddress) {

        //insert domain from email address and name.
        $query = "
INSERT INTO  `civicrm_option_value`
             (`option_group_id`, `label`, `value`, `name` , `grouping`, `filter`, `is_default`,
              `weight`, `description`, `is_optgroup`, `is_reserved`, `is_active`, `component_id`)
     VALUES  ( %1, %2, %3, %2, NULL, 0, 1, %4, 'Default domain email address and from name.', 0, 0, 1, NULL)";

        $params = array(1 => array($fmaGroupId, 'Integer'),
          2 => array($formEmailAddress, 'String'),
          3 => array($maxVal, 'Integer'),
          4 => array($maxWt, 'Integer'),
        );
        CRM_Core_DAO::executeQuery($query, $params);
      }

      //drop civicrm_domain.email_name and
      //civicrm_domain.email_address.
      $query = "
ALTER TABLE `civicrm_domain`
       DROP `email_name`,
       DROP `email_address`";

      CRM_Core_DAO::executeQuery($query, CRM_Core_DAO::$_nullArray);
    }
  }

  /* preserve the mailer preferences from config backend to
     * civicrm_preferences and unset these from config backend.
     */
  function mailerPreferences() {

    $mailerValues = array();
    $mailerFields = array(
      'outBound_option', 'smtpServer', 'smtpPort', 'smtpAuth',
      'smtpUsername', 'smtpPassword', 'sendmail_path', 'sendmail_args',
    );

    //get the mailer preferences from backend
    //store in civicrm_preferences and unset from backend.
    $domain = new CRM_Core_DAO_Domain();
    $domain->find(TRUE);
    if ($domain->config_backend) {
      $backendValues = unserialize($domain->config_backend);
      foreach ($mailerFields as $field) {
        $mailerValues[$field] = CRM_Utils_Array::value($field, $backendValues);
        if (array_key_exists($field, $backendValues)) {
          unset($backendValues[$field]);
        }
      }

      $domain->config_backend = serialize($backendValues);
      $domain->save();

      $sql = 'SELECT id, mailing_backend FROM civicrm_preferences';
      $mailingDomain = CRM_Core_DAO::executeQuery($sql);
      $mailingDomain->find(TRUE);
      $mailingDomain->mailing_backend = serialize($mailerValues);
      $mailingDomain->save();
    }
  }
}

