<?php

namespace Civi\Afform;

/**
 * Given an Afform document, scan it for translateable strings.
 */
class StringScanner {

  /**
   * @var string[]
   */
  protected $stringTranslations = [];

  /**
   * Search a form for any translatable strings.
   *
   * @param array $form
   *   Metadata describing form. Ex: ['title' => 'Hello world']
   * @param string $html
   *   Form layout
   * @return $this
   */
  public function scan(array $form, string $html) {
    $doc = \phpQuery::newDocument($html, 'text/html');

    // Record Title.
    if (isset($form['title'])) {
      $this->stringTranslations[] = $form['title'];
    }

    // Find markup to be translated.
    $contentSelectors = \CRM_Utils_JS::getContentSelectors();
    $contentSelectors = implode(',', $contentSelectors);
    $doc->find($contentSelectors)->each(function (\DOMElement $item) {
      // FIXME: Should this be:   $this->scanString(pq($item)->html());
      $markup = '';
      foreach ($item->childNodes as $child) {
        $markup .= $child->ownerDocument->saveXML($child);
      }
      $this->scanString($markup);
    });

    // Find attributes to be translated.
    $attributes = \CRM_Utils_JS::getAttributeSelectors();
    foreach ($attributes as $attribute) {
      $doc->find('[' . $attribute . ']')->each(function (\DOMElement $item) use ($attribute) {
        $this->scanString($item->getAttribute($attribute));
      });
    }

    // Get sub-attributes to be translated.
    $defnSelectors = \CRM_Utils_JS::getDefnSelectors();
    $doc->find('af-field[defn]')->each(function (\DOMElement $item) use ($defnSelectors, $inputSelectors) {
      $defn = \CRM_Utils_JS::decode($item->getAttribute('defn'));
      // Check Defn Selectors.
      foreach ($defnSelectors as $selector) {
        $this->defnLookupTranslate($defn, $selector);
      }
    });

    return $this;
  }

  /**
   * Helper to find defn data recursively
   */
  protected function defnLookupTranslate(&$defn, $selector) {
    $subsels = explode('.', $selector);
    if (count($subsels) == 1) {
      if (isset($defn[$selector]) && !is_array($defn[$selector])) {
        $this->scanString($defn[$selector]);
      }
    }
    elseif (count($subsels) > 1) {
      // go deeper in the defn array
      $parentSel = $subsels[0];
      unset($subsels[0]);
      // we use '*' to indicate that this is an array of objects so we can loop on the array
      if (isset($subsels[1]) && $subsels[1] == '*') {
        unset($subsels[1]);
        foreach ($defn[$parentSel] as &$subDefn) {
          $this->defnLookupTranslate($subDefn, implode('.', $subsels));
        }
      }
      elseif (isset($defn[$parentSel])) {
        $this->defnLookupTranslate($defn[$parentSel], implode('.', $subsels));
      }
    }
  }

  /**
   * Record String for translation.
   */
  private function scanString($value) {
    $value = trim($value);
    if ((strpos($value, '{{') === FALSE) && !empty($value)) {
      $this->stringTranslations[] = $value;
    }
  }

  /**
   * List of unique, translateable strings.
   *
   * @return array
   */
  public function getStrings(): array {
    return array_unique($this->stringTranslations);
  }

}
