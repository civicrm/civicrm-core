<?php

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\ClassMetadataFactory;
use Doctrine\ORM\Query\ResultSetMapping;
use Doctrine\DBAL\Types\Type as DoctrineType;

/**
 * Read the schema specification and parse into internal data structures
 */

class CRM_Core_CodeGen_FakeConnection extends Doctrine\DBAL\Connection
{
  public function __construct()
  {
  }

  public function getDatabase()
  {
    return 'civicrm';
  }

  public function getDatabasePlatform()
  { 
    return new \Doctrine\DBAL\Platforms\MySqlPlatform();
  }

  public function getSchemaManager()
  {
    return new \Doctrine\DBAL\Schema\MySqlSchemaManager($this);
  }
}

class CRM_Core_CodeGen_EntityManagerWithoutConnection extends \Doctrine\ORM\EntityManager
{
  private $config;
  public $metadataFactory;

  public function __construct($config)
  {
    $this->evm = new \Doctrine\Common\EventManager();
    $this->config = $config;
  }

  public function getClassMetadata($className)
  {
    return $this->metadataFactory->getMetadataFor($className);
  }

  public function getConfiguration()
  {
    return $this->config;
  }

  public function getConnection()
  {
    return new CRM_Core_CodeGen_FakeConnection();
  } 

  public function getEventManager()
  { 
    return $this->evm;
  }
}

class CRM_Core_CodeGen_Doctrine {

  public static $doctrine_types_to_crm_type_str = array(
    DoctrineType::BOOLEAN => 'CRM_Utils_Type::T_BOOLEAN',
    DoctrineType::BLOB => 'CRM_Utils_Type::T_MEDIUMBLOB',
    DoctrineType::DATE => 'CRM_Utils_Type::T_DATE',
    DoctrineType::DATETIME => 'CRM_Utils_Type::T_DATE + CRM_Utils_Type::T_TIME',
    DoctrineType::DECIMAL => 'CRM_Utils_Type::T_MONEY',
    DoctrineType::FLOAT => 'CRM_Utils_Type::T_FLOAT',
    DoctrineType::INTEGER => 'CRM_Utils_Type::T_INT',
    DoctrineType::STRING => 'CRM_Utils_Type::T_STRING',
    DoctrineType::TEXT => 'CRM_Utils_Type::T_TEXT',
  );

  public static $doctrine_types_to_php_type_str = array(
    DoctrineType::BOOLEAN => 'boolean',
    DoctrineType::BLOB => 'mediumblob',
    DoctrineType::DATE => 'datetime',
    DoctrineType::DATETIME => 'datetime',
    DoctrineType::DECIMAL => 'float',
    DoctrineType::FLOAT => 'float',
    DoctrineType::INTEGER => 'int unsigned',
    DoctrineType::STRING => 'string',
    DoctrineType::TEXT => 'text',
  );

  public $dao_metadata = array();
  public $entity_manager;
  public $metadata; 

  public function buildDAOFieldsForClassMetadata($class_metadata)
  {
    $fields = array();
    foreach ($class_metadata->fieldMappings as $field_mapping) {
      $field = array(
        'cols' => NULL, /* XXX */
        'comment' => '', /* XXX */
        'crmType' => $this->crmTypeForDoctrineType($field_mapping['type']),
        'export' => NULL, /* XXX */
        'import' => NULL, /* XXX */
        'length' => $field_mapping['length'],
        'localizable' => NULL, /* XXX */
        'name' => $field_mapping['columnName'],
        'phpType' => $this->phpTypeForDoctrineType($field_mapping['type']),
        'pseudoconstant' => NULL, /* XXX */
        'required' => $field_mapping['nullable'] ? NULL : 'true',
        'rows' => NULL, /* XXX */
        'rule' => NULL, /* XXX */
        'title' => NULL, /* XXX */
        'uniqueName' => NULL, /* XXX */
      );
      if ($field_mapping['type'] == DoctrineType::STRING || $field_mapping['type'] == DoctrineType::STRING) {
        $field['size'] = $this->crmSizeForDoctrineLength($field_mapping['length']);
      }
      $fields[] = $field;
    }
    return $fields;
  }

  public function buildDAOFieldsForClassMetadataAssociations($class_metadata)
  {
    $fields = array();
    foreach ($class_metadata->associationMappings as $association_mapping) {
      if ($association_mapping['type'] != ClassMetadata::MANY_TO_ONE) {
        continue;
      }
      $join_info = $association_mapping['joinColumns'][0];
      $field = array(
        'crmType' => 'CRM_Utils_Type::T_INT',
        'FKClassName' => $this->entityClassNametoDAOClassName($association_mapping['targetEntity']),
        'localizable' => NULL, /* XXX */
        'name' => $join_info['name'],
        'phpType' => 'unsigned int',
        'required' => $join_info['nullable'] ? NULL : 'true',
      );
      $fields[] = $field;
    }
    return $fields;
  }

