<?php
namespace Civi\Contribute\Service;

use Civi\Afform\Event\AfformSubmitEvent;
use Civi\Afform\Event\AfformValidateEvent;
use Civi\Contribute\Utils\PriceFieldUtils;
use Civi\Core\Service\AutoService;
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
  protected function isActive($formDataModel): bool {
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
      'civi.afform.validate' => [
        // TODO: this belongs in a hook to validate a form
        // that is being saved or loaded rather than submitted
        // but we dont have that yet - hopefully the admin will
        // try to submit the form at least once
        ['validateFormModel', 1000],
        ['validateLineItems', 101],
      ],
      'civi.afform.submit' => [
        // the GenericEntitySave is a no-op for Contributions
        // this provides the equivalent functionality for new Contributions
        // TODO: provide sensible default for existing contributions
        ['saveNewContribution', 0],
      ],
    ];
  }

  public function validateFormModel(AfformValidateEvent $event) {
    $model = $event->getFormDataModel();

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
      $event->addError(E::ts('Handling multiple contributions on the same form is not supported'));
      return;
    }
    $contribution = reset($contributions);
    if (count(array_filter($contribution['actions'])) !== 1) {
      $event->addError(E::ts('Contribution action should be create or update but not both.'));
      return;
    }

    // TODO: check any entities with price fields are ordered *before* the contribution
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
    $savedContribution = \Civi\Api4\Order::create(FALSE)
      ->setContributionValues($contribution)
      ->setLineItems($lineItems)
      ->execute()
      ->first();

    $event->setEntityId(0, $savedContribution['id']);

    if ($contribution['recur_period'] ?? NULL) {
      $this->createContributionRecur($savedContribution['id'], $contribution['recur_period']);
    }

  }

  /**
   * Calculate line items for an individual record
   */
  private function getLineItemsForRecord(string $entityType, array $values, array $priceFields): array {
    $lineItems = [];

    foreach ($values as $key => $fieldValue) {
      $priceField = array_find($priceFields, fn ($priceField) => $priceField['name'] === $key);
      if (!$priceField) {
        continue;
      }
      // $fieldValue can be scalar or array
      foreach ((array) $fieldValue as $singleFieldValue) {
        $lineItems[] = PriceFieldUtils::getLineItemForPriceFieldValue($entityType, $values['id'] ?? NULL, $priceField, $singleFieldValue);
      }
    }

    return $lineItems;
  }

  /**
   * For a recurring contribution, create a ContributionRecur record as well
   */
  public function createContributionRecur(int $contributionId, string $recurPeriod) {
    // get values we need to reuse from the contribution record
    $contribution = \Civi\Api4\Contribution::get(FALSE)
      ->addSelect('contact_id', 'total_amount', 'currency', 'is_test')
      ->addWhere('id', '=', $contributionId)
      ->execute()
      ->single();

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
    $nextSched = (new DateTime("+ {$recurParams['frequency_interval']} {$recurParams['frequency_unit']}"))->format('Y-m-d');

    $recurRecordId = \Civi\Api4\ContributionRecur::create(FALSE)
      ->addValue('contact_id', $contribution['contact_id'])
      ->addValue('amount', $contribution['total_amount'])
      ->addValue('currency', $contribution['currency'])
      ->addValue('is_test', $contribution['is_test'])
      ->addValue('frequency_unit', $recurParams['frequency_unit'])
      ->addValue('frequency_interval', $recurParams['frequency_interval'])
      ->addValue('next_sched_contribution_date', $nextSched)
      ->execute()
      ->single()['id'];

    // attach the existing contribution to the recurring record
    \Civi\Api4\Contribution::update(FALSE)
      ->addWhere('id', '=', $contributionId)
      ->addValue('contribution_recur_id', $recurRecordId)
      ->execute();

    // TODO: do we need to copy the first contribution as a template?
    // or will it be used anyway if no template contribution exists
  }

}
