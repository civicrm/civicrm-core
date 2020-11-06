<?php

/**
 * Class CRM_Custom_Form_FieldTest
 * @group headless
 */
class CRM_Custom_Form_FieldTest extends CiviUnitTestCase {

  public function setUp() {
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
        'null',
      ],
      1 => [
        [
          'data_type' => 'String',
          'html_type' => 'Select',
        ],
        'null',
      ],
      2 => [
        [
          'data_type' => 'String',
          'html_type' => 'Radio',
        ],
        'null',
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
        'null',
      ],
      5 => [
        [
          'data_type' => 'String',
          'html_type' => 'Text',
          'serialize' => '1',
        ],
        'null',
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
        'null',
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
        'null',
      ],
      11 => [
        [
          'data_type' => 'Int',
          'html_type' => 'Select',
        ],
        'null',
      ],
      12 => [
        [
          'data_type' => 'Int',
          'html_type' => 'Radio',
        ],
        'null',
      ],
      13 => [
        [
          'data_type' => 'Int',
          'html_type' => 'Text',
          'serialize' => '1',
        ],
        'null',
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
        'null',
      ],
      16 => [
        [
          'data_type' => 'Float',
          'html_type' => 'Text',
        ],
        'null',
      ],
      17 => [
        [
          'data_type' => 'Float',
          'html_type' => 'Select',
        ],
        'null',
      ],
      18 => [
        [
          'data_type' => 'Float',
          'html_type' => 'Radio',
        ],
        'null',
      ],
      19 => [
        [
          'data_type' => 'Float',
          'html_type' => 'Text',
          'serialize' => '1',
        ],
        'null',
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
        'null',
      ],
      22 => [
        [
          'data_type' => 'Money',
          'html_type' => 'Text',
        ],
        'null',
      ],
      23 => [
        [
          'data_type' => 'Money',
          'html_type' => 'Select',
        ],
        'null',
      ],
      24 => [
        [
          'data_type' => 'Money',
          'html_type' => 'Radio',
        ],
        'null',
      ],
      25 => [
        [
          'data_type' => 'Money',
          'html_type' => 'Text',
          'serialize' => '1',
        ],
        'null',
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
        'null',
      ],
      28 => [
        [
          'data_type' => 'Memo',
          'html_type' => 'TextArea',
        ],
        'null',
      ],
      29 => [
        [
          'data_type' => 'Memo',
          'html_type' => 'RichTextEditor',
        ],
        'null',
      ],
      30 => [
        [
          'data_type' => 'Memo',
          'html_type' => 'TextArea',
          'serialize' => '1',
        ],
        'null',
      ],
      31 => [
        [
          'data_type' => 'Memo',
          'html_type' => 'RichTextEditor',
          'serialize' => '1',
        ],
        'null',
      ],
      32 => [
        [
          'data_type' => 'Date',
          'html_type' => 'Select Date',
        ],
        'null',
      ],
      33 => [
        [
          'data_type' => 'Date',
          'html_type' => 'Select Date',
          'serialize' => '1',
        ],
        'null',
      ],
      34 => [
        [
          'data_type' => 'Boolean',
          'html_type' => 'Radio',
        ],
        'null',
      ],
      35 => [
        [
          'data_type' => 'Boolean',
          'html_type' => 'Radio',
          'serialize' => '1',
        ],
        'null',
      ],
      36 => [
        [
          'data_type' => 'StateProvince',
          'html_type' => 'Select',
        ],
        'null',
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
        'null',
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
        'null',
      ],
      41 => [
        [
          'data_type' => 'File',
          'html_type' => 'File',
          'serialize' => '1',
        ],
        'null',
      ],
      42 => [
        [
          'data_type' => 'Link',
          'html_type' => 'Link',
        ],
        'null',
      ],
      43 => [
        [
          'data_type' => 'Link',
          'html_type' => 'Link',
          'serialize' => '1',
        ],
        'null',
      ],
      44 => [
        [
          'data_type' => 'ContactReference',
          'html_type' => 'Autocomplete-Select',
        ],
        'null',
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
