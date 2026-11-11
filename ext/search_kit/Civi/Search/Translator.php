<?php

namespace Civi\Search;

use Civi\Core\Attribute\CiviEventListener;
use Civi\Core\Service\AutoService;
use Civi\Api4\TranslationSource;

/**
 * @service civi.search.translator
 */
class Translator extends AutoService {

  /**
   * User-facing texts of a display: TRUE marks a text, an array a nested setting, [0 => spec] a list of them.
   */
  public const TEXTS = [
    'label' => TRUE,
    'settings' => [
      'description' => TRUE,
      'noResultsText' => TRUE,
      'button' => TRUE,
      'tally' => ['label' => TRUE],
      'editableRow' => ['createLabel' => TRUE],
      'addButton' => ['text' => TRUE],
      'toolbar' => [['text' => TRUE, 'title' => TRUE]],
      'columns' => [
        [
          'label' => TRUE,
          'title' => TRUE,
          'text' => TRUE,
          'rewrite' => TRUE,
          'empty_value' => TRUE,
          'image' => ['alt' => TRUE],
          'tally' => ['label' => TRUE],
          'links' => [['text' => TRUE, 'title' => TRUE]],
        ],
      ],
    ],
  ];

  /**
   * Call $callback on each user-facing text of a display, replacing the text with its result.
   *
   * Markup or Smarty in a text marks it as a template, which a translation could break, so it is skipped.
   */
  public static function walkTexts(array &$item, callable $callback, array $texts = self::TEXTS): void {
    foreach ($texts as $key => $spec) {
      // The rewrite of an html column is markup, not a text
      if ($key === 'rewrite' && ($item['type'] ?? NULL) === 'html') {
        continue;
      }
      if ($spec === TRUE) {
        if (!empty($item[$key]) && is_string($item[$key]) && !self::isTemplate($item[$key])) {
          $item[$key] = $callback($item[$key]);
        }
      }
      elseif (isset($item[$key]) && is_array($item[$key])) {
        if (isset($spec[0])) {
          foreach ($item[$key] as &$child) {
            if (is_array($child)) {
              self::walkTexts($child, $callback, $spec[0]);
            }
          }
        }
        else {
          self::walkTexts($item[$key], $callback, $spec);
        }
      }
    }
  }

  private static function isTemplate(string $text): bool {
    return (str_contains($text, '<') && str_contains($text, '>'))
      || (str_contains($text, '{') && str_contains($text, '}'));
  }

  /**
   * When changing to multilingual, ensure we extract all translation source strings
   */
  #[CiviEventListener(event: 'civi.core.makeMultilingual')]
  public function makeMultilingual() {
    self::initSourceTranslations();
  }

  public static function initSourceTranslations() {
    // foreach search display, update
    $searchDisplays = \Civi\Api4\SearchDisplay::get(FALSE)
      ->execute();
    self::updateSearchDisplaySources((array) $searchDisplays);
  }

  public static function updateSearchDisplaySources(array $searchDisplays) {
    $strings = [];
    foreach ($searchDisplays as $display) {
      self::walkTexts($display, function (string $text) use (&$strings) {
        if (self::isWorthy($text)) {
          $strings[trim($text)] = 1;
        }
        return $text;
      });
    }
    self::saveTranslations(array_keys($strings));
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
