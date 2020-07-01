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
 * Upgrade logic for FiveTwentyNine */
class CRM_Upgrade_Incremental_php_FiveTwentyNine extends CRM_Upgrade_Incremental_Base {

  /**
   * Compute any messages which should be displayed beforeupgrade.
   *
   * Note: This function is called iteratively for each upcoming
   * revision to the database.
   *
   * @param string $preUpgradeMessage
   * @param string $rev
   *   a version number, e.g. '4.4.alpha1', '4.4.beta3', '4.4.0'.
   * @param null $currentVer
   */
  public function setPreUpgradeMessage(&$preUpgradeMessage, $rev, $currentVer = NULL) {
    // Example: Generate a pre-upgrade message.
    // if ($rev == '5.12.34') {
    //   $preUpgradeMessage .= '<p>' . ts('A new permission, "%1", has been added. This permission is now used to control access to the Manage Tags screen.', array(1 => ts('manage tags'))) . '</p>';
    // }
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
    // Example: Generate a post-upgrade message.
    // if ($rev == '5.12.34') {
    //   $postUpgradeMessage .= '<br /><br />' . ts("By default, CiviCRM now disables the ability to import directly from SQL. To use this feature, you must explicitly grant permission 'import SQL datasource'.");
    // }
  }

  /**
   * Upgrade function.
   *
   * @param string $rev
   */
  public function upgrade_5_29_alpha1($rev) {
    $this->addTask(ts('Upgrade DB to %1: SQL', [1 => $rev]), 'runSql', $rev);

    list($minId, $maxId) = CRM_Core_DAO::executeQuery("SELECT coalesce(min(id),0), coalesce(max(id),0)
      FROM civicrm_relationship ")->getDatabaseResult()->fetchRow();
    for ($startId = $minId; $startId <= $maxId; $startId += self::BATCH_SIZE) {
      $endId = $startId + self::BATCH_SIZE - 1;
      $title = ts("Upgrade DB to %1: Fill civicrm_relationship_vtx (%2 => %3)", [
        1 => $rev,
        2 => $startId,
        3 => $endId,
      ]);
      $this->addTask($title, 'populateRelationshipVortex', $startId, $endId);
    }
  }

  /**
   * @param \CRM_Queue_TaskContext $ctx
   * @param int $startId
   *   The lowest relationship ID that should be updated.
   * @param int $endId
   *   The highest relationship ID that should be updated.
   * @return bool
   *   TRUE on success
   */
  public static function populateRelationshipVortex(CRM_Queue_TaskContext $ctx, $startId, $endId) {
    // NOTE: We duplicate CRM_Contact_BAO_RelationshipVortex::$mappings in case
    // the schema evolves over multiple releases.
    $mappings = [
      'a_b' => [
        'relationship_id' => 'rel.id',
        'relationship_type_id' => 'rel.relationship_type_id',
        'orientation' => '"a_b"',
        'near_contact_id' => 'rel.contact_id_a',
        'relation' => 'reltype.name_a_b',
        'far_contact_id' => 'rel.contact_id_b',
      ],
      'b_a' => [
        'relationship_id' => 'rel.id',
        'relationship_type_id' => 'rel.relationship_type_id',
        'orientation' => '"b_a"',
        'near_contact_id' => 'rel.contact_id_b',
        'relation' => 'reltype.name_b_a',
        'far_contact_id' => 'rel.contact_id_a',
      ],
    ];
    $keyFields = ['relationship_id', 'orientation'];

    foreach ($mappings as $mapping) {
      $query = CRM_Utils_SQL_Select::from('civicrm_relationship rel')
        ->join('reltype', 'INNER JOIN civicrm_relationship_type reltype ON rel.relationship_type_id = reltype.id')
        ->syncInto('civicrm_relationship_vtx', $keyFields, $mapping)
        ->where('rel.id >= #START AND rel.id <= #END', [
          '#START' => $startId,
          '#END' => $endId,
        ]);
      $query->execute();
    }

    return TRUE;
  }

}
