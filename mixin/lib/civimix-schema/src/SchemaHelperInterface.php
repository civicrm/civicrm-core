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
 * @method bool hasSchema()
 *
 * @method void install()
 * @method void uninstall()
 *
 * @method string generateInstallSql()
 * @method string generateUninstallSql()
 *
 * TODO: void addTables(string[] $tables)
 * TODO: void addColumn(string $table, string $column)
 *
 * To see the latest implementation:
 *
 * @see ./SchemaHelper.php
 */
interface SchemaHelperInterface {

}
