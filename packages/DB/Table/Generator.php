<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * DB_Table_Generator - Generates DB_Table subclass skeleton code
 * 
 * Parts of this class were adopted from the DB_DataObject PEAR package.
 * 
 * PHP versions 4 and 5
 *
 * LICENSE:
 * 
 * Copyright (c) 1997-2007, Paul M. Jones <pmjones@php.net>
 *                          Alan Knowles <alan@akbkhome.com>
 *                          David C. Morse <morse@php.net>
 *                          Mark Wiesemann <wiesemann@php.net>
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions
 * are met:
 *
 *    * Redistributions of source code must retain the above copyright
 *      notice, this list of conditions and the following disclaimer.
 *    * Redistributions in binary form must reproduce the above copyright
 *      notice, this list of conditions and the following disclaimer in the 
 *      documentation and/or other materials provided with the distribution.
 *    * The names of the authors may not be used to endorse or promote products 
 *      derived from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS
 * IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO,
 * THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR
 * PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR
 * CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL,
 * EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO,
 * PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR
 * PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY
 * OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING
 * NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * @category Database
 * @package  DB_Table
 * @author   Alan Knowles <alan@akbkhome.com> 
 * @author   David C. Morse <morse@php.net>
 * @license  http://opensource.org/licenses/bsd-license.php New BSD License
 * @version  CVS: $Id: Generator.php,v 1.17 2008/05/14 18:36:27 wiesemann Exp $
 * @link     http://pear.php.net/package/DB_Table
 */

// {{{ Includes

/**#@+
 * Include basic classes
 */
/**
 * The PEAR class (used for errors)
 */
require_once 'PEAR.php';

/**
 * DB_Table table abstraction class
 */
require_once 'DB/Table.php';

/**
 * DB_Table_Manager class (used to reverse engineer indices)
 */
require_once 'DB/Table/Manager.php';
/**#@-*/

// }}}
// {{{ Error code constants

/**#@+
 * Error codes
 */
/**
 * Parameter is not a DB/MDB2 object
 */
define('DB_TABLE_GENERATOR_ERR_DB_OBJECT', -301);

/**
 * Parameter is not a DB/MDB2 object
 */
define('DB_TABLE_GENERATOR_ERR_INDEX_COL', -302);

/**
 * Error while creating file/directory
 */
define('DB_TABLE_GENERATOR_ERR_FILE', -303);
/**#@-*/

// }}}
// {{{ Error messages
/**
 * US-English default error messages.
 */
$GLOBALS['_DB_TABLE_GENERATOR']['default_error'] = array(
        DB_TABLE_GENERATOR_ERR_DB_OBJECT =>
            'Invalid DB/MDB2 object parameter. Function',
        DB_TABLE_GENERATOR_ERR_INDEX_COL =>
            'Index column is not a valid column name. Index column',
        DB_TABLE_GENERATOR_ERR_FILE =>
            'Can\'t create file/directory:'
);

// merge default and user-defined error messages
if (!isset($GLOBALS['_DB_TABLE_GENERATOR']['error'])) {
    $GLOBALS['_DB_TABLE_GENERATOR']['error'] = array();
}
foreach ($GLOBALS['_DB_TABLE_GENERATOR']['default_error'] as $code => $message) {
    if (!array_key_exists($code, $GLOBALS['_DB_TABLE_GENERATOR']['error'])) {
        $GLOBALS['_DB_TABLE_GENERATOR']['error'][$code] = $message;
    }
}

// }}}
// {{{ class DB_Table_Generator

