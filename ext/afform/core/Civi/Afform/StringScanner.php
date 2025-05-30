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
    $inputSelectors = \CRM_Utils_JS::getInputAttributeSelectors();
    $doc->find('af-field[defn]')->each(function (\DOMElement $item) use ($defnSelectors, $inputSelectors) {
      $defn = \CRM_Utils_JS::decode($item->getAttribute('defn'));
      // Check Defn Selectors.
      foreach ($defnSelectors as $attribute) {
        if (isset($defn[$attribute]) && is_array($defn[$attribute])) {
          $input = $defn[$attribute];
          if (is_array($input)) {
            foreach ($input as $item) {
              $this->scanArray($inputSelectors, $item);
            }
          }
          else {
            $this->scanArray($inputSelectors, $input);
          }
        }
        else {
          $this->scanString($defn[$attribute]);
        }
      }
    });

    return $this;
  }

  /**
   * Process array of selectors.
   */
  private function scanArray($selectors, $item) {
    foreach ($selectors as $selector) {
      if (isset($item[$selector])) {
        $this->scanString($item[$selector]);
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
