<?php

namespace Civi\ext\afform\mock\tests\phpunit\api\v4\Afform;

use api\v4\Afform\AfformUsageTestCase;
use Civi\Api4\Afform;
use Civi\Api4\AfformSubmission;

/**
 * Tests for Afform extra fields.
 *
 * @group headless
 */
class AfformExtraFieldInputTypeTest extends AfformUsageTestCase {

  /**
   * Extra field name => input type. Mixes bare types (Hidden, Select) with
   * the two that have an extra_defn set.
   */
  private const EXTRAS = [
    'extra_hidden' => 'Hidden',
    'extra_select' => 'Select',
    'extra_checkbox' => 'CheckBox',
    'extra_date' => 'Date',
  ];

  private function extraMarkup(): string {
    $markup = '';
    foreach (self::EXTRAS as $name => $inputType) {
      $markup .= "  <af-field defn=\"{name: '$name', input_type: '$inputType'}\" />\n";
    }
    return $markup;
  }

  public function testExtraFieldMetadataInjectionForNonTextInputTypes(): void {
    $layout = <<<EOHTML
<af-form ctrl="afform">
  <af-entity data="{contact_type: 'Individual'}" type="Contact" name="Individual1" label="Individual 1" actions="{create: true, update: true}" security="RBAC" />
  <fieldset af-fieldset="Individual1" class="af-container" af-title="Individual 1">
    <af-field name="first_name" />
    <af-field name="last_name" />
  </fieldset>
{$this->extraMarkup()}  <button class="af-button btn btn-primary" crm-icon="fa-check" ng-click="afform.submit()">Submit</button>
</af-form>
EOHTML;

    $this->useValues([
      'layout' => $layout,
      'permission' => \CRM_Core_Permission::ALWAYS_ALLOW_PERMISSION,
    ]);

    // The form's Angular module isn't in the cache until we flush.
    \Civi::service('angular')->clear();

    // getPartials() applies the change sets registered on hook_civicrm_alterAngular,
    // i.e. AfformMetadataInjector::preprocess().
    $partials = \Civi::service('angular')->getPartials($this->formName);

    // The form's own partial is the one whose key ends in .aff.html — the
    // same pattern the injector's alterHtml() regex matches.
    $formHtml = NULL;
    foreach ($partials as $key => $html) {
      if (str_ends_with($key, '.aff.html')) {
        $formHtml = $html;
        break;
      }
    }
    $this->assertNotNull($formHtml, 'Expected an .aff.html partial for the form.');

    // Every extra (bare or not) gets its input-type template -> proof that
    // setFieldMetadata completed for each, including the bare Hidden/Select
    // that drive the `?? []` guard.
    foreach (self::EXTRAS as $inputType) {
      $this->assertStringContainsString(
        "~/af/fields/$inputType.html",
        $formHtml,
        "Metadata for the '$inputType' extra field was not injected."
      );
    }

    // Make sure the the default `data_type` is added to the bare Checkbox.
    $this->assertStringContainsString('Boolean', $formHtml, "CheckBox extra did not receive data_type 'Boolean'.");

    // Check for is_date as it is set once input_type resolves to 'Date'
    $this->assertStringContainsString('is_date', $formHtml, "Date extra did not receive the is_date flag.");
  }

  /**
   * Make sure `extras` fields are saved upon submission.
   */
  public function testSubmitWithNonTextExtraFields(): void {
    $layout = <<<EOHTML
<af-form ctrl="afform">
  <af-entity data="{source: 'Hello'}" type="Individual" name="Individual1" label="Individual 1" actions="{create: true, update: true}" security="RBAC" />
  <fieldset af-fieldset="Individual1" class="af-container" af-title="Individual 1">
    <af-field name="first_name" />
    <af-field name="last_name" />
  </fieldset>
{$this->extraMarkup()}  <button class="af-button btn btn-primary" crm-icon="fa-check" ng-click="afform.submit()">Submit</button>
</af-form>
EOHTML;

    $this->useValues([
      'layout' => $layout,
      'permission' => \CRM_Core_Permission::ALWAYS_ALLOW_PERMISSION,
      'create_submission' => TRUE,
    ]);

    $values = [
      'Individual1' => [
        [
          'fields' => [
            'first_name' => 'Jane',
            'last_name' => 'Doe',
          ],
        ],
      ],
      'extra' => [
        'fields' => [
          'extra_hidden' => 'cart-token-123',
          'extra_select' => 'option_b',
          'extra_checkbox' => TRUE,
          'extra_date' => '2024-07-04',
        ],
      ],
    ];

    // Make sure we can submit the form with the extras fields.
    $result = Afform::submit()
      ->setName($this->formName)
      ->setValues($values)
      ->execute();

    // Real entity saved => the pipeline ran past the extra-entity id lookup.
    $contactId = $result[0]['Individual1'][0]['id'];
    $this->assertIsNumeric($contactId);
    $this->assertGreaterThan(0, $contactId);

    // Make sure our extras are being saved along with the entity.
    $submission = AfformSubmission::get(FALSE)
      ->addOrderBy('id', 'DESC')
      ->setLimit(1)
      ->execute()->single()['data'];

    $this->assertEquals('Jane', $submission['Individual1'][0]['fields']['first_name']);
    $this->assertEquals('cart-token-123', $submission['extra']['fields']['extra_hidden']);
    $this->assertEquals('option_b', $submission['extra']['fields']['extra_select']);
    $this->assertEquals(TRUE, $submission['extra']['fields']['extra_checkbox']);
    $this->assertEquals('2024-07-04', $submission['extra']['fields']['extra_date']);
  }

}