/**
 * class DB_Table_Generator - Generates DB_Table subclass skeleton code
 *
 * This class generates the php code necessary to use the DB_Table
 * package to interact with an existing database. This requires the
 * generation of a skeleton subclass definition be generated for each
 * table in the database, in which the $col, $idx, and $auto_inc_col
 * properties are constructed using a table schema that is obtained
 * by querying the database.
 *
 * The class can also generate a file, named 'Database.php' by default,
 * that includes (require_once) each of the table subclass definitions,
 * instantiates one object of each DB_Table subclass (i.e., one object
 * for each table), instantiates a parent DB_Table_Database object,
 * adds all the tables to that parent, attempts to guess foreign key
 * relationships between tables based on the column names, and adds
 * the inferred references to the parent object.
 *
 * All of the code is written to a directory whose path is given by
 * the property $class_write_path. By default, this is the current
 * directory.  By default, the name of the class constructed for a
 * table named 'thing' is "Thing_Table". That is, the class name is
 * the table name, with the first letter upper case, with a suffix
 * '_Table'.  This suffix can be changed by setting the $class_suffix
 * property. The file containing a subclass definition is the
 * subclass name with a php extension, e.g., 'Thing_Table.php'. The
 * object instantiated from that subclass is the same as the table
 * name, with no suffix, e.g., 'thing'.
 *
 * To generate the code for all of the tables in a database named
 * $database, instantiate a MDB2 or DB object named $db that connects
 * to the database of interest, and execute the following code:
 * <code>
 *     $generator = new DB_Table_Generator($db, $database);
 *     $generator->class_write_path = $class_write_path;
 *     $generator->generateTableClassFiles();
 *     $generator->generateDatabaseFile();
 * </code>
 * Here $class_write_path should be the path (without a trailing
 * separator) to a directory in which all of the code should be
 * written. If this directory does not exist, it will be created.
 * If the directory does already exist, exising files will not
 * be overwritten. If $class_write_path is not set (i.e., if this
 * line is omitted) all the code will be written to the current
 * directory.  If ->generateDatabaseFile() is called, it must be
 * called after ->generateTableClassFiles().
 *
 * By default, ->generateTableClassFiles() and ->generateDatabaseFiles()
 * generate code for all of the tables in the current database. To
 * generate code for a specified list of tables, set the value of the
 * public $tables property to a sequential list of table names before
 * calling either of these methods. Code can be generated for three
 * tables named 'table1', 'table2', and 'table3' as follows:
 * <code>
 *     $generator = new DB_Table_Generator($db, $database);
 *     $generator->class_write_path = $class_write_path;
 *     $generator->tables = array('table1', 'table2', 'table3');
 *     $generator->generateTableClassFiles();
 *     $generator->generateDatabaseFile();
 * </code>
 * If the $tables property is not set to a non-null value prior
 * to calling ->generateTableClassFiles() then, by default, the
 * database is queried for a list of all table names, by calling the
 * ->getTableNames() method from within ->generateTableClassFiles().
 *
 * PHP version 4 and 5
 *
 * @category Database
 * @package  DB_Table
 * @author   Alan Knowles <alan@akbkhome.com> 
 * @author   David C. Morse <morse@php.net>
 * @license  http://www.gnu.org/copyleft/lesser.html LGPL
 * @version  Release: 1.5.6
 * @link     http://pear.php.net/package/DB_Table
 */
class DB_Table_Generator
{

    // {{{ Properties

    /**
     * Name of the database
     *
     * @var string
     * @access public
     */
    var $name = null;

    /**
     * The PEAR DB/MDB2 object that connects to the database.
     *
     * @var object
     * @access private
     */
    var $db = null;

    /**
     * The backend type. May have values 'db' or 'mdb2'
     *
     * @var string
     * @access private
     */
    var $backend = null;

    /**
    * If there is an error on instantiation, this captures that error.
    *
    * This property is used only for errors encountered in the constructor
    * at instantiation time.  To check if there was an instantiation error...
    *
    * <code>
    *     $obj =& new DB_Table_Generator();
    *     if ($obj->error) {
    *         // ... error handling code here ...
    *     }
    * </code>
    *
    * @var object PEAR_Error
    * @access public
    */
    var $error = null;

    /**
     * Numerical array of table name strings
     *
     * @var array
     * @access public
     */
    var $tables = array();

    /**
     * Class being extended (DB_Table or generic subclass)
     *
     * @var string
     * @access public
     */
    var $extends = 'DB_Table';

    /**
     * Path to definition of the class $this->extends
     *
     * @var string
     * @access public
     */
    var $extends_file = 'DB/Table.php';

    /**
     * Suffix to add to table names to obtain corresponding class names
     *
     * @var string
     * @access public
     */
    var $class_suffix = "_Table";

    /**
     * Path to directory in which subclass definitions should be written
     *
     * Value should not include a trailing "/".
     *
     * @var string
     * @access public
     */
    var $class_write_path = '';

    /**
     * Include path to subclass definition files from database file
     *
     * Used to create require_once statements in the Database.php file,
     * which is in the same directory as the class definition files. Leave
     * as empty string if your PHP include_path contains ".". The value
     * should not include a trailing "/", which is added automatically
     * to values other than the empty string.
     *
     * @var string
     * @access public
     */
    var $class_include_path = '';

    /**
     * Array of column definitions
     *
     * Array $this->col[table_name][column_name] = column definition.
     * Column definition is an array with the same format as the $col
     * property of a DB_Table object
     *
     * @var array
     * @access public
     */
    var $col = array();

    /**
     * Array of index/constraint definitions.
     *
     * Array $this->idx[table_table][index_name] = Index definition.
     * The index definition is an array with the same format as the
     * DB_Table $idx property property array.
     *
     * @var array
     * @access public
     */
     var $idx = array();

    /**
     * Array of auto_increment column names
     *
     * Array $this->auto_inc_col[table_name] = auto-increment column
     *
     * @var array
     * @access public
     */
     var $auto_inc_col = array();

    /**
     * Array of primary keys
     *
     * @var array
     * @access public
     */
     var $primary_key = array();

    /**
     * MDB2 'idxname_format' option, format of index names
     *
     * For use in printf() formatting. Use '%s' to use index names as
     * returned by getTableConstraints/Indexes, and '%s_idx' to add an
     * '_idx' suffix. For MySQL, use the default value '%'.
     *
     * @var string
     * @access public
     */
    var $idxname_format = '%s';

