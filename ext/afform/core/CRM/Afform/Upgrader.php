<?php
use CRM_Afform_ExtensionUtil as E;

/**
 * Collection of upgrade steps.
 */
class CRM_Afform_Upgrader extends CRM_Extension_Upgrader_Base {

  /**
   * Update names of blocks and joins
   *
   * @return bool
   */
  public function upgrade_1000(): bool {
    $this->ctx->log->info('Applying update 1000');
    $scanner = new CRM_Afform_AfformScanner();
    $localDir = $scanner->getSiteLocalPath();

    // Update form markup with new block directive names
    $replacements = [
      'afjoin-address-default>' => 'afblock-contact-address>',
      'afjoin-email-default>' => 'afblock-contact-email>',
      'afjoin-i-m-default>' => 'afblock-contact-i-m>',
      'afjoin-phone-default>' => 'afblock-contact-phone>',
      'afjoin-website-default>' => 'afblock-contact-website>',
      'afjoin-custom-' => 'afblock-custom-',
    ];
    foreach (glob("$localDir/*." . $scanner::LAYOUT_FILE) as $fileName) {
      $html = file_get_contents($fileName);
      $html = str_replace(array_keys($replacements), array_values($replacements), $html);
      file_put_contents($fileName, $html);
    }
    $this->updateBlockMetadata($scanner);

    return TRUE;
  }

  /**
   * Update form metadata with new block property names
   * @param CRM_Afform_AfformScanner $scanner
   */
  private function updateBlockMetadata(CRM_Afform_AfformScanner $scanner): void {
    $localDir = $scanner->getSiteLocalPath();
    $replacements = [
      'join' => 'join_entity',
      'block' => 'entity_type',
    ];
    foreach (glob("$localDir/*." . $scanner::METADATA_JSON) as $fileName) {
      $meta = json_decode(file_get_contents($fileName), TRUE);
      foreach ($replacements as $oldKey => $newKey) {
        if (isset($meta[$oldKey])) {
          $meta[$newKey] = $meta[$oldKey];
          unset($meta[$oldKey]);
        }
      }
      if (!empty($meta['entity_type'])) {
        $meta['type'] = 'block';
        if ($meta['entity_type'] === '*') {
          unset($meta['entity_type']);
        }
      }
      file_put_contents($fileName, json_encode($meta, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }
  }

  /**
   * Upgrade 1001 - install civicrm_afform_submission table
   * @return bool
   */
  public function upgrade_1001(): bool {
    $this->ctx->log->info('Applying update 1001 - install civicrm_afform_submission table.');
    if (!CRM_Core_DAO::singleValueQuery("SHOW TABLES LIKE 'civicrm_afform_submission'")) {
      $this->executeSqlFile('sql/upgrade_1001.sql');
    }
    return TRUE;
  }

  /**
   * Upgrade 1002 - repeat block metadata update to fix errors when saving blocks
   * @see #22963
   * @return bool
   */
  public function upgrade_1002(): bool {
    $this->ctx->log->info('Applying update 1002 - repeat block metadata update.');
    $scanner = new CRM_Afform_AfformScanner();
    $this->updateBlockMetadata($scanner);

    return TRUE;
  }

  /**
   * Upgrade 1003 - add status column to afform submissions
   * @see https://lab.civicrm.org/dev/core/-/issues/4232
   * @return bool
   */
  public function upgrade_1003(): bool {
    $this->ctx->log->info('Applying update 1003 - add status column to afform submissions.');
    $this->addColumn('civicrm_afform_submission', 'status_id', "INT UNSIGNED NOT NULL  DEFAULT 1 COMMENT 'fk to Afform Submission Status options in civicrm_option_values'");
    return TRUE;
  }

  /**
   * Upgrade 1004 - initialize form builder source to translate
   * @see https://github.com/civicrm/civicrm-core/pull/32859
   * @return bool
   */
  public function upgrade_1004(): bool {
    $this->ctx->log->info('Applying update 1004 - initialize translatable afform string sources.');
    \Civi\Afform\Utils::initSourceTranslations();
    return TRUE;
  }

  public function upgrade_1005(): bool {
    E::schema()->createEntityTable('schema/upgrader/1005-SearchParamSet.entityType.php');
    return TRUE;
  }

}
