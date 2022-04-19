<?php

namespace Civi\Financialacls;

class Hooks {

  /**
   * Listener for 'civi.api4.authorizeRecord::Contribution'
   *
   * @param \Civi\Api4\Event\AuthorizeRecordEvent $e
   * @throws \CRM_Core_Exception
   */
  public static function api4_authorizeContribution(\Civi\Api4\Event\AuthorizeRecordEvent $e) {
    if (!financialacls_is_acl_limiting_enabled()) {
      return;
    }
    if ($e->getEntityName() === 'Contribution') {
      $contributionID = $e->getRecord()['id'] ?? NULL;
      $financialTypeID = $e->getRecord()['financial_type_id'] ?? \CRM_Core_DAO::getFieldValue('CRM_Contribute_DAO_Contribution', $contributionID, 'financial_type_id');
      if (!\CRM_Core_Permission::check(_financialacls_getRequiredPermission($financialTypeID, $e->getActionName()), $e->getUserID())) {
        $e->setAuthorized(FALSE);
      }
      if ($e->getActionName() === 'delete') {
        // First check contribution financial type
        // Now check permissioned line items & permissioned contribution
        if (!\CRM_Financial_BAO_FinancialType::checkPermissionedLineItems($contributionID, 'delete', FALSE, $e->getUserID())
        ) {
          $e->setAuthorized(FALSE);
        }
      }
    }
  }

}
