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
    // 1. only formBuilder = ok
    // 2. can we make it work before some other angular complexity is added (e.g. icons) = faux problème 
    // 3. loop on selectors

/*
- af-entity[label]
- af-field[defn.label]
- af-field[defn.help_pre]
- af-field[defn.help_post]
- af-field[defn.placeholder]
- af-field[defn.options.label] => (Custom options for Date -- Or we have a custom case for all date fields) i.e. defn="{options: [{id: 'this.day', label: 'Today'}
- p.af-text (should we add a class for saying it's translatable?)
- div.af-markup (should we add a class for saying it's translatable?) -> it should be assumed to be 
- fieldset[af-copy] (label of the copie button)
- fieldset[af-repeat] (label of the repeat button)
- button
- [af-title] */

    $changeSet = \Civi\Angular\ChangeSet::create('translate')
      ->alterHtml(';.aff.html;', function (\phpQueryObject $doc) {

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

  // TODO: make this a generic class
  public function translateContent(&$item) {
    $item->textContent = ts($item->textContent);
  }

  public function translateAttribute(&$item, $attribute) {
    $item->setAttribute($attribute, ts($item->getAttribute($attribute)));
  }

}

