<?php

namespace CiviMix\Schema;

/**
 * The SchemaHelperInterface provides utility methods for managing the schema
 * in an extension (e.g. installing or uninstalling all SQL tables).
 *
 * The interface is implemented by the reloadable library (civimix-schema@5). To ensure
 * newer revisions of the library can be loaded, the implementation is an anonymous-class,
 * and the interface uses soft type-hints.
 *
 * [[ CiviCRM 5.74+ / civimix-schema@5.74+ ]]
 *
 * @method bool hasSchema()
 * @method void install()
 * @method void uninstall()
 * @method string generateInstallSql()
 * @method string generateUninstallSql()
 *
 * [[ CiviCRM 5.76+ / civimix-schema@5.76+ ]]
 *
 * @method string arrayToSql(array $defn) Converts an entity or field definition to SQL statement.
 *
 * [[ CiviCRM 6.2+ / civimix-schema@5.85+ ]]
 *
 * @method bool createEntityTable(string $filePath)
 * @method bool alterSchemaField(string $entityName, string $fieldName, array $fieldSpec, ?string $position = NULL)
 *
 * [[ CiviCRM 6.10+ / civimix-schema@5.93+ ]]
 *
 * @method bool schemaFieldExists(string $entityName, string $fieldName)
 * @method bool dropSchemaField(string $entityName, string $fieldName)
 * @method string|null getTableName(string $entityName)
 * @method bool tableExists(string $tableName)
 * @method bool dropTable(string $tableName)
 *
 * To see the latest implementation:
 *
 * @see ./SchemaHelper.php
 */
interface SchemaHelperInterface {

}
