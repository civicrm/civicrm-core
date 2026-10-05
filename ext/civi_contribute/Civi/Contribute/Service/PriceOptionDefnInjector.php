<?php

namespace Civi\Contribute\Service;

use Civi\Contribute\Utils\PriceFieldUtils;
use Civi\Core\Service\AutoService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Attaches an `if:` rule to admin-visibility (non-public) price options in
 * <af-field> defn.options, so they are hidden client-side from users who may
 * not see them.
 *
 * Admin price options stay in the option list (see
 * PriceFieldUtils::fetchPriceFieldSpecs) because they are real, selectable
 * options for privileged users. The baked afform markup is shared across all
 * users (it is cached), so we cannot decide visibility here; instead we tag
 * each restricted option with:
 *
 *   if: [['<entity>[0][fields][has_all_price_options]', 'IS NOT EMPTY']]
 *
 * The `has_all_price_options` flag is published per-user at prefill time by
 * PriceOptionAvailabilityPublisher. When it is absent/empty (the default, and
 * the case for every user without 'edit contributions') the option is hidden.
 *
 * Client-side hiding is a UX affordance only: the authoritative gate is
 * CreateContribution::getLineItemsForRecord, which re-checks the permission
 * and rejects a restricted value regardless of what the browser submits.
 *
 * Runs at priority -100 so it executes AFTER AfformMetadataInjector (default
 * priority 0) has populated defn.options - lower priority = later in Symfony
 * EventDispatcher. Mirrors civicrm-payflowpro's OptionDefnInjector.
 *
 * @service civi.contribute.price_option_defn_injector
 */
class PriceOptionDefnInjector extends AutoService implements EventSubscriberInterface {

  public static function getSubscribedEvents(): array {
    return [
      'hook_civicrm_alterAngular' => ['preprocess', -100],
    ];
  }

  /**
   * @param \Civi\Core\Event\GenericHookEvent $e
   *
   * @see CRM_Utils_Hook::alterAngular()
   */
  public function preprocess(\Civi\Core\Event\GenericHookEvent $e): void {
    $restrictedIds = PriceFieldUtils::getRestrictedPriceFieldValueIds();
    if (!$restrictedIds) {
      return;
    }
    // Restrict the walk to fields that are actually price fields, so we never
    // mistake an unrelated field's option id for a PriceFieldValue id.
    $priceFieldNames = self::getPriceFieldNames();
    if (!$priceFieldNames) {
      return;
    }
    // Fast lookup sets.
    $restricted = array_fill_keys($restrictedIds, TRUE);

    $changeSet = \Civi\Angular\ChangeSet::create('priceOptionConditionals')
      ->alterHtml(';\\.aff\\.html$;', function ($doc, $path) use ($priceFieldNames, $restricted) {
        foreach (pq('af-field', $doc) as $afField) {
          /** @var \DOMElement $afField */
          $name = $afField->getAttribute('name');
          if ($name === '') {
            continue;
          }
          // The af-field name may carry a ':name' pseudoconstant suffix or a
          // ','-joined range; the price-field spec name has neither. Normalise
          // the same way core does (strip the comma-join, then the suffix)
          // before matching.
          $baseName = explode(':', explode(',', $name)[0])[0];
          if (!isset($priceFieldNames[$baseName])) {
            continue;
          }
          $this->amendField($afField, $restricted);
        }
      });
    $e->angular->add($changeSet);
  }

  /**
   * Read the field's defn, attach the visibility `if:` to any option whose id
   * is a restricted (admin) PriceFieldValue, write defn back. No-op if the
   * field has no options or none are restricted.
   *
   * @param \DOMElement $afField
   * @param array<int,true> $restricted
   *   Restricted PriceFieldValue ids as a lookup set.
   *
   * @throws \Exception
   */
  protected function amendField(\DOMElement $afField, array $restricted): void {
    $existing = trim(pq($afField)->attr('defn') ?: '');
    // If the markup author wrote a non-object defn, leave it alone - same
    // posture as AfformMetadataInjector::setFieldMetadata.
    if ($existing && $existing[0] !== '{') {
      return;
    }

    $rawDefn = $existing ? \CRM_Utils_JS::getRawProps($existing) : [];
    if (empty($rawDefn['options'])) {
      return;
    }

    $options = \CRM_Utils_JS::decode($rawDefn['options']);
    if (!is_array($options)) {
      return;
    }

    // Resolve the containing entity name so the af-if can read the flag off
    // the right record (e.g. Contribution1[0][fields][has_all_price_options]).
    $entityName = pq($afField)->parents('[af-fieldset]')->attr('af-fieldset');
    if (!$entityName) {
      return;
    }
    $lhs = $entityName . '[0][fields][' . PriceFieldUtils::RESTRICTED_OPTIONS_FLAG . ']';

    $changed = FALSE;
    foreach ($options as &$opt) {
      // Options here are the verbose [{id, label, ...}] form (afform loads
      // them with loadOptions => [id, label, ...]).
      if (!isset($opt['id']) || !empty($opt['if'])) {
        continue;
      }
      if (isset($restricted[(int) $opt['id']])) {
        $opt['if'] = [[$lhs, 'IS NOT EMPTY']];
        $changed = TRUE;
      }
    }
    unset($opt);

    if (!$changed) {
      return;
    }
    $rawDefn['options'] = \CRM_Utils_JS::encode($options);
    pq($afField)->attr('defn', htmlspecialchars(\CRM_Utils_JS::writeObject($rawDefn), ENT_COMPAT));
  }

  /**
   * All price-field full names (across every price-bearing entity), as a
   * lookup set keyed by name. These are the only <af-field> names whose
   * option ids are PriceFieldValue ids.
   *
   * @return array<string, true>
   */
  protected static function getPriceFieldNames(): array {
    $names = [];
    foreach (PriceFieldUtils::getPriceFieldSpecs() as $entitySpecs) {
      foreach (array_keys($entitySpecs) as $fullName) {
        $names[$fullName] = TRUE;
      }
    }
    return $names;
  }

}
