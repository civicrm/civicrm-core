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

use Civi\Core\HookInterface;

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */
class CRM_Core_BAO_TranslationSource extends CRM_Core_DAO_TranslationSource implements HookInterface {

  /**
   * Get a list of tables with translatable strings.
   *
   * @return string[]
   *   Ex: ['civicrm_event' => 'civicrm_event']
   */
  public static function getEntityTables() {
    if (!isset(Civi::$statics[__CLASS__]['allTables'])) {
      $tables = array_keys(self::getTranslatedFields());
      Civi::$statics[__CLASS__]['allTables'] = array_combine($tables, $tables);
    }
    return Civi::$statics[__CLASS__]['allTables'];
  }

  /**
   * Get a list of fields with translatable strings.
   *
   * @return string[]
   *   Ex: ['title' => 'title', 'description' => 'description']
   */
  public static function getEntityFields() {
    if (!isset(Civi::$statics[__CLASS__]['allFields'])) {
      $allFields = [];
      foreach (self::getTranslatedFields() as $columns) {
        foreach ($columns as $column => $sqlExpr) {
          $allFields[$column] = $column;
        }
      }
      Civi::$statics[__CLASS__]['allFields'] = $allFields;
    }
    return Civi::$statics[__CLASS__]['allFields'];
  }

  /**
   * Get all the translations sources replacement
   */
  public static function getTranslationSources($language) {
    $sources = [];
    $sql = "
SELECT source, string
FROM civicrm_translation t
  INNER JOIN civicrm_translation_source ts ON t.source_key = ts.source_key
WHERE t.language = %1 AND t.status_id = 1";
    $dao = CRM_Core_DAO::executeQuery($sql, [1 => [$language, 'String']], TRUE, NULL, FALSE, FALSE);
    // These columns seem to be like 'civicrm_contact.first_name'... stored with HTML entities...
    $htmlInput = CRM_Utils_API_HTMLInputCoder::singleton();
    while ($dao->fetch()) {
      if (!empty($dao->string)) {
        $sources[$htmlInput->decodeValue($dao->source)] = $htmlInput->decodeValue($dao->string);
      }
    }
    return $sources;
  }

  /**
   * Create String Unique Identifier (GUID)
   * string $original
   * @return $guid
   */
  public static function createGuid(string $original): string {
    $raw = mb_strtolower($original);
    $raw = preg_replace(';\</?(b|i|strong|em)\>;', '', $raw);
    $raw = preg_replace('/\s+/', ' ', $raw);
    $raw = trim($raw);

    // We want it to be short, which md5 does. We're not really security-sensitive about collisions.
    // Hostile translators can easily screw with you regardless -- a manufactured collision is the least of it.

    // $digest = hash_hmac('md5', $raw, 'FIXME_SECRET_VALUE', TRUE); // Strongly prevent collisions (yay) but hinder staging<=>prod (ugh)
    $digest = hash('md5', $raw, TRUE);

    return CRM_Utils_String::base64UrlEncode($digest);
  }

}
