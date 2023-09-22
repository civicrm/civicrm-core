<?php

/**
 * Class CRM_Custom_Form_FieldTest
 * @group headless
 */
class CRM_Custom_Form_FieldTest extends CiviUnitTestCase {

  public function setUp(): void {
    parent::setUp();
  }

  /**
   * Test the serialize type is determined properly based on form input.
   *
   * @dataProvider serializeDataProvider
   *
   * @param array $input
   * @param int|string $expected
   */
  public function testDetermineSerializeType(array $input, $expected) {
    $form = new CRM_Custom_Form_Field();
    $this->assertSame($expected, $form->determineSerializeType($input));
  }

  /**
   * DataProvider for testDetermineSerializeType
   * @return array
   */
  public function serializeDataProvider():array {
    return [
      0 => [
        [
          'data_type' => 'String',
          'html_type' => 'Text',
        ],
        0,
      ],
      1 => [
        [
          'data_type' => 'String',
          'html_type' => 'Select',
        ],
        0,
      ],
      2 => [
        [
          'data_type' => 'String',
          'html_type' => 'Radio',
        ],
        0,
      ],
      3 => [
        [
          'data_type' => 'String',
          'html_type' => 'CheckBox',
        ],
        CRM_Core_DAO::SERIALIZE_SEPARATOR_BOOKEND,
      ],
      4 => [
        [
          'data_type' => 'String',
          'html_type' => 'Autocomplete-Select',
        ],
        0,
      ],
      5 => [
        [
          'data_type' => 'String',
          'html_type' => 'Text',
          'serialize' => '1',
        ],
        0,
      ],
      6 => [
        [
          'data_type' => 'String',
          'html_type' => 'Select',
          'serialize' => '1',
        ],
        CRM_Core_DAO::SERIALIZE_SEPARATOR_BOOKEND,
      ],
      7 => [
        [
          'data_type' => 'String',
          'html_type' => 'Radio',
          'serialize' => '1',
        ],
        0,
      ],
      8 => [
        [
          'data_type' => 'String',
          'html_type' => 'CheckBox',
          'serialize' => '1',
        ],
        CRM_Core_DAO::SERIALIZE_SEPARATOR_BOOKEND,
      ],
      9 => [
        [
          'data_type' => 'String',
          'html_type' => 'Autocomplete-Select',
          'serialize' => '1',
        ],
        CRM_Core_DAO::SERIALIZE_SEPARATOR_BOOKEND,
      ],
      10 => [
        [
          'data_type' => 'Int',
          'html_type' => 'Text',
        ],
        0,
      ],
      11 => [
        [
          'data_type' => 'Int',
          'html_type' => 'Select',
        ],
        0,
      ],
      12 => [
        [
          'data_type' => 'Int',
          'html_type' => 'Radio',
        ],
        0,
      ],
      13 => [
        [
          'data_type' => 'Int',
          'html_type' => 'Text',
          'serialize' => '1',
        ],
        0,
      ],
      14 => [
        [
          'data_type' => 'Int',
          'html_type' => 'Select',
          'serialize' => '1',
        ],
        CRM_Core_DAO::SERIALIZE_SEPARATOR_BOOKEND,
      ],
      15 => [
        [
          'data_type' => 'Int',
          'html_type' => 'Radio',
          'serialize' => '1',
        ],
        0,
      ],
      16 => [
        [
          'data_type' => 'Float',
          'html_type' => 'Text',
        ],
        0,
      ],
      17 => [
        [
          'data_type' => 'Float',
          'html_type' => 'Select',
        ],
        0,
      ],
      18 => [
        [
          'data_type' => 'Float',
          'html_type' => 'Radio',
        ],
        0,
      ],
      19 => [
        [
          'data_type' => 'Float',
          'html_type' => 'Text',
          'serialize' => '1',
        ],
        0,
      ],
      20 => [
        [
          'data_type' => 'Float',
          'html_type' => 'Select',
          'serialize' => '1',
        ],
        CRM_Core_DAO::SERIALIZE_SEPARATOR_BOOKEND,
      ],
      21 => [
        [
          'data_type' => 'Float',
          'html_type' => 'Radio',
          'serialize' => '1',
        ],
        0,
      ],
      22 => [
        [
          'data_type' => 'Money',
          'html_type' => 'Text',
        ],
        0,
      ],
      23 => [
        [
          'data_type' => 'Money',
          'html_type' => 'Select',
        ],
        0,
      ],
      24 => [
        [
          'data_type' => 'Money',
          'html_type' => 'Radio',
        ],
        0,
      ],
      25 => [
        [
          'data_type' => 'Money',
          'html_type' => 'Text',
          'serialize' => '1',
        ],
        0,
      ],
      26 => [
        [
          'data_type' => 'Money',
          'html_type' => 'Select',
          'serialize' => '1',
        ],
        CRM_Core_DAO::SERIALIZE_SEPARATOR_BOOKEND,
      ],
      27 => [
        [
          'data_type' => 'Money',
          'html_type' => 'Radio',
          'serialize' => '1',
        ],
        0,
      ],
      28 => [
        [
          'data_type' => 'Memo',
          'html_type' => 'TextArea',
        ],
        0,
      ],
      29 => [
        [
          'data_type' => 'Memo',
          'html_type' => 'RichTextEditor',
        ],
        0,
      ],
      30 => [
        [
          'data_type' => 'Memo',
          'html_type' => 'TextArea',
          'serialize' => '1',
        ],
        0,
      ],
      31 => [
        [
          'data_type' => 'Memo',
          'html_type' => 'RichTextEditor',
          'serialize' => '1',
        ],
        0,
      ],
      32 => [
        [
          'data_type' => 'Date',
          'html_type' => 'Select Date',
        ],
        0,
      ],
      33 => [
        [
          'data_type' => 'Date',
          'html_type' => 'Select Date',
          'serialize' => '1',
        ],
        0,
      ],
      34 => [
        [
          'data_type' => 'Boolean',
          'html_type' => 'Radio',
        ],
        0,
      ],
      35 => [
        [
          'data_type' => 'Boolean',
          'html_type' => 'Radio',
          'serialize' => '1',
        ],
        0,
      ],
      36 => [
        [
          'data_type' => 'StateProvince',
          'html_type' => 'Select',
        ],
        0,
      ],
      37 => [
        [
          'data_type' => 'StateProvince',
          'html_type' => 'Select',
          'serialize' => '1',
        ],
        CRM_Core_DAO::SERIALIZE_SEPARATOR_BOOKEND,
      ],
      38 => [
        [
          'data_type' => 'Country',
          'html_type' => 'Select',
        ],
        0,
      ],
      39 => [
        [
          'data_type' => 'Country',
          'html_type' => 'Select',
          'serialize' => '1',
        ],
        CRM_Core_DAO::SERIALIZE_SEPARATOR_BOOKEND,
      ],
      40 => [
        [
          'data_type' => 'File',
          'html_type' => 'File',
        ],
        0,
      ],
      41 => [
        [
          'data_type' => 'File',
          'html_type' => 'File',
          'serialize' => '1',
        ],
        0,
      ],
      42 => [
        [
          'data_type' => 'Link',
          'html_type' => 'Link',
        ],
        0,
      ],
      43 => [
        [
          'data_type' => 'Link',
          'html_type' => 'Link',
          'serialize' => '1',
        ],
        0,
      ],
      44 => [
        [
          'data_type' => 'ContactReference',
          'html_type' => 'Autocomplete-Select',
        ],
        0,
      ],
      45 => [
        [
          'data_type' => 'ContactReference',
          'html_type' => 'Autocomplete-Select',
          'serialize' => '1',
        ],
        CRM_Core_DAO::SERIALIZE_SEPARATOR_BOOKEND,
      ],
    ];
  }

}
