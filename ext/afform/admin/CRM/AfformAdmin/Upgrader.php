<?php
use CRM_AfformAdmin_ExtensionUtil as E;

/**
 * Collection of upgrade steps.
 */
class CRM_AfformAdmin_Upgrader extends CRM_Extension_Upgrader_Base {

  /**
   * Obsolete upgrade step, no longer does anything
   *
   * @return bool
   * @throws Exception
   */
  public function upgrade_0001(): bool {
    $this->ctx->log->info('Applying update 0001');
    return TRUE;
  }

}
