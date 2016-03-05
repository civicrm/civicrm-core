<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2015                                |
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
 * Upgrade logic for 4.4
 */
class CRM_Upgrade_Incremental_php_FourFour extends CRM_Upgrade_Incremental_Base {
  const MAX_WORD_REPLACEMENT_SIZE = 255;

  /**
   * Compute any messages which should be displayed beforeupgrade.
   *
   * Note: This function is called iteratively for each upcoming
   * revision to the database.
   *
   * @param $preUpgradeMessage
   * @param string $rev
   *   a version number, e.g. '4.4.alpha1', '4.4.beta3', '4.4.0'.
   * @param string $currentVer
   */
  public function setPreUpgradeMessage(&$preUpgradeMessage, $rev, $currentVer = NULL) {
    if ($rev == '4.4.beta1') {
      $apiCalls = self::getConfigArraysAsAPIParams(FALSE);
      $oversizedEntries = 0;
      foreach ($apiCalls as $params) {
        if (!self::isValidWordReplacement($params)) {
          $oversizedEntries++;
        }
      }
      if ($oversizedEntries > 0) {
        $preUpgradeMessage .= '<br/>' . ts("WARNING: There are %1 word-replacement entries which will not be valid in v4.4+ (eg with over 255 characters). They will be dropped during upgrade. For details, consult the CiviCRM log.", array(
            1 => $oversizedEntries,
          ));
      }
    }
  }

  /**
   * Compute any messages which should be displayed after upgrade.
   *
   * @param string $postUpgradeMessage
   *   alterable.
   * @param string $rev
   *   an intermediate version; note that setPostUpgradeMessage is called repeatedly with different $revs.
   */
  public function setPostUpgradeMessage(&$postUpgradeMessage, $rev) {
    if ($rev == '4.4.1') {
      $config = CRM_Core_Config::singleton();
      if (!empty($config->useIDS)) {
        $postUpgradeMessage .= '<br />' . ts("The setting to skip IDS check has been removed. Your site has this configured in civicrm.settings.php but it will no longer work. Instead, use the new permission 'skip IDS check' to bypass the IDS system.");
      }
    }
    if ($rev == '4.4.3') {
      $postUpgradeMessage .= '<br /><br />' . ts('Default versions of the following System Workflow Message Templates have been modified to handle new functionality: <ul><li>Events - Registration Confirmation and Receipt (on-line)</li></ul> If you have modified these templates, please review the new default versions and implement updates as needed to your copies (Administer > Communications > Message Templates > System Workflow Messages).');
    }
    if ($rev == '4.4.3') {
      $query = "SELECT cft.id financial_trxn
FROM civicrm_financial_trxn cft
LEFT JOIN civicrm_entity_financial_trxn ceft ON ceft.financial_trxn_id = cft.id
LEFT JOIN civicrm_contribution cc ON ceft.entity_id = cc.id
WHERE ceft.entity_table = 'civicrm_contribution' AND cft.payment_instrument_id IS NULL;";
      $dao = CRM_Core_DAO::executeQuery($query);
      if ($dao->N) {
        $postUpgradeMessage .= '<br /><br /><strong>' . ts('Your database contains %1 financial transaction records with no payment instrument (Paid By is empty). If you use the Accounting Batches feature this may result in unbalanced transactions. If you do not use this feature, you can ignore the condition (although you will be required to select a Paid By value for new transactions). <a href="%2" target="_blank">You can review steps to correct transactions with missing payment instruments on the wiki.</a>', array(
              1 => $dao->N,
              2 => 'http://wiki.civicrm.org/confluence/display/CRMDOC/Fixing+Transactions+Missing+a+Payment+Instrument+-+4.4.3+Upgrades',
            )) . '</strong>';
      }
    }
    if ($rev == '4.4.6') {
      $postUpgradeMessage .= '<br /><br /><strong>' . ts('Your contact image urls have been upgraded. If your contact image urls did not follow the standard format for image Urls they have not been upgraded. Please check the log to see image urls that were not upgraded.');
    }
  }

  /**
   * Upgrade 4.4.alpha1.
   *
   * @param string $rev
   *
   * @return bool
   */
  public function upgrade_4_4_alpha1($rev) {
    // task to process sql
    $this->addTask(ts('Upgrade DB to %1: SQL', array(1 => '4.4.alpha1')), 'runSql', $rev);

    // Consolidate activity contacts CRM-12274.
    $this->addTask('Consolidate activity contacts', 'activityContacts');

    return TRUE;
  }