    // }}}
    // {{{ function DB_Table_Generator(&$db, $name)

    /**
     * Constructor
     *
     * If an error is encountered during instantiation, the error
     * message is stored in the $this->error property of the resulting
     * object. See $error property docblock for a discussion of error
     * handling.
     *
     * @param object &$db  DB/MDB2 database connection object
     * @param string $name database name string
     *
     * @return object DB_Table_Generator
     * @access public
     */
    function __construct(&$db, $name)
    {
        // Is $db an DB/MDB2 object or null?
        if (is_a($db, 'db_common')) {
            $this->backend = 'db';
        } elseif (is_a($db, 'mdb2_driver_common')) {
            $this->backend = 'mdb2';
        } else {
            $this->error =&
                DB_Table_Generator::throwError(DB_TABLE_GENERATOR_ERR_DB_OBJECT,
                'DB_Table_Generator');
            return;
        }
        $this->db   =& $db;
        $this->name =  $name;

    }

    // }}}
    // {{{ function &throwError($code, $extra = null)

    /**
     * Specialized version of throwError() modeled on PEAR_Error.
     *
     * Throws a PEAR_Error with a DB_Table_Generator error message based
     * on a DB_Table_Generator constant error code.
     *
     * @param string $code  A DB_Table_Generator error code constant.
     * @param string $extra Extra text for the error (in addition to the
     *                       regular error message).
     *
     * @return object PEAR_Error
     * @access public
     * @static
     */
    function &throwError($code, $extra = null)
    {
        // get the error message text based on the error code
        $text = 'DB_TABLE_GENERATOR ERROR - ' . "\n"
              . $GLOBALS['_DB_TABLE_GENERATOR']['error'][$code];

        // add any additional error text
        if ($extra) {
            $text .= ' ' . $extra;
        }

        // done!
        $error = PEAR::throwError($text, $code);
        return $error;
    }

    // }}}
    // {{{ function setErrorMessage($code, $message = null)

    /**
     * Overwrites one or more error messages, e.g., to internationalize them.
     *
     * @param mixed  $code    If string, the error message with code $code will be
     *                        overwritten by $message. If array, each key is a
     *                        code and each value is a new message.
     * @param string $message Only used if $key is not an array.
     *
     * @return void
     * @access public
     */
    function setErrorMessage($code, $message = null)
    {
        if (is_array($code)) {
            foreach ($code as $single_code => $single_message) {
                $GLOBALS['_DB_TABLE_GENERATOR']['error'][$single_code]
                    = $single_message;
            }
        } else {
            $GLOBALS['_DB_TABLE_GENERATOR']['error'][$code] = $message;
        }
    }

    // }}}
    // {{{ function getTableNames()

    /**
     * Gets a list of tables from the database
     *
     * Upon successful completion, names are stored in the $this->tables
     * array. If an error is encountered, a PEAR Error is returned, and
     * $this->tables is reset to null.
     *
     * @return mixed true on success, PEAR Error on failure
     * @access public
     */
    function getTableNames()
    {

        if ($this->backend == 'db') {
            // try getting a list of schema tables first. (postgres)
            $this->db->expectError(DB_ERROR_UNSUPPORTED);
            $this->tables = $this->db->getListOf('schema.tables');
            $this->db->popExpect();
            if (PEAR::isError($this->tables)) {
                // try a list of tables, not qualified by 'schema'
                $this->db->expectError(DB_ERROR_UNSUPPORTED);
                $this->tables = $this->db->getListOf('tables');
                $this->db->popExpect();
            }
        } else {
            // Temporarily change 'portability' MDB2 option
            $portability = $this->db->getOption('portability');
            $this->db->setOption('portability',
                MDB2_PORTABILITY_ALL ^ MDB2_PORTABILITY_FIX_CASE);

            $this->db->loadModule('Manager');
            $this->db->loadModule('Reverse');

            // Get list of tables
            $this->tables = $this->db->manager->listTables();

            // Restore original MDB2 'portability'
            $this->db->setOption('portability', $portability);
        }
        if (PEAR::isError($this->tables)) {
            $error        = $this->tables;
            $this->tables = null;
            return $error;
        } else {
            $this->tables = array_map(array($this, 'tableName'),
                                      $this->tables);
            return true;
        }
    }

    // }}}
    // {{{ function getTableDefinition($table)

