<?php

/**
 * Class CRM_ACL_BAO_ACLTest
 * @group headless
 */
class CRM_ACL_DAO_ACLTest extends CiviUnitTestCase {

  /**
   * Super-brittle test added while refactoring DAOs to use CRM_Core_DAO_Base.
   * Probably should be dialed back or deleted once refactor is complete.
   */
  public function testBasicInfo(): void {
    $this->assertEquals('civicrm_acl', CRM_ACL_DAO_ACL::getTableName());
    $this->assertEquals('civicrm', CRM_ACL_DAO_ACL::getExtensionName());
    $aclBao = new CRM_ACL_DAO_ACL();
    $this->assertFalse($aclBao->getLog());
  }

  /**
   * Super-brittle test added while refactoring DAOs to use CRM_Core_DAO_Base.
   * Probably should be dialed back or deleted once refactor is complete.
   */
  public function testIndices(): void {
    $expected = [
      'index_acl_id' => [
        'name' => 'index_acl_id',
        'field' => [
          0 => 'acl_id',
        ],
        'localizable' => FALSE,
        'sig' => 'civicrm_acl::0::acl_id',
      ],
    ];
    $this->assertEquals($expected, CRM_ACL_BAO_ACL::indices(FALSE));
  }

  /**
   * Super-brittle test added while refactoring DAOs to use CRM_Core_DAO_Base.
   * Probably should be dialed back or deleted once refactor is complete.
   */
  public function testLinks(): void {
    $expected = [
      'add' => 'civicrm/acl/edit?reset=1&action=add',
      'delete' => 'civicrm/acl/delete?reset=1&action=delete&id=[id]',
      'update' => 'civicrm/acl/edit?reset=1&action=edit&id=[id]',
      'browse' => 'civicrm/acl',
    ];
    $actual = CRM_ACL_BAO_ACL::getEntityPaths();
    $this->assertEquals($expected, $actual);
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
        'title' => ts('ACL ID'),
        'description' => ts('Unique table ID'),
        'required' => TRUE,
        'usage' => [
          'import' => FALSE,
          'export' => FALSE,
          'duplicate_matching' => FALSE,
          'token' => FALSE,
        ],
        'where' => 'civicrm_acl.id',
        'table_name' => 'civicrm_acl',
        'entity' => 'ACL',
        'bao' => 'CRM_ACL_BAO_ACL',
        'localizable' => 0,
        'html' => [
          'type' => 'Number',
        ],
        'readonly' => TRUE,
        'add' => '1.6',
      ],
      'name' => [
        'name' => 'name',
        'type' => CRM_Utils_Type::T_STRING,
        'title' => ts('ACL Name'),
        'description' => ts('ACL Name.'),
        'maxlength' => 64,
        'size' => CRM_Utils_Type::BIG,
        'usage' => [
          'import' => FALSE,
          'export' => FALSE,
          'duplicate_matching' => FALSE,
          'token' => FALSE,
        ],
        'where' => 'civicrm_acl.name',
        'table_name' => 'civicrm_acl',
        'entity' => 'ACL',
        'bao' => 'CRM_ACL_BAO_ACL',
        'localizable' => 0,
        'html' => [
          'type' => 'Text',
        ],
        'add' => '1.6',
      ],
      'deny' => [
        'name' => 'deny',
        'type' => CRM_Utils_Type::T_BOOLEAN,
        'title' => ts('Deny ACL?'),
        'description' => ts('Is this ACL entry Allow  (0) or Deny (1) ?'),
        'required' => TRUE,
        'usage' => [
          'import' => FALSE,
          'export' => FALSE,
          'duplicate_matching' => FALSE,
          'token' => FALSE,
        ],
        'where' => 'civicrm_acl.deny',
        'default' => '0',
        'table_name' => 'civicrm_acl',
        'entity' => 'ACL',
        'bao' => 'CRM_ACL_BAO_ACL',
        'localizable' => 0,
        'html' => [
          'type' => 'Radio',
        ],
        'add' => '1.6',
      ],
      'entity_table' => [
        'name' => 'entity_table',
        'type' => CRM_Utils_Type::T_STRING,
        'title' => ts('ACL Entity'),
        'description' => ts('Table of the object possessing this ACL entry (Contact, Group, or ACL Group)'),
        'required' => TRUE,
        'maxlength' => 64,
        'size' => CRM_Utils_Type::BIG,
        'usage' => [
          'import' => FALSE,
          'export' => FALSE,
          'duplicate_matching' => FALSE,
          'token' => FALSE,
        ],
        'where' => 'civicrm_acl.entity_table',
        'table_name' => 'civicrm_acl',
        'entity' => 'ACL',
        'bao' => 'CRM_ACL_BAO_ACL',
        'localizable' => 0,
        'add' => '1.6',
      ],
      'entity_id' => [
        'name' => 'entity_id',
        'type' => CRM_Utils_Type::T_INT,
        'title' => ts('Entity ID'),
        'description' => ts('ID of the object possessing this ACL'),
        'usage' => [
          'import' => FALSE,
          'export' => FALSE,
          'duplicate_matching' => FALSE,
          'token' => FALSE,
        ],
        'where' => 'civicrm_acl.entity_id',
        'table_name' => 'civicrm_acl',
        'entity' => 'ACL',
        'bao' => 'CRM_ACL_BAO_ACL',
        'localizable' => 0,
        'DFKEntityColumn' => 'entity_table',
        'FKColumnName' => 'id',
        'pseudoconstant' => [
          'optionGroupName' => 'acl_role',
          'optionEditPath' => 'civicrm/admin/options/acl_role',
        ],
        'add' => '1.6',
      ],
      'operation' => [
        'name' => 'operation',
        'type' => CRM_Utils_Type::T_STRING,
        'title' => ts('ACL Operation'),
        'description' => ts('What operation does this ACL entry control?'),
        'required' => TRUE,
        'maxlength' => 8,
        'size' => CRM_Utils_Type::EIGHT,
        'usage' => [
          'import' => FALSE,
          'export' => FALSE,
          'duplicate_matching' => FALSE,
          'token' => FALSE,
        ],
        'where' => 'civicrm_acl.operation',
        'table_name' => 'civicrm_acl',
        'entity' => 'ACL',
        'bao' => 'CRM_ACL_BAO_ACL',
        'localizable' => 0,
        'html' => [
          'type' => 'Select',
        ],
        'pseudoconstant' => [
          'callback' => 'CRM_ACL_BAO_ACL::operation',
        ],
        'add' => '1.6',
      ],
      'object_table' => [
        'name' => 'object_table',
        'type' => CRM_Utils_Type::T_STRING,
        'title' => ts('ACL Object'),
        'description' => ts('The table of the object controlled by this ACL entry'),
        'maxlength' => 64,
        'size' => CRM_Utils_Type::BIG,
        'usage' => [
          'import' => FALSE,
          'export' => FALSE,
          'duplicate_matching' => FALSE,
          'token' => FALSE,
        ],
        'where' => 'civicrm_acl.object_table',
        'table_name' => 'civicrm_acl',
        'entity' => 'ACL',
        'bao' => 'CRM_ACL_BAO_ACL',
        'localizable' => 0,
        'html' => [
          'type' => 'Select',
          'label' => ts("Type of Data"),
        ],
        'pseudoconstant' => [
          'callback' => 'CRM_ACL_BAO_ACL::getObjectTableOptions',
        ],
        'add' => '1.6',
      ],
      'object_id' => [
        'name' => 'object_id',
        'type' => CRM_Utils_Type::T_INT,
        'title' => ts('ACL Object ID'),
        'description' => ts('The ID of the object controlled by this ACL entry'),
        'usage' => [
          'import' => FALSE,
          'export' => FALSE,
          'duplicate_matching' => FALSE,
          'token' => FALSE,
        ],
        'where' => 'civicrm_acl.object_id',
        'table_name' => 'civicrm_acl',
        'entity' => 'ACL',
        'bao' => 'CRM_ACL_BAO_ACL',
        'localizable' => 0,
        'html' => [
          'type' => 'Select',
          'label' => ts("Which Data"),
          'controlField' => 'object_table',
        ],
        'pseudoconstant' => [
          'callback' => 'CRM_ACL_BAO_ACL::getObjectIdOptions',
          'prefetch' => 'disabled',
        ],
        'add' => '1.6',
      ],
      'acl_table' => [
        'name' => 'acl_table',
        'type' => CRM_Utils_Type::T_STRING,
        'title' => ts('ACL Table'),
        'description' => ts('If this is a grant/revoke entry, what table are we granting?'),
        'maxlength' => 64,
        'size' => CRM_Utils_Type::BIG,
        'usage' => [
          'import' => FALSE,
          'export' => FALSE,
          'duplicate_matching' => FALSE,
          'token' => FALSE,
        ],
        'where' => 'civicrm_acl.acl_table',
        'table_name' => 'civicrm_acl',
        'entity' => 'ACL',
        'bao' => 'CRM_ACL_BAO_ACL',
        'localizable' => 0,
        'add' => '1.6',
      ],
      'acl_id' => [
        'name' => 'acl_id',
        'type' => CRM_Utils_Type::T_INT,
        'title' => ts('ACL Group ID'),
        'description' => ts('ID of the ACL or ACL group being granted/revoked'),
        'usage' => [
          'import' => FALSE,
          'export' => FALSE,
          'duplicate_matching' => FALSE,
          'token' => FALSE,
        ],
        'where' => 'civicrm_acl.acl_id',
        'table_name' => 'civicrm_acl',
        'entity' => 'ACL',
        'bao' => 'CRM_ACL_BAO_ACL',
        'localizable' => 0,
        'add' => '1.6',
      ],
      'is_active' => [
        'name' => 'is_active',
        'type' => CRM_Utils_Type::T_BOOLEAN,
        'title' => ts('ACL Is Active?'),
        'description' => ts('Is this property active?'),
        'required' => TRUE,
        'usage' => [
          'import' => FALSE,
          'export' => FALSE,
          'duplicate_matching' => FALSE,
          'token' => FALSE,
        ],
        'where' => 'civicrm_acl.is_active',
        'default' => '1',
        'table_name' => 'civicrm_acl',
        'entity' => 'ACL',
        'bao' => 'CRM_ACL_BAO_ACL',
        'localizable' => 0,
        'html' => [
          'type' => 'CheckBox',
          'label' => ts("Enabled"),
        ],
        'add' => '1.6',
      ],
      'priority' => [
        'name' => 'priority',
        'type' => CRM_Utils_Type::T_INT,
        'title' => ts('Priority'),
        'required' => TRUE,
        'usage' => [
          'import' => FALSE,
          'export' => FALSE,
          'duplicate_matching' => FALSE,
          'token' => FALSE,
        ],
        'where' => 'civicrm_acl.priority',
        'default' => '0',
        'table_name' => 'civicrm_acl',
        'entity' => 'ACL',
        'bao' => 'CRM_ACL_BAO_ACL',
        'localizable' => 0,
        'html' => [
          'type' => 'Number',
        ],
        'add' => '5.64',
      ],
    ];
    $actual = CRM_ACL_BAO_ACL::fields();
    $this->assertEquals($expected, $actual);
  }

  /**
   * Super-brittle test added while refactoring DAOs to use CRM_Core_DAO_Base.
   * Probably should be dialed back or deleted once refactor is complete.
   */
  public function testImport(): void {
    $expected = [];
    $this->assertEquals($expected, CRM_ACL_BAO_ACL::import());
  }

  /**
   * Super-brittle test added while refactoring DAOs to use CRM_Core_DAO_Base.
   * Probably should be dialed back or deleted once refactor is complete.
   */
  public function testExport(): void {
    $expected = [];
    $this->assertEquals($expected, CRM_ACL_BAO_ACL::export());
  }

}
