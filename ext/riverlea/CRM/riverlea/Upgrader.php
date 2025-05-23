<?php
use CRM_riverlea_ExtensionUtil as E;

/**
 * Collection of upgrade steps.
 */
class CRM_riverlea_Upgrader extends \CRM_Extension_Upgrader_Base {

  public function upgrade_1000(): bool {
    E::schema()->createEntityTable('schema/upgrader/1000-RiverleaStream.entityType.php');
    return TRUE;
  }

}