    /**
     * Gets column and index definitions by querying database
     *
     * Upon return, column definitions are stored in $this->col[$table],
     * and index definitions in $this->idx[$table].
     *
     * Calls DB/MDB2::tableInfo() for column definitions, and uses
     * the DB_Table_Manager class to obtain index definitions.
     *
     * @param string $table name of table
     *
     * @return mixed true on success, PEAR Error on failure
     * @access public
     */
    function getTableDefinition($table)
    {
        /*
        // postgres strip the schema bit from the
        if (!empty($options['generator_strip_schema'])) {
            $bits = explode('.', $table,2);
            $table = $bits[0];
            if (count($bits) > 1) {
                $table = $bits[1];
            }
        }
        */

        if ($this->backend == 'db') {

            $defs = $this->db->tableInfo($table);
            if (PEAR::isError($defs)) {
                return $defs;
            }
            $this->columns[$table] = $defs;

        } else {

            // Temporarily change 'portability' MDB2 option
            $portability = $this->db->getOption('portability');
            $this->db->setOption('portability',
                MDB2_PORTABILITY_ALL ^ MDB2_PORTABILITY_FIX_CASE);

            $this->db->loadModule('Manager');
            $this->db->loadModule('Reverse');

            // Columns
            $defs = $this->db->reverse->tableInfo($table);
            if (PEAR::isError($defs)) {
                return $defs;
            }

            // rename the 'length' key, so it matches db's return.
            foreach ($defs as $k => $v) {
                if (isset($defs[$k]['length'])) {
                    $defs[$k]['len'] = $defs[$k]['length'];
                }
            }

            $this->columns[$table] = $defs;

            // Temporarily set 'idxname_format' MDB2 option to $this->idx_format
            $idxname_format = $this->db->getOption('idxname_format');
            $this->db->setOption('idxname_format', $this->idxname_format);
        }

        // Default - no auto increment column
        $this->auto_inc_col[$table] = null;

        // Loop over columns to create $this->col[$table]
        $this->col[$table] = array();
        foreach ($defs as $t) {

            $name = $t['name'];
            $col  = array();

            switch (strtoupper($t['type'])) {
            case 'INT2':     // postgres
            case 'TINYINT':
            case 'TINY':     //mysql
            case 'SMALLINT':
                $col['type'] = 'smallint';
                break;
            case 'INT4':      // postgres
            case 'SERIAL4':   // postgres
            case 'INT':
            case 'SHORT':     // mysql
            case 'INTEGER':
            case 'MEDIUMINT':
            case 'YEAR':
                $col['type'] = 'integer';
                break;
            case 'BIGINT':
            case 'LONG':    // mysql
            case 'INT8':    // postgres
            case 'SERIAL8': // postgres
                $col['type'] = 'bigint';
                break;
            case 'REAL':
            case 'NUMERIC':
            case 'NUMBER': // oci8
            case 'FLOAT':  // mysql
            case 'FLOAT4': // real (postgres)
                $col['type'] = 'single';
                break;
            case 'DOUBLE':
            case 'DOUBLE PRECISION': // double precision (firebird)
            case 'FLOAT8':           // double precision (postgres)
                $col['type'] = 'double';
                break;
            case 'DECIMAL':
            case 'MONEY':   // mssql and maybe others
                $col['type'] = 'decimal';
                break;
            case 'BIT':
            case 'BOOL':
            case 'BOOLEAN':
                $col['type'] = 'boolean';
                break;
            case 'STRING':
            case 'CHAR':
                $col['type'] = 'char';
                break;
            case 'VARCHAR':
            case 'VARCHAR2':
            case 'TINYTEXT':
                $col['type'] = 'varchar';
                break;
            case 'TEXT':
            case 'MEDIUMTEXT':
            case 'LONGTEXT':
                $col['type'] = 'clob';
                break;
            case 'DATE':
                $col['type'] = 'date';
                break;
            case 'TIME':
                $col['type'] = 'time';
                break;
            case 'DATETIME':  // mysql
            case 'TIMESTAMP':
                $col['type'] = 'timestamp';
                break;
            case 'ENUM':
            case 'SET':         // not really but oh well
            case 'TIMESTAMPTZ': // postgres
            case 'BPCHAR':      // postgres
            case 'INTERVAL':    // postgres (eg. '12 days')
            case 'CIDR':        // postgres IP net spec
            case 'INET':        // postgres IP
            case 'MACADDR':     // postgress network Mac address.
            case 'INTEGER[]':   // postgres type
            case 'BOOLEAN[]':   // postgres type
                $col['type'] = 'varchar';
                break;
            default:
                $col['type'] = $t['type'] . ' (Unknown type)';
                break;
            }

            // Set length and scope if required
            if (in_array($col['type'], array('char','varchar','decimal'))) {
                if (isset($t['len'])) {
                    $col['size'] = (int) $t['len'];
                } elseif ($col['type'] == 'varchar') {
                    $col['size'] = 255; // default length
                } elseif ($col['type'] == 'char') {
                    $col['size'] = 128; // default length
                } elseif ($col['type'] == 'decimal') {
                    $col['size'] = 15; // default length
                }
                if ($col['type'] == 'decimal') {
                    $col['scope'] = 2;
                }
            }
            if (isset($t['notnull'])) {
                if ($t['notnull']) {
                    $col['require'] = true;
                }
            }
            if (isset($t['autoincrement'])) {
                $this->auto_inc_col[$table] = $name;
            }
            if (isset($t['flags'])) {
                $flags = $t['flags'];
                if (preg_match('/not[ _]null/i', $flags)) {
                    $col['require'] = true;
                }
                if (preg_match("/(auto_increment|nextval\()/i", $flags)) {
                    $this->auto_inc_col[$table] = $name;
                }
            }
            $require = isset($col['require']) ? $col['require'] : false;
            if ($require) {
                if (isset($t['default'])) {
                    $default = $t['default'];
                    $type    = $col['type'];
                    if (in_array($type,
                                 array('smallint', 'integer', 'bigint'))) {
                        $default = (int) $default;
                    } elseif (in_array($type, array('single', 'double'))) {
                        $default = (float) $default;
                    } elseif ($type == 'boolean') {
                        $default = (int) $default ? 1 : 0;
                    }
                    $col['default'] = $default;
                }
            }
            $this->col[$table][$name] = $col;

        }

        // Make array with lower case column array names as keys
        $col_lc = array();
        foreach ($this->col[$table] as $name => $def) {
            $name_lc          = strtolower($name);
            $col_lc[$name_lc] = $name;
        }

        // Constraints/Indexes
        $DB_indexes = DB_Table_Manager::getIndexes($this->db, $table);
        if (PEAR::isError($DB_indexes)) {
            return $DB_indexes;
        }

        // Check that index columns correspond to valid column names.
        // Try to correct problems with capitalization, if necessary.
        foreach ($DB_indexes as $type => $indexes) {
            foreach ($indexes as $name => $fields) {
                foreach ($fields as $key => $field) {

                    // If index column is not a valid column name
                    if (!array_key_exists($field, $this->col[$table])) {

                        // Try a case-insensitive match
                        $field_lc = strtolower($field);
                        if (isset($col_lc[$field_lc])) {
                            $correct = $col_lc[$field_lc];
                            $DB_indexes[$type][$name][$key]
                                 = $correct;
                        } else {
                            $code   =  DB_TABLE_GENERATOR_ERR_INDEX_COL;
                            $return =&
                                DB_Table_Generator::throwError($code, $field);
                        }

                    }
                }
            }
        }

        // Generate index definitions, if any, as php code
        $n_idx = 0;
        $u     = array();

        $this->idx[$table]         = array();
        $this->primary_key[$table] = null;
        foreach ($DB_indexes as $type => $indexes) {
            if (count($indexes) > 0) {
                foreach ($indexes as $name => $fields) {
                    $this->idx[$table][$name]         = array();
                    $this->idx[$table][$name]['type'] = $type;
                    if (count($fields) == 1) {
                        $key = $fields[0];
                    } else {
                        $key = array();
                        foreach ($fields as $value) {
                            $key[] = $value;
                        }
                    }
                    $this->idx[$table][$name]['cols'] = $key;
                    if ($type == 'primary') {
                        $this->primary_key[$table] = $key;
                    }
                }
            }
        }

        if ($this->backend == 'mdb2') {
            // Restore original MDB2 'idxname_format' and 'portability'
            $this->db->setOption('idxname_format', $idxname_format);
            $this->db->setOption('portability', $portability);
        }

        return true;
    }

