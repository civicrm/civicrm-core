<?php
use CRM_OAuth_ExtensionUtil as E;

/**
 * Collection of upgrade steps.
 */
class CRM_OAuth_Upgrader extends CRM_Extension_Upgrader_Base {

  /**
   * @see CRM_Utils_Hook::install()
   */
  public function install() {
    $domainId = CRM_Core_Config::domainID();
    civicrm_api3('Navigation', 'create', [
      'sequential' => 1,
      'domain_id' => $domainId,
      'url' => "civicrm/admin/oauth",
      'permission' => "manage OAuth client",
      'label' => "OAuth",
      'permission_operator' => "OR",
      'has_separator' => 0,
      'is_active' => 1,
      'parent_id' => "System Settings",
    ]);
  }

  /**
   * Add support for OAuthContactToken
   *
   * @return bool
   * @throws Exception
   */
  public function upgrade_0001(): bool {
    $this->ctx->log->info('Applying update 0001');
    $this->executeSqlFile('sql/upgrade_0001.sql');
    return TRUE;
  }

  /**
   * Add support for tenancy
   *
   * @return bool
   * @throws Exception
   */
  public function upgrade_5581(): bool {
    $this->ctx->log->info('Applying oauth-client update 5581. Adding tenant column to the civicrm_oauth_client table.');
    $this->addTask('Add Tenant ID to civicrm_oauth_client', 'addColumn', 'civicrm_oauth_client', 'tenant', 'varchar(128) NULL COMMENT "Tenant ID" AFTER guid');
    return TRUE;
  }

}
