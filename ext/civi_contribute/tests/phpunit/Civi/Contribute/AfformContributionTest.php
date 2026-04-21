<?php

namespace Civi\Contribute;

use Civi\Api4\Afform;
use Civi\Contribute\Utils\PriceFieldUtils;
use Civi\Test;
use Civi\Test\CiviEnvBuilder;
use Civi\Test\HeadlessInterface;
use PHPUnit\Framework\TestCase;

/**
 * Test Contribution handling in Afform
 *
 * @group headless
 */
class AfformContributionTest extends TestCase implements HeadlessInterface {

  protected $afformContributionSettingBackup;
  protected int $eventId;
  protected int $inPersonPriceFieldValueId;

  /**
   * Setup used when HeadlessInterface is implemented.
   *
   * Civi\Test has many helpers, like install(), uninstall(), sql(), and sqlFile().
   *
   * @link https://github.com/civicrm/org.civicrm.testapalooza/blob/master/civi-test.md
   *
   * @return \Civi\Test\CiviEnvBuilder
   *
   * @throws \CRM_Extension_Exception_ParseException
   */
  public function setUpHeadless(): CiviEnvBuilder {
    return Test::headless()
      ->installMe(__DIR__)
      ->install(['civi_event', 'org.civicrm.afform'])
      ->apply();
  }

  public function setUp(): void {
    \Civi::settings()->set('contribute_enable_afform_contributions', TRUE);

    \Civi\Api4\PriceSet::save(FALSE)
      ->addRecord([
        // participant
        'extends' => [1],
        'name' => 'participant_fields',
        'title' => 'Participant Options',
      ])
      ->addRecord([
         // contribution
        'extends' => [2],
        'name' => 'donation_options',
        'title' => 'Donation Options',
      ])
      ->execute();

    $fields = \Civi\Api4\PriceField::save(FALSE)
      ->addRecord([
        'name' => 'ticket_option',
        'label' => 'Ticket Option',
        'html_type' => 'Radio',
        'price_set_id:name' => 'participant_fields',
      ])
      ->addRecord([
        'name' => 'additional_donation',
        'label' => 'Additional Donation',
        'html_type' => 'Text',
        'price_set_id:name' => 'donation_options',
      ])
      ->execute();

    $priceFieldValues = \Civi\Api4\PriceFieldValue::save(FALSE)
      ->addRecord([
        'name' => 'in_person',
        'label' => 'In person',
        'amount' => 10,
        'price_field_id' => $fields[0]['id'],
        'financial_type_id:name' => 'Event Fee',
      ])
      ->addRecord([
        'name' => 'online',
        'label' => 'Online',
        'amount' => 5,
        'price_field_id' => $fields[0]['id'],
        'financial_type_id:name' => 'Event Fee',
      ])
      ->execute();

    $event = \Civi\Api4\Event::save(FALSE)
      ->addRecord([
        'title' => 'Test event',
        'event_type_id' => 1,
        'start_date' => 'now',
      ])
      ->execute()->single();

    $this->eventId = $event['id'];

    $this->inPersonPriceFieldValueId = $priceFieldValues[0]['id'];

    // reset the price field cache
    // TODO: this should probably be included in post hook
    unset(\Civi::$statics[PriceFieldUtils::class]);

    $layout = <<<HTML
    <af-form ctrl="afform">
      <af-entity type="Individual" name="Individual1" label="Individual 1" actions="{create: true, update: true}" security="FBAC" />
      <af-entity type="Participant" name="Participant1" label="Participant 1" data="{contact_id: 'Individual1'}" actions="{create: true, update: true}" security="FBAC" />
      <af-entity type="Contribution" name="Contribution1" label="Contribution 1" data="{contact_id: 'Individual1', financial_type_id: 1}" actions="{create: true, update: false}" security="FBAC" />
      <fieldset af-fieldset="Individual1" class="af-container" af-title="Individual 1">
        <div class="af-container">
          <af-field name="first_name" />
          <af-field name="last_name" />
        </div>
      </fieldset>
      <fieldset af-fieldset="Participant1" class="af-container" af-title="Participant 1">
        <div class="af-container">
          <!-- standard field for participant -->
          <af-field name="event_id" defn="{required: true}" />
          <!-- price field for participant -->
          <af-field name="participant_fields.ticket_option" />
        </div>
      </fieldset>
      <fieldset af-fieldset="Contribution1" class="af-container" af-title="Contribution 1">
        <div class="af-container">
          <!-- standard field for Contribution -->
          <af-field name="source" />
          <!-- price field for Contribution -->
          <af-field name="donation_options.additional_donation" />
        </div>
      </fieldset>
    </af-form>
    HTML;

    Afform::save(FALSE)
      ->addRecord([
        'name' => 'testAfformContribution',
        'layout' => $layout,
      ])
      ->setLayoutFormat('html')
      ->execute();

  }

