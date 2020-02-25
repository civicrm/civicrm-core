<?php

/**
 * Collection of upgrade steps
 */
class CRM_Iats_Upgrader extends CRM_Iats_Upgrader_Base {

  // By convention, functions that look like "function upgrade_NNNN()" are
  // upgrade tasks. They are executed in order (like Drupal's hook_update_N).

  public function getCurrentRevision() {
    // reset the saved extension version as well
    try {
      $xmlfile = CRM_Core_Resources::singleton()->getPath('com.iatspayments.civicrm','info.xml');
      $myxml = simplexml_load_file($xmlfile);
      $version = (string) $myxml->version;
      Civi::settings()->set('iats_extension_version', $version);
    }
    catch (Exception $e) {
      // ignore
    }
    return parent::getCurrentRevision();
  }
  /**
   * Standard: run an install sql script
   */
  public function install() {
    $this->executeSqlFile('sql/install.sql');
  }

  /**
   * Standard: run an uninstall script
   */
  public function uninstall() {
   $this->executeSqlFile('sql/uninstall.sql');
  }

  public function upgrade_1_2_010() {
    CRM_Core_ManagedEntities::singleton(TRUE)->reconcile();
    return TRUE;
  }

  /**
   * Example: Run a simple query when a module is enabled
   *
  public function enable() {
    CRM_Core_DAO::executeQuery('UPDATE foo SET is_active = 1 WHERE bar = "whiz"');
  }
  */

  /**
   * Example: Run a simple query when a module is disabled
   *
  public function disable() {
    CRM_Core_DAO::executeQuery('UPDATE foo SET is_active = 0 WHERE bar = "whiz"');
  }
  */

  /**
   * Add the uk_dd table
   *
   * @return TRUE on success
   * @throws Exception
   */
  public function upgrade_1_3_001() {
    $this->ctx->log->info('Applying update 1_3_001');
    $this->executeSqlFile('sql/upgrade_1_3_001.sql');
    return TRUE;
  }

  public function upgrade_1_3_002() {
    CRM_Core_ManagedEntities::singleton(TRUE)->reconcile();
    return TRUE;
  }

  public function upgrade_1_4_001() {
    // reset iATS Extension Version in the civicrm_setting table
    Civi::settings()->set('iats_extension_version', NULL);
    return TRUE;
  }

  public function upgrade_1_5_000() {
    // reset iATS Extension Version in the civicrm_setting table
    Civi::settings()->set('iats_extension_version', NULL);
    return TRUE;
  }

  public function upgrade_1_5_003() {
    // populate the new payment instrument id fields in the payment_processor and payment_processor_type fields
    $version = CRM_Utils_System::version();
    if (version_compare($version, '4.7') >= 0) {
      $this->executeSqlFile('sql/upgrade_1_5_003.sql');
    }
    return TRUE;
  }

  public function upgrade_1_6_001() {
    $this->ctx->log->info('Applying update 1_6_001');
    try {
      $this->executeSqlFile('sql/upgrade_1_6_001.sql');
    }
    catch (Exception $e) {
      $this->ctx->log->info($e->getMessage());
    }
    return TRUE;
  }

  public function upgrade_1_6_002() {
    $this->ctx->log->info('Applying update 1_6_002');
    try {
      $this->executeSqlFile('sql/upgrade_1_6_002.sql');
    }
    catch (Exception $e) {
      $this->ctx->log->info($e->getMessage());
    }
    return TRUE;
  }

  public function upgrade_1_6_003() {
    $this->ctx->log->info('(Re)applying update 1_5_003');
    try {
      $this->ctx->log->info('(Re)applying update 1_5_003');
      $this->executeSqlFile('sql/upgrade_1_5_003.sql');
    }
    catch (Exception $e) {
      $this->ctx->log->info($e->getMessage());
    }
    try {
      $this->ctx->log->info('Setting payment instrument label');
      $acheft_option_value_id = civicrm_api3('OptionValue', 'getvalue', array('return' => 'id', 'value' => 2, 'option_group_id' => 'payment_instrument'));
      civicrm_api3('OptionValue', 'create', array('label' => 'ACHEFT', 'id' => $acheft_option_value_id));
    }
    catch (Exception $e) {
      $this->ctx->log->info($e->getMessage());
    }
    return TRUE;
  }

  public function upgrade_1_6_004() {
    $this->ctx->log->info('Applying update 1_6_004');
    try {
      $this->executeSqlFile('sql/upgrade_1_6_004.sql');
    }
    catch (Exception $e) {
      $this->ctx->log->info($e->getMessage());
    }
    return TRUE;
  }

  public function upgrade_1_7_001() {
    $this->ctx->log->info('Applying update 1_7_001');
    try {
      $this->executeSqlFile('sql/upgrade_1_7_001.sql');
    }
    catch (Exception $e) {
      $this->ctx->log->info($e->getMessage());
    }
    return TRUE;
  }

