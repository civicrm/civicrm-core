<?php

namespace Civi\Afform;

use Civi\Angular\ChangeSet;
use Civi\Core\Service\AutoService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * @service civi.afform.translator
 */
class Translator extends AutoService implements EventSubscriberInterface {

  /**
   *
   */
  public static function getSubscribedEvents() {
    return [
      '&hook_civicrm_alterAngular' => 'translateAfform',
    ];
  }

  /**
   * @param \Civi\Angular\Manager $angular
   */
  public function translateAfform($angular) {

    /**
     * Find all the content that should be auto send to ts function
     * There are 3 kinds of translation :
     * - html tag content
     * - html attribute values
     * - json sub-attribute in defn html attribute
     */

    $changeSet = ChangeSet::create('translate')
      ->alterHtml(
        ';\.aff\.html$;', function (\phpQueryObject $doc) {

          // Translate content.
          $contentSelectors = \CRM_Utils_JS::getContentSelectors();
          $contentSelectors = implode(',', $contentSelectors);
          $doc->find($contentSelectors)->each(
            function (\DOMElement $item) use ($contentSelectors) {
              $pqItem = pq($item);
              $markup = $pqItem->html();
              if (!empty($markup)) {
                $translated = _ts($markup);
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
              function (\DOMElement $item) use ($attribute) {
                $this->translateAttribute($item, $attribute);
              }
            );
          }

          // Translate Defn values.
          $defnSelectors = \CRM_Utils_JS::getDefnSelectors();
          $inputSelectors = \CRM_Utils_JS::getInputAttributeSelectors();
          $doc->find('af-field[defn]')->each(
            function (\DOMElement $item) use ($defnSelectors, $inputSelectors) {
              $defn = \CRM_Utils_JS::decode($item->getAttribute('defn'), 1);
              // $item->setAttribute('debug', gettype($defn));
              foreach ($defnSelectors as $attribute) {
                if (isset($defn[$attribute])) {
                  if (is_array($defn[$attribute])) {
                    foreach ($defn[$attribute] as $input) {
                      $this->translateItemArray($input, $inputSelectors);
                    }
                  }
                  else {
                    $this->translateItemArray($defn[$attribute], $inputSelectors);
                  }
                }
              }
              $item->setAttribute('defn', \CRM_Utils_JS::encode($defn));
            }
          );
        }
      );
    $angular->add($changeSet);
  }

  /**
   * Helper to translate attributes.
   */
  public function translateAttribute(&$item, $attribute) {
    $item->setAttribute($attribute, _ts($item->getAttribute($attribute)));
  }

  /**
   * Helper to translate subattributes.
   */
  public function translateItemArray(&$item, $selectors) {
    foreach ($selectors as $selector) {
      if (isset($item[$selector])) {
        $item[$selector] = _ts($item[$selector]);
      }
    }
  }

}