  public function tearDown(): void {
    \Civi\Api4\PriceFieldValue::delete(FALSE)
      ->addWhere('name', 'IN', ['in_person', 'online'])
      //->addWhere('id', '>', 0)
      ->execute();

    \Civi\Api4\PriceField::delete(FALSE)
      ->addWhere('name', 'IN', ['ticket_option', 'additional_donation'])
      //->addWhere('id', '>', 0)
      ->execute();

    \Civi\Api4\PriceSet::delete(FALSE)
      ->addWhere('name', 'IN', ['participant_fields', 'donation_options'])
      //->addWhere('id', '>', 0)
      ->execute();

    // \Civi\Api4\Afform::delete(FALSE)->addWhere('name', '=', 'testAfformContribution')->execute();

    \Civi\Api4\Event::delete(FALSE)->addWhere('title', '=', 'Test event')->execute();

    \Civi::settings()->set('contribute_enable_afform_contributions', $this->afformContributionSettingBackup);
  }

  /**
   * @throws \CRM_Core_Exception
   */
  public function testContributionCreate(): void {
    $response = Afform::submit(FALSE)
      ->setName('testAfformContribution')
      ->setValues([
        'Individual1' => [
          [
            'fields' => [
              'first_name' => 'Test',
              'last_name' => 'Contact',
            ],
          ],
        ],
        'Participant1' => [
          [
            'fields' => [
              'event_id' => $this->eventId,
              'participant_fields.ticket_option' => $this->inPersonPriceFieldValueId,
            ],
          ],
        ],
        'Contribution1' => [
          [
            'fields' => [
              'source' => 'testContributionCreate',
              // free text input
              'donation_options.additional_donation' => 5,
            ],
          ],
        ],
      ])
      ->execute();

    // check a valid contribution ID
    $contributionId = $response->single()['Contribution1'][0]['id'];
    $this->assertEquals(TRUE, $contributionId > 0);

    // check a valid participant ID
    $participantId = $response->single()['Participant1'][0]['id'];
    $this->assertEquals(TRUE, $participantId > 0);

    $contribution = \Civi\Api4\Contribution::get(FALSE)
      ->addWhere('id', '=', $contributionId)
      ->execute()
      ->single();

    // check expected amount
    $this->assertEquals(15, $contribution['total_amount']);

    // get line items
    $lineItems = \Civi\Api4\LineItem::get(FALSE)
      ->addWhere('contribution_id', '=', $contributionId)
      ->execute();

    // should have 2 line items
    $this->assertEquals(2, $lineItems->count());

    // should have 1 participant line item
    $participantLineItems = array_filter((array) $lineItems, fn ($lineItem) => $lineItem['entity_table'] === 'civicrm_participant');
    $this->assertEquals(1, count($participantLineItems));

    // should be linked to the participant
    $participantLineItem = array_values($participantLineItems)[0];
    $this->assertEquals($participantId, $participantLineItem['entity_id']);
  }

  /**
   * @throws \CRM_Core_Exception
   */
  public function testValidateLineItems(): void {
    try {
      $response = Afform::submit(FALSE)
        ->setName('testAfformContribution')
        ->setValues([
          'Individual1' => [
            [
              'fields' => [
                'first_name' => 'Test',
                'last_name' => 'Contact',
              ],
            ],
          ],
          'Participant1' => [
            [
              'fields' => [
                'event_id' => $this->eventId,
                // 'participant_fields.ticket_option' => $this->inPersonPriceFieldValueId,
              ],
            ],
          ],
          'Contribution1' => [
            [
              'fields' => [
                'source' => 'testContributionCreate',
                // 'donation_options.additional_donation' => 5,
              ],
            ],
          ],
        ])
        ->execute();

      $this->fail('Afform::submit should have failed');
    }
    catch (\CRM_Core_Exception $e) {
      $this->assertEquals(TRUE, \str_contains($e->getMessage(), 'No line items'));
    }

  }

}
