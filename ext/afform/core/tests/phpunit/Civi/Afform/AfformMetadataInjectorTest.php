<?php
/*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
 */

namespace Civi\Afform;

use Civi\Api4\Afform;
use Civi\Test;
use Civi\Test\CiviEnvBuilder;
use Civi\Test\HeadlessInterface;
use PHPUnit\Framework\TestCase;

/**
 * Tests for AfformMetadataInjector.
 *
 * @group headless
 */
class AfformMetadataInjectorTest extends TestCase implements HeadlessInterface {

  use Test\Api4TestTrait;

  protected string $formName;
  protected string $moduleName;

  public function setUpHeadless(): CiviEnvBuilder {
    return Test::headless()
      ->installMe(__DIR__)
      ->install('org.civicrm.search_kit')
      ->install('civicrm_admin_ui')
      ->apply();
  }

  public function setUp(): void {
    parent::setUp();
    $this->formName = uniqid('testForm');
    $this->moduleName = _afform_angular_module_name($this->formName);
  }

  public function tearDown(): void {
    Afform::revert(FALSE)
      ->addWhere('name', '=', $this->formName)
      ->execute();
    \Civi::service('angular')->clear();
    $this->deleteTestRecords();
    parent::tearDown();
  }

  /**
   * Test that field metadata (labels, data_type, template, options, etc.)
   * is properly injected into field tags' defn attribute.
   */
  public function testMetadataInjection(): void {
    $this->createTestRecord('CustomGroup', [
      'extends' => 'Individual',
      'name' => 'testcustom',
    ]);
    $this->createTestRecord('CustomField', [
      'custom_group_id:name' => 'testcustom',
      'name' => 'testselect',
      'data_type' => 'String',
      'html_type' => 'Select',
      'option_values' => [
        ['label' => 'First', 'id' => '1', 'name' => 'one'],
        ['label' => 'Second', 'id' => '2', 'name' => 'two'],
        ['label' => 'Third', 'id' => '3', 'name' => 'three'],
        ['label' => 'Fourth', 'id' => '4', 'name' => 'four'],
      ],
    ]);

    $layout = <<<EOHTML
<af-form ctrl="afform">
  <af-entity type="Individual" name="Individual1" label="Individual 1" actions="{create: true, update: true}" security="RBAC" />
  <fieldset af-fieldset="Individual1" class="af-container" af-title="Individual 1">
    <af-field name="first_name" />
    <af-field name="gender_id" />
    <af-field name="testcustom.testselect" />
  </fieldset>
</af-form>
EOHTML;

    Afform::create(FALSE)
      ->setValues([
        'name' => $this->formName,
        'title' => 'Test Form',
        'layout' => $layout,
      ])
      ->execute();

    $doc = $this->getFormDocument($this->moduleName);

    // Inspect first_name field
    $firstNameNodes = $doc->find('af-field[name="first_name"]');
    $this->assertCount(1, $firstNameNodes);
    $firstNameDefn = \CRM_Utils_JS::decode($firstNameNodes->attr('defn'));
    $this->assertEquals('First Name', $firstNameDefn['label']);
    $this->assertEquals('String', $firstNameDefn['data_type']);
    $this->assertEquals('Text', $firstNameDefn['input_type']);
    $this->assertEquals('~/af/fields/Text.html', $firstNameDefn['template']);

    // Inspect gender_id field
    $genderNodes = $doc->find('af-field[name="gender_id"]');
    $genderDefn = \CRM_Utils_JS::decode($genderNodes->attr('defn'));
    $this->assertEquals('Gender', $genderDefn['label']);
    $this->assertEquals('Integer', $genderDefn['data_type']);
    $options = array_column($genderDefn['options'], 'id', 'label');
    // data_type is Integer so keys will be int
    $this->assertSame(1, $options['Female']);
    $this->assertSame(2, $options['Male']);

    // Inspect custom select field
    $customSelectNodes = $doc->find('af-field[name="testcustom.testselect"]');
    $customSelectDefn = \CRM_Utils_JS::decode($customSelectNodes->attr('defn'));
    $this->assertEquals('~/af/fields/Select.html', $customSelectDefn['template']);
    $this->assertEquals('String', $customSelectDefn['data_type']);
    $options = array_column($customSelectDefn['options'], 'id', 'label');
    // data_type is String so keys will be string
    $this->assertSame('1', $options['First']);
    $this->assertSame('2', $options['Second']);
  }

