<?php

namespace Civi\Api4\Utils;

use Civi\Afform\StringVisitor;
use Civi\Api4\TranslationSource;
use Civi\Afform\Utils;

/**
 * Class AfformSaveTrait.
 *
 * @package Civi\Api4\Action\Afform
 */
trait AfformSaveTrait {

  use AfformFormatTrait;

  /**
   *
   */
  protected function writeRecord($item) {
    /** @var \CRM_Afform_AfformScanner $scanner */
    $scanner = \Civi::service('afform_scanner');

    // If no name given, create a unique name based on the title
    $orig = [];
    $this->checkNameForAfform($item, $orig, $scanner);

    // Check if updating or creating
    if (!$orig) {
      $item['created_id'] = \CRM_Core_Session::getLoggedInContactID();
    }

    // FIXME validate all field data.
    $item = _afform_fields_filter($item);

    // Create or update aff.html.
    if (isset($item['layout'])) {
      $layoutPath = $scanner->createSiteLocalPath($item['name'], 'aff.html');
      \CRM_Utils_File::createDir(dirname($layoutPath));
      $html = $this->convertInputToHtml($item['layout']);

      // Are we multilingual.
      if (\CRM_Core_I18n::isMultiLingual()) {
        self::saveTranslations($item, $html);
      }
      file_put_contents($layoutPath, $html);
      // FIXME check for writability then success. Report errors.
    }

    $meta = $item + (array) $orig;
    unset($meta['layout'], $meta['name']);
    if (isset($meta['permission']) && is_string($meta['permission'])) {
      $meta['permission'] = explode(',', $meta['permission']);
    }
    if (!empty($meta)) {
      $metaPath = $scanner->createSiteLocalPath($item['name'], \CRM_Afform_AfformScanner::METADATA_JSON);
      \CRM_Utils_File::createDir(dirname($metaPath));
      // Add eof newline to make files git-friendly.
      file_put_contents($metaPath, json_encode($meta, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
      // FIXME check for writability then success. Report errors.
    }

    // We may have changed list of files covered by the cache.
    _afform_clear();

    // If the dashlet or navigation setting changed, managed entities must be reconciled.
    if (Utils::shouldReconcileManaged($item, $orig ?? [])) {
      \CRM_Core_ManagedEntities::singleton()->reconcile(\CRM_Afform_ExtensionUtil::LONG_NAME);
    }

    if (Utils::shouldClearMenuCache($item, $orig ?? [])) {
      \CRM_Core_Menu::store();
    }

    $item['module_name'] = _afform_angular_module_name($item['name'], 'camel');
    $item['directive_name'] = _afform_angular_module_name($item['name'], 'dash');
    return $meta + $item;
  }

  /**
   * @param array $item The afform item being processed.
   * @param array $orig The existing afform if already created.
   * @param \CRM_Afform_AfformScanner $scanner
   *
   * @return void
   * @throws \CRM_Core_Exception
   * @throws \Civi\API\Exception\UnauthorizedException
   */
  protected function checkNameForAfform(&$item, &$orig, $scanner) {
    if (empty($item['name'])) {
      $prefix = 'af' . ($item['type'] ?? '');
      $item['name'] = _afform_angular_module_name($prefix . '-' . \CRM_Utils_String::munge($item['title'], '-'));
      $suffix = '';
      while (
        file_exists($scanner->createSiteLocalPath($item['name'] . $suffix, \CRM_Afform_AfformScanner::METADATA_JSON))
        || file_exists($scanner->createSiteLocalPath($item['name'] . $suffix, \CRM_Afform_AfformScanner::LAYOUT_FILE))
      ) {
        $suffix++;
      }
      $item['name'] .= $suffix;
      $orig = NULL;
    }
    elseif (!preg_match('/^[a-zA-Z][-_a-zA-Z0-9]*$/', $item['name'])) {
      throw new \CRM_Core_Exception("Afform.{$this->getActionName()}: name should begin with a letter and only contain alphanumerics underscores and dashes.");
    }
    else {
      // Fetch existing metadata
      $fields = \Civi\Api4\Afform::getfields()->setCheckPermissions(FALSE)->setAction('create')->addSelect('name')->execute()->column('name');
      unset($fields[array_search('layout', $fields)]);
      $orig = \Civi\Api4\Afform::get()->setCheckPermissions(FALSE)->addWhere('name', '=', $item['name'])->setSelect($fields)->execute()->first();
    }
  }

  /**
   * Save Translation Strings from Form to database
   * array $form
   * string $html
   */
  protected static function saveTranslations($form, $html) {
    $strings = StringVisitor::extractStrings($form, $html);

    // Save the form strings.
    if (!empty($strings)) {
      // Create context hash (for now we just record the entity)
      $context_key = \CRM_Core_BAO_TranslationSource::createGuid(':::afform');

      // Build the array for the table.
      $records = [];
      foreach ($strings as $value) {
        $source_key = \CRM_Core_BAO_TranslationSource::createGuid($value);
        $records[] = ['source' => $value, 'source_key' => $source_key, 'context_key' => $context_key, 'entity' => 'afform'];
      }
      TranslationSource::save(FALSE)
        ->setRecords($records)
        ->setMatch(['source_key'])
        ->execute();
    }
  }

}