    // }}}
    // {{{ function buildTableClass($table, $indent = '')

    /**
     * Returns one skeleton DB_Table subclass definition, as php code
     *
     * The returned subclass definition string contains values for the
     * $col (column), $idx (index) and $auto_inc_col properties, with
     * no method definitions.
     *
     * @param string $table  name of table
     * @param string $indent string of whitespace for base indentation
     *
     * @return string skeleton DB_Table subclass definition
     * @access public
     */
    function buildTableClass($table, $indent = '')
    {
        $s   = array();
        $idx = array();
        $u   = array();
        $v   = array();
        $l   = 0;

        $s[]     = $indent . '/*';
        $s[]     = $indent . ' * Create the table object';
        $s[]     = $indent . ' */';
        $s[]     = $indent . 'class ' . $this->className($table)
                 . " extends {$this->extends} {\n";
        $indent .= '    ';

        $s[]     = $indent . '/*';
        $s[]     = $indent . ' * Column definitions';
        $s[]     = $indent . ' */';
        $s[]     = $indent . 'var $col = array(' . "\n";
        $indent .= '    ';

        // Begin loop over columns
        foreach ($this->col[$table] as $name => $col) {

            // Generate DB_Table column definitions as php code
            $t  = array();
            $t1 = array();
            $l1 = 0;

            $name     = $indent . "'{$name}'";
            $l        = max($l, strlen($name));
            $v[$name] = "array(\n";
            $indent  .= '    ';
            foreach ($col as $key => $value) {
                if (is_string($value)) {
                    $value = "'{$value}'";
                } elseif (is_bool($value)) {
                    $value = $value ? 'true' : 'false';
                } else {
                    $value = (string) $value;
                }
                $l1   = max($l1, strlen($key) + 2);
                $t1[] = array("'{$key}'", $value) ;
            }
            foreach ($t1 as $value) {
                $t[] = $indent . str_pad($value[0], $l1, ' ', STR_PAD_RIGHT)
                     . ' => ' . $value[1];
            }
            $v[$name] .= implode(",\n", $t) . "\n";
            $indent    = substr($indent, 0, -4);
            $v[$name] .= $indent . ')';
        } //end loop over columns

        foreach ($v as $key => $value) {
            $u[] = str_pad($key, $l, ' ', STR_PAD_RIGHT)
                 . ' => ' . $value;
        }
        $s[]    = implode(",\n\n", $u) . "\n";
        $indent = substr($indent, 0, -4);
        $s[]    = $indent . ");\n";

        // Generate index definitions, if any, as php code
        if (count($this->idx[$table]) > 0) {
            $u = array();
            $v = array();
            $l = 0;

            $s[]     = $indent . '/*';
            $s[]     = $indent . ' * Index definitions';
            $s[]     = $indent . ' */';
            $s[]     = $indent . 'var $idx = array(' . "\n";
            $indent .= '    ';
            foreach ($this->idx[$table] as $name => $def) {
                $type      = $def['type'];
                $cols      = $def['cols'];
                $name      = $indent . "'{$name}'";
                $l         = max($l, strlen($name));
                $v[$name]  = "array(\n";
                $indent   .= '    ';
                $v[$name] .= $indent . "'type' => '{$type}',\n";
                if (is_array($cols)) {
                    $v[$name] .= $indent . "'cols' => array(\n";
                    $indent   .= '    ';
                    $t         = array();
                    foreach ($cols as $value) {
                        $t[] = $indent . "'{$value}'";
                    }
                    $v[$name] .= implode(",\n", $t) . "\n";
                    $indent    = substr($indent, 0, -4);
                    $v[$name] .= $indent . ")\n";
                } else {
                    $v[$name] = $v[$name] . $indent . "'cols' => '{$cols}'\n";
                }
                $indent    = substr($indent, 0, -4);
                $v[$name] .= $indent . ")";
            }

            foreach ($v as $key => $value) {
                $u[] = str_pad($key, $l, ' ', STR_PAD_RIGHT)
                     . ' => ' . $value;
            }
            $s[]    = implode(",\n\n", $u) . "\n";
            $indent = substr($indent, 0, -4);
            $s[]    = $indent . ");\n";
        } // end index generation

        // Write auto_inc_col
        if (isset($this->auto_inc_col[$table])) {
            $s[] = $indent . '/*';
            $s[] = $indent . ' * Auto-increment declaration';
            $s[] = $indent . ' */';
            $s[] = $indent . 'var $auto_inc_col = '
                           . "'{$this->auto_inc_col[$table]}';\n";
        }
        $indent = substr($indent, 0, -4);
        $s[]    = $indent . '}';

        // Implode and return lines of class definition
        return implode("\n", $s) . "\n";

    }