  /**
   * Test custom field metadata injection and auto-generated custom group afform block.
   */
  public function testCustomFieldMetadataInjection(): void {
    $groupName = 'cg' . rand(1000, 9999);
    $this->createTestRecord('CustomGroup', [
      'title' => 'Test Custom Group',
      'name' => $groupName,
      'extends' => 'Individual',
    ]);

    // 1. String Text field without options, with default value
    $this->createTestRecord('CustomField', [
      'custom_group_id:name' => $groupName,
      'name' => 'f_string',
      'label' => 'Field String',
      'data_type' => 'String',
      'html_type' => 'Text',
      'default_value' => 'Hello World',
    ]);

    // 2. Integer Text field without options, with default value
    $this->createTestRecord('CustomField', [
      'custom_group_id:name' => $groupName,
      'name' => 'f_int',
      'label' => 'Field Int',
      'data_type' => 'Int',
      'html_type' => 'Text',
      'default_value' => '42',
    ]);

    // 3. Float Text field without options, with default value
    $this->createTestRecord('CustomField', [
      'custom_group_id:name' => $groupName,
      'name' => 'f_float',
      'label' => 'Field Float',
      'data_type' => 'Float',
      'html_type' => 'Text',
      'default_value' => '3.14',
    ]);

    // 4. Boolean field without options, with default value
    $this->createTestRecord('CustomField', [
      'custom_group_id:name' => $groupName,
      'name' => 'f_bool',
      'label' => 'Field Bool',
      'data_type' => 'Boolean',
      'html_type' => 'Radio',
      'default_value' => '1',
    ]);

    // 5. String Select with options, without serialize, with default value
    $this->createTestRecord('CustomField', [
      'custom_group_id:name' => $groupName,
      'name' => 'f_select',
      'label' => 'Field Select',
      'data_type' => 'String',
      'html_type' => 'Select',
      'default_value' => '1',
      'option_values' => [
        ['label' => 'First', 'id' => '1', 'name' => 'one'],
        ['label' => 'Second', 'id' => '2', 'name' => 'two'],
      ],
    ]);

    // 6. String Checkbox with options, with serialize, with default value
    $this->createTestRecord('CustomField', [
      'custom_group_id:name' => $groupName,
      'name' => 'f_checkbox',
      'label' => 'Field Checkbox',
      'data_type' => 'String',
      'html_type' => 'CheckBox',
      'serialize' => 1,
      'default_value' => \CRM_Utils_Array::implodePadded(['1', '2']),
      'option_values' => [
        ['label' => 'First', 'id' => '1', 'name' => 'one'],
        ['label' => 'Second', 'id' => '2', 'name' => 'two'],
      ],
    ]);

    // 7. Field without default value
    $this->createTestRecord('CustomField', [
      'custom_group_id:name' => $groupName,
      'name' => 'f_nodefault',
      'label' => 'Field No Default',
      'data_type' => 'String',
      'html_type' => 'Text',
    ]);

    // 8. Date field without options, with default value
    $this->createTestRecord('CustomField', [
      'custom_group_id:name' => $groupName,
      'name' => 'f_date',
      'label' => 'Field Date',
      'data_type' => 'Date',
      'html_type' => 'Select Date',
      'date_format' => 'yy-mm-dd',
      'time_format' => 0,
      'default_value' => '2026-01-15',
    ]);

    // 9. Integer field with default value '0' (tests that falsy default values are preserved)
    $this->createTestRecord('CustomField', [
      'custom_group_id:name' => $groupName,
      'name' => 'f_zero',
      'label' => 'Field Zero',
      'data_type' => 'Int',
      'html_type' => 'Text',
      'default_value' => '0',
    ]);

    // 10 Integer field with serialized defaults
    $this->createTestRecord('CustomField', [
      'custom_group_id:name' => $groupName,
      'name' => 'f_multiselectint',
      'label' => 'Field MultiSelectInt',
      'data_type' => 'Int',
      'html_type' => 'Select',
      'serialize' => 1,
      'default_value' => \CRM_Utils_Array::implodePadded(['1', '2']),
      'option_values' => [
        ['label' => 'First', 'id' => 1, 'name' => 'one'],
        ['label' => 'Second', 'id' => 2, 'name' => 'two'],
        ['label' => 'Third', 'id' => 3, 'name' => 'three'],
      ],
    ]);

    // Multi-record CustomGroup (tests Custom_<group> entity spec lookup)
    $multiGroupName = 'cg_multi' . rand(1000, 9999);
    $this->createTestRecord('CustomGroup', [
      'title' => 'Multi Custom Group',
      'name' => $multiGroupName,
      'extends' => 'Individual',
      'is_multiple' => TRUE,
    ]);
    $this->createTestRecord('CustomField', [
      'custom_group_id:name' => $multiGroupName,
      'name' => 'f_multi_opt',
      'label' => 'Field Multi Opt',
      'data_type' => 'String',
      'html_type' => 'Select',
      'default_value' => '1',
      'option_values' => [
        ['label' => 'First', 'id' => '1', 'name' => 'one'],
        ['label' => 'Second', 'id' => '2', 'name' => 'two'],
      ],
    ]);

    \Civi::service('angular')->clear();

    $moduleName = _afform_angular_module_name('afblockCustom_' . $groupName);
    $doc = $this->getFormDocument($moduleName);

    // Inspect fields
    $fields = $doc->find('af-field');
    $this->assertCount(10, $fields);

    // 1. String Text field
    $stringField = $doc->find("af-field[name='{$groupName}.f_string']");
    $this->assertCount(1, $stringField);
    $stringDefn = \CRM_Utils_JS::decode($stringField->attr('defn'));
    $this->assertSame('Test Custom Group: Field String', $stringDefn['label']);
    $this->assertSame('String', $stringDefn['data_type']);
    $this->assertSame('Text', $stringDefn['input_type']);
    $this->assertSame('~/af/fields/Text.html', $stringDefn['template']);
    $this->assertSame('Hello World', $stringDefn['afform_default']);

    // 2. Integer Text field
    $intField = $doc->find("af-field[name='{$groupName}.f_int']");
    $this->assertCount(1, $intField);
    $intDefn = \CRM_Utils_JS::decode($intField->attr('defn'));
    $this->assertSame('Test Custom Group: Field Int', $intDefn['label']);
    $this->assertSame('Integer', $intDefn['data_type']);
    $this->assertSame('Number', $intDefn['input_type']);
    $this->assertSame('~/af/fields/Number.html', $intDefn['template']);
    $this->assertSame(42, $intDefn['afform_default']);

    // 3. Float Text field
    $floatField = $doc->find("af-field[name='{$groupName}.f_float']");
    $this->assertCount(1, $floatField);
    $floatDefn = \CRM_Utils_JS::decode($floatField->attr('defn'));
    $this->assertSame('Test Custom Group: Field Float', $floatDefn['label']);
    $this->assertSame('Float', $floatDefn['data_type']);
    $this->assertSame('Number', $floatDefn['input_type']);
    $this->assertSame('~/af/fields/Number.html', $floatDefn['template']);
    $this->assertSame(3.14, $floatDefn['afform_default']);

    // 4. Boolean field
    $boolField = $doc->find("af-field[name='{$groupName}.f_bool']");
    $this->assertCount(1, $boolField);
    $boolDefn = \CRM_Utils_JS::decode($boolField->attr('defn'));
    $this->assertSame('Test Custom Group: Field Bool', $boolDefn['label']);
    $this->assertSame('Boolean', $boolDefn['data_type']);
    $this->assertSame('Radio', $boolDefn['input_type']);
    $this->assertSame('~/af/fields/Radio.html', $boolDefn['template']);
    $this->assertSame(TRUE, $boolDefn['afform_default']);

    // 5. String Select with options, without serialize
    // Suffix :name maps option ID '1' to option name 'one'
    $selectField = $doc->find("af-field[name='{$groupName}.f_select:name']");
    $this->assertCount(1, $selectField);
    $selectDefn = \CRM_Utils_JS::decode($selectField->attr('defn'));
    $this->assertSame('Test Custom Group: Field Select', $selectDefn['label']);
    $this->assertSame('String', $selectDefn['data_type']);
    $this->assertSame('Select', $selectDefn['input_type']);
    $this->assertSame('~/af/fields/Select.html', $selectDefn['template']);
    $selectOptions = array_column($selectDefn['options'], 'id', 'label');
    $this->assertSame('one', $selectOptions['First']);
    $this->assertSame('two', $selectOptions['Second']);
    $this->assertSame('one', $selectDefn['afform_default']);

    // 6. Checkbox with serialize
    // Suffix :name maps option IDs ['1', '2'] to option names ['one', 'two']
    $checkboxField = $doc->find("af-field[name='{$groupName}.f_checkbox:name']");
    $this->assertCount(1, $checkboxField);
    $checkboxDefn = \CRM_Utils_JS::decode($checkboxField->attr('defn'));
    $this->assertSame('Test Custom Group: Field Checkbox', $checkboxDefn['label']);
    $this->assertSame('String', $checkboxDefn['data_type']);
    $this->assertSame('CheckBox', $checkboxDefn['input_type']);
    $this->assertSame('~/af/fields/CheckBox.html', $checkboxDefn['template']);
    $checkboxOptions = array_column($checkboxDefn['options'], 'id', 'label');
    $this->assertSame('one', $checkboxOptions['First']);
    $this->assertSame('two', $checkboxOptions['Second']);
    $this->assertSame(['one', 'two'], $checkboxDefn['afform_default']);

    // 7. Field without default
    $noDefaultField = $doc->find("af-field[name='{$groupName}.f_nodefault']");
    $this->assertCount(1, $noDefaultField);
    $noDefaultDefn = \CRM_Utils_JS::decode($noDefaultField->attr('defn'));
    $this->assertSame('Test Custom Group: Field No Default', $noDefaultDefn['label']);
    $this->assertSame('String', $noDefaultDefn['data_type']);
    $this->assertSame('Text', $noDefaultDefn['input_type']);
    $this->assertSame('~/af/fields/Text.html', $noDefaultDefn['template']);
    $this->assertArrayNotHasKey('afform_default', $noDefaultDefn);

    // 8. Date field
    $dateField = $doc->find("af-field[name='{$groupName}.f_date']");
    $this->assertCount(1, $dateField);
    $dateDefn = \CRM_Utils_JS::decode($dateField->attr('defn'));
    $this->assertSame('Test Custom Group: Field Date', $dateDefn['label']);
    $this->assertSame('Date', $dateDefn['data_type']);
    $this->assertSame('Date', $dateDefn['input_type']);
    $this->assertSame('~/af/fields/Date.html', $dateDefn['template']);
    $this->assertSame('2026-01-15', $dateDefn['afform_default']);
    $this->assertSame(1, $dateDefn['is_date']);

    // 9. Integer field with default 0
    $zeroField = $doc->find("af-field[name='{$groupName}.f_zero']");
    $this->assertCount(1, $zeroField);
    $zeroDefn = \CRM_Utils_JS::decode($zeroField->attr('defn'));
    $this->assertSame('Test Custom Group: Field Zero', $zeroDefn['label']);
    $this->assertSame('Integer', $zeroDefn['data_type']);
    $this->assertSame('Number', $zeroDefn['input_type']);
    $this->assertSame('~/af/fields/Number.html', $zeroDefn['template']);
    $this->assertSame(0, $zeroDefn['afform_default']);

    // 10. Integer field with serialized defaults
    $multiSelectIntField = $doc->find("af-field[name='{$groupName}.f_multiselectint:name']");
    $this->assertCount(1, $multiSelectIntField);
    $multiSelectIntDefn = \CRM_Utils_JS::decode($multiSelectIntField->attr('defn'));
    $this->assertSame('Test Custom Group: Field MultiSelectInt', $multiSelectIntDefn['label']);
    $this->assertSame('String', $multiSelectIntDefn['data_type']);
    $this->assertSame('Select', $multiSelectIntDefn['input_type']);
    $this->assertSame('~/af/fields/Select.html', $multiSelectIntDefn['template']);
    $multiSelectIntOptions = array_column($multiSelectIntDefn['options'], 'id', 'label');
    $this->assertSame('one', $multiSelectIntOptions['First']);
    $this->assertSame('two', $multiSelectIntOptions['Second']);
    $this->assertSame('three', $multiSelectIntOptions['Third']);
    $this->assertSame(['one', 'two'], $multiSelectIntDefn['afform_default']);

    // Also inspect the auto-generated view form (covering afformView.tpl)
    $viewModuleName = _afform_angular_module_name('afformViewCustom_' . $groupName);
    $viewDoc = $this->getFormDocument($viewModuleName);

    // Parent field in view form
    $parentField = $viewDoc->find("af-field[name='id']");
    $this->assertCount(1, $parentField);
    $parentDefn = \CRM_Utils_JS::decode($parentField->attr('defn'));
    $this->assertSame('Hidden', $parentDefn['input_type']);

    // Check that custom fields in view form have input_type DisplayOnly and corresponding template
    $viewStringField = $viewDoc->find("af-field[name='{$groupName}.f_string']");
    $this->assertCount(1, $viewStringField);
    $viewStringDefn = \CRM_Utils_JS::decode($viewStringField->attr('defn'));
    $this->assertSame('DisplayOnly', $viewStringDefn['input_type']);
    $this->assertSame('~/af/fields/DisplayOnly.html', $viewStringDefn['template']);
    $this->assertSame('Hello World', $viewStringDefn['afform_default']);

    $viewSelectField = $viewDoc->find("af-field[name='{$groupName}.f_select:name']");
    $this->assertCount(1, $viewSelectField);
    $viewSelectDefn = \CRM_Utils_JS::decode($viewSelectField->attr('defn'));
    $this->assertSame('DisplayOnly', $viewSelectDefn['input_type']);
    $this->assertSame('~/af/fields/DisplayOnly.html', $viewSelectDefn['template']);

    $viewMultiSelectInt = $viewDoc->find("af-field[name='{$groupName}.f_multiselectint:name']");
    $this->assertCount(1, $viewMultiSelectInt);
    $viewMultiSelectIntDefn = \CRM_Utils_JS::decode($viewMultiSelectInt->attr('defn'));
    $this->assertSame('DisplayOnly', $viewMultiSelectIntDefn['input_type']);
    $this->assertSame('~/af/fields/DisplayOnly.html', $viewMultiSelectIntDefn['template']);
    $this->assertSame(['one', 'two'], $viewMultiSelectIntDefn['afform_default']);

    // Also inspect the multi-record custom group block
    $multiModuleName = _afform_angular_module_name('afblockCustom_' . $multiGroupName);
    $multiDoc = $this->getFormDocument($multiModuleName);

    // For multi-record fields, key is just field name with suffix, not prefixed by group name
    $multiField = $multiDoc->find("af-field[name='f_multi_opt:name']");
    $this->assertCount(1, $multiField);
    $multiDefn = \CRM_Utils_JS::decode($multiField->attr('defn'));
    $this->assertSame('Multi Custom Group: Field Multi Opt', $multiDefn['label']);
    $this->assertSame('String', $multiDefn['data_type']);
    $this->assertSame('Select', $multiDefn['input_type']);
    $this->assertSame('~/af/fields/Select.html', $multiDefn['template']);
    $this->assertSame('one', $multiDefn['afform_default']);
  }

  private function getFormDocument($moduleName): \phpQueryObject {
    // Retrieving partials triggers alterHtml callbacks registered via hook_civicrm_alterAngular,
    // which invokes AfformMetadataInjector::preprocess()
    $partials = \Civi::service('angular')->getPartials($moduleName);

    foreach ($partials as $path => $html) {
      if (str_ends_with($path, '.aff.html')) {
        return \phpQuery::newDocumentHTML($html);
      }
    }

    throw new \CRM_Core_Exception('Expected an .aff.html partial for the form.');
  }

}
