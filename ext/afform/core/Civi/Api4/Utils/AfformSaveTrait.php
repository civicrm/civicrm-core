<?php

namespace Civi\Api4\Utils;

use Civi\Api4\TranslationSource;
use Civi\Api4\Afform;
use Civi\Afform\Utils;

/**
 * Class AfformSaveTrait.
 *
 * @package Civi\Api4\Action\Afform
 */
trait AfformSaveTrait {

  use AfformFormatTrait;

  /**
   * Translatable Strings
   * string $translateStrings
   */
  protected $stringTranslations = [];

  /**
   *
   */
  protected function writeRecord($item) {
    /** @var \CRM_Afform_AfformScanner $scanner */
    $scanner = \Civi::service('afform_scanner');

    // If no name given, create a unique name based on the title.
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
      // Fetch existing metadata.
      $fields = Afform::getfields()->setCheckPermissions(FALSE)->setAction('create')->addSelect('name')->execute()->column('name');
      unset($fields[array_search('layout', $fields)]);
      $orig = Afform::get()->setCheckPermissions(FALSE)->addWhere('name', '=', $item['name'])->setSelect($fields)->execute()->first();
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
        $this->saveTranslations($item, $html);
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
   * Save Translation Strings from Form to database
   * array $form
   * string $html
   */
  protected function saveTranslations($form, $html) {
    $strings = [];
    $doc = \phpQuery::newDocument($html, 'text/html');

    // Record Title.
    if (isset($form['title'])) {
      $this->stringTranslations[] = $form['title'];
    }

    // Find markup to be translated.
    $contentSelectors = \CRM_Utils_JS::getContentSelectors();
    $contentSelectors = implode(',', $contentSelectors);
    $doc->find($contentSelectors)->each(function (\DOMElement $item) {
      $markup = '';
      foreach ($item->childNodes as $child) {
        $markup .= $child->ownerDocument->saveXML($child);
      }
      $this->saveTranslatableString($markup);
    });

    // Find attributes to be translated.
    $attributes = \CRM_Utils_JS::getAttributeSelectors();
    foreach ($attributes as $attribute) {
      $doc->find('[' . $attribute . ']')->each(function (\DOMElement $item) use ($attribute) {
        $this->saveTranslatableString($item->getAttribute($attribute));
      });
    }

    // Get sub-attributes to be translated.
    $defnSelectors = \CRM_Utils_JS::getDefnSelectors();
    $inputSelectors = \CRM_Utils_JS::getInputAttributeSelectors();
    $doc->find('af-field[defn]')->each(function (\DOMElement $item) use ($defnSelectors, $inputSelectors) {
      $defn = \CRM_Utils_JS::decode($item->getAttribute('defn'));
      // Check Defn Selectors.
      foreach ($defnSelectors as $attribute) {
        if (isset($defn[$attribute]) && is_array($defn[$attribute])) {
          $input = $defn[$attribute];
          if (is_array($input)) {
            foreach ($input as $item) {
              $this->processTranslatableArray($inputSelectors, $item);
            }
          }
          else {
            $this->processTranslatableArray($inputSelectors, $input);
          }
        }
        else {
          $this->saveTranslatableString($defn[$attribute]);
        }
      }
    });

    // Save the form strings.
    if (!empty($this->stringTranslations)) {
      $this->stringTranslations = array_unique($this->stringTranslations);

      // Build the array for the table.
      $strings = [];
      foreach ($this->stringTranslations as $value) {
        $strings[] = ['source' => $value];
      }

      TranslationSource::save(FALSE)
        ->addRecord(...$strings)
        ->setMatch([
          'source',
        ])
        ->execute();

    }
  }

  /**
   * Process array of selectors.
   */
  protected function processTranslatableArray($selectors, $item) {
    foreach ($selectors as $selector) {
      if (isset($item[$selector])) {
        $this->saveTranslatableString($item[$selector]);
      }
    }
  }

  /**
   * Record String for translation.
   */
  protected function saveTranslatableString($value) {
    $value = trim($value);
    if ((strpos($value, '{{') === FALSE) && !empty($value)) {
      $this->stringTranslations[] = $value;
    }
  }

}