    // }}}
    // {{{ function buildTableClasses()

    /**
     * Returns a string containing all table class definitions in one file
     *
     * The returned string contains the contents of a single php file with
     * definitions of DB_Table subclasses associated with all of the tables
     * in $this->tables. If $this->tables is initially null, method
     * $this->getTableNames() is called internally to generate a list of
     * table names.
     *
     * The returned string includes the opening and closing <?php and ?>
     * script elements, and the require_once line needed to include the
     * $this->extend_class (i.e., DB_Table or a subclass) that is being
     * extended. To use, write this string to a new php file.
     *
     * Usage:
     * <code>
     *     $generator = new DB_Table_Generator($db, $database);
     *     echo $generator->buildTablesClasses();
     * </code>
     *
     * @return mixed a string with all table class definitions,
     *                PEAR Error on failure
     * @access public
     */
    function buildTableClasses()
    {
        // If $this->tables is null, call getTableNames()
        if (!$this->tables) {
            $return = $this->getTableNames();
            if (PEAR::isError($return)) {
                return $return;
            }
        }

        $s   = array();
        $s[] = '<?php';
        $s[] = '/*';
        $s[] = ' * Include basic class';
        $s[] = ' */';
        $s[] = "require_once '{$this->extends_file}';\n";
        foreach ($this->tables as $table) {
            $return = $this->getTableDefinition($table);
            if (PEAR::isError($return)) {
                return $return;
            }
            $s[] = $this->buildTableClass($table) . "\n";
        }
        $s[] = '?>';
        return implode("\n", $s);
    }

    // }}}
    // {{{ function generateTableClassFiles()

