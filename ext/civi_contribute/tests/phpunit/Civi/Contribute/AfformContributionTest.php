<?php

namespace Civi\Contribute;

use Civi\Afform\Event\AfformEntitySortEvent;
use Civi\Afform\FormDataModel;
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
    unset(\Civi::$statics[PriceFieldUtils::class . '::restrictedPriceFieldValueIds']);

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
      ->addWhere('name', 'IN', ['in_person', 'online', 'admin_only'])
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

  public function testValidateLineItems(): void {
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

    $result = $response->first();
    $this->assertTrue($result['is_blocking_error'] ?? FALSE);
    $messages = implode("\n", array_map(fn (\Civi\Api4\Generic\Error $error) => $error->getMessage(), $result['errors']));
    $this->assertEquals(TRUE, \str_contains($messages, 'No line items'));
  }

  public function testEntityDependencyOrdering() {
    $formHtml = <<<HTML
      <af-form>
        <af-entity type="Contribution" name="Contribution1" data="{contact_id: 'Individual1'}" />
        <af-entity type="Participant" name="Participant1">
        <af-entity type="Participant" name="Participant2" data="{'participant_fields.ticket_option': 10}">
        <af-entity type="Individual" name="Individual1" />
        <fieldset af-fieldset="Participant1" class="af-container">
          <af-field name="participant_fields.ticket_option" />
          <af-field name="source" />
        </fieldset>
        <fieldset af-fieldset="Individual1" class="af-container">
          <af-field name="first_name" />
          <af-field name="last_name" />
        </fieldset>
      </af-form>
      HTML;

    $parser = new \CRM_Afform_ArrayHtml();
    $formDataModel = new FormDataModel($parser->convertHtmlToArray($formHtml));

    $entityValues = [
      'Contribution1' => [['fields' => ['contact_id' => 'Individual1']]],
      'Participant1' => [['fields' => ['participant_fields.ticket_option' => '5', 'source' => 'Test Registration']]],
      'Participant2' => [['fields' => ['participant_fields.ticket_option' => '10']]],
      'Individual1' => [['fields' => ['first_name' => 'Test', 'last_name' => 'Contact']]],
    ];

    $sorter = new AfformEntitySortEvent([], $formDataModel, new \Civi\Api4\Generic\BasicGetAction('', ''), $entityValues);
    \Civi::dispatcher()->dispatch('civi.afform.sort.submit', $sorter);
    $sorted = $sorter->getSorted();

    // expect Participants and Contact moved before Contribution
    $expected = [
      'Individual1',
      'Participant1',
      'Participant2',
      'Contribution1',
    ];
    $this->assertEquals($expected, $sorted);
  }

  /**
   * An active "admin" visibility PriceFieldValue is a restricted option: it
   * stays in the option list (unlike an inactive value) but is reported by
   * getRestrictedPriceFieldValueIds(); public values are not.
   *
   * @throws \CRM_Core_Exception
   */
  public function testGetRestrictedPriceFieldValueIds(): void {
    $adminPfvId = $this->addAdminTicketOption();

    $restricted = PriceFieldUtils::getRestrictedPriceFieldValueIds();
    $this->assertContains($adminPfvId, $restricted);
    // The public "In person" value must NOT be treated as restricted.
    $this->assertNotContains($this->inPersonPriceFieldValueId, $restricted);
  }

  /**
   * A user without 'edit contributions' may not submit an admin-visibility
   * price option, even though it is present in the option list.
   *
   * @throws \CRM_Core_Exception
   */
  public function testAdminPriceOptionRejectedWithoutPermission(): void {
    $adminPfvId = $this->addAdminTicketOption();

    $permission = \CRM_Core_Config::singleton()->userPermissionClass;
    $backup = $permission->permissions;
    // Grant enough to run the form, but NOT 'edit contributions'.
    $permission->permissions = ['access CiviCRM', 'access CiviContribute', 'access CiviEvent'];
    try {
      Afform::submit(FALSE)
        ->setName('testAfformContribution')
        ->setValues([
          'Individual1' => [['fields' => ['first_name' => 'Test', 'last_name' => 'Contact']]],
          'Participant1' => [
            [
              'fields' => [
                'event_id' => $this->eventId,
                'participant_fields.ticket_option' => $adminPfvId,
              ],
            ],
          ],
          'Contribution1' => [['fields' => ['source' => 'restricted']]],
        ])
        ->execute();
      $this->fail('Afform::submit should have rejected the restricted price option');
    }
    catch (\CRM_Core_Exception $e) {
      $this->assertStringContainsString('not permitted', $e->getMessage());
    }
    finally {
      $permission->permissions = $backup;
    }
  }

  /**
   * A user with 'edit contributions' may submit an admin-visibility price
   * option and it produces a line item.
   *
   * @throws \CRM_Core_Exception
   */
  public function testAdminPriceOptionAllowedWithPermission(): void {
    $adminPfvId = $this->addAdminTicketOption(20);

    // Default headless permissions (NULL) grant everything, including
    // 'edit contributions'.
    $response = Afform::submit(FALSE)
      ->setName('testAfformContribution')
      ->setValues([
        'Individual1' => [['fields' => ['first_name' => 'Admin', 'last_name' => 'User']]],
        'Participant1' => [
          [
            'fields' => [
              'event_id' => $this->eventId,
              'participant_fields.ticket_option' => $adminPfvId,
            ],
          ],
        ],
        'Contribution1' => [['fields' => ['source' => 'adminAllowed']]],
      ])
      ->execute();

    $contributionId = $response->single()['Contribution1'][0]['id'];
    $this->assertGreaterThan(0, $contributionId);

    $contribution = \Civi\Api4\Contribution::get(FALSE)
      ->addWhere('id', '=', $contributionId)
      ->execute()
      ->single();
    // Only the admin ticket option (amount 20) contributes to the total.
    $this->assertEquals(20, $contribution['total_amount']);
  }

  /**
   * The prefill response carries `has_all_price_options` on each price-bearing
   * entity, TRUE only when the current user holds 'edit contributions'. This is
   * what the client-side af-if reads to decide whether to show admin options.
   *
   * @throws \CRM_Core_Exception
   */
  public function testPrefillPublishesRestrictedFlag(): void {
    $this->addAdminTicketOption();
    $flag = PriceFieldUtils::RESTRICTED_OPTIONS_FLAG;

    // Default headless permissions (NULL) grant everything => flag TRUE.
    $prefill = Afform::prefill(FALSE)
      ->setName('testAfformContribution')
      ->execute()
      ->indexBy('name');
    $this->assertTrue((bool) ($prefill['Participant1']['values'][0]['fields'][$flag] ?? NULL));
    $this->assertTrue((bool) ($prefill['Contribution1']['values'][0]['fields'][$flag] ?? NULL));

    // Without 'edit contributions' => flag FALSE.
    $permission = \CRM_Core_Config::singleton()->userPermissionClass;
    $backup = $permission->permissions;
    $permission->permissions = ['access CiviCRM', 'access CiviContribute', 'access CiviEvent'];
    try {
      $prefill = Afform::prefill(FALSE)
        ->setName('testAfformContribution')
        ->execute()
        ->indexBy('name');
      $this->assertFalse((bool) ($prefill['Participant1']['values'][0]['fields'][$flag] ?? NULL));
    }
    finally {
      $permission->permissions = $backup;
    }
  }

  /**
   * The alterAngular injector tags an admin option with an `if:` bound to the
   * flag - even when the price field is referenced with a ':name' suffix (the
   * real-world markup form). This is the regression guard for suffix matching.
   *
   * @throws \CRM_Core_Exception
   */
  public function testInjectorTagsAdminOptionWithNameSuffix(): void {
    $adminPfvId = $this->addAdminTicketOption();

    // Reference the price field WITH a ':name' suffix, as real forms do.
    $layout = <<<HTML
    <af-form ctrl="afform">
      <af-entity type="Individual" name="Individual1" label="Individual 1" actions="{create: true}" security="FBAC" />
      <af-entity type="Participant" name="Participant1" label="Participant 1" data="{contact_id: 'Individual1'}" actions="{create: true}" security="FBAC" />
      <fieldset af-fieldset="Participant1" class="af-container">
        <af-field name="event_id" />
        <af-field name="participant_fields.ticket_option:name" />
      </fieldset>
    </af-form>
    HTML;
    Afform::save(FALSE)
      ->addRecord(['name' => 'testAfformPriceSuffix', 'layout' => $layout])
      ->setLayoutFormat('html')
      ->execute();

    $moduleName = Afform::get(FALSE)
      ->addWhere('name', '=', 'testAfformPriceSuffix')
      ->addSelect('module_name')
      ->execute()
      ->single()['module_name'];

    // Fresh Manager so alterAngular (and thus our injector) recomputes against
    // the admin PriceFieldValue just created, rather than a cached change set.
    $manager = new \Civi\Angular\Manager(\CRM_Core_Resources::singleton());
    $html = implode("\n", $manager->getPartials($moduleName));

    // The suffixed field was matched and the admin option carries the if:.
    $this->assertStringContainsString(PriceFieldUtils::RESTRICTED_OPTIONS_FLAG, $html);
    $this->assertStringContainsString('IS NOT EMPTY', $html);
    // The admin PFV id must appear in the baked options (it is not filtered out).
    $this->assertStringContainsString((string) $adminPfvId, $html);
  }

  /**
   * Add an active admin-visibility PriceFieldValue to the participant
   * ticket_option field and refresh the PriceFieldUtils caches.
   *
   * @throws \CRM_Core_Exception
   */
  private function addAdminTicketOption(float $amount = 20): int {
    $ticketFieldId = \Civi\Api4\PriceField::get(FALSE)
      ->addWhere('name', '=', 'ticket_option')
      ->execute()
      ->single()['id'];

    $pfv = \Civi\Api4\PriceFieldValue::create(FALSE)
      ->addValue('name', 'admin_only')
      ->addValue('label', 'Admin only')
      ->addValue('amount', $amount)
      ->addValue('price_field_id', $ticketFieldId)
      ->addValue('financial_type_id:name', 'Event Fee')
      ->addValue('visibility_id:name', 'admin')
      ->execute()
      ->single();

    // The specs + restricted-id lists are statically cached; refresh both so
    // the new option is visible to this request.
    unset(\Civi::$statics[PriceFieldUtils::class]);
    unset(\Civi::$statics[PriceFieldUtils::class . '::restrictedPriceFieldValueIds']);

    return (int) $pfv['id'];
  }

}
