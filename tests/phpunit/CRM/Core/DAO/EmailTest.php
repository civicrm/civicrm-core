<?php

/**
 * Class CRM_Core_BAO_EmailTest
 * @group headless
 */
class CRM_Core_DAO_EmailTest extends CiviUnitTestCase {

  /**
   * Super-brittle test added while refactoring DAOs to use CRM_Core_DAO_Base.
   * Probably should be dialed back or deleted once refactor is complete.
   */
  public function testBasicInfo(): void {
    $this->assertEquals('civicrm_email', CRM_Core_DAO_Email::getTableName());
    $this->assertEquals('civicrm', CRM_Core_DAO_Email::getExtensionName());
    $emailBao = new CRM_Core_DAO_Email();
    $this->assertTrue($emailBao->getLog());
  }

  /**
   * Super-brittle test added while refactoring DAOs to use CRM_Core_DAO_Base.
   * Probably should be dialed back or deleted once refactor is complete.
   */
  public function testDescription(): void {
    $expected = 'Email information for a specific location.';
    $actual = CRM_Core_DAO_Email::getEntityDescription();
    $this->assertEquals($expected, $actual);
  }

  /**
   * Super-brittle test added while refactoring DAOs to use CRM_Core_DAO_Base.
   * Probably should be dialed back or deleted once refactor is complete.
   */
  public function testIndices(): void {
    $expected = [
      'index_location_type' => [
        'name' => 'index_location_type',
        'field' => [
          0 => 'location_type_id',
        ],
        'localizable' => FALSE,
        'sig' => 'civicrm_email::0::location_type_id',
      ],
      'UI_email' => [
        'name' => 'UI_email',
        'field' => [
          0 => 'email',
        ],
        'localizable' => FALSE,
        'sig' => 'civicrm_email::0::email',
      ],
      'index_is_primary' => [
        'name' => 'index_is_primary',
        'field' => [
          0 => 'is_primary',
        ],
        'localizable' => FALSE,
        'sig' => 'civicrm_email::0::is_primary',
      ],
      'index_is_billing' => [
        'name' => 'index_is_billing',
        'field' => [
          0 => 'is_billing',
        ],
        'localizable' => FALSE,
        'sig' => 'civicrm_email::0::is_billing',
      ],
    ];
    $this->assertEquals($expected, CRM_Core_BAO_Email::indices(FALSE));
  }

