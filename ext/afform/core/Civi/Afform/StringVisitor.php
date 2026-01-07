<?php

namespace Civi\Afform;

/**
 * Utility to walk through an Afform document and perform some action on every localizable string.
 *
 * This class is copied into civistrings. Please make sure it is self-contained.
 * If this file is updated, then the composer.json file of civistrings must also
 * be updated to use the latest version.
 */
class StringVisitor {

  /**
   * Search a form for any translatable strings.
   *
   * @param array $form
   *   Metadata describing form. Ex: ['title' => 'Hello world']
   * @param string $html
   *   Form layout (encoded as an HTML string).
   * @return $this
   */
  public static function extractStrings(array $form, string $html): array {
    $doc = \phpQuery::newDocument($html, 'text/html');
    $strings = [];

    (new StringVisitor())->visitMetadata($form, function ($s) use (&$strings) {
      $strings[] = $s;
      return $s;
    });
    (new StringVisitor())->visit($doc, function ($s) use (&$strings) {
      $strings[] = $s;
      return $s;
    });
    return array_unique($strings);
  }

  /**
   * Search an affor for translatable strings. Specifically, in metadata
   * such as ('title', 'redirect', 'confirmation_message')
   *
   * @param array $form
   *   Metadata describing the form. Ex: ['title' => 'Hello world']
   * @param callable $callback
   *   Filter the value of a string. This should return the new value.
   *   Function(string $value, string $context): string
   * @return void
   */
  public function visitMetadata(array &$form, $callback) {
    if ($form === NULL) {
      return;
    }

    $formFields = ['title', 'confirmation_message', 'redirect'];
    foreach ($formFields as $field) {
      if (!empty($form[$field])) {
        $form[$field] = $callback($form[$field]);
      }
    }
  }

  /**
   * Search an afform for translateable strings. These may appear in many places,
   * such as the metadata ('title'), HTML body ('p.af-text'), attributes ('af-title'),
   * and field-definitions ('af-field[defn]')
   *
   * Whenever we find a string, apply a filter.
   *
   * @param \phpQueryObject|null $doc
   *   Parsed layout for the form.
   * @param callable $callback
   *   Filter the value of a string. This should return the new value.
   *   Function(string $value, string $context): string
   * @return void
   * @throws \CRM_Core_Exception
   */
  public function visit($doc, $callback) {
    if ($doc === NULL) {
      return;
    }

    // Translate content.
    $contentSelectors = \CRM_Utils_JS::getContentSelectors();
    $contentSelectors = implode(',', $contentSelectors);
    $doc->find($contentSelectors)->each(
      function (\DOMElement $item) use ($contentSelectors, $callback) {
        $pqItem = pq($item);
        $markup = trim($pqItem->html());
        if ($this->isWorthy($markup)) {
          $translated = $callback($markup, 'content');
          if ($markup !== $translated) {
            $pqItem->html($translated);
          }
        }
      }
    );

    // Translate Attributes.
    $attributeSelectors = \CRM_Utils_JS::getAttributeSelectors();
    foreach ($attributeSelectors as $attribute) {
      $doc->find('[' . $attribute . ']')->each(
        function (\DOMElement $item) use ($attribute, $callback) {
          $this->translateAttribute($item, $attribute, $callback);
        }
      );
    }

    // Translate Defn values.
    $defnSelectors = \CRM_Utils_JS::getDefnSelectors();
    $doc->find('af-field[defn]')->each(
      function (\DOMElement $item) use ($defnSelectors, $callback) {
        $defn = \CRM_Utils_JS::decode($item->getAttribute('defn'));
        foreach ($defnSelectors as $selector) {
          $this->defnLookupTranslate($defn, $selector, $callback);
        }
        $item->setAttribute('defn', \CRM_Utils_JS::encode($defn));
      }
    );

  }

  /**
   * Helper to translate attributes.
   */
  protected function translateAttribute(&$item, $attribute, $callback) {
    $value = $item->getAttribute($attribute);
    if ($this->isWorthy($value)) {
      $item->setAttribute($attribute, $callback($value, 'attribute'));
    }
  }

  /**
   * Helper to translate defn data recursively
   */
  protected function defnLookupTranslate(&$defn, $selector, $callback) {
    $subsels = explode('.', $selector);
    if (count($subsels) == 1) {
      if (isset($defn[$selector]) && $this->isWorthy($defn[$selector])) {
        $defn[$selector] = $callback($defn[$selector], 'defn');
      }
    }
    elseif (count($subsels) > 1) {
      // go deeper in the defn array
      $parentSel = $subsels[0];
      unset($subsels[0]);
      // we use '*' to indicate that this is an array of objects so we can loop on the array
      if (isset($subsels[1]) && $subsels[1] == '*' && !empty($defn[$parentSel])) {
        unset($subsels[1]);
        foreach ($defn[$parentSel] as &$subDefn) {
          $this->defnLookupTranslate($subDefn, implode('.', $subsels), $callback);
        }
      }
      elseif (isset($defn[$parentSel])) {
        $this->defnLookupTranslate($defn[$parentSel], implode('.', $subsels), $callback);
      }
    }
  }

  protected function isWorthy($value): bool {
    return !is_array($value)
      && (!str_contains($value, '{{'))
      && (!str_contains($value, 'ts('))
      && !empty($value);
  }

}