    /**
     * Writes all table class definitions to separate files
     *
     * Usage:
     * <code>
     *     $generator = new DB_Table_Generator($db, $database);
     *     $generator->generateTableClassFiles();
     * </code>
     *
     * @return mixed true on success, PEAR Error on failure
     * @access public
     */
    function generateTableClassFiles()
    {
        // If $this->tables is null, call getTableNames()
        if (!$this->tables) {
            $return = $this->getTableNames();
            if (PEAR::isError($return)) {
                return $return;
            }
        }

        // Write all table class definitions to separate files
        foreach ($this->tables as $table) {
            $classname = $this->className($table);
            $filename  = $this->classFileName($classname);
            $base      = $this->class_write_path;
            if ($base) {
                if (!file_exists($base)) {
                    include_once 'System.php';
                    if (!@System::mkdir(array('-p', $base))) {
                        return $this->throwError(DB_TABLE_GENERATOR_ERR_FILE,
                            $base);
                    }

                }
                $filename = "{$base}/{$filename}";
            }
            if (!file_exists($filename)) {
                $s      = array();
                $s[]    = '<?php';
                $s[]    = '/*';
                $s[]    = ' * Include basic class';
                $s[]    = ' */';
                $s[]    = "require_once '{$this->extends_file}';\n";
                $return = $this->getTableDefinition($table);
                if (PEAR::isError($return)) {
                    return $return;
                }
                $s[] = $this->buildTableClass($table);
                $s[] = '?>';
                $s[] = '';
                $out = implode("\n", $s);
                if (!$file = @fopen($filename, 'wb')) {
                    return $this->throwError(DB_TABLE_GENERATOR_ERR_FILE,
                            $filename);
                }
                fputs($file, $out);
                fclose($file);
            }
        }

        return true;
    }

    // }}}
    // {{{ function generateDatabaseFile($object_name = null)