  /**
   * Super-brittle test added while refactoring DAOs to use CRM_Core_DAO_Base.
   * Probably should be dialed back or deleted once refactor is complete.
   */
  public function testFields(): void {
    $expected = [
      'id' => [
        'name' => 'id',
        'type' => CRM_Utils_Type::T_INT,
        'title' => ts('Email ID'),
        'description' => ts('Unique Email ID'),
        'required' => TRUE,
        'usage' => [
          'import' => FALSE,
          'export' => FALSE,
          'duplicate_matching' => FALSE,
          'token' => FALSE,
        ],
        'where' => 'civicrm_email.id',
        'table_name' => 'civicrm_email',
        'entity' => 'Email',
        'bao' => 'CRM_Core_BAO_Email',
        'localizable' => 0,
        'html' => [
          'type' => 'Number',
        ],
        'readonly' => TRUE,
        'add' => '1.1',
      ],
      'contact_id' => [
        'name' => 'contact_id',
        'type' => CRM_Utils_Type::T_INT,
        'title' => ts('Contact ID'),
        'description' => ts('FK to Contact ID'),
        'usage' => [
          'import' => FALSE,
          'export' => FALSE,
          'duplicate_matching' => FALSE,
          'token' => FALSE,
        ],
        'where' => 'civicrm_email.contact_id',
        'table_name' => 'civicrm_email',
        'entity' => 'Email',
        'bao' => 'CRM_Core_BAO_Email',
        'localizable' => 0,
        'FKClassName' => 'CRM_Contact_DAO_Contact',
        'FKColumnName' => 'id',
        'html' => [
          'label' => ts("Contact"),
        ],
        'add' => '2.0',
      ],
      'location_type_id' => [
        'name' => 'location_type_id',
        'type' => CRM_Utils_Type::T_INT,
        'title' => ts('Email Location Type'),
        'description' => ts('Which Location does this email belong to.'),
        'usage' => [
          'import' => FALSE,
          'export' => FALSE,
          'duplicate_matching' => FALSE,
          'token' => FALSE,
        ],
        'where' => 'civicrm_email.location_type_id',
        'table_name' => 'civicrm_email',
        'entity' => 'Email',
        'bao' => 'CRM_Core_BAO_Email',
        'localizable' => 0,
        'html' => [
          'type' => 'Select',
        ],
        'pseudoconstant' => [
          'table' => 'civicrm_location_type',
          'keyColumn' => 'id',
          'labelColumn' => 'display_name',
        ],
        'add' => '2.0',
      ],
      'email' => [
        'name' => 'email',
        'type' => CRM_Utils_Type::T_STRING,
        'title' => ts('Email'),
        'description' => ts('Email address'),
        'maxlength' => 254,
        'size' => 30,
        'usage' => [
          'import' => TRUE,
          'export' => TRUE,
          'duplicate_matching' => TRUE,
          'token' => FALSE,
        ],
        'import' => TRUE,
        'where' => 'civicrm_email.email',
        'headerPattern' => '/e.?mail/i',
        'dataPattern' => '/^[a-zA-Z][\w\.-]*[a-zA-Z0-9]@[a-zA-Z0-9][\w\.-]*[a-zA-Z0-9]\.[a-zA-Z][a-zA-Z\.]*[a-zA-Z]$/',
        'export' => TRUE,
        'rule' => 'email',
        'table_name' => 'civicrm_email',
        'entity' => 'Email',
        'bao' => 'CRM_Core_BAO_Email',
        'localizable' => 0,
        'html' => [
          'type' => 'Email',
        ],
        'add' => '1.1',
      ],
      'is_primary' => [
        'name' => 'is_primary',
        'type' => CRM_Utils_Type::T_BOOLEAN,
        'title' => ts('Is Primary'),
        'description' => ts('Is this the primary email address'),
        'required' => TRUE,
        'usage' => [
          'import' => FALSE,
          'export' => FALSE,
          'duplicate_matching' => FALSE,
          'token' => FALSE,
        ],
        'where' => 'civicrm_email.is_primary',
        'default' => '0',
        'table_name' => 'civicrm_email',
        'entity' => 'Email',
        'bao' => 'CRM_Core_BAO_Email',
        'localizable' => 0,
        'html' => [
          'type' => 'Radio',
        ],
        'add' => '1.1',
      ],
      'is_billing' => [
        'name' => 'is_billing',
        'type' => CRM_Utils_Type::T_BOOLEAN,
        'title' => ts('Is Billing Email?'),
        'description' => ts('Is this the billing?'),
        'required' => TRUE,
        'usage' => [
          'import' => FALSE,
          'export' => FALSE,
          'duplicate_matching' => FALSE,
          'token' => FALSE,
        ],
        'where' => 'civicrm_email.is_billing',
        'default' => '0',
        'table_name' => 'civicrm_email',
        'entity' => 'Email',
        'bao' => 'CRM_Core_BAO_Email',
        'localizable' => 0,
        'add' => '2.0',
      ],
      'on_hold' => [
        'name' => 'on_hold',
        'type' => CRM_Utils_Type::T_INT,
        'title' => ts('On Hold'),
        'description' => ts('Implicit FK to civicrm_option_value where option_group = email_on_hold.'),
        'required' => TRUE,
        'usage' => [
          'import' => FALSE,
          'export' => TRUE,
          'duplicate_matching' => FALSE,
          'token' => FALSE,
        ],
        'where' => 'civicrm_email.on_hold',
        'export' => TRUE,
        'default' => '0',
        'table_name' => 'civicrm_email',
        'entity' => 'Email',
        'bao' => 'CRM_Core_BAO_Email',
        'localizable' => 0,
        'html' => [
          'type' => 'Select',
        ],
        'pseudoconstant' => [
          'callback' => 'CRM_Core_PseudoConstant::emailOnHoldOptions',
        ],
        'add' => '1.1',
      ],
      'is_bulkmail' => [
        'name' => 'is_bulkmail',
        'type' => CRM_Utils_Type::T_BOOLEAN,
        'title' => ts('Use for Bulk Mail'),
        'description' => ts('Is this address for bulk mail ?'),
        'required' => TRUE,
        'usage' => [
          'import' => FALSE,
          'export' => TRUE,
          'duplicate_matching' => FALSE,
          'token' => FALSE,
        ],
        'where' => 'civicrm_email.is_bulkmail',
        'export' => TRUE,
        'default' => '0',
        'table_name' => 'civicrm_email',
        'entity' => 'Email',
        'bao' => 'CRM_Core_BAO_Email',
        'localizable' => 0,
        'add' => '1.9',
      ],
      'hold_date' => [
        'name' => 'hold_date',
        'type' => CRM_Utils_Type::T_DATE + CRM_Utils_Type::T_TIME,
        'title' => ts('Hold Date'),
        'description' => ts('When the address went on bounce hold'),
        'usage' => [
          'import' => FALSE,
          'export' => FALSE,
          'duplicate_matching' => FALSE,
          'token' => FALSE,
        ],
        'where' => 'civicrm_email.hold_date',
        'table_name' => 'civicrm_email',
        'entity' => 'Email',
        'bao' => 'CRM_Core_BAO_Email',
        'localizable' => 0,
        'html' => [
          'type' => 'Select Date',
          'formatType' => 'activityDateTime',
          'label' => ts("Hold Date"),
        ],
        'readonly' => TRUE,
        'add' => '1.1',
      ],
      'reset_date' => [
        'name' => 'reset_date',
        'type' => CRM_Utils_Type::T_DATE + CRM_Utils_Type::T_TIME,
        'title' => ts('Reset Date'),
        'description' => ts('When the address bounce status was last reset'),
        'usage' => [
          'import' => FALSE,
          'export' => FALSE,
          'duplicate_matching' => FALSE,
          'token' => FALSE,
        ],
        'where' => 'civicrm_email.reset_date',
        'table_name' => 'civicrm_email',
        'entity' => 'Email',
        'bao' => 'CRM_Core_BAO_Email',
        'localizable' => 0,
        'html' => [
          'type' => 'Select Date',
          'formatType' => 'activityDateTime',
          'label' => ts("Reset Date"),
        ],
        'add' => '1.1',
      ],
      'signature_text' => [
        'name' => 'signature_text',
        'type' => CRM_Utils_Type::T_TEXT,
        'title' => ts('Signature Text'),
        'description' => ts('Text formatted signature for the email.'),
        'usage' => [
          'import' => TRUE,
          'export' => TRUE,
          'duplicate_matching' => TRUE,
          'token' => FALSE,
        ],
        'import' => TRUE,
        'where' => 'civicrm_email.signature_text',
        'export' => TRUE,
        'default' => NULL,
        'table_name' => 'civicrm_email',
        'entity' => 'Email',
        'bao' => 'CRM_Core_BAO_Email',
        'localizable' => 0,
        'html' => [
          'label' => ts("Signature Text"),
        ],
        'add' => '3.2',
      ],
      'signature_html' => [
        'name' => 'signature_html',
        'type' => CRM_Utils_Type::T_TEXT,
        'title' => ts('Signature Html'),
        'description' => ts('HTML formatted signature for the email.'),
        'usage' => [
          'import' => TRUE,
          'export' => TRUE,
          'duplicate_matching' => TRUE,
          'token' => FALSE,
        ],
        'import' => TRUE,
        'where' => 'civicrm_email.signature_html',
        'export' => TRUE,
        'default' => NULL,
        'table_name' => 'civicrm_email',
        'entity' => 'Email',
        'bao' => 'CRM_Core_BAO_Email',
        'localizable' => 0,
        'html' => [
          'label' => ts("Signature HTML"),
        ],
        'add' => '3.2',
      ],
    ];
    $actual = CRM_Core_BAO_Email::fields();
    $this->assertEquals($expected, $actual);
  }

