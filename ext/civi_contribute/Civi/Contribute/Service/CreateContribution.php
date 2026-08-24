<?php
namespace Civi\Contribute\Service;

use Civi\Afform\Event\AfformEntitySortEvent;
use Civi\Afform\Event\AfformSubmitEvent;
use Civi\Afform\Event\AfformValidateEvent;
use Civi\Afform\FormDataModel;
use Civi\Contribute\Utils\PriceFieldUtils;
use Civi\Core\Event\PreEvent;
use Civi\Core\Service\AutoService;
use CRM_Afform_ArrayHtml;
use DateTime;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

use CRM_Contribute_ExtensionUtil as E;

/**
 * @service civi.afform.create_contribution
 */
class CreateContribution extends AutoService implements EventSubscriberInterface {

  protected bool $active = TRUE;

  /**
   * Public method to disable this service if not desired, using:
   * \Civi::service('civi.afform.create_contribution')->setActive(FALSE);
   *
   * @param bool $active
   * @return $this
   */
  public function setActive($active) {
    $this->active = $active;
    return $this;
  }

  /**
   * Default is to run if we have a contribution record on the form
   *
   * Can be overridden using setActive method
   */
  protected function isActive(FormDataModel $formDataModel): bool {
    if (!\Civi::settings()->get('contribute_enable_afform_contributions')) {
      return FALSE;
    }
    if (!$this->active) {
      return FALSE;
    }
    foreach ($formDataModel->getEntities() as $formEntity) {
      if ($formEntity['type'] === 'Contribution' && $formEntity['actions']['create']) {
        // creating new contributions
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * @return array
   */
  public static function getSubscribedEvents(): array {
    return [
      // validate afform config
      'hook_civicrm_pre' => ['validateFormModel', 100],
      // add dependencies from Contribution to entities with Price Fields
      'civi.afform.sort.submit' => ['onAfformEntitySort', 0],
      'civi.afform.validate' => ['validateLineItems', 101],
      // the GenericEntitySave is a no-op for Contributions
      // this provides the equivalent functionality for new Contributions
      // TODO: provide sensible default for existing contributions
      'civi.afform.submit' => ['saveNewContribution', 0],
    ];
  }

  public function validateFormModel(PreEvent $event) {
    if ($event->entity !== 'Afform') {
      return;
    }

    $layout = $event->getValue('layout');
    if (!$layout) {
      // layout isn't being edited - no need to revalidate model
      return;
    }
    if (is_string($layout)) {
      // convert HTML => array to initialise FormDataModel
      $layout = (new CRM_Afform_ArrayHtml())->convertHtmlToArray($layout);
    }
    $model = new FormDataModel($layout);

    // only validate forms this service cares about
    if (!$this->isActive($model)) {
      return;
    }
    // find contributions on the form
    $contributions = array_filter($model->getEntities(), fn ($entity) => $entity['type'] === 'Contribution');

    if (!$contributions) {
      // shouldn't reach here with isActive check above
      return;
    }
    if (count($contributions) > 1) {
      throw new \CRM_Core_Exception(E::ts('Handling multiple contributions on the same form is not supported'));
    }
    $contribution = reset($contributions);
    if (count(array_filter($contribution['actions'])) !== 1) {
      throw new \CRM_Core_Exception(E::ts('Contribution action should be create or update but not both.'));
    }

    // TODO 1: ensure at least one price field on the form

    // TODO 2: if there is a price field anywhere on the form, ensure there is a Contribution entity
  }

  public function validateLineItems(AfformValidateEvent $event) {
    $dataModel = $event->getFormDataModel();
    if (!$this->isActive($dataModel)) {
      return;
    }

    $lineItems = $this->gatherLineItems($event, FALSE);

    // TODO implement hookable validation event
    // \Civi::log()->debug("Afform Payment Validate: " . json_encode($lineItems));
    // $validationErrors = \Civi\Api4\Order::validate()->setLineItems($lineItems)->execute()
    // foreach ($validationErrors as $error) {
    //   $event->setError($error);
    // }

    // in the absence of hookable validation, provide this sensible default
    // this catches cases when user must select one of a number of possible
    // price fields to provide line items, but no specific price field is required
    if (!$lineItems) {
      $event->addError(E::ts('No line items for creating contribution'));
      return;
    }

    $event->getApiRequest()->setResponseItem('line_items', $lineItems);
  }

  protected function gatherLineItems(AfformSubmitEvent|AfformValidateEvent $event, bool $requireLinkedEntityIds = TRUE) {
    $dataModel = $event->getFormDataModel();
    $allSubmittedValues = $event->getSubmittedValues();

    $lineItems = [];

    foreach ($dataModel->getEntities() as $entityName => $entity) {
      $entityType = $entity['type'];
      $priceFields = $entityType ? PriceFieldUtils::getPriceFieldsForEntity($entityType) : NULL;
      if (!$priceFields) {
        continue;
      }
      $requireId = ($requireLinkedEntityIds && $entityType !== 'Contribution');
      $entityIds = $requireId ? $event->getEntityIds($entityName) : NULL;

      foreach ($allSubmittedValues[$entityName] as $i => $submittedValues) {
        $values = array_merge($entity['data'], $submittedValues['fields']);

        // note when validating (or preapproving) we dont need any entity IDs
        // but when creating the contribution we need the entity IDs for saved
        // linked records (e.g. participant ID / membership ID)
        if ($requireId) {
          if (empty($entityIds[$i])) {
            // skip this record
            \Civi::log()->debug("Skipping line items for {$entityName}.{$i} on {$event->getAfform()['name']} as no entity id found.");
            continue;
          }
          $values['id'] = $entityIds[$i];
        }

        $lineItems = array_merge($lineItems, $this->getLineItemsForRecord($entityType, $values, $priceFields));
      }
    }

    return $lineItems;
  }

  public function saveNewContribution(AfformSubmitEvent $event) {
    if ($event->getEntityType() !== 'Contribution') {
      return;
    }
    if (!$this->isActive($event->getFormDataModel())) {
      return;
    }

    $lineItems = $this->gatherLineItems($event);

    $contribution = $event->getRecords()[0]['fields'];

    if (\Civi::service('civi.checkout')->isTestMode()) {
      $contribution['is_test'] = TRUE;
    }

    // use order to create the contribution record
    $orderAPI = \Civi\Api4\Order::create(FALSE)
      ->setContributionValues($contribution)
      ->setLineItems($lineItems);
    if ($contribution['recur_period'] ?? NULL) {
      $orderAPI->setContributionRecurValues($this->getContributionRecurValues($contribution['recur_period']));
    }
    $savedContribution = $orderAPI->execute()
      ->first();

    $event->setEntityId(0, $savedContribution['id']);
  }

  /**
   * Calculate line items for an individual record
   */
  private function getLineItemsForRecord(string $entityType, array $values, array $priceFields): array {
    $lineItems = [];

    // Authoritative gate for admin-visibility (non-public) price options.
    // These stay in the option list (see PriceFieldUtils::fetchPriceFieldSpecs)
    // and are hidden client-side by an af-if, but the client is not trusted:
    // reject a restricted option submitted by a user who may not select it.
    // Mirrors CRM_Contribute_Form_Contribution_Main::buildPriceSet().
    $restrictedOptionIds = PriceFieldUtils::getRestrictedPriceFieldValueIds();
    $mayUseRestricted = !$restrictedOptionIds || \CRM_Core_Permission::check('edit contributions');

    foreach ($values as $key => $fieldValue) {
      $priceField = array_find($priceFields, fn ($priceField) => $priceField['name'] === $key);
      if (!$priceField) {
        continue;
      }
      // $fieldValue can be scalar or array
      foreach ((array) $fieldValue as $singleFieldValue) {
        // Only guard genuine option selections (a PFV id present in this
        // field's option list) - never a quantity/amount entered on a
        // qty or Default Contribution Amount field.
        $isOption = isset($priceField['options']) && \array_key_exists($singleFieldValue, $priceField['options']);
        if ($isOption && !$mayUseRestricted && \in_array((int) $singleFieldValue, $restrictedOptionIds, TRUE)) {
          throw new \CRM_Core_Exception(E::ts('You are not permitted to select one of the chosen options.'));
        }
        $lineItems[] = PriceFieldUtils::getLineItemForPriceFieldValue($entityType, $values['id'] ?? NULL, $priceField, $singleFieldValue);
      }
    }

    return $lineItems;
  }

  /**
   * For a recurring contribution, create a ContributionRecur record as well
   */
  private function getContributionRecurValues(string $recurPeriod): array {
    // unpack recurPeriod parameter
    // TODO: provide extendable options (option group) for this
    $recurParams = match($recurPeriod) {
      'monthly' => [
        'frequency_unit' => 'month',
        'frequency_interval' => 1,
      ],
      'yearly' => [
        'frequency_unit' => 'year',
        'frequency_interval' => 1,
      ],
      default => throw new \CRM_Core_Exception('Unrecognised recur_period value'),
    };

    // calculate the next scheduled date
    // @todo: Don't think we need this as it should be calculated automatically by BAO/ContributionRecur
    $recurParams['next_sched_contribution_date'] = (new DateTime("+ {$recurParams['frequency_interval']} {$recurParams['frequency_unit']}"))->format('Y-m-d');
    return $recurParams;
  }

  public function onAfformEntitySort(AfformEntitySortEvent $e): void {
    $formEntities = $e->getFormDataModel()->getEntities();

    // see if there is a Contribution entity on the form
    // NOTE: currently we expect max one Contribution entity
    $contributionEntity = array_find_key($formEntities, fn ($details) => $details['type'] === 'Contribution');
    if (!$contributionEntity) {
      // if not, ignore
      return;
    }

    foreach ($formEntities as $entity => $details) {
      // no point adding depedency on itself
      if ($entity === $contributionEntity) {
        continue;
      }
      if ($this->afformEntityHasPriceField($details)) {
        $e->addDependency($contributionEntity, $entity);
      }
    }
  }

  private function afformEntityHasPriceField(array $entityDetails): bool {
    $entityType = $entityDetails['type'];
    if (!$entityType) {
      // skip things like 'extra'
      return FALSE;
    }

    $priceFields = PriceFieldUtils::getPriceFieldsForEntity($entityType);

    // if there are no price fields for this entity, then
    if (!$priceFields) {
      return FALSE;
    }

    if (\array_intersect_key($priceFields, $entityDetails['data'] ?? [])) {
      return TRUE;
    }
    if (\array_intersect_key($priceFields, $entityDetails['fields'] ?? [])) {
      return TRUE;
    }
    return FALSE;
  }

}
