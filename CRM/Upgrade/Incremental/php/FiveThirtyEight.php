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
 * Upgrade logic for FiveThirtyEight
 */
class CRM_Upgrade_Incremental_php_FiveThirtyEight extends CRM_Upgrade_Incremental_Base {

  /**
   * Upgrade function.
   *
   * @param string $rev
   */
  public function upgrade_5_38_alpha1($rev) {
    $this->addTask(ts('Upgrade DB to %1: SQL', [1 => $rev]), 'runSql', $rev);
    $this->addTask('Enable Payflow Pro Payment Processor Extension', 'enablePayflowProExtension');
    // Refresh extension cache due to renaming search_kit extension
    CRM_Extension_System::singleton()->getManager()->refresh();
  }

  public static function enablePayflowProExtension(CRM_Queue_TaskContext $ctx) {
    $payflowProPaymentProcessorType = CRM_Core_DAO::singleValueQuery("SELECT id FROM civicrm_payment_processor_type WHERE class_name = 'Payment_PayflowPro'");
    if ($payflowProPaymentProcessorType) {
      $payflowProPaymentProcessorCount = CRM_Core_DAO::singleValueQuery("SELECT count(id) FROM civicrm_payment_processor WHERE payment_processor_type_id = %1", [1 => [$payflowProPaymentProcessorType, 'Positive']]);
      if ($payflowProPaymentProcessorCount) {
        $insert = CRM_Utils_SQL_Insert::into('civicrm_extension')->row([
          'type' => 'module',
          'full_name' => 'payflowpro',
          'name' => 'PayPal PayFlo Pro Integration',
          'label' => 'PayPal PayFlo Pro Integration',
          'file' => 'payflowpro',
          'schema_version' => NULL,
          'is_active' => 1,
        ]);
        CRM_Core_DAO::executeQuery($insert->usingReplace()->toSQL());
        $managedEntity = CRM_Utils_SQL_Insert::into('civicrm_managed')->row([
          'name' => 'PayflowPro',
          'module' => 'payflowpro',
          'entity_type' => 'PaymentProcessorType',
          'entity_id' => $payflowProPaymentProcessorType,
          'cleanup' => NULL,
        ]);
        CRM_Core_DAO::executeQuery($managedEntity->usingReplace()->toSQL());
      }
      else {
        CRM_Core_DAO::executeQuery("DELETE FROM civicrm_payment_processor_type WHERE id = %1", [1 => [$payflowProPaymentProcessorType, 'Positive']]);
      }
    }
    return TRUE;
  }

}