  /**
   * Super-brittle test added while refactoring DAOs to use CRM_Core_DAO_Base.
   * Probably should be dialed back or deleted once refactor is complete.
   */
  public function testImport(): void {
    $expected = [
      'email' => [
        'name' => 'email',
        'type' => CRM_Utils_Type::T_STRING,
        'title' => ts('Email'),
        'description' => ts('Email address'),
        'maxlength' => 254,
        'size' => 30,
        'usage' => [
          'import' => TRUE,
          'export' => TRUE,
          'duplicate_matching' => TRUE,
          'token' => FALSE,
        ],
        'import' => TRUE,
        'where' => 'civicrm_email.email',
        'headerPattern' => '/e.?mail/i',
        'dataPattern' => '/^[a-zA-Z][\w\.-]*[a-zA-Z0-9]@[a-zA-Z0-9][\w\.-]*[a-zA-Z0-9]\.[a-zA-Z][a-zA-Z\.]*[a-zA-Z]$/',
        'export' => TRUE,
        'rule' => 'email',
        'table_name' => 'civicrm_email',
        'entity' => 'Email',
        'bao' => 'CRM_Core_BAO_Email',
        'localizable' => 0,
        'html' => [
          'type' => 'Email',
        ],
        'add' => '1.1',
      ],
      'signature_text' => [
        'name' => 'signature_text',
        'type' => CRM_Utils_Type::T_TEXT,
        'title' => ts('Signature Text'),
        'description' => ts('Text formatted signature for the email.'),
        'usage' => [
          'import' => TRUE,
          'export' => TRUE,
          'duplicate_matching' => TRUE,
          'token' => FALSE,
        ],
        'import' => TRUE,
        'where' => 'civicrm_email.signature_text',
        'export' => TRUE,
        'default' => NULL,
        'table_name' => 'civicrm_email',
        'entity' => 'Email',
        'bao' => 'CRM_Core_BAO_Email',
        'localizable' => 0,
        'html' => [
          'label' => ts("Signature Text"),
        ],
        'add' => '3.2',
      ],
      'signature_html' => [
        'name' => 'signature_html',
        'type' => CRM_Utils_Type::T_TEXT,
        'title' => ts('Signature Html'),
        'description' => ts('HTML formatted signature for the email.'),
        'usage' => [
          'import' => TRUE,
          'export' => TRUE,
          'duplicate_matching' => TRUE,
          'token' => FALSE,
        ],
        'import' => TRUE,
        'where' => 'civicrm_email.signature_html',
        'export' => TRUE,
        'default' => NULL,
        'table_name' => 'civicrm_email',
        'entity' => 'Email',
        'bao' => 'CRM_Core_BAO_Email',
        'localizable' => 0,
        'html' => [
          'label' => ts("Signature HTML"),
        ],
        'add' => '3.2',
      ],
    ];
    $this->assertEquals($expected, CRM_Core_BAO_Email::import());
  }

