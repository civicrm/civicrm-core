<?php

namespace Civi\Afform;

use Civi\Core\Service\AutoService;
use Smarty\Filter\FilterInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * @service civi.afform.translator
 */
class Translator extends AutoService implements EventSubscriberInterface {

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

    $changeSet = \Civi\Angular\ChangeSet::create('translate')
      ->alterHtml(';\.aff\.html$;', function (\phpQueryObject $doc) {

        // content to be translated
        $contentSelectors = 'p.af-text, div.af-markup, button';
        $doc->find($contentSelectors)->each(function(\DOMElement $item) {
          $this->translateContent($item);
        });

        // attributes to be translated
        foreach (['af-title', 'af-copy', 'af-repeat'] as $attribute) {
          $doc->find('[' . $attribute . ']')->each(function(\DOMElement $item) use ($attribute) {
            $this->translateAttribute($item, $attribute);
          });
        }

        // defn sub-attributes to be translated
        $doc->find('af-field[defn]')->each(function(\DOMElement $item) {
          $defn = \CRM_Utils_JS::decode($item->getAttribute('defn'));
          foreach (['label', 'help_pre', 'help_post', 'placeholder'] as $attribute) {
            if (isset($defn[$attribute])) {
              $defn[$attribute] = ts($defn[$attribute]);
            }
          }
          if (isset($defn['options'])) {
            foreach ($defn['options'] as $idx => $option) {
              if (isset($option['label'])) {
                $defn['options'][$idx]['label'] = ts($option['label']);
              }
            }
          }
          $item->setAttribute('defn', \CRM_Utils_JS::encode($defn));
        });
      });
    $angular->add($changeSet);
  }

  public function translateContent(&$item) {
    $item->textContent = _ts($item->textContent);
  }

  public function translateAttribute(&$item, $attribute) {
    $item->setAttribute($attribute, ts($item->getAttribute($attribute)));
  }

}