    /**
     * Writes a file to instantiate Table and Database objects
     *
     * After successful completion, a file named 'Database.php' will be
     * have been created in the $this->class_write_path directory. This
     * file should normally be included in application php scripts. It
     * can be renamed by the user.
     *
     * Usage:
     * <code>
     *     $generator = new DB_Table_Generator($db, $database);
     *     $generator->generateTableClassFiles();
     *     $generator->generateDatabaseFile();
     * </code>
     *
     * @param string $object_name variable name for DB_Table_Database object
     *
     * @return mixed true on success, PEAR Error on failure
     * @access public
     */
    function generateDatabaseFile($object_name = null)
    {
        // Set name for DB_Table_Database object
        if ($object_name) {
            $object_name = "\${$object_name}";
        } else {
            $object_name = '$db'; //default
        }
        $backend = strtoupper($this->backend); // 'DB' or 'MDB2'

        if ('DB' == $backend) {
            $dsn = $this->db->dsn;
        } else {
            $dsn = $this->db->getDSN('array');
        }

        // Create array d[] containing lines of database php file
        $d   = array();
        $d[] = '<?php';
        $d[] = '/*';
        $d[] = ' * Include basic classes';
        $d[] = ' */';
        $d[] = "require_once '{$backend}.php';";
        $d[] = "require_once 'DB/Table/Database.php';";

        // Require_once statements for subclass definitions
        foreach ($this->tables as $table) {
            $classname      = $this->className($table);
            $class_filename = $this->classFileName($classname);
            if ($this->class_include_path) {
                $d[] = 'require_once '
                     . "'{$this->class_include_path}/{$class_filename}';";
            } else {
                $d[] = "require_once '{$class_filename}';";
            }
        }
        $d[] = '';

        $d[] = '/*';
        $d[] = ' * NOTE: User must uncomment & edit code to create $dsn';
        $d[] = ' */';
        $d[] = "//\$phptype  = '{$dsn['phptype']}';";
        $d[] = "//\$username = '{$dsn['username']}';";
        $d[] = "//\$password = ''; // put your password here";
        $d[] = "//\$hostname = '{$dsn['hostspec']}';";
        $d[] = "//\$database = '{$dsn['database']}';";
        $d[] = "//\$create   = false; // 'drop', 'safe', 'verify', 'alter'";
        $d[] = '//$dsn      = "{$phptype}://{$username}:{$password}@{$hostname}'
             . '/{$database}";';
        $d[] = '';

        $d[] = '/*';
        $d[] = " * Instantiate {$backend} connection object \$conn";
        $d[] = ' */';
        $d[] = "\$conn =& {$backend}::connect(\$dsn);";
        $d[] = 'if (PEAR::isError($conn)) {';
        $d[] = '    echo "Error connecting to database server\n";';
        $d[] = '    echo $conn->getMessage();';
        $d[] = '    die;';
        $d[] = '}';
        $d[] = '';

        $d[] = '/*';
        $d[] = ' * Create one instance of each DB_Table subclass';
        $d[] = ' */';
        foreach ($this->tables as $table) {
            $classname = $this->className($table);

            $d[] = "\${$table} = new {$classname}("
                 . '$conn, ' . "'{$table}'" . ', $create);';
            $d[] = "if (PEAR::isError(\${$table}->error)) {";
            $d[] = '    echo "Can\'t create table object.\n";';
            $d[] = "    echo \${$table}->error->getMessage();";
            $d[] = '    die;';
            $d[] = '}';

        }
        $d[] = '';

        $d[] = '/*';
        $d[] = ' * Instantiate a parent DB_Table_Database object';
        $d[] = ' */';
        $d[] = "{$object_name} = new DB_Table_Database(\$conn, \$database);";
        $d[] = "if (PEAR::isError({$object_name}->error)) {";
        $d[] = '    echo "Can\'t create database object.\n";';
        $d[] = "    echo {$object_name}->error->getMessage();";
        $d[] = '    die;';
        $d[] = '}';
        $d[] = '';

        $d[] = '/*';
        $d[] = ' * Add DB_Table objects to parent DB_Table_Database object';
        $d[] = ' */';
        foreach ($this->tables as $table) {
            $classname = $this->className($table);

            $d[] = "\$result = {$object_name}->addTable(\${$table});";
            $d[] = 'if (PEAR::isError($result)) {';
            $d[] = '    echo "Can\'t add table object to database object.\n";';
            $d[] = '    echo $result->getMessage();';
            $d[] = '    die;';
            $d[] = '}';
        }
        $d[] = '';

        // Add foreign key references: If the name of an integer column
        // matches "/id$/i" (i.e., the names ends with id, ID, or Id), the
        // remainder of the name matches the name $rtable of another table,
        // and $rtable has an integer primary key, then the column is
        // assumed to be a foreign key that references $rtable.

        $d[] = '/*';
        $d[] = ' * Add auto-guessed foreign references';
        $d[] = ' */';
        foreach ($this->col as $table => $col) {
            foreach ($col as $col_name => $def) {

                // Only consider integer columns
                $ftype = $def['type'];
                if (!in_array($ftype, array('integer','smallint','bigint'))) {
                    continue;
                }
                if (preg_match("/id$/i", $col_name)) {
                    $column_base = preg_replace('/_?id$/i', '', $col_name);
                    foreach ($this->tables as $rtable) {
                        if (!preg_match("/^{$rtable}$/i", $column_base)) {
                            continue;
                        }
                        if (preg_match("/^{$table}$/i", $column_base)) {
                            continue;
                        }
                        if (!isset($this->primary_key[$rtable])) {
                            continue;
                        }
                        $rkey = $this->primary_key[$rtable];
                        if (is_array($rkey)) {
                            continue;
                        }
                        $rtype = $this->col[$rtable][$rkey]['type'];
                        if (!in_array($rtype,
                            array('integer','smallint','bigint'))) {
                            continue;
                        }
                        $d[] = "\$result = {$object_name}->addRef('{$table}', "
                             . "'{$col_name}', '{$rtable}');";
                        $d[] = 'if (PEAR::isError($result)) {';
                        $d[] = '    echo "Can\'t add foreign key reference.\n";';
                        $d[] = '    echo $result->getMessage();';
                        $d[] = '    die;';
                        $d[] = '}';
                    }
                }
            }
        }
        $d[] = '';
        $d[] = '/*';
        $d[] = ' * Add any additional foreign key references here';
        $d[] = ' *';
        $d[] = ' * Add any linking table declarations here';
        $d[] = ' * Uncomment next line to add all possible linking tables;';
        $d[] = ' */';
        $d[] = "//\$result = {$object_name}->addAllLinks();";
        $d[] = '//if (PEAR::isError($result)) {';
        $d[] = '//    echo "Can\'t add linking tables.\n";';
        $d[] = '//    echo $result->getMessage();';
        $d[] = '//    die;';
        $d[] = '//}';
        $d[] = '';

        // Closing script element
        $d[] = '?>';
        $d[] = '';

        // Open and write file
        $base = $this->class_write_path;
        if ($base) {
            if (!file_exists($base)) {
                include_once 'System.php';
                if (!@System::mkdir(array('-p', $base))) {
                    return $this->throwError(DB_TABLE_GENERATOR_ERR_FILE, $base);
                }
            }
            $filename = $base . '/Database.php';
        } else {
            $filename = 'Database.php';
        }
        if (!$file = @fopen($filename, 'wb')) {
            return $this->throwError(DB_TABLE_GENERATOR_ERR_FILE, $filename);
        }
        $out = implode("\n", $d);
        fputs($file, $out);
        fclose($file);

        return true;
    }

    // }}}
    // {{{ function className($table)

    /**
     * Convert a table name into a class name
     *
     * Converts all non-alphanumeric characters to '_', capitalizes
     * first letter, and adds $this->class_suffix to end. Override
     * this if you want something else.
     *
     * @param string $table name of table
     *
     * @return string class name;
     * @access public
     */
    function className($table)
    {
        $name = preg_replace('/[^A-Z0-9]/i', '_', ucfirst(trim($table)));
        return  $name . $this->class_suffix;
    }

    // }}}
    // {{{ function tableName($table)

    /**
     * Returns a valid variable name from a table name
     *
     * Converts all non-alphanumeric characters to '_'. Override
     * this if you want something else.
     *
     * @param string $table name of table
     *
     * @return string variable name;
     * @access public
     */
    function tableName($table)
    {
        return preg_replace('/[^A-Z0-9]/i', '_', trim($table));
    }

    // }}}
    // {{{ function classFileName($class_name)

    /**
     * Returns the path to a file containing a class definition
     *
     * Appends '.php' to class name.
     *
     * @param string $class_name name of class
     *
     * @return string file name
     * @access public
     */
    function classFileName($class_name)
    {
        $filename = $class_name . '.php';
        return $filename;
    }

    // }}}

}
// }}}