  public function buildDAOForeignKeyForClassMetadata($class_metadata)
  {
    $fields = array();
    foreach ($class_metadata->associationMappings as $association_mapping) {
      if ($association_mapping['type'] != ClassMetadata::MANY_TO_ONE) {
        continue;
      }
      $join_info = $association_mapping['joinColumns'][0];
      $field = array(
        'key' => $join_info['referencedColumnName'],
        'name' => $join_info['name'],
        'table' => $this->tableNameFor($association_mapping['targetEntity']),
      );
      $fields[] = $field;
    }
    if (empty($fields)) {
      return NULL;
    } else {
      return $fields;
    }
  }

  public function buildDAOTableForClassMetadata($class_metadata)
  {
    $table = array();
    $table['sourceFile'] = implode(DIRECTORY_SEPARATOR, explode('\\', $class_metadata->name)) . ".php";
    $name_parts = $this->entityClassNameToDAONameParts($class_metadata->name);
    $entity_class_name = end($name_parts);
    $table['daoDir'] = implode(DIRECTORY_SEPARATOR, array_slice($name_parts, 0, -1));
    $table['daoFileName'] = "$entity_class_name.php";
    $table['className'] = implode('_', $name_parts);
    $table['name'] = $class_metadata->getTableName();
    $table['dynamicForeignKey'] = array(); /* XXX */
    $table['log'] = 'false'; /* XXX */
    $table['localizable'] = TRUE; /* XXX */
    $table['labelName'] = preg_replace("/^civicrm_/", '', $table['name']);
    $table['fields'] = $this->buildDAOFieldsForClassMetadata($class_metadata);
    $table['fields'] = array_merge($table['fields'], $this->buildDAOFieldsForClassMetadataAssociations($class_metadata));
    $table['foreignKey'] = $this->buildDAOForeignKeyForClassMetadata($class_metadata);
    return $table;
  }

  function buildDAOMetadata()
  {
    foreach ($this->metadata as $class_metadata) {
      $table = $this->buildDAOTableForClassMetadata($class_metadata);
      $this->dao_metadata[] = $table;
    }
  }

  function crmSizeForDoctrineLength($doctrine_length) {
    // This map is slightly different from CRM_Core_Form_Renderer::$_sizeMapper
    // Because we usually want fields to render as smaller than their maxlength
    $sizes = array(
      2 => 'TWO',
      4 => 'FOUR',
      6 => 'SIX',
      8 => 'EIGHT',
      16 => 'TWELVE',
      32 => 'MEDIUM',
      64 => 'BIG',
    );
    foreach ($sizes as $length => $name) {
      if ($doctrine_length <= $length) {
        return "CRM_Utils_Type::$name";
      }
    }
    return 'CRM_Utils_Type::HUGE';
  }

  function crmTypeForDoctrineType($doctrine_type) {
    if (array_key_exists($doctrine_type, self::$doctrine_types_to_crm_type_str)) {
      return self::$doctrine_types_to_crm_type_str[$doctrine_type];
    } else {
      throw new Exception("Unable to find a crmType for doctrine type '$doctrine_type'.");
    }
  }

  function entityClassNameToDAOClassName($entity_class_name)
  {
    return implode('_', $this->entityClassNameToDAONameParts($entity_class_name));
  }

  function entityClassNameToDAONameParts($entity_class_name)
  {
    $name_parts = explode('\\', $entity_class_name);
    if ($name_parts[1] == 'CCase') {
      $name_parts[1] = 'Case';
    }
    if ($name_parts[count($name_parts)-1] == 'CCase') {
      $name_parts[count($name_parts)-1] = 'Case';
    }
    $name_parts[0] = 'CRM';
    array_splice($name_parts, -1, 0, 'DAO');
    return $name_parts;
  }

  function load()
  {
    $container = \Civi\Core\Container::singleton();
    $config = $container->get('doctrine_configuration');
    $this->entity_manager = new CRM_Core_CodeGen_EntityManagerWithoutConnection($config);
    $class_metadata_factory = new ClassMetadataFactory();
    $class_metadata_factory->setEntityManager($this->entity_manager);
    $this->entity_manager->metadataFactory = $class_metadata_factory;
    $this->metadata = $class_metadata_factory->getAllMetadata();
    $this->buildDAOMetadata();
  }

  function phpTypeForDoctrineType($doctrine_type) {
    if (array_key_exists($doctrine_type, self::$doctrine_types_to_php_type_str)) {
      return self::$doctrine_types_to_php_type_str[$doctrine_type];
    } else {
      throw new Exception("Unable to find a phpType for doctrine type '$doctrine_type'.");
    }
  }


  function tableNameFor($entity_name)
  {
    foreach ($this->metadata as $class_metadata) {
      if ($class_metadata->name == $entity_name)
      {
        return $class_metadata->getTableName();
      }
    }
    throw new Exception("Unable to find table name for entity $entity_name.\n");
  }
}
