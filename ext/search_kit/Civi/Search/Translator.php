<?php

namespace Civi\Search;

use Civi\Core\Service\AutoService;
use Civi\Api4\TranslationSource;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * @service civi.search.translator
 */
class Translator extends AutoService implements EventSubscriberInterface {

  /**
   *
   */
  public static function getSubscribedEvents(): array {
    return [
      'civi.core.makeMultilingual' => 'makeMultilingual',
    ];
  }

  /**
   * When changing to multilingual, ensure we extract all translation source strings
   */
  public function makeMultilingual() {
    self::initSourceTranslations();
  }

  public static function initSourceTranslations() {
    // foreach search display, update
    $searchDisplays = \Civi\Api4\SearchDisplay::get(FALSE)
      ->execute();
    self::updateSearchDisplaySources((array) $searchDisplays);
  }

  public static function updateSearchDisplaySources(array $searchDisplay) {
    // extracting labels as source for translation
    // similar to Civi\Afform\StringVisitor but simpler
    $translatableFields = ['label', 'title', 'description', 'text', 'empty_value', 'rewrite'];
    $strings = [];
    foreach ($searchDisplay as $item) {
      $settings = $item['settings'] ?? [];
      self::extractStrings($strings, $settings, $translatableFields);
    }
    $strings = array_keys($strings);
    self::saveTranslations($strings);
  }

  protected static function extractStrings(array &$strings, array $def, array $translatableFields) {
    foreach ($def as $key => $value) {
      if (is_array($value)) {
        self::extractStrings($strings, $value, $translatableFields);
      }
      elseif (in_array($key, $translatableFields)
        // special case of rewrite, exclude html
        && !($key == 'rewrite' && ($def['type'] ?? '') == 'html')
        && self::isWorthy($value)) {
        $strings[$value] = 1;
      }
    }
  }

  protected static function saveTranslations(array $strings) {
    // Save the form strings.
    if (!empty($strings)) {
      // Create context hash (for now we just record the entity)
      $context_key = \CRM_Core_BAO_TranslationSource::createGuid(':::search_kit');

      // Build the array for the table.
      $records = [];
      foreach ($strings as $value) {
        $source_key = \CRM_Core_BAO_TranslationSource::createGuid($value);
        $records[$source_key] = ['source' => $value, 'source_key' => $source_key, 'context_key' => $context_key, 'entity' => 'search_kit'];
      }
      $records = array_values($records);
      TranslationSource::save(FALSE)
        ->setRecords($records)
        ->setMatch(['source_key'])
        ->execute();
    }
  }

  protected static function isWorthy(?string $value): bool {
    return !empty($value)
      && !is_array($value)
      // ignore value with smarty
      && !(str_contains($value, '{') && str_contains($value, '}'))
      // ignore value with custom translation
      && (!str_contains($value, 'ts('))
      // ignore value that are a simple field token
      && !preg_match('/^\[[A-Za-z0-9._-]+\]$/', trim($value));
  }

}