  /* convert any iATS legacy iats_customer_codes to using the payment_token table */
  public function upgrade_1_7_002() {
    $this->ctx->log->info('Applying update 1_7_002');
    try {
      $insert = 'INSERT INTO civicrm_payment_token (contact_id, payment_processor_id, token, ip_address, email) 
        SELECT cr.contact_id, cr.payment_processor_id, icc.customer_code, icc.ip, icc.email FROM civicrm_contribution_recur cr INNER JOIN civicrm_iats_customer_codes icc ON cr.id = icc.recur_id';
      $dao = CRM_Core_DAO::executeQuery($insert);
      $update = 'UPDATE civicrm_contribution_recur cr INNER JOIN civicrm_iats_customer_codes icc ON cr.id = icc.recur_id 
        INNER JOIN civicrm_payment_token pt on pt.token = icc.customer_code SET cr.payment_token_id = pt.id';
      $dao = CRM_Core_DAO::executeQuery($update);
      $rename = 'RENAME TABLE `civicrm_iats_customer_codes` TO `backup_iats_customer_codes`';
      $dao = CRM_Core_DAO::executeQuery($rename);
    }
    catch (Exception $e) {
      $this->ctx->log->info($e->getMessage());
    }
    return TRUE;
  }

  /* convert any earlier versions of FAPS recurring payment records */
  public function upgrade_1_7_003() {
    $this->ctx->log->info('Applying update 1_7_003');
    try {
      $insert = 'INSERT INTO civicrm_payment_token (contact_id, payment_processor_id, token)
        SELECT cr.contact_id, cr.payment_processor_id, cr.processor_id FROM civicrm_contribution_recur cr
        INNER JOIN civicrm_payment_processor pp ON cr.payment_processor_id = pp.id
        WHERE NOT(ISNULL(processor_id)) AND pp.class_name LIKE "Payment_Faps%"';
      $dao = CRM_Core_DAO::executeQuery($insert);
      $update = 'UPDATE civicrm_contribution_recur cr
        INNER JOIN civicrm_payment_processor pp ON cr.payment_processor_id = pp.id
        INNER JOIN civicrm_payment_token pt on pt.token = cr.processor_id
        SET cr.payment_token_id = pt.id WHERE pp.class_name LIKE "Payment_Faps%"';
      $dao = CRM_Core_DAO::executeQuery($update);
      $rename = 'UPDATE civicrm_contribution_recur cr
        INNER JOIN civicrm_payment_processor pp ON cr.payment_processor_id = pp.id
        SET cr.processor_id = NULL WHERE pp.class_name LIKE "Payment_Faps%"';
      $dao = CRM_Core_DAO::executeQuery($rename);
    }
    catch (Exception $e) {
      $this->ctx->log->info($e->getMessage());
    }
    return TRUE;
  }


  /**
   * Example: Run an external SQL script
   *
   * @return TRUE on success
   * @throws Exception
  public function upgrade_4201() {
    $this->ctx->log->info('Applying update 4201');
    // this path is relative to the extension base dir
    $this->executeSqlFile('sql/upgrade_4201.sql');
    return TRUE;
  } // */


  /**
   * Example: Run a slow upgrade process by breaking it up into smaller chunk
   *
   * @return TRUE on success
   * @throws Exception
  public function upgrade_4202() {
    $this->ctx->log->info('Planning update 4202'); // PEAR Log interface

    $this->addTask(ts('Process first step'), 'processPart1', $arg1, $arg2);
    $this->addTask(ts('Process second step'), 'processPart2', $arg3, $arg4);
    $this->addTask(ts('Process second step'), 'processPart3', $arg5);
    return TRUE;
  }
  public function processPart1($arg1, $arg2) { sleep(10); return TRUE; }
  public function processPart2($arg3, $arg4) { sleep(10); return TRUE; }
  public function processPart3($arg5) { sleep(10); return TRUE; }
  // */


  /**
   * Example: Run an upgrade with a query that touches many (potentially
   * millions) of records by breaking it up into smaller chunks.
   *
   * @return TRUE on success
   * @throws Exception
  public function upgrade_4203() {
    $this->ctx->log->info('Planning update 4203'); // PEAR Log interface

    $minId = CRM_Core_DAO::singleValueQuery('SELECT coalesce(min(id),0) FROM civicrm_contribution');
    $maxId = CRM_Core_DAO::singleValueQuery('SELECT coalesce(max(id),0) FROM civicrm_contribution');
    for ($startId = $minId; $startId <= $maxId; $startId += self::BATCH_SIZE) {
      $endId = $startId + self::BATCH_SIZE - 1;
      $title = ts('Upgrade Batch (%1 => %2)', array(
        1 => $startId,
        2 => $endId,
      ));
      $sql = '
        UPDATE civicrm_contribution SET foobar = whiz(wonky()+wanker)
        WHERE id BETWEEN %1 and %2
      ';
      $params = array(
        1 => array($startId, 'Integer'),
        2 => array($endId, 'Integer'),
      );
      $this->addTask($title, 'executeSql', $sql, $params);
    }
    return TRUE;
  } // */

}
