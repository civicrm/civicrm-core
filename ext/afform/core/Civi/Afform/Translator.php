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
              $markup = trim($markup);
              if (!empty($markup)) {
                $translated = html_entity_decode(_ts(htmlentities($markup)));
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
          $doc->find('af-field[defn]')->each(
            function (\DOMElement $item) use ($defnSelectors) {
              $defn = \CRM_Utils_JS::decode($item->getAttribute('defn'), 1);
              foreach ($defnSelectors as $selector) {
                $this->defnLookupTranslate($defn, $selector);
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
   * Helper to translate defn data recursively
   */
  public function defnLookupTranslate(&$defn, $selector) {
    $subsels = explode('.', $selector);
    if (count($subsels) == 1) {
      if (isset($defn[$selector]) && !is_array($defn[$selector])) {
        $defn[$selector] = _ts($defn[$selector]);
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
          $this->defnLookupTranslate($subDefn, implode('.', $subsels));
        }
      }
      elseif (isset($defn[$parentSel])) {
        $this->defnLookupTranslate($defn[$parentSel], implode('.', $subsels));
      }
    }
  }

}