  /**
   * Super-brittle test added while refactoring DAOs to use CRM_Core_DAO_Base.
   * Probably should be dialed back or deleted once refactor is complete.
   */
  public function testExport(): void {
    $expected = [
      'email' => [
        'name' => 'email',
        'type' => CRM_Utils_Type::T_STRING,
        'title' => ts('Email'),
        'description' => ts('Email address'),
        'maxlength' => 254,
        'size' => 30,
        'usage' => [
          'import' => TRUE,
          'export' => TRUE,
          'duplicate_matching' => TRUE,
          'token' => FALSE,
        ],
        'import' => TRUE,
        'where' => 'civicrm_email.email',
        'headerPattern' => '/e.?mail/i',
        'dataPattern' => '/^[a-zA-Z][\w\.-]*[a-zA-Z0-9]@[a-zA-Z0-9][\w\.-]*[a-zA-Z0-9]\.[a-zA-Z][a-zA-Z\.]*[a-zA-Z]$/',
        'export' => TRUE,
        'rule' => 'email',
        'table_name' => 'civicrm_email',
        'entity' => 'Email',
        'bao' => 'CRM_Core_BAO_Email',
        'localizable' => 0,
        'html' => [
          'type' => 'Email',
        ],
        'add' => '1.1',
      ],
      'signature_text' => [
        'name' => 'signature_text',
        'type' => CRM_Utils_Type::T_TEXT,
        'title' => ts('Signature Text'),
        'description' => ts('Text formatted signature for the email.'),
        'usage' => [
          'import' => TRUE,
          'export' => TRUE,
          'duplicate_matching' => TRUE,
          'token' => FALSE,
        ],
        'import' => TRUE,
        'where' => 'civicrm_email.signature_text',
        'export' => TRUE,
        'default' => NULL,
        'table_name' => 'civicrm_email',
        'entity' => 'Email',
        'bao' => 'CRM_Core_BAO_Email',
        'localizable' => 0,
        'html' => [
          'label' => ts("Signature Text"),
        ],
        'add' => '3.2',
      ],
      'signature_html' => [
        'name' => 'signature_html',
        'type' => CRM_Utils_Type::T_TEXT,
        'title' => ts('Signature Html'),
        'description' => ts('HTML formatted signature for the email.'),
        'usage' => [
          'import' => TRUE,
          'export' => TRUE,
          'duplicate_matching' => TRUE,
          'token' => FALSE,
        ],
        'import' => TRUE,
        'where' => 'civicrm_email.signature_html',
        'export' => TRUE,
        'default' => NULL,
        'table_name' => 'civicrm_email',
        'entity' => 'Email',
        'bao' => 'CRM_Core_BAO_Email',
        'localizable' => 0,
        'html' => [
          'label' => ts("Signature HTML"),
        ],
        'add' => '3.2',
      ],
      'on_hold' => [
        'name' => 'on_hold',
        'type' => CRM_Utils_Type::T_INT,
        'title' => ts('On Hold'),
        'description' => ts('Implicit FK to civicrm_option_value where option_group = email_on_hold.'),
        'required' => TRUE,
        'usage' => [
          'import' => FALSE,
          'export' => TRUE,
          'duplicate_matching' => FALSE,
          'token' => FALSE,
        ],
        'where' => 'civicrm_email.on_hold',
        'export' => TRUE,
        'default' => '0',
        'table_name' => 'civicrm_email',
        'entity' => 'Email',
        'bao' => 'CRM_Core_BAO_Email',
        'localizable' => 0,
        'html' => [
          'type' => 'Select',
        ],
        'pseudoconstant' => [
          'callback' => 'CRM_Core_PseudoConstant::emailOnHoldOptions',
        ],
        'add' => '1.1',
      ],
      'is_bulkmail' => [
        'name' => 'is_bulkmail',
        'type' => CRM_Utils_Type::T_BOOLEAN,
        'title' => ts('Use for Bulk Mail'),
        'description' => ts('Is this address for bulk mail ?'),
        'required' => TRUE,
        'usage' => [
          'import' => FALSE,
          'export' => TRUE,
          'duplicate_matching' => FALSE,
          'token' => FALSE,
        ],
        'where' => 'civicrm_email.is_bulkmail',
        'export' => TRUE,
        'default' => '0',
        'table_name' => 'civicrm_email',
        'entity' => 'Email',
        'bao' => 'CRM_Core_BAO_Email',
        'localizable' => 0,
        'add' => '1.9',
      ],
    ];
    $this->assertEquals($expected, CRM_Core_BAO_Email::export());
  }

}