  /**
   * @param $rev
   */
  public function upgrade_4_4_beta1($rev) {
    $this->addTask(ts('Upgrade DB to %1: SQL', array(1 => '4.4.beta1')), 'runSql', $rev);

    // add new 'data' column in civicrm_batch
    $query = 'ALTER TABLE civicrm_batch ADD data LONGTEXT NULL COMMENT "cache entered data"';
    CRM_Core_DAO::executeQuery($query, array(), TRUE, NULL, FALSE, FALSE);

    // check if batch entry data exists in civicrm_cache table
    $query = 'SELECT path, data FROM civicrm_cache WHERE group_name = "batch entry"';
    $dao = CRM_Core_DAO::executeQuery($query);
    while ($dao->fetch()) {
      // get batch id $batchId[2]
      $batchId = explode('-', $dao->path);
      $data = unserialize($dao->data);

      // move the data to civicrm_batch table
      CRM_Core_DAO::setFieldValue('CRM_Batch_DAO_Batch', $batchId[2], 'data', json_encode(array('values' => $data)));
    }

    // delete entries from civicrm_cache table
    $query = 'DELETE FROM civicrm_cache WHERE group_name = "batch entry"';
    CRM_Core_DAO::executeQuery($query);

    $this->addTask('Migrate custom word-replacements', 'wordReplacements');
  }

  /**
   * @param $rev
   */
  public function upgrade_4_4_1($rev) {
    $config = CRM_Core_Config::singleton();
    // CRM-13327 upgrade handling for the newly added name badges
    $ogID = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_OptionGroup', 'name_badge', 'id', 'name');
    $nameBadges = array_flip(array_values(CRM_Core_BAO_OptionValue::getOptionValuesAssocArrayFromName('name_badge')));
    unset($nameBadges['Avery 5395']);
    if (!empty($nameBadges)) {
      $dimension = '{"paper-size":"a4","orientation":"portrait","font-name":"times","font-size":6,"font-style":"","NX":2,"NY":4,"metric":"mm","lMargin":6,"tMargin":19,"SpaceX":0,"SpaceY":0,"width":100,"height":65,"lPadding":0,"tPadding":0}';
      $query = "UPDATE civicrm_option_value
        SET value = '{$dimension}'
        WHERE option_group_id = %1 AND name = 'Fattorini Name Badge 100x65'";

      CRM_Core_DAO::executeQuery($query, array(1 => array($ogID, 'Integer')));
    }
    else {
      $dimensions = array(
        1 => '{"paper-size":"a4","orientation":"landscape","font-name":"times","font-size":6,"font-style":"","NX":2,"NY":1,"metric":"mm","lMargin":25,"tMargin":27,"SpaceX":0,"SpaceY":35,"width":106,"height":150,"lPadding":5,"tPadding":5}',
        2 => '{"paper-size":"a4","orientation":"portrait","font-name":"times","font-size":6,"font-style":"","NX":2,"NY":4,"metric":"mm","lMargin":6,"tMargin":19,"SpaceX":0,"SpaceY":0,"width":100,"height":65,"lPadding":0,"tPadding":0}',
        3 => '{"paper-size":"a4","orientation":"portrait","font-name":"times","font-size":6,"font-style":"","NX":2,"NY":2,"metric":"mm","lMargin":10,"tMargin":28,"SpaceX":0,"SpaceY":0,"width":96,"height":121,"lPadding":5,"tPadding":5}',
      );
      $insertStatements = array(
        1 => "($ogID, %1, '{$dimensions[1]}', %1, NULL, 0, NULL, 2, NULL, 0, 0, 1, NULL, NULL)",
        2 => "($ogID, %2, '{$dimensions[2]}', %2, NULL, 0, NULL, 3, NULL, 0, 0, 1, NULL, NULL)",
        3 => "($ogID, %3, '{$dimensions[3]}', %3, NULL, 0, NULL, 4, NULL, 0, 0, 1, NULL, NULL)",
      );

      $queryParams = array(
        1 => array('A6 Badge Portrait 150x106', 'String'),
        2 => array('Fattorini Name Badge 100x65', 'String'),
        3 => array('Hanging Badge 3-3/4" x 4-3"/4', 'String'),
      );

      foreach ($insertStatements as $values) {
        $query = 'INSERT INTO civicrm_option_value (`option_group_id`, `label`, `value`, `name`, `grouping`, `filter`, `is_default`, `weight`, `description`, `is_optgroup`, `is_reserved`, `is_active`, `component_id`, `visibility_id`) VALUES' . $values;
        CRM_Core_DAO::executeQuery($query, $queryParams);
      }
    }

    // CRM-12578 - Prior to this version a CSS file under drupal would disable core css
    if (!empty($config->customCSSURL) && strpos($config->userFramework, 'Drupal') === 0) {
      // The new setting doesn't exist yet - need to create it first
      $sql = '
        INSERT INTO civicrm_setting (group_name, name , value , domain_id , is_domain , created_date)
        VALUES (%1, %2, %3, %4, %5, now())';
      CRM_Core_DAO::executeQuery($sql, array(
        1 => array(CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME, 'String'),
        2 => array('disable_core_css', 'String'),
        3 => array(serialize(1), 'String'),
        4 => array(CRM_Core_Config::domainID(), 'Positive'),
        5 => array(1, 'Int'),
      ));
      Civi::service('settings_manager')->flush();
    }

    // CRM-13701 - Fix $config->timeInputFormat
    $sql = "
      SELECT time_format
      FROM   civicrm_preferences_date
      WHERE  time_format IS NOT NULL
      AND    time_format <> ''
      LIMIT  1
    ";
    $timeInputFormat = CRM_Core_DAO::singleValueQuery($sql);
    if ($timeInputFormat && $timeInputFormat != $config->timeInputFormat) {
      $params = array('timeInputFormat' => $timeInputFormat);
      CRM_Core_BAO_ConfigSetting::add($params);
    }

    // CRM-13698 - add 'Available' and 'No-show' activity statuses
    $insertStatus = array();
    $nsinc = $avinc = $inc = 0;
    if (!CRM_Core_OptionGroup::getValue('activity_status', 'Available', 'name')) {
      $insertStatus[] = "(%1, 'Available', %2, 'Available',  NULL, 0, NULL, %3, 0, 0, 1, NULL, NULL)";
      $avinc = $inc = 1;
    }
    if (!CRM_Core_OptionGroup::getValue('activity_status', 'No_show', 'name')) {
      $insertStatus[] = "(%1, 'No-show', %4, 'No_show',  NULL, 0, NULL, %5, 0, 0, 1, NULL, NULL)";
      $nsinc = $inc + 1;
    }
    if (!empty($insertStatus)) {
      $acOptionGroupID = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_OptionGroup', 'activity_status', 'id', 'name');
      $maxVal = CRM_Core_DAO::singleValueQuery("SELECT MAX(ROUND(op.value)) FROM civicrm_option_value op WHERE op.option_group_id  = $acOptionGroupID");
      $maxWeight = CRM_Core_DAO::singleValueQuery("SELECT MAX(weight) FROM civicrm_option_value WHERE option_group_id = $acOptionGroupID");

      $p[1] = array($acOptionGroupID, 'Integer');
      if ($avinc) {
        $p[2] = array($avinc + $maxVal, 'Integer');
        $p[3] = array($avinc + $maxWeight, 'Integer');
      }
      if ($nsinc) {
        $p[4] = array($nsinc + $maxVal, 'Integer');
        $p[5] = array($nsinc + $maxWeight, 'Integer');
      }
      $insertStatus = implode(',', $insertStatus);

      $sql = "
INSERT INTO
   civicrm_option_value (`option_group_id`, label, `value`, `name`, `grouping`, `filter`, `is_default`, `weight`, `is_optgroup`, `is_reserved`, `is_active`, `component_id`, `visibility_id`)
VALUES {$insertStatus}";
      CRM_Core_DAO::executeQuery($sql, $p);
    }

    $this->addTask(ts('Upgrade DB to %1: SQL', array(1 => '4.4.1')), 'runSql', $rev);
    $this->addTask('Patch word-replacement schema', 'wordReplacements_patch', $rev);
  }

  /**
   * @param $rev
   *
   * @return bool
   */
  public function upgrade_4_4_4($rev) {
    $fkConstraint = array();
    if (!CRM_Core_DAO::checkFKConstraintInFormat('civicrm_activity_contact', 'activity_id')) {
      $fkConstraint[] = "ADD CONSTRAINT `FK_civicrm_activity_contact_activity_id` FOREIGN KEY (`activity_id`) REFERENCES `civicrm_activity` (`id`) ON DELETE CASCADE";
    }
    if (!CRM_Core_DAO::checkFKConstraintInFormat('civicrm_activity_contact', 'contact_id')) {
      $fkConstraint[] = "ADD CONSTRAINT `FK_civicrm_activity_contact_contact_id` FOREIGN KEY (`contact_id`) REFERENCES `civicrm_contact` (`id`) ON DELETE CASCADE;
";
    }

    if (!empty($fkConstraint)) {
      $fkConstraint = implode(',', $fkConstraint);
      $sql = "ALTER TABLE `civicrm_activity_contact`
{$fkConstraint}
";
      // CRM-14036 : delete entries of un-mapped contacts
      CRM_Core_DAO::executeQuery("DELETE ac FROM civicrm_activity_contact ac
LEFT JOIN civicrm_contact c
ON c.id = ac.contact_id
WHERE c.id IS NULL;
");
      // delete entries of un-mapped activities
      CRM_Core_DAO::executeQuery("DELETE ac FROM civicrm_activity_contact ac
LEFT JOIN civicrm_activity a
ON a.id = ac.activity_id
WHERE a.id IS NULL;
");

      CRM_Core_DAO::executeQuery("SET FOREIGN_KEY_CHECKS=0;");
      CRM_Core_DAO::executeQuery($sql);
      CRM_Core_DAO::executeQuery("SET FOREIGN_KEY_CHECKS=1;");
    }

    // task to process sql
    $this->addTask(ts('Upgrade DB to %1: SQL', array(1 => '4.4.4')), 'runSql', $rev);

    // CRM-13892 : add `name` column to dashboard schema
    $query = "
ALTER TABLE civicrm_dashboard
    ADD name varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'Internal name of dashlet.' AFTER domain_id ";
    CRM_Core_DAO::executeQuery($query, array(), TRUE, NULL, FALSE, FALSE);

    $dashboard = new CRM_Core_DAO_Dashboard();
    $dashboard->find();
    while ($dashboard->fetch()) {
      $urlElements = explode('/', $dashboard->url);
      if ($urlElements[1] == 'dashlet') {
        $url = explode('&', $urlElements[2]);
        $name = $url[0];
      }
      elseif ($urlElements[1] == 'report') {
        $url = explode('&', $urlElements[3]);
        $name = 'report/' . $url[0];
      }
      $values .= "
      WHEN {$dashboard->id} THEN '{$name}'
      ";
    }

    $query = "
     UPDATE civicrm_dashboard
  SET name = CASE id
  {$values}
  END;
    ";
    CRM_Core_DAO::executeQuery($query, array(), TRUE, NULL, FALSE, FALSE);

    // CRM-13998 : missing alter statements for civicrm_report_instance
    $this->addTask(ts('Confirm civicrm_report_instance sql table for upgrades'), 'updateReportInstanceTable');

    return TRUE;
  }

  /**
   * @param $rev
   */
  public function upgrade_4_4_6($rev) {
    $sql = "SELECT count(*) AS count FROM INFORMATION_SCHEMA.STATISTICS where " .
      "TABLE_SCHEMA = database() AND INDEX_NAME = 'index_image_url' AND TABLE_NAME = 'civicrm_contact';";
    $dao = CRM_Core_DAO::executeQuery($sql);
    $dao->fetch();
    if ($dao->count < 1) {
      $sql = "CREATE INDEX index_image_url ON civicrm_contact (image_url);";
      $dao = CRM_Core_DAO::executeQuery($sql);
    }
    $minId = CRM_Core_DAO::singleValueQuery('SELECT coalesce(min(id),0) FROM civicrm_contact WHERE image_URL IS NOT NULL');
    $maxId = CRM_Core_DAO::singleValueQuery('SELECT coalesce(max(id),0) FROM civicrm_contact WHERE image_URL IS NOT NULL');
    for ($startId = $minId; $startId <= $maxId; $startId += self::BATCH_SIZE) {
      $endId = $startId + self::BATCH_SIZE - 1;
      $title = "Upgrade image_urls ($startId => $endId)";
      $this->addTask($title, 'upgradeImageUrls', $startId, $endId);
    }
  }

  /**
   * Upgrade script for 4.4.7.
   *
   * @param string $rev
   * @param string $originalVer
   * @param string $latestVer
   */
  public function upgrade_4_4_7($rev, $originalVer, $latestVer) {
    // For WordPress/Joomla(?), cleanup broken image_URL from 4.4.6 upgrades - https://issues.civicrm.org/jira/browse/CRM-14971
    $exBackendUrl = CRM_Utils_System::url('civicrm/contact/imagefile', 'photo=XXX', TRUE); // URL formula from 4.4.6 upgrade
    $exFrontendUrl = CRM_Utils_System::url('civicrm/contact/imagefile', 'photo=XXX', TRUE, NULL, TRUE, TRUE);
    if ($originalVer == '4.4.6' && $exBackendUrl != $exFrontendUrl) {
      $minId = CRM_Core_DAO::singleValueQuery('SELECT coalesce(min(id),0) FROM civicrm_contact WHERE image_URL IS NOT NULL');
      $maxId = CRM_Core_DAO::singleValueQuery('SELECT coalesce(max(id),0) FROM civicrm_contact WHERE image_URL IS NOT NULL');
      for ($startId = $minId; $startId <= $maxId; $startId += self::BATCH_SIZE) {
        $endId = $startId + self::BATCH_SIZE - 1;
        $title = "Upgrade image_urls ($startId => $endId)";
        $this->addTask($title, 'cleanupBackendImageUrls', $startId, $endId);
      }
    }
    $this->addTask(ts('Update saved search information'), 'changeSavedSearch');
  }

  /**
   * Upgrade image URLs.
   *
   * @param \CRM_Queue_TaskContext $ctx
   * @param $startId
   * @param $endId
   *
   * @return bool
   */
  public static function upgradeImageUrls(CRM_Queue_TaskContext $ctx, $startId, $endId) {
    $dao = self::findContactImageUrls($startId, $endId);
    $failures = array();
    $config = CRM_Core_Config::singleton();
    while ($dao->fetch()) {
      $imageURL = $dao->image_url;
      $baseurl = CIVICRM_UF_BASEURL;
      //CRM-15897 - gross hack for joomla to remove the administrator/
      if ($config->userFramework == 'Joomla') {
        $baseurl = str_replace("/administrator/", "/", $baseurl);
      }
      $baselen = strlen($baseurl);
      if (substr($imageURL, 0, $baselen) == $baseurl) {
        $photo = basename($dao->image_url);
        $fullpath = $config->customFileUploadDir . $photo;
        if (file_exists($fullpath)) {
          // For anyone who upgraded 4.4.6 release (eg 4.4.0=>4.4.6), the $newImageUrl incorrectly used backend URLs.
          // For anyone who skipped 4.4.6 (eg 4.4.0=>4.4.7), the $newImageUrl correctly uses frontend URLs
          self::setContactImageUrl($dao->id,
            CRM_Utils_System::url('civicrm/contact/imagefile', 'photo=' . $photo, TRUE, NULL, TRUE, TRUE));
        }
        else {
          $failures[$dao->id] = $dao->image_url;
        }
      }
      else {
        $failures[$dao->id] = $dao->image_url;
      }
    }
    CRM_Core_Error::debug_var('imageUrlsNotUpgraded', $failures);
    return TRUE;
  }

  /**
   * Change saved search.
   *
   * @param \CRM_Queue_TaskContext $ctx
   *
   * @return bool
   */
  public static function changeSavedSearch(CRM_Queue_TaskContext $ctx) {
    $membershipStatuses = array_flip(CRM_Member_PseudoConstant::membershipStatus());

    $dao = new CRM_Contact_DAO_SavedSearch();
    $dao->find();
    while ($dao->fetch()) {
      $formValues = NULL;
      if (!empty($dao->form_values)) {
        $formValues = unserialize($dao->form_values);
      }
      if (!empty($formValues['mapper'])) {
        foreach ($formValues['mapper'] as $key => $value) {
          foreach ($value as $k => $v) {
            if ($v[0] == 'Membership' && in_array($v[1], array('membership_status', 'membership_status_id'))) {
              $value = $formValues['value'][$key][$k];
              $op = $formValues['operator'][$key][$k];
              if ($op == 'IN') {
                $value = trim($value);
                $value = str_replace('(', '', $value);
                $value = str_replace(')', '', $value);

                $v = explode(',', $value);
                $value = array();
                foreach ($v as $k1 => $v2) {
                  if (is_numeric($v2)) {
                    break 2;
                  }
                  $value[$k1] = $membershipStatuses[$v2];
                }
                $formValues['value'][$key][$k] = "(" . implode(',', $value) . ")";
              }
              elseif (in_array($op, array('=', '!='))) {
                if (is_numeric($value)) {
                  break;
                }
                $formValues['value'][$key][$k] = $membershipStatuses[$value];
              }
            }
          }
        }
        $dao->form_values = serialize($formValues);
        $dao->save();
      }
    }

    return TRUE;
  }

  /**
   * For WordPress/Joomla(?) sites which upgraded to 4.4.6, find back-end image_URLs
   * (e.g. "http://example.com/wp-admin/admin.php?page=CiviCRM&amp;q=civicrm/contact/imagefile&amp;photo=123.jpg")
   * and convert them to front-end URLs
   * (e.g. "http://example.com/?page=CiviCRM&amp;q=civicrm/contact/imagefile&amp;photo=123.jpg").
   *
   * @param CRM_Queue_TaskContext $ctx
   * @param int $startId
   * @param int $endId
   * @return bool
   */
  public static function cleanupBackendImageUrls(CRM_Queue_TaskContext $ctx, $startId, $endId) {
    $dao = self::findContactImageUrls($startId, $endId);
    while ($dao->fetch()) {
      $imageUrl = str_replace('&amp;', '&', $dao->image_url);
      if (preg_match(":civicrm/contact/imagefile.*photo=:", $imageUrl)) {
        // looks like one of ours
        $imageUrlParts = parse_url($imageUrl);
        parse_str($imageUrlParts['query'], $imageUrlQuery);
        self::setContactImageUrl($dao->id,
          CRM_Utils_System::url('civicrm/contact/imagefile', 'photo=' . $imageUrlQuery['photo'], TRUE, NULL, TRUE, TRUE));
      }
    }
    return TRUE;
  }

  /**
   * @param int $startId
   * @param int $endId
   * @return CRM_Core_DAO
   *   columns include "id" and "image_URL"
   */
  public static function findContactImageUrls($startId, $endId) {
    $sql = "
SELECT id, image_url
FROM civicrm_contact
WHERE 1
AND id BETWEEN %1 AND %2
AND image_URL IS NOT NULL
";

    $params = array(
      1 => array($startId, 'Integer'),
      2 => array($endId, 'Integer'),
    );
    $dao = CRM_Core_DAO::executeQuery($sql, $params, TRUE, NULL, FALSE, FALSE);
    return $dao;
  }

  /**
   * @param int $cid
   * @param string $newImageUrl
   */
  public static function setContactImageUrl($cid, $newImageUrl) {
    $sql = 'UPDATE civicrm_contact SET image_url=%1 WHERE id=%2';
    $params = array(
      1 => array($newImageUrl, 'String'),
      2 => array($cid, 'Integer'),
    );
    $updatedao = CRM_Core_DAO::executeQuery($sql, $params);
  }

  /**
   * Update activity contacts CRM-12274
   *
   * @param CRM_Queue_TaskContext $ctx
   *
   * @return bool
   *   TRUE for success
   */
  public static function activityContacts(CRM_Queue_TaskContext $ctx) {
    $upgrade = new CRM_Upgrade_Form();

    $activityContacts = CRM_Core_OptionGroup::values('activity_contacts', FALSE, FALSE, FALSE, NULL, 'name');
    $ovValue[] = $sourceID = CRM_Utils_Array::key('Activity Source', $activityContacts);
    $ovValue[] = $assigneeID = CRM_Utils_Array::key('Activity Assignees', $activityContacts);
    $ovValue[] = $targetID = CRM_Utils_Array::key('Activity Targets', $activityContacts);

    $optionGroupID = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_OptionGroup', 'activity_contacts', 'id', 'name');
    if (!empty($ovValue)) {
      $ovValues = implode(', ', $ovValue);
      $query = "
UPDATE civicrm_option_value
SET    is_reserved = 1
WHERE  option_group_id = {$optionGroupID} AND value IN ($ovValues)";

      $dao = CRM_Core_DAO::executeQuery($query);
    }

    if (!$assigneeID) {
      $assigneeID = 1;
      $value[] = "({$optionGroupID}, 'Activity Assignees', 1, 'Activity Assignees', 1, 1, 1)";
    }
    if (!$sourceID) {
      $sourceID = 2;
      $value[] = "({$optionGroupID}, 'Activity Source', 2, 'Activity Source', 2, 1, 1)";
    }
    if (!$targetID) {
      $targetID = 3;
      $value[] = "({$optionGroupID}, 'Activity Targets', 3, 'Activity Targets', 3, 1, 1)";
    }

    if (!$assigneeID || !$sourceID || !$targetID) {
      $insert = "
INSERT INTO civicrm_option_value
(option_group_id, label, value, name, weight, is_reserved, is_active)
VALUES

";
      $values = implode(', ', $value);
      $query = $insert . $values;
      $dao = CRM_Core_DAO::executeQuery($query);
    }

    // sometimes an user does not make a clean backup and the above table
    // already exists, so lets delete this table - CRM-13665
    $query = "DROP TABLE IF EXISTS civicrm_activity_contact";
    $dao = CRM_Core_DAO::executeQuery($query);

    $query = "
CREATE TABLE IF NOT EXISTS civicrm_activity_contact (
  id int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Activity contact id',
  activity_id int(10) unsigned NOT NULL COMMENT 'Foreign key to the activity for this record.',
  contact_id int(10) unsigned NOT NULL COMMENT 'Foreign key to the contact for this record.',
  record_type_id int(10) unsigned DEFAULT NULL COMMENT 'The record type id for this row',
  PRIMARY KEY (id),
  UNIQUE KEY UI_activity_contact (contact_id,activity_id,record_type_id),
  KEY FK_civicrm_activity_contact_activity_id (activity_id)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
";

    $dao = CRM_Core_DAO::executeQuery($query);

    $query = "
INSERT INTO civicrm_activity_contact (activity_id, contact_id, record_type_id)
SELECT      activity_id, target_contact_id, {$targetID} as record_type_id
FROM        civicrm_activity_target";

    $dao = CRM_Core_DAO::executeQuery($query);

    $query = "
INSERT INTO civicrm_activity_contact (activity_id, contact_id, record_type_id)
SELECT      activity_id, assignee_contact_id, {$assigneeID} as record_type_id
FROM        civicrm_activity_assignment";
    $dao = CRM_Core_DAO::executeQuery($query);

    $query = "
  INSERT INTO civicrm_activity_contact (activity_id, contact_id, record_type_id)
SELECT      id, source_contact_id, {$sourceID} as record_type_id
FROM        civicrm_activity
WHERE       source_contact_id IS NOT NULL";

    $dao = CRM_Core_DAO::executeQuery($query);

    $query = "DROP TABLE civicrm_activity_target";
    $dao = CRM_Core_DAO::executeQuery($query);

    $query = "DROP TABLE civicrm_activity_assignment";
    $dao = CRM_Core_DAO::executeQuery($query);

    $query = "ALTER  TABLE civicrm_activity
     DROP FOREIGN KEY FK_civicrm_activity_source_contact_id";

    $dao = CRM_Core_DAO::executeQuery($query);

    $query = "ALTER  TABLE civicrm_activity DROP COLUMN source_contact_id";
    $dao = CRM_Core_DAO::executeQuery($query);

    return TRUE;
  }

  /**
   * Migrate word-replacements from $config to civicrm_word_replacement
   *
   * @param CRM_Queue_TaskContext $ctx
   *
   * @return bool
   *   TRUE for success
   * @see http://issues.civicrm.org/jira/browse/CRM-13187
   */
  public static function wordReplacements(CRM_Queue_TaskContext $ctx) {
    $query = "
CREATE TABLE IF NOT EXISTS `civicrm_word_replacement` (
     `id` int unsigned NOT NULL AUTO_INCREMENT  COMMENT 'Word replacement ID',
     `find_word` varchar(255) COLLATE utf8_bin    COMMENT 'Word which need to be replaced',
     `replace_word` varchar(255) COLLATE utf8_bin    COMMENT 'Word which will replace the word in find',
     `is_active` tinyint    COMMENT 'Is this entry active?',
     `match_type` enum('wildcardMatch', 'exactMatch')   DEFAULT 'wildcardMatch',
     `domain_id` int unsigned    COMMENT 'FK to Domain ID. This is for Domain specific word replacement',
    PRIMARY KEY ( `id` ),
    UNIQUE INDEX `UI_domain_find` (domain_id, find_word),
    CONSTRAINT FK_civicrm_word_replacement_domain_id FOREIGN KEY (`domain_id`) REFERENCES `civicrm_domain`(`id`)
)  ENGINE=InnoDB DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci  ;
    ";
    $dao = CRM_Core_DAO::executeQuery($query);

    self::rebuildWordReplacementTable();
    return TRUE;
  }

  /**
   * Fix misconfigured constraints created in 4.4.0. To distinguish the good
   * and bad configurations, we change the constraint name from "UI_find"
   * (the original name in 4.4.0) to "UI_domain_find" (the new name in
   * 4.4.1).
   *
   * @param CRM_Queue_TaskContext $ctx
   * @param $rev
   *
   * @return bool
   *   TRUE for success
   * @see http://issues.civicrm.org/jira/browse/CRM-13655
   */
  public static function wordReplacements_patch(CRM_Queue_TaskContext $ctx, $rev) {
    if (CRM_Core_DAO::checkConstraintExists('civicrm_word_replacement', 'UI_find')) {
      CRM_Core_DAO::executeQuery("ALTER TABLE civicrm_word_replacement DROP FOREIGN KEY FK_civicrm_word_replacement_domain_id;");
      CRM_Core_DAO::executeQuery("ALTER TABLE civicrm_word_replacement DROP KEY FK_civicrm_word_replacement_domain_id;");
      CRM_Core_DAO::executeQuery("ALTER TABLE civicrm_word_replacement DROP KEY UI_find;");
      CRM_Core_DAO::executeQuery("ALTER TABLE civicrm_word_replacement MODIFY COLUMN `find_word` varchar(255) COLLATE utf8_bin DEFAULT NULL COMMENT 'Word which need to be replaced';");
      CRM_Core_DAO::executeQuery("ALTER TABLE civicrm_word_replacement MODIFY COLUMN `replace_word` varchar(255) COLLATE utf8_bin DEFAULT NULL COMMENT 'Word which will replace the word in find';");
      CRM_Core_DAO::executeQuery("ALTER TABLE civicrm_word_replacement ADD CONSTRAINT UI_domain_find UNIQUE KEY `UI_domain_find` (`domain_id`,`find_word`);");
      CRM_Core_DAO::executeQuery("ALTER TABLE civicrm_word_replacement ADD CONSTRAINT FK_civicrm_word_replacement_domain_id FOREIGN KEY (`domain_id`) REFERENCES `civicrm_domain` (`id`);");
    }
    return TRUE;
  }

  /**
   * Get all the word-replacements stored in config-arrays
   * and convert them to params for the WordReplacement.create API.
   *
   * Note: This function is duplicated in CRM_Core_BAO_WordReplacement and
   * CRM_Upgrade_Incremental_php_FourFour to ensure that the incremental upgrade
   * step behaves consistently even as the BAO evolves in future versions.
   * However, if there's a bug in here prior to 4.4.0, we should apply the
   * bugfix in both places.
   *
   * @param bool $rebuildEach
   *   Whether to perform rebuild after each individual API call.
   * @return array
   *   Each item is $params for WordReplacement.create
   * @see CRM_Core_BAO_WordReplacement::convertConfigArraysToAPIParams
   */
  public static function getConfigArraysAsAPIParams($rebuildEach) {
    $wordReplacementCreateParams = array();
    // get all domains
    $result = civicrm_api3('domain', 'get', array(
      'return' => array('locale_custom_strings'),
    ));
    if (!empty($result["values"])) {
      foreach ($result["values"] as $value) {
        $params = array();
        $params["domain_id"] = $value["id"];
        $params["options"] = array('wp-rebuild' => $rebuildEach);
        // unserialize word match string
        $localeCustomArray = array();
        if (!empty($value["locale_custom_strings"])) {
          $localeCustomArray = unserialize($value["locale_custom_strings"]);
        }
        if (!empty($localeCustomArray)) {
          $wordMatchArray = array();
          // Traverse Language array
          foreach ($localeCustomArray as $localCustomData) {
            // Traverse status array "enabled" "disabled"
            foreach ($localCustomData as $status => $matchTypes) {
              $params["is_active"] = ($status == "enabled") ? TRUE : FALSE;
              // Traverse Match Type array "wildcardMatch" "exactMatch"
              foreach ($matchTypes as $matchType => $words) {
                $params["match_type"] = $matchType;
                foreach ($words as $word => $replace) {
                  $params["find_word"] = $word;
                  $params["replace_word"] = $replace;
                  $wordReplacementCreateParams[] = $params;
                }
              }
            }
          }
        }
      }
    }
    return $wordReplacementCreateParams;
  }

  /**
   * Get all the word-replacements stored in config-arrays
   * and write them out as records in civicrm_word_replacement.
   *
   * Note: This function is duplicated in CRM_Core_BAO_WordReplacement and
   * CRM_Upgrade_Incremental_php_FourFour to ensure that the incremental upgrade
   * step behaves consistently even as the BAO evolves in future versions.
   * However, if there's a bug in here prior to 4.4.0, we should apply the
   * bugfix in both places.
   */
  public static function rebuildWordReplacementTable() {
    civicrm_api3('word_replacement', 'replace', array(
      'options' => array('match' => array('domain_id', 'find_word')),
      'values' => array_filter(self::getConfigArraysAsAPIParams(FALSE), array(__CLASS__, 'isValidWordReplacement')),
    ));
    CRM_Core_BAO_WordReplacement::rebuild();
  }


  /**
   * CRM-13998 missing alter statements for civicrm_report_instance
   */
  public function updateReportInstanceTable() {

    // add civicrm_report_instance.name

    $sql = "SELECT count(*) FROM information_schema.columns "
      . "WHERE table_schema = database() AND table_name = 'civicrm_report_instance' AND COLUMN_NAME = 'name' ";

    $res = CRM_Core_DAO::singleValueQuery($sql);

    if ($res <= 0) {
      $sql = "ALTER TABLE civicrm_report_instance ADD `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'when combined with report_id/template uniquely identifies the instance'";
      $res = CRM_Core_DAO::executeQuery($sql);
    }

    // add civicrm_report_instance args

    $sql = "SELECT count(*) FROM information_schema.columns WHERE table_schema = database() AND table_name = 'civicrm_report_instance' AND COLUMN_NAME = 'args' ";

    $res = CRM_Core_DAO::singleValueQuery($sql);

    if ($res <= 0) {
      $sql = "ALTER TABLE civicrm_report_instance ADD `args` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'arguments that are passed in the url when invoking the instance'";

      $res = CRM_Core_DAO::executeQuery($sql);
    }

    return TRUE;
  }

  /**
   * @param array $params
   * @return bool
   *   TRUE if $params is valid
   */
  public static function isValidWordReplacement($params) {
    $result = strlen($params['find_word']) <= self::MAX_WORD_REPLACEMENT_SIZE && strlen($params['replace_word']) <= self::MAX_WORD_REPLACEMENT_SIZE;
    if (!$result) {
      CRM_Core_Error::debug_var('invalidWordReplacement', $params);
    }
    return $result;
  }

}
