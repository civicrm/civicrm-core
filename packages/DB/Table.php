<?php

/**
 * DB_Table is a database API and data type SQL abstraction class.
 * 
 * DB_Table provides database API abstraction, data type abstraction,
 * automated SELECT, INSERT, and UPDATE queries, automated table
 * creation, automated validation of inserted/updated column values,
 * and automated creation of QuickForm elements based on the column
 * definitions.
 * 
 * PHP versions 4 and 5
 *
 * LICENSE:
 * 
 * Copyright (c) 1997-2007, Paul M. Jones <pmjones@php.net>
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
 * @author   Paul M. Jones <pmjones@php.net>
 * @author   David C. Morse <morse@php.net>
 * @author   Mark Wiesemann <wiesemann@php.net>
 * @license  http://opensource.org/licenses/bsd-license.php New BSD License
 * @version  CVS: $Id: Table.php,v 1.90 2008/12/25 19:56:35 wiesemann Exp $
 * @link     http://pear.php.net/package/DB_Table
 */

/**
 * Error code at instantiation time when the first parameter to the
 * constructor is not a PEAR DB object.
 */
define('DB_TABLE_ERR_NOT_DB_OBJECT',    -1);

/**
 * Error code at instantiation time when the PEAR DB/MDB2 $phptype is not
 * supported by DB_Table.
 */
define('DB_TABLE_ERR_PHPTYPE',          -2);

/**
 * Error code when you call select() or selectResult() and the first
 * parameter is a string that does not match any of the $this->sql keys.
 */
define('DB_TABLE_ERR_SQL_UNDEF',        -3);

/**
 * Error code when you call select*() or buildSQL() and the first
 * parameter is neither an array nor a string
 */
define('DB_TABLE_ERR_SQL_NOT_STRING',   -4);

/**
 * Error code when you try to insert data to a column that is not in the
 * $this->col array.
 */
define('DB_TABLE_ERR_INS_COL_NOMAP',    -5);

/**
 * Error code when you try to insert data, and that data does not have a
 * column marked as 'require' in the $this->col array.
 */
define('DB_TABLE_ERR_INS_COL_REQUIRED', -6);

/**
 * Error code when auto-validation fails on data to be inserted.
 */
define('DB_TABLE_ERR_INS_DATA_INVALID', -7);

/**
 * Error code when you try to update data to a column that is not in the
 * $this->col array.
 */
define('DB_TABLE_ERR_UPD_COL_NOMAP',    -8);

/**
 * Error code when you try to update data, and that data does not have a
 * column marked as 'require' in the $this->col array.
 */
define('DB_TABLE_ERR_UPD_COL_REQUIRED', -9);

/**
 * Error code when auto-validation fails on update data.
 */
define('DB_TABLE_ERR_UPD_DATA_INVALID', -10);

/**
 * Error code when you use a create() flag that is not recognized (must
 * be 'safe', 'drop', 'verify' or boolean false.
 */
define('DB_TABLE_ERR_CREATE_FLAG',      -11);

/**
 * Error code at create() time when you define an index in $this->idx
 * that has no columns.
 */
define('DB_TABLE_ERR_IDX_NO_COLS',      -12);

/**
 * Error code at create() time when you define an index in $this->idx
 * that refers to a column that does not exist in the $this->col array.
 */
define('DB_TABLE_ERR_IDX_COL_UNDEF',    -13);

/**
 * Error code at create() time when you define a $this->idx index type
 * that is not recognized (must be 'normal' or 'unique').
 */
define('DB_TABLE_ERR_IDX_TYPE',         -14);

/**
 * Error code at create() time when you have an error in a 'char' or
 * 'varchar' definition in $this->col (usually because 'size' is wrong).
 */
define('DB_TABLE_ERR_DECLARE_STRING',   -15);

/**
 * Error code at create() time when you have an error in a 'decimal'
 * definition (usually becuase the 'size' or 'scope' are wrong).
 */
define('DB_TABLE_ERR_DECLARE_DECIMAL',  -16);

/**
 * Error code at create() time when you define a column in $this->col
 * with an unrecognized 'type'.
 */
define('DB_TABLE_ERR_DECLARE_TYPE',     -17);

/**
 * Error code at validation time when a column in $this->col has an
 * unrecognized 'type'.
 */
define('DB_TABLE_ERR_VALIDATE_TYPE',    -18);

/**
 * Error code at create() time when you define a column in $this->col
 * with an invalid column name (usually because it's a reserved keyword).
 */
define('DB_TABLE_ERR_DECLARE_COLNAME',  -19);

/**
 * Error code at create() time when you define an index in $this->idx
 * with an invalid index name (usually because it's a reserved keyword).
 */
define('DB_TABLE_ERR_DECLARE_IDXNAME',  -20);

/**
 * Error code at create() time when you define an index in $this->idx
 * that refers to a CLOB column.
 */
define('DB_TABLE_ERR_IDX_COL_CLOB',     -21);

/**
 * Error code at create() time when you define a column name that is
 * more than 30 chars long (an Oracle restriction).
 */
define('DB_TABLE_ERR_DECLARE_STRLEN',   -22);

/**
 * Error code at create() time when the index name ends up being more
 * than 30 chars long (an Oracle restriction).
 */
define('DB_TABLE_ERR_IDX_STRLEN',       -23);

/**
 * Error code at create() time when the table name is more than 30 chars
 * long (an Oracle restriction).
 */
define('DB_TABLE_ERR_TABLE_STRLEN',     -24);

/**
 * Error code at nextID() time when the sequence name is more than 30
 * chars long (an Oracle restriction).
 */
define('DB_TABLE_ERR_SEQ_STRLEN',       -25);

/**
 * Error code at verify() time when the table does not exist in the
 * database.
 */
define('DB_TABLE_ERR_VER_TABLE_MISSING', -26);

/**
 * Error code at verify() time when the column does not exist in the
 * database table.
 */
define('DB_TABLE_ERR_VER_COLUMN_MISSING', -27);

/**
 * Error code at verify() time when the column type does not match the
 * type specified in the column declaration.
 */
define('DB_TABLE_ERR_VER_COLUMN_TYPE',  -28);

/**
 * Error code at instantiation time when the column definition array
 * does not contain at least one column.
 */
define('DB_TABLE_ERR_NO_COLS',          -29);

/**
 * Error code at verify() time when an index cannot be found in the
 * database table.
 */
define('DB_TABLE_ERR_VER_IDX_MISSING',   -30);

/**
 * Error code at verify() time when an index does not contain all
 * columns that it should contain.
 */
define('DB_TABLE_ERR_VER_IDX_COL_MISSING', -31);

/**
 * Error code at instantiation time when a creation mode
 * is not available for a phptype.
 */
define('DB_TABLE_ERR_CREATE_PHPTYPE', -32);

/**
 * Error code at create() time when you define more than one primary key
 * in $this->idx.
 */
define('DB_TABLE_ERR_DECLARE_PRIMARY', -33);

/**
 * Error code at create() time when a primary key is defined in $this->idx
 * and SQLite is used (SQLite does not support primary keys).
 */
define('DB_TABLE_ERR_DECLARE_PRIM_SQLITE', -34);

/**
 * Error code at alter() time when altering a table field is not possible
 * (e.g. because MDB2 has no support for the change or because the DBMS
 * does not support the change).
 */
define('DB_TABLE_ERR_ALTER_TABLE_IMPOS', -35);

/**
 * Error code at alter() time when altering a(n) index/constraint is not possible
 * (e.g. because MDB2 has no support for the change or because the DBMS
 * does not support the change).
 */
define('DB_TABLE_ERR_ALTER_INDEX_IMPOS', -36);

/**
 * Error code at insert() time due to invalid the auto-increment column
 * definition. This column must be an integer type and required.
 */
define('DB_TABLE_ERR_AUTO_INC_COL', -37);

/**
 * Error code at instantiation time when both the $table parameter
 * and the $table class property are missing.
 */
define('DB_TABLE_ERR_TABLE_NAME_MISSING', -38);

/**
 * The DB_Table_Base parent class
 */
require_once 'DB/Table/Base.php';

/**
 * The PEAR class for errors
 */
require_once 'PEAR.php';

/**
 * The Date class for recasting date and time values
 */
require_once 'DB/Table/Date.php';


/**
 * DB_Table supports these RDBMS engines and their various native data
 * types; we need these here instead of in Manager.php because the
 * initial array key tells us what databases are supported.
 */
$GLOBALS['_DB_TABLE']['type'] = array(
    'fbsql' => array(
        'boolean'   => 'DECIMAL(1,0)',
        'char'      => 'CHAR',
        'varchar'   => 'VARCHAR',
        'smallint'  => 'SMALLINT',
        'integer'   => 'INTEGER',
        'bigint'    => 'LONGINT',
        'decimal'   => 'DECIMAL',
        'single'    => 'REAL',
        'double'    => 'DOUBLE PRECISION',
        'clob'      => 'CLOB',
        'date'      => 'CHAR(10)',
        'time'      => 'CHAR(8)',
        'timestamp' => 'CHAR(19)'
    ),
    'ibase' => array(
        'boolean'   => 'DECIMAL(1,0)',
        'char'      => 'CHAR',
        'varchar'   => 'VARCHAR',
        'smallint'  => 'SMALLINT',
        'integer'   => 'INTEGER',
        'bigint'    => 'BIGINT',
        'decimal'   => 'DECIMAL',
        'single'    => 'FLOAT',
        'double'    => 'DOUBLE PRECISION',
        'clob'      => 'BLOB SUB_TYPE 1',
        'date'      => 'DATE',
        'time'      => 'TIME',
        'timestamp' => 'TIMESTAMP'
    ),
    'mssql' => array(
        'boolean'   => 'DECIMAL(1,0)',
        'char'      => 'CHAR',
        'varchar'   => 'VARCHAR',
        'smallint'  => 'SMALLINT',
        'integer'   => 'INTEGER',
        'bigint'    => 'BIGINT',
        'decimal'   => 'DECIMAL',
        'single'    => 'REAL',
        'double'    => 'FLOAT',
        'clob'      => 'TEXT',
        'date'      => 'CHAR(10)',
        'time'      => 'CHAR(8)',
        'timestamp' => 'CHAR(19)'
    ),
    'mysql' => array(
        'boolean'   => 'DECIMAL(1,0)',
        'char'      => 'CHAR',
        'varchar'   => 'VARCHAR',
        'smallint'  => 'SMALLINT',
        'integer'   => 'INTEGER',
        'bigint'    => 'BIGINT',
        'decimal'   => 'DECIMAL',
        'single'    => 'FLOAT',
        'double'    => 'DOUBLE',
        'clob'      => 'LONGTEXT',
        'date'      => 'CHAR(10)',
        'time'      => 'CHAR(8)',
        'timestamp' => 'CHAR(19)'
    ),
    'mysqli' => array(
        'boolean'   => 'DECIMAL(1,0)',
        'char'      => 'CHAR',
        'varchar'   => 'VARCHAR',
        'smallint'  => 'SMALLINT',
        'integer'   => 'INTEGER',
        'bigint'    => 'BIGINT',
        'decimal'   => 'DECIMAL',
        'single'    => 'FLOAT',
        'double'    => 'DOUBLE',
        'clob'      => 'LONGTEXT',
        'date'      => 'CHAR(10)',
        'time'      => 'CHAR(8)',
        'timestamp' => 'CHAR(19)'
    ),
    'oci8' => array(
        'boolean'   => 'NUMBER(1)',
        'char'      => 'CHAR',
        'varchar'   => 'VARCHAR2',
        'smallint'  => 'NUMBER(6)',
        'integer'   => 'NUMBER(11)',
        'bigint'    => 'NUMBER(19)',
        'decimal'   => 'NUMBER',
        'single'    => 'REAL',
        'double'    => 'DOUBLE PRECISION',
        'clob'      => 'CLOB',
        'date'      => 'CHAR(10)',
        'time'      => 'CHAR(8)',
        'timestamp' => 'CHAR(19)'
    ),
    'pgsql' => array(
        'boolean'   => 'DECIMAL(1,0)',
        'char'      => 'CHAR',
        'varchar'   => 'VARCHAR',
        'smallint'  => 'SMALLINT',
        'integer'   => 'INTEGER',
        'bigint'    => 'BIGINT',
        'decimal'   => 'DECIMAL',
        'single'    => 'REAL',
        'double'    => 'DOUBLE PRECISION',
        'clob'      => 'TEXT',
        'date'      => 'CHAR(10)',
        'time'      => 'CHAR(8)',
        'timestamp' => 'CHAR(19)'
    ),
    'sqlite' => array(
        'boolean'   => 'BOOLEAN',
        'char'      => 'CHAR',
        'varchar'   => 'VARCHAR',
        'smallint'  => 'SMALLINT',
        'integer'   => 'INTEGER',
        'bigint'    => 'BIGINT',
        'decimal'   => 'NUMERIC',
        'single'    => 'FLOAT',
        'double'    => 'DOUBLE',
        'clob'      => 'CLOB',
        'date'      => 'DATE',
        'time'      => 'TIME',
        'timestamp' => 'TIMESTAMP'
    )
);


/** 
 * US-English default error messages. If you want to internationalize, you can
 * set the translated messages via $GLOBALS['_DB_TABLE']['error']. You can also
 * use DB_Table::setErrorMessage(). Examples:
 * 
 * <code>
 * (1) $GLOBALS['_DB_TABLE]['error'] = array(DB_TABLE_ERR_PHPTYPE   => '...',
 *                                           DB_TABLE_ERR_SQL_UNDEF => '...');
 * (2) DB_Table::setErrorMessage(DB_TABLE_ERR_PHPTYPE,   '...');
 *     DB_Table::setErrorMessage(DB_TABLE_ERR_SQL_UNDEF, '...');
 * (3) DB_Table::setErrorMessage(array(DB_TABLE_ERR_PHPTYPE   => '...');
 *                                     DB_TABLE_ERR_SQL_UNDEF => '...');
 * (4) $obj = new DB_Table();
 *     $obj->setErrorMessage(DB_TABLE_ERR_PHPTYPE,   '...');
 *     $obj->setErrorMessage(DB_TABLE_ERR_SQL_UNDEF, '...');
 * (5) $obj = new DB_Table();
 *     $obj->setErrorMessage(array(DB_TABLE_ERR_PHPTYPE   => '...');
 *                                 DB_TABLE_ERR_SQL_UNDEF => '...');
 * </code>
 * 
 * For errors that can occur with-in the constructor call (i.e. e.g. creating
 * or altering the database table), only the code from examples (1) to (3)
 * will alter the default error messages early enough. For errors that can
 * occur later, examples (4) and (5) are also valid.
 */
$GLOBALS['_DB_TABLE']['default_error'] = array(
    DB_TABLE_ERR_NOT_DB_OBJECT       => 'First parameter must be a DB/MDB2 object',
    DB_TABLE_ERR_PHPTYPE             => 'DB/MDB2 phptype (or dbsyntax) not supported',
    DB_TABLE_ERR_SQL_UNDEF           => 'Select query string not in a key of $sql. Key',
    DB_TABLE_ERR_SQL_NOT_STRING      => 'Select query is neither an array nor a string',
    DB_TABLE_ERR_INS_COL_NOMAP       => 'Insert column not in map',
    DB_TABLE_ERR_INS_COL_REQUIRED    => 'Insert data must be set and non-null for column',
    DB_TABLE_ERR_INS_DATA_INVALID    => 'Insert data not valid for column',
    DB_TABLE_ERR_UPD_COL_NOMAP       => 'Update column not in map',
    DB_TABLE_ERR_UPD_COL_REQUIRED    => 'Update column must be set and non-null',
    DB_TABLE_ERR_UPD_DATA_INVALID    => 'Update data not valid for column',
    DB_TABLE_ERR_CREATE_FLAG         => 'Create flag not valid',
    DB_TABLE_ERR_IDX_NO_COLS         => 'No columns for index',
    DB_TABLE_ERR_IDX_COL_UNDEF       => 'Column not in map for index',
    DB_TABLE_ERR_IDX_TYPE            => 'Type not valid for index',
    DB_TABLE_ERR_DECLARE_STRING      => 'String column declaration not valid',
    DB_TABLE_ERR_DECLARE_DECIMAL     => 'Decimal column declaration not valid',
    DB_TABLE_ERR_DECLARE_TYPE        => 'Column type not valid',
    DB_TABLE_ERR_VALIDATE_TYPE       => 'Cannot validate for unknown type on column',
    DB_TABLE_ERR_DECLARE_COLNAME     => 'Column name not valid',
    DB_TABLE_ERR_DECLARE_IDXNAME     => 'Index name not valid',
    DB_TABLE_ERR_DECLARE_TYPE        => 'Column type not valid',
    DB_TABLE_ERR_IDX_COL_CLOB        => 'CLOB column not allowed for index',
    DB_TABLE_ERR_DECLARE_STRLEN      => 'Column name too long, 30 char max',
    DB_TABLE_ERR_IDX_STRLEN          => 'Index name too long, 30 char max',
    DB_TABLE_ERR_TABLE_STRLEN        => 'Table name too long, 30 char max',
    DB_TABLE_ERR_SEQ_STRLEN          => 'Sequence name too long, 30 char max',
    DB_TABLE_ERR_VER_TABLE_MISSING   => 'Verification failed: table does not exist',
    DB_TABLE_ERR_VER_COLUMN_MISSING  => 'Verification failed: column does not exist',
    DB_TABLE_ERR_VER_COLUMN_TYPE     => 'Verification failed: wrong column type',
    DB_TABLE_ERR_NO_COLS             => 'Column definition array may not be empty',
    DB_TABLE_ERR_VER_IDX_MISSING     => 'Verification failed: index does not exist',
    DB_TABLE_ERR_VER_IDX_COL_MISSING => 'Verification failed: index does not contain all specified cols',
    DB_TABLE_ERR_CREATE_PHPTYPE      => 'Creation mode is not supported for this phptype',
    DB_TABLE_ERR_DECLARE_PRIMARY     => 'Only one primary key is allowed',
    DB_TABLE_ERR_DECLARE_PRIM_SQLITE => 'SQLite does not support primary keys',
    DB_TABLE_ERR_ALTER_TABLE_IMPOS   => 'Alter table failed: changing the field type not possible',
    DB_TABLE_ERR_ALTER_INDEX_IMPOS   => 'Alter table failed: changing the index/constraint not possible',
    DB_TABLE_ERR_AUTO_INC_COL        => 'Illegal auto-increment column definition',
    DB_TABLE_ERR_TABLE_NAME_MISSING  => 'Table name missing in constructor and class'
);

// merge default and user-defined error messages
if (!isset($GLOBALS['_DB_TABLE']['error'])) {
    $GLOBALS['_DB_TABLE']['error'] = array();
}
foreach ($GLOBALS['_DB_TABLE']['default_error'] as $code => $message) {
    if (!array_key_exists($code, $GLOBALS['_DB_TABLE']['error'])) {
        $GLOBALS['_DB_TABLE']['error'][$code] = $message;
    }
}

// set default value for length check switch
if (!isset($GLOBALS['_DB_TABLE']['disable_length_check'])) {
    $GLOBALS['_DB_TABLE']['disable_length_check'] = false;
}

/**
 * DB_Table is a database API and data type SQL abstraction class.
 * 
 * DB_Table provides database API abstraction, data type abstraction,
 * automated SELECT, INSERT, and UPDATE queries, automated table
 * creation, automated validation of inserted/updated column values,
 * and automated creation of QuickForm elemnts based on the column
 * definitions.
 * 
 * @category Database
 * @package  DB_Table
 * @author   Paul M. Jones <pmjones@php.net>
 * @author   David C. Morse <morse@php.net>
 * @author   Mark Wiesemann <wiesemann@php.net>
 * @version  Release: 1.5.6
 * @link     http://pear.php.net/package/DB_Table
 */

class DB_Table extends DB_Table_Base 
{
    
    /**
     * The table or view in the database to which this object binds.
     * 
     * @access public
     * @var string
     */
    var $table = null;
    
    /**
     * DB_Table_Database instance that this table belongs to.
     * 
     * @access private
     * @var object
     */
    var $_database = null;


    /**
     * Associative array of column definitions.
     * 
     * @access public
     * @var array
     */
    var $col = array();
    
    
    /**
     * Associative array of index definitions.
     * 
     * @access public
     * @var array
     */
    var $idx = array();
    
    /**
     * Name of an auto-increment column, if any. Null otherwise.
     *
     * A table can contain at most one auto-increment column. 
     * Auto-incrementing is implemented in the insert() method,
     * using a sequence accessed by the nextID() method.
     *
     * @access public
     * @var string
     */
    var $auto_inc_col = null;


    /**
     * Boolean flag to turn on (true) or off (false) auto-incrementing.
     * 
     * Auto-increment column $auto_inc_col upon insertion only if $_auto_inc is
     * true and the value of that column is null in the data to be inserted.
     *
     * @var bool
     * @access private
     */
    var $_auto_inc = true;


    /**
     * Whether or not to automatically validate data at insert-time.
     * 
     * @var bool
     * @access private
     */
    var $_valid_insert = true;
    
    /**
     * Whether or not to automatically validate data at update-time.
     * 
     * @var bool
     * @access private
     */
    var $_valid_update = true;
    

    /**
     * Whether or not to automatically recast data at insert- and update-time.
     * 
     * @var    bool
     * @access private
     */
    var $_auto_recast = true;
    
    
    /**
     * Constructor.
     *
     * The constructor returns a DB_Table object that wraps an
     * instance $db DB or MDB2, and that binds to a specific database
     * table named $table. It can optionally create the database table
     * or verify that its schema matches that declared in the $col and
     * $idx parameters, depending on the value of the $create parameter.
     *
     * If there is an error on instantiation, $this->error will be 
     * populated with the PEAR_Error.
     * 
     * @param object &$db A PEAR DB/MDB2 object.
     * 
     * @param string $table The table name to connect to in the database.
     * 
     * @param mixed $create The automatic table creation mode to pursue:
     * - boolean false to not attempt creation
     * - 'safe' to create the table only if it does not exist
     * - 'drop' to drop any existing table with the same name and re-create it
     * - 'verify' to check whether the table exists, whether all the columns
     *   exist, whether the columns have the right type, and whether the indexes
     *   exist and have the right type
     * - 'alter' does the same as 'safe' if the table does not exist; if it
     *   exists, a verification for columns existence, the column types, the
     *   indexes existence, and the indexes types will be performed and the
     *   table schema will be modified if needed
     * 
     * @return object DB_Table
     * @access public
     */
    function __construct(&$db, $table = null, $create = false)
    {
        // Identify the class for error handling by parent class
        $this->_primary_subclass = 'DB_TABLE';

        // is the first argument a DB/MDB2 object?
        $this->backend = null;
        if (is_subclass_of($db, 'db_common')) {
            $this->backend = 'db';
        } elseif (is_subclass_of($db, 'mdb2_driver_common')) {
            $this->backend = 'mdb2';
        }

        if (is_null($this->backend)) {
            $this->error = DB_Table::throwError(DB_TABLE_ERR_NOT_DB_OBJECT);
            return;
        }
        
        // set the class properties
        $this->db =& $db;
        if (is_null($table)) {
            // $table parameter not given => check $table class property
            if (is_null($this->table)) {
                $this->error = DB_Table::throwError(DB_TABLE_ERR_TABLE_NAME_MISSING);
                return;
            }
        } else {
            $this->table = $table;
        }
        
        // is the RDBMS supported?
        $phptype = $db->phptype;
        $dbsyntax = $db->dbsyntax;
        if (! DB_Table::supported($phptype, $dbsyntax)) {
            $this->error =& DB_Table::throwError(
                DB_TABLE_ERR_PHPTYPE,
                "({$db->phptype})"
            );
            return;
        }

        // load MDB2_Extended module
        if ($this->backend == 'mdb2') {
            $this->db->loadModule('Extended', null, false);
        }

        // should we attempt table creation?
        if ($create) {

            if ($this->backend == 'mdb2') {
                $this->db->loadModule('Manager');
            }

            // check whether the chosen mode is supported
            $mode_supported = DB_Table::modeSupported($create, $phptype);
            if (PEAR::isError($mode_supported)) {
                $this->error =& $mode_supported;
                return;
            }
            if (!$mode_supported) {
                $this->error =& $this->throwError(
                    DB_TABLE_ERR_CREATE_PHPTYPE,
                    "('$create', '$phptype')"
                );
                return;
            }

            include_once 'DB/Table/Manager.php';

            switch ($create) {

                case 'alter':
                    $result = $this->alter();
                    break;

                case 'drop':
                case 'safe':
                    $result = $this->create($create);
                    break;

                case 'verify':
                    $result = $this->verify();
                    break;
            }
            
            if (PEAR::isError($result)) {
                // problem creating/altering/verifing the table
                $this->error =& $result;
                return;
            }
        }
    }
    
    
    /**
     * Is a particular RDBMS supported by DB_Table?
     * 
     * @static
     * @param string $phptype The RDBMS type for PHP.
     * @param string $dbsyntax The chosen database syntax.
     * @return bool  True if supported, false if not.
     * @access public
     */
    
    function supported($phptype, $dbsyntax = '')
    {
        // only Firebird is supported, not its ancestor Interbase
        if ($phptype == 'ibase' && $dbsyntax != 'firebird') {
            return false;
        }
        $supported = array_keys($GLOBALS['_DB_TABLE']['type']);
        return in_array(strtolower($phptype), $supported);
    }


    /**
     * Is a creation mode supported for a RDBMS by DB_Table?
     * 
     * @param string $mode The chosen creation mode.
     * @param string $phptype The RDBMS type for PHP.
     * @return bool  True if supported, false if not (PEAR_Error on failure)
     *
     * @throws PEAR_Error if
     *     Unknown creation mode is specified (DB_TABLE_ERR_CREATE_FLAG)
     * 
     * @access public
     */
    function modeSupported($mode, $phptype)
    {
        // check phptype for validity
        $supported = array_keys($GLOBALS['_DB_TABLE']['type']);
        if (!in_array(strtolower($phptype), $supported)) {
            return false;
        }

        switch ($mode) {
            case 'drop':
            case 'safe':
                // supported for all RDBMS
                return true;

            case 'alter':
            case 'verify':
                // not supported for fbsql and mssql (yet)
                switch ($phptype) {
                    case 'fbsql':
                    case 'mssql':
                        return false;
                    default:
                        return true;
                }

            default:
                // unknown creation mode
                return $this->throwError(
                    DB_TABLE_ERR_CREATE_FLAG,
                    "('$mode')"
                );
        }
    }


    /**
     * Overwrite one or more error messages, e.g. to internationalize them.
     * 
     * @param mixed $code If string, the error message with code $code will
     * be overwritten by $message. If array, the error messages with code
     * of each array key will be overwritten by the key's value.
     * 
     * @param string $message Only used if $key is not an array.
     * @return void
     * @access public
     */
    function setErrorMessage($code, $message = null) {
        if (is_array($code)) {
            foreach ($code as $single_code => $single_message) {
                $GLOBALS['_DB_TABLE']['error'][$single_code] = $single_message;
            }
        } else {
            $GLOBALS['_DB_TABLE']['error'][$code] = $message;
        }
    }


    /**
     * 
     * Returns all or part of the $this->col property array.
     * 
     * @param mixed $col If null, returns the $this->col property array
     * as it is.  If string, returns that column name from the $this->col
     * array. If an array, returns those columns named as the array
     * values from the $this->col array as an array.
     *
     * @return mixed All or part of the $this->col property array, or
     *               boolean false if no matching column names are found.
     * @access public
     */
    function getColumns($col = null)
    {
        // by default, return all column definitions
        if (is_null($col)) {
            return $this->col;
        }
        
        // if the param is a string, only return the column definition
        // named by the that string
        if (is_string($col)) {
            if (isset($this->col[$col])) {
                return $this->col[$col];
            } else {
                return false;
            }
        }
        
        // if the param is a sequential array of column names,
        // return only those columns named in that array
        if (is_array($col)) {
            $set = array();
            foreach ($col as $name) {
                $set[$name] = $this->getColumns($name);
            }
            
            if (count($set) == 0) {
                return false;
            } else {
                return $set;
            }
        }
        
        // param was not null, string, or array
        return false;
    }
    
    
    /**
     * Returns all or part of the $this->idx property array.
     * 
     * @param mixed $idx Index name (key in $this->idx), or array of
     *                   index name strings.
     * 
     * @return mixed All or part of the $this->idx property array, 
     *               or boolean false if $idx is not null but invalid
     * 
     * @access public
     */
    function getIndexes($idx = null)
    {
        // by default, return all index definitions
        if (is_null($idx)) {
            return $this->idx;
        }
        
        // if the param is a string, only return the index definition
        // named by the that string
        if (is_string($idx)) {
            if (isset($this->idx[$idx])) {
                return $this->idx[$idx];
            } else {
                return false;
            }
        }
        
        // if the param is a sequential array of index names,
        // return only those indexes named in that array
        if (is_array($idx)) {
            $set = array();
            foreach ($idx as $name) {
                $set[$name] = $this->getIndexes($name);
            }
            
            if (count($set) == 0) {
                return false;
            } else {
                return $set;
            }
        }
        
        // param was not null, string, or array
        return false;
    }
    
    
    /**
     * Connect or disconnect a DB_Table_Database instance to this table
     * instance.
     * 
     * Used to re-connect this DB_Table object to a parent DB_Table_Database
     * object during unserialization. Can also disconnect if the $database 
     * parameter is null. Use the DB_Table_Database::addTable method instead 
     * to add a table to a new DB_Table_Database.
     * 
     * @param object &$database DB_Table_Database instance that this table
     *               belongs to (or null to disconnect from instance).
     * 
     * @return void
     * @access public
     */
    function setDatabaseInstance(&$database)
    {
        if (is_a($database, 'DB_Table_Database')) {
            $this->_database =& $database;
        } elseif (is_null($database)) {
            $this->_database = null;
        }
    }

    
    /**
     * Inserts a single table row.
     *
     * Inserts data from associative array $data, in which keys are column
     * names and values are column values. All required columns (except an
     * auto-increment column) must be included in the data array. Columns
     * values that are not set or null are inserted as SQL NULL values. 
     *
     * If an auto-increment column is declared (by setting $this->auto_inc_col),
     * and the value of that column in $data is not set or null, then a new
     * sequence value will be generated and inserted.
     *
     * If auto-recasting is enabled (if $this->_auto_recast), the method will
     * try, if necessary to recast $data to proper column types, with recast().
     *
     * If auto-validation is enabled (if $this->_valid_insert), the method
     * will validates column types with validInsert() before insertion.
     *
     * @access public
     * 
     * @param array $data An associative array of key-value pairs where
     * the key is the column name and the value is the column value. 
     * This is the data that will be inserted into the table.  
     * 
     * @return mixed Void on success (PEAR_Error on failure)
     *
     * @throws PEAR_Error if:
     *     - Error in auto_inc_col declaration (DB_TABLE_ERR_AUTO_INC_COL)
     *     - Error returned by DB/MDB2::autoExecute() (Error bubbled up)
     *
     * @see validInsert()
     * @see DB::autoExecute()
     * @see MDB2::autoExecute()
     */
    function insert($data)
    {
        // Auto-increment if enabled and input value is null or not set
        if ($this->_auto_inc 
            && !is_null($this->auto_inc_col) 
            && !isset($data[$this->auto_inc_col]) 
           ) {
            $column = $this->auto_inc_col;
            // check that the auto-increment column exists
            if (!array_key_exists($column, $this->col)) {
                return $this->throwError(
                        DB_TABLE_ERR_AUTO_INC_COL,
                        ": $column does not exist");
            }
            // check that the column is integer 
            if (!in_array($this->col[$column]['type'],
                           array('integer','smallint','bigint'))) {
                return $this->throwError(
                        DB_TABLE_ERR_AUTO_INC_COL,
                        ": $column is not an integer");
            }
            // check that the column is required
            // Note: The insert method will replace a null input value 
            // of $data[$column] with a sequence value. This makes 
            // the column effectively 'not null'. This column must be
            // 'required' for consistency, to make this explicit.
            if (!$this->isRequired($column)) {
                return $this->throwError(
                        DB_TABLE_ERR_AUTO_INC_COL,
                        ": $column is not required");
            }
            // set the value
            $id = $this->nextID();
            if (PEAR::isError($id)) {
                return $id;
            }
            $data[$column] = $id;
        }

        // forcibly recast the data elements to their proper types?
        if ($this->_auto_recast) {
            $this->recast($data);
        }

        // validate the data if auto-validation is turned on
        if ($this->_valid_insert) {
            $result = $this->validInsert($data);
            if (PEAR::isError($result)) {
                return $result;
            }
        }

        // Does a parent DB_Table_Database object exist?
        if ($this->_database) {

            $_database = $this->_database;
  
            // Validate foreign key values (if enabled)
            if ($_database->_check_fkey) {
               $result = $_database->validForeignKeys($this->table, $data);
               if (PEAR::isError($result)) {
                   return $result;
               }
            }
    
        }
       
        // Do insertion
        if ($this->backend == 'mdb2') {
            $result = $this->db->extended->autoExecute($this->table, $data,
                MDB2_AUTOQUERY_INSERT);
        } else {
            $result = $this->db->autoExecute($this->table, $data,
                DB_AUTOQUERY_INSERT);
        }
        return $result;
    }
    
    
    /**
     * Turns on or off auto-incrementing of $auto_inc_col column (if any)
     * 
     * For auto-incrementing to work, an $auto_inc_col column must be declared,
     * auto-incrementing must be enabled (by this method), and the value of
     * the $auto_inc_col column must be not set or null in the $data passed to
     * the insert method. 
     * 
     * @param  bool $flag True to turn on auto-increment, false to turn off.
     * @return void
     * @access public
     */
    function setAutoInc($flag = true)
    {
        if ($flag) {
            $this->_auto_inc = true;
        } else {
            $this->_auto_inc = false;
        }
    }
    
    
    /**
     * Turns on (or off) automatic validation of inserted data.
     * 
     * Enables (if $flag is true) or disables (if $flag is false) automatic 
     * validation of data types prior to actual insertion into the database 
     * by the DB_Table::insert() method.
     *
     * @param  bool $flag True to turn on auto-validation, false to turn off.
     * @return void
     * @access public
     */
    function autoValidInsert($flag = true)
    {
        if ($flag) {
            $this->_valid_insert = true;
        } else {
            $this->_valid_insert = false;
        }
    }
    
    
    /**
     * Validates an array for insertion into the table.
     * 
     * @param array $data An associative array of key-value pairs where
     * the key is the column name and the value is the column value.  This
     * is the data that will be inserted into the table.  Data is checked
     * against the column data type for validity.
     * 
     * @return boolean true on success (PEAR_Error on failure)
     *
     * @throws PEAR_Error if:
     *     - Invalid column name key in $data (DB_TABLE_ERR_INS_COL_NOMAP)
     *     - Missing required column value    (DB_TABLE_ERR_INS_COL_NOMAP)
     *     - Column value doesn't match type  (DB_TABLE_ERR_INS_DATA_INVALID)
     *
     * @access public
     * 
     * @see insert()
     */
    function validInsert(&$data)
    {
        // loop through the data, and disallow insertion of unmapped
        // columns
        foreach ($data as $col => $val) {
            if (! isset($this->col[$col])) {
                return $this->throwError(
                    DB_TABLE_ERR_INS_COL_NOMAP,
                    "('$col')"
                );
            }
        }
        
        // loop through each column mapping, and check the data to be
        // inserted into it against the column data type. we loop through
        // column mappings instead of the insert data to make sure that
        // all necessary columns are being inserted.
        foreach ($this->col as $col => $val) {
            
            // is the value allowed to be null?
            if (isset($val['require']) &&
                $val['require'] == true &&
                (! isset($data[$col]) || is_null($data[$col]))) {
                return $this->throwError(
                    DB_TABLE_ERR_INS_COL_REQUIRED,
                    "'$col'"
                );
            }
            
            // does the value to be inserted match the column data type?
            if (isset($data[$col]) &&
                ! $this->isValid($data[$col], $col)) {
                return $this->throwError(
                    DB_TABLE_ERR_INS_DATA_INVALID,
                    "'$col' ('$data[$col]')"
                );
            }
        }
        
        return true;
    }
    
    
    /**
     * Update table row or rows that match a custom WHERE clause
     *
     * Constructs and submits an SQL UPDATE command to update columns whose
     * names are keys in the $data array parameter, in all rows that match
     * the logical condition given by the $where string parameter.
     * 
     * If auto-recasting is enabled (if $this->_auto_recast), update() will
     * try, if necessary, to recast $data to proper column types, with recast().
     *
     * If auto-validation is enabled (if $this->_valid_insert), update() 
     * validates column types with validUpdate() before insertion.
     *
     * @param array $data An associative array of key-value pairs where the
     * key is the column name and the value is the column value. These are
     * the columns that will be updated with new values.
     * 
     * @param string $where An SQL WHERE clause limiting which records are
     * are to be updated.
     * 
     * @return mixed Void on success, a PEAR_Error object on failure.
     *
     * @throws PEAR_Error if:
     *     - Data fails type validation (bubbles error returned by validUpdate)
     *     - Error thrown by DB/MDB2::autoexecute()
     *
     * @access public
     * 
     * @see validUpdate()
     * @see DB::autoExecute()
     * @see MDB2::autoExecute()
     */
    function update($data, $where)
    {
        // forcibly recast the data elements to their proper types?
        if ($this->_auto_recast) {
            $this->recast($data);
        }
        
        // validate the data if auto-validation is turned on
        if ($this->_valid_update) {
            $result = $this->validUpdate($data);
            if (PEAR::isError($result)) {
                return $result;
            }
        }

        // Does a parent DB_Table_Database object exist?
        if ($this->_database) {
  
            $_database =& $this->_database;

            // Validate foreign key values (if enabled)
            if ($_database->_check_fkey) {
               $result = $_database->validForeignKeys($this->table, $data);
               if (PEAR::isError($result)) {
                   return $result;
               } 
            }
    
            // Implement any relevant ON UPDATE actions
            $result = $_database->onUpdateAction($this, $data, $where);
            if (PEAR::isError($result)) {
                return $result;
            }

        }
       
        // Submit update command 
        if ($this->backend == 'mdb2') {
            $result = $this->db->extended->autoExecute($this->table, $data,
                MDB2_AUTOQUERY_UPDATE, $where);
        } else {
            $result = $this->db->autoExecute($this->table, $data,
                DB_AUTOQUERY_UPDATE, $where);
        }
        return $result;

    }
    
    
    /**
     * Turns on (or off) automatic validation of updated data.
     * 
     * Enables (if $flag is true) or disables (if $flag is false) automatic 
     * validation of data types prior to updating rows in the database by
     * the {@link update()} method.
     *
     * @param  bool $flag True to turn on auto-validation, false to turn off.
     * @return void
     * @access public
     */
    function autoValidUpdate($flag = true)
    {
        if ($flag) {
            $this->_valid_update = true;
        } else {
            $this->_valid_update = false;
        }
    }
    
    
    /**
     * Validates an array for updating the table.
     * 
     * @param array $data An associative array of key-value pairs where
     * the key is the column name and the value is the column value.  This
     * is the data that will be inserted into the table.  Data is checked
     * against the column data type for validity.
     * 
     * @return mixed Boolean true on success (PEAR_Error object on failure)
     *
     * @throws PEAR_Error if
     *     - Invalid column name key in $data (DB_TABLE_ERR_UPD_COL_NOMAP)
     *     - Missing required column value    (DB_TABLE_ERR_UPD_COL_NOMAP)
     *     - Column value doesn't match type  (DB_TABLE_ERR_UPD_DATA_INVALID)
     *
     * @access public
     * 
     * @see update()
     */
    function validUpdate(&$data)
    {
        // loop through each data element, and check the
        // data to be updated against the column data type.
        foreach ($data as $col => $val) {
            
            // does the column exist?
            if (! isset($this->col[$col])) {
                return $this->throwError(
                    DB_TABLE_ERR_UPD_COL_NOMAP,
                    "('$col')"
                );
            }
            
            // the column definition
            $defn = $this->col[$col];
            
            // is it allowed to be null?
            if (isset($defn['require']) &&
                $defn['require'] == true &&
                isset($data[$col]) &&
                is_null($data[$col])) {
                return $this->throwError(
                    DB_TABLE_ERR_UPD_COL_REQUIRED,
                    $col
                );
            }
            
            // does the value to be inserted match the column data type?
            if (! $this->isValid($data[$col], $col)) {
                return $this->throwError(
                    DB_TABLE_ERR_UPD_DATA_INVALID,
                    "$col ('$data[$col]')"
                );
            }
        }
        
        return true;
    }
    
    
    /**
     * Deletes table rows matching a custom WHERE clause.
     * 
     * Constructs and submits and SQL DELETE command with the specified WHERE 
     * clause. Command is submitted by DB::query() or MDB2::exec().
     *
     * If a reference to a DB_Table_Database instance exists, carry out any
     * ON DELETE actions declared in that instance before actual insertion, 
     * if emulation of ON DELETE actions is enabled in that instance.
     *
     * @param string $where Logical condition in the WHERE clause of the 
     *                      delete command.
     *
     * @return mixed void on success (PEAR_Error on failure)
     *
     * @throws PEAR_Error if
     *     DB::query() or MDB2::exec() returns error (bubbles up)
     *
     * @access public
     * 
     * @see DB::query()
     * @see MDB2::exec()
     */
    function delete($where)
    {
        // Does a parent DB_Table_Database object exist?
        if ($this->_database) {
  
            $_database =& $this->_database;

            // Implement any relevant ON DELETE actions
            $result = $_database->onDeleteAction($this, $where);
            if (PEAR::isError($result)) {
                return $result;
            }

        }
       
        if ($this->backend == 'mdb2') {
            $result = $this->db->exec("DELETE FROM $this->table WHERE $where");
        } else {
            $result = $this->db->query("DELETE FROM $this->table WHERE $where");
        }
        return $result;
    }
    
    
    /**
     *
     * Generates and returns a sequence value.
     *
     * Generates a sequence value by calling the DB or MDB2::nextID() method. The
     * sequence name defaults to the table name, or may be specified explicitly.
     * 
     * @param  string  $seq_name The sequence name; defaults to table_id.
     * 
     * @return integer The next value in the sequence (PEAR_Error on failure)
     *
     * @throws PEAR_Error if
     *     Sequence name too long (>26 char + _seq) (DB_TABLE_ERR_SEQ_STRLEN)
     *
     * @access public
     * 
     * @see DB::nextID()
     * @see MDB2::nextID()
     */
    function nextID($seq_name = null)
    {
        if (is_null($seq_name)) {
            $seq_name = "{$this->table}";
        } else {
            $seq_name = "{$this->table}_{$seq_name}";
        }
        
        // the maximum length is 30, but PEAR DB/MDB2 will add "_seq" to the
        // name, so the max length here is less 4 chars. we have to
        // check here because the sequence will be created automatically
        // by PEAR DB/MDB2, which will not check for length on its own.
        if (   $GLOBALS['_DB_TABLE']['disable_length_check'] === false
            && strlen($seq_name) > 26
           ) {
            return DB_Table::throwError(
                DB_TABLE_ERR_SEQ_STRLEN,
                " ('$seq_name')"
            );
            
        }
        return $this->db->nextId($seq_name);
    }
    
    
    /**
     * Escapes and enquotes a value for use in an SQL query.
     * 
     * Simple wrapper for DB_Common::quoteSmart() or MDB2::quote(), which 
     * returns the value of one of these functions. Helps makes user input 
     * safe against SQL injection attack.
     * 
     * @param mixed $val The value to be quoted
     *
     * @return string The value with quotes escaped, inside single quotes if 
     *                non-numeric.
     *
     * @throws PEAR_Error if
     *     DB_Common::quoteSmart() or MDB2::quote() returns Error (bubbled up)
     * 
     * @access public
     * 
     * @see DB_Common::quoteSmart()
     * @see MDB2::quote()
     */
    function quote($val)
    {
        if ($this->backend == 'mdb2') {
            $val = $this->db->quote($val);
        } else {
            $val = $this->db->quoteSmart($val);
        }
        return $val;
    }
    
    
    /**
     * Returns a blank row array based on the column map.
     * 
     * The array keys are the column names, and all values are set to null.
     * 
     * @return array An associative array where keys are column names and
     *               all values are null.
     * @access public
     */
    function getBlankRow()
    {
        $row = array();
        
        foreach ($this->col as $key => $val) {
            $row[$key] = null;
        }
        
        $this->recast($row);
        
        return $row;
    }
    
    
    /**
     * Turns on (or off) automatic recasting of insert and update data.
     * 
     * Turns on (if $flag is true) or off (if $flag is false) automatic forcible 
     * recasting of data to the declared data type, if required, prior to inserting 
     * or updating.  The recasting is done by calling the DB_Table::recast() 
     * method from within the DB_Table::insert() and DB_Table::update().
     * 
     * @param bool $flag True to automatically recast insert and update data,
     *                   false to not do so.
     * @return void
     * @access public
     */
    function autoRecast($flag = true)
    {
        if ($flag) {
            $this->_auto_recast = true;
        } else {
            $this->_auto_recast = false;
        }
    }
    
    
    /**
     * Forces array elements to the proper types for their columns.
     * 
     * This will not valiate the data, and will forcibly change the data
     * to match the recast-type.
     * 
     * The date, time, and timestamp recasting has special logic for
     * arrays coming from an HTML_QuickForm object so that the arrays
     * are converted into properly-formatted strings.
     * 
     * @todo If a column key holds an array of values (say from a multiple
     * select) then this method will not work properly; it will recast the
     * value to the string 'Array'.  Is this bad?
     * 
     * @param array   &$data The data array to re-cast.
     * 
     * @return void
     * 
     * @access public
     */
    function recast(&$data)
    {
        $keys = array_keys($data);
        
        $null_if_blank = array('date', 'time', 'timestamp', 'smallint',
            'integer', 'bigint', 'decimal', 'single', 'double');
        
        foreach ($keys as $key) {
        
            if (! isset($this->col[$key])) {
                continue;
            }
            
            unset($val);
            $val =& $data[$key];
            
            // convert blanks to null for non-character field types
            $convert = in_array($this->col[$key]['type'], $null_if_blank);
            if (is_array($val)) {  // if one of the given array values is
                                   // empty, null will be the new value if
                                   // the field is not required
                $tmp_val = implode('', $val);
                foreach ($val as $array_val) {
                    if (trim((string) $array_val) == '') {
                        $tmp_val = '';
                        break;
                    }
                }
            } else {
                $tmp_val = $val;
            }
            if ($convert && trim((string) $tmp_val) == '' && (
                !isset($this->col[$key]['require']) ||
                $this->col[$key]['require'] === false
              )
            ) {
                $val = null;
            }
            
            // skip explicit NULL values
            if (is_null($val)) {
                continue;
            }
            
            // otherwise, recast to the column type
            switch ($this->col[$key]['type']) {
            
            case 'boolean':
                $val = ($val) ? 1 : 0;
                break;
                
            case 'char':
            case 'varchar':
            case 'clob':
                settype($val, 'string');
                break;
                
            case 'date':

                // smart handling of non-standard (i.e. Y-m-d) date formats,
                // this allows to use two-digit years (y) and short (M) or
                // long (F) names of months without having to recast the
                // date value yourself
                if (is_array($val)) {
                    if (isset($val['y'])) {
                        $val['Y'] = $val['y'];
                    }
                    if (isset($val['F'])) {
                        $val['m'] = $val['F'];
                    }
                    if (isset($val['M'])) {
                        $val['m'] = $val['M'];
                    }
                }

                if (is_array($val) &&
                    isset($val['Y']) &&
                    isset($val['m']) &&
                    isset($val['d'])) {
                    
                    // the date is in HTML_QuickForm format,
                    // convert into a string
                    $y = (strlen($val['Y']) < 4)
                        ? str_pad($val['Y'], 4, '0', STR_PAD_LEFT)
                        : $val['Y'];
                    
                    $m = (strlen($val['m']) < 2)
                        ? '0'.$val['m'] : $val['m'];
                        
                    $d = (strlen($val['d']) < 2)
                        ? '0'.$val['d'] : $val['d'];
                        
                    $val = "$y-$m-$d";
                    
                } else {
                
                    // convert using the Date class
                    $tmp = new DB_Table_Date($val);
                    $val = $tmp->format('%Y-%m-%d');
                    
                }
                
                break;
            
            case 'time':
            
                if (is_array($val) &&
                    isset($val['H']) &&
                    isset($val['i']) &&
                    isset($val['s'])) {
                    
                    // the time is in HTML_QuickForm format,
                    // convert into a string
                    $h = (strlen($val['H']) < 2)
                        ? '0' . $val['H'] : $val['H'];
                    
                    $i = (strlen($val['i']) < 2)
                        ? '0' . $val['i'] : $val['i'];
                        
                    $s = (strlen($val['s']) < 2)
                        ? '0' . $val['s'] : $val['s'];
                        
                        
                    $val = "$h:$i:$s";
                    
                } else {
                    // date does not matter in this case, so
                    // pre 1970 and post 2040 are not an issue.
                    $tmp = strtotime(date('Y-m-d') . " $val");
                    $val = date('H:i:s', $tmp);
                }
                
                break;
                
            case 'timestamp':

                // smart handling of non-standard (i.e. Y-m-d) date formats,
                // this allows to use two-digit years (y) and short (M) or
                // long (F) names of months without having to recast the
                // date value yourself
                if (is_array($val)) {
                    if (isset($val['y'])) {
                        $val['Y'] = $val['y'];
                    }
                    if (isset($val['F'])) {
                        $val['m'] = $val['F'];
                    }
                    if (isset($val['M'])) {
                        $val['m'] = $val['M'];
                    }
                }

                if (is_array($val) &&
                    isset($val['Y']) &&
                    isset($val['m']) &&
                    isset($val['d']) &&
                    isset($val['H']) &&
                    isset($val['i']) &&
                    isset($val['s'])) {
                    
                    // timestamp is in HTML_QuickForm format,
                    // convert each element to a string. pad
                    // with zeroes as needed.
                
                    $y = (strlen($val['Y']) < 4)
                        ? str_pad($val['Y'], 4, '0', STR_PAD_LEFT)
                        : $val['Y'];
                    
                    $m = (strlen($val['m']) < 2)
                        ? '0'.$val['m'] : $val['m'];
                        
                    $d = (strlen($val['d']) < 2)
                        ? '0'.$val['d'] : $val['d'];
                        
                    $h = (strlen($val['H']) < 2)
                        ? '0' . $val['H'] : $val['H'];
                    
                    $i = (strlen($val['i']) < 2)
                        ? '0' . $val['i'] : $val['i'];
                        
                    $s = (strlen($val['s']) < 2)
                        ? '0' . $val['s'] : $val['s'];
                        
                    $val = "$y-$m-$d $h:$i:$s";
                    
                } else {
                    // convert using the Date class
                    $tmp = new DB_Table_Date($val);
                    $val = $tmp->format('%Y-%m-%d %H:%M:%S');
                }
                
                break;
            
            case 'smallint':
            case 'integer':
            case 'bigint':
                settype($val, 'integer');
                break;
            
            case 'decimal':
            case 'single':
            case 'double':
                settype($val, 'float');
                break;

            }
        }
    }
    
    
    /**
     * Creates the table based on $this->col and $this->idx.
     * 
     * @param string $flag The automatic table creation mode to pursue:
     * - 'safe' to create the table only if it does not exist
     * - 'drop' to drop any existing table with the same name and re-create it
     * 
     * @return mixed Boolean true if the table was successfully created,
     *               false if there was no need to create the table, or
     *               a PEAR_Error if the attempted creation failed.
     *
     * @throws PEAR_Error if
     *     - DB_Table_Manager::tableExists() returns Error (bubbles up)
     *     - DB_Table_Manager::create() returns Error (bubbles up)
     * 
     * @access public
     * 
     * @see DB_Table_Manager::tableExists()
     * @see DB_Table_Manager::create()
     */
    function create($flag)
    {
        include_once 'DB/Table/Manager.php';

        // are we OK to create the table?
        $ok = false;
        
        // check the create-flag
        switch ($flag) {

            case 'drop':
                // drop only if table exists
                $table_exists = DB_Table_Manager::tableExists($this->db,
                                                              $this->table);
                if (PEAR::isError($table_exists)) {
                    return $table_exists;
                }
                if ($table_exists) {
                    // forcibly drop an existing table
                    if ($this->backend == 'mdb2') {
                        $this->db->manager->dropTable($this->table);
                    } else {
                        $this->db->query("DROP TABLE {$this->table}");
                    }
                }
                $ok = true;
                break;

            case 'safe':
                // create only if table does not exist
                $table_exists = DB_Table_Manager::tableExists($this->db,
                                                              $this->table);
                if (PEAR::isError($table_exists)) {
                    return $table_exists;
                }
                // ok to create only if table does not exist
                $ok = !$table_exists;
                break;

        }

        // are we going to create the table?
        if (! $ok) {
            return false;
        }

        return DB_Table_Manager::create(
            $this->db, $this->table, $this->col, $this->idx
        );
    }
    
    
    /**
     * Alters the table to match schema declared in $this->col and $this->idx.
     *
     * If the table does not exist, create it instead.
     * 
     * @return boolean true if altering is successful (PEAR_Error on failure)
     *
     * @throws PEAR_Error if
     *     - DB_Table_Manager::tableExists() returns Error (bubbles up)
     *     - DB_Table_Manager::create() returns Error (bubbles up)
     *     - DB_Table_Manager::alter() returns Error (bubbles up)
     *
     * @access public
     *
     * @see DB_Table_Manager::tableExists()
     * @see DB_Table_Manager::create()
     * @see DB_Table_Manager::alter()
     */
    function alter()
    {
        $create = false;
        
        // alter the table columns and indexes if the table exists
        $table_exists = DB_Table_Manager::tableExists($this->db,
                                                      $this->table);
        if (PEAR::isError($table_exists)) {
            return $table_exists;
        }
        if (!$table_exists) {
            // table does not exist => just create the table, there is
            // nothing that could be altered
            $create = true;
        }

        if ($create) {
            return DB_Table_Manager::create(
                $this->db, $this->table, $this->col, $this->idx
            );
        }

        return DB_Table_Manager::alter(
            $this->db, $this->table, $this->col, $this->idx
        );
    }
    
    
    /**
     * Verifies the table based on $this->col and $this->idx.
     * 
     * @return boolean true if verification succees (PEAR_Error on failure).
     *
     * @throws PEAR_Error if
     *     DB_Table_Manager::verify() returns Error (bubbles up)
     *
     * @access public
     * 
     * @see DB_Table_Manager::verify()
     */
    function verify()
    {
        return DB_Table_Manager::verify(
            $this->db, $this->table, $this->col, $this->idx
        );
    }
    
    
    /**
     * Checks if a value validates against the DB_Table data type for a
     * given column. This only checks that it matches the data type; it
     * does not do extended validation.
     * 
     * @param array $val A value to check against the column's DB_Table
     * data type.
     * 
     * @param array $col A column name from $this->col.
     * 
     * @return boolean True if $val validates against data type, false if not
     *
     * @throws PEAR_Error if
     *     Invalid column type in $this->col (DB_TABLE_ERR_VALIDATE_TYPE)
     *
     * @access public
     * 
     * @see DB_Table_Valid
     */
    function isValid($val, $col)
    {
        // is the value null?
        if (is_null($val)) {
            // is the column required?
            if ($this->isRequired($col)) {
                // yes, so not valid
                return false;
            } else {
                // not required, so it's valid
                return true;
            }
        }
        
        // make sure we have the validation class
        include_once 'DB/Table/Valid.php';
        
        // validate values per the column type.  we use sqlite
        // as the single authentic list of allowed column types,
        // regardless of the actual rdbms being used.
        $map = array_keys($GLOBALS['_DB_TABLE']['type']['sqlite']);
        
        // is the column type on the map?
        if (! in_array($this->col[$col]['type'], $map)) {
            return $this->throwError(
                DB_TABLE_ERR_VALIDATE_TYPE,
                "'$col' ('{$this->col[$col]['type']}')"
            );
        }
        
        // validate for the type
        switch ($this->col[$col]['type']) {
        
        case 'char':
        case 'varchar':
            $result = DB_Table_Valid::isChar(
                $val,
                $this->col[$col]['size']
            );
            break;
        
        case 'decimal':
            $result = DB_Table_Valid::isDecimal(
                $val,
                $this->col[$col]['size'],
                $this->col[$col]['scope']
            );
            break;
            
        default:
            $result = call_user_func(
                array(
                    'DB_Table_Valid',
                    'is' . ucwords($this->col[$col]['type'])
                ),
                $val
            );
            break;

        }
        
        // have we passed the check so far, and should we
        // also check for allowed values?
        if ($result && isset($this->col[$col]['qf_vals'])) {
            $keys = array_keys($this->col[$col]['qf_vals']);
            
            $result = in_array(
                $val,
                array_keys($this->col[$col]['qf_vals'])
            );
        }
        
        return $result;
    }
    
    
    /**
     * Is a specific column required to be set and non-null?
     * 
     * @param mixed $column The column to check against.
     * @return boolean      True if required, false if not.
     * @access public
     */
    function isRequired($column)
    {
        if (isset($this->col[$column]['require']) &&
            $this->col[$column]['require'] == true) {
            return true;
        } else {
            return false;
        }
    }
    
    
    /**
     * 
     * Creates and returns a QuickForm object based on table columns.
     *
     * @param array $columns A sequential array of column names to use in
     * the form; if null, uses all columns.
     *
     * @param string $array_name By default, the form will use the names
     * of the columns as the names of the form elements.  If you pass
     * $array_name, the column names will become keys in an array named
     * for this parameter.
     * 
     * @param array $args An associative array of optional arguments to
     * pass to the QuickForm object.  The keys are...
     *
     * 'formName' : String, name of the form; defaults to the name of this
     * table.
     * 
     * 'method' : String, form method; defaults to 'post'.
     * 
     * 'action' : String, form action; defaults to
     * $_SERVER['REQUEST_URI'].
     * 
     * 'target' : String, form target target; defaults to '_self'
     * 
     * 'attributes' : Associative array, extra attributes for <form>
     * tag; the key is the attribute name and the value is attribute
     * value.
     * 
     * 'trackSubmit' : Boolean, whether to track if the form was
     * submitted by adding a special hidden field
     * 
     * @param string $clientValidate By default, validation will match
     * the 'qf_client' value from the column definition.  However, if
     * you set $clientValidate to true or false, this will override the
     * value from the column definition.
     *
     * @param array $formFilters An array with filter function names or
     * callbacks that will be applied to all form elements.
     *
     * @return object HTML_QuickForm
     * 
     * @access public
     *
     * @see HTML_QuickForm
     * @see DB_Table_QuickForm
     */
    function &getForm($columns = null, $array_name = null, $args = array(),
        $clientValidate = null, $formFilters = null)
    {
        include_once 'DB/Table/QuickForm.php';
        $coldefs = $this->_getFormColDefs($columns);
        $form =& DB_Table_QuickForm::getForm($coldefs, $array_name, $args,
            $clientValidate, $formFilters);
        return $form;
    }
    
    
    /**
     * Adds elements and rules to a pre-existing HTML_QuickForm object.
     * 
     * By default, the form will use the names of the columns as the names 
     * of the form elements.  If you pass $array_name, the column names 
     * will become keys in an array named for this parameter.
     *
     * @param object &$form      An HTML_QuickForm object.
     * 
     * @param array $columns     A sequential array of column names to use in
     *                           the form; if null, uses all columns.
     *
     * @param string $array_name Name of array of column names
     *
     * @param clientValidate
     * 
     * @return void
     * 
     * @access public
     * 
     * @see HTML_QuickForm
     * 
     * @see DB_Table_QuickForm
     */
    function addFormElements(&$form, $columns = null, $array_name = null,
        $clientValidate = null)
    {
        include_once 'DB/Table/QuickForm.php';
        $coldefs = $this->_getFormColDefs($columns);
        DB_Table_QuickForm::addElements($form, $coldefs, $array_name);
        DB_Table_QuickForm::addRules($form, $coldefs, $array_name, 
           $clientValidate);
    }


    /**
     * Adds static form elements like 'header', 'static', 'submit' or 'reset' 
     * to a pre-existing HTML_QuickForm object. The form elements needs to be
     * defined in a property called $frm.
     * 
     * @param object &$form An HTML_QuickForm object.
     * @return void
     * @access public
     * 
     * @see HTML_QuickForm
     * @see DB_Table_QuickForm
     */
    function addStaticFormElements(&$form)
    {
        include_once 'DB/Table/QuickForm.php';
        DB_Table_QuickForm::addStaticElements($form, $this->frm);
    }


    /**
     * 
     * Creates and returns an array of QuickForm elements based on an
     * array of DB_Table column names.
     * 
     * @param array $columns A sequential array of column names to use in
     * the form; if null, uses all columns.
     * 
     * @param string $array_name By default, the form will use the names
     * of the columns as the names of the form elements.  If you pass
     * $array_name, the column names will become keys in an array named
     * for this parameter.
     * 
     * @return array An array of HTML_QuickForm_Element objects.
     * 
     * @access public
     * 
     * @see HTML_QuickForm
     * @see DB_Table_QuickForm
     */
    function &getFormGroup($columns = null, $array_name = null)
    {
        include_once 'DB/Table/QuickForm.php';
        $coldefs = $this->_getFormColDefs($columns);
        $group = DB_Table_QuickForm::getGroup($coldefs, $array_name);
        return $group;
    }
    
    
    /**
     * Creates and returns a single QuickForm element based on a DB_Table
     * column name.
     * 
     * @param string $column   A DB_Table column name.
     * @param string $elemname The name to use for the generated QuickForm
     *                         element.
     * 
     * @return object HTML_QuickForm_Element
     * 
     * @access public
     * 
     * @see HTML_QuickForm
     * @see DB_Table_QuickForm
     */
    
    function &getFormElement($column, $elemname)
    {
        include_once 'DB/Table/QuickForm.php';
        $coldef = $this->_getFormColDefs($column);
        DB_Table_QuickForm::fixColDef($coldef[$column], $elemname);
        $element =& DB_Table_QuickForm::getElement($coldef[$column],
            $elemname);
        return $element;
    }

    /**
     * Creates and returns an array of QuickForm elements based on a DB_Table
     * column name.
     * 
     * @author Ian Eure <ieure@php.net>
     * 
     * @param array $cols        Array of DB_Table column names
     * @param string $array_name The name to use for the generated QuickForm
     *                           elements.
     * @return object HTML_QuickForm_Element
     * 
     * @access public
     * 
     * @see HTML_QuickForm
     * @see DB_Table_QuickForm
     */
    function &getFormElements($cols, $array_name = null)
    {
        include_once 'DB/Table/QuickForm.php';
        $elements = DB_Table_QuickForm::getElements($cols, $array_name);
        return $elements;
    }
    
    
    /**
     * Creates a column definition array suitable for DB_Table_QuickForm.
     * 
     * @param string|array $column_set A string column name, a sequential
     * array of columns names, or an associative array where the key is a
     * column name and the value is the default value for the generated
     * form element.  If null, uses all columns for this class.
     * 
     * @return array An array of column defintions suitable for passing
     *               to DB_Table_QuickForm.
     *
     * @access public
     * 
     */
    function _getFormColDefs($column_set = null)
    {
        if (is_null($column_set)) {
            // no columns or columns+values; just return the $this->col
            // array.
            return $this->getColumns($column_set);
        }
        
        // check to see if the keys are sequential integers.  if so,
        // the $column_set is just a list of columns.
        settype($column_set, 'array');
        $keys = array_keys($column_set);
        $all_integer = true;
        foreach ($keys as $val) {
            if (! is_integer($val)) {
                $all_integer = false;
                break;
            }
        }
        
        if ($all_integer) {
        
            // the column_set is just a list of columns; get back the $this->col
            // array elements matching this list.
            $coldefs = $this->getColumns($column_set);
            
        } else {
            
            // the columns_set is an associative array where the key is a
            // column name and the value is the form element value.
            $coldefs = $this->getColumns($keys);
            foreach ($coldefs as $key => $val) {
                $coldefs[$key]['qf_setvalue'] = $column_set[$key];
            }
            
        }
        
        return $coldefs;
    }

    /**
     * Returns XML string representation of the table
     *
     * @param  string $indent string of whitespace
     * @return string XML string
     * @access public
     */
    function toXML($indent = '') {
        require_once 'DB/Table/XML.php';
        $s = array();
        $s[] = DB_Table_XML::openTag('table', $indent);
        $s[] = DB_Table_XML::lineElement('name', $this->table, $indent);
        $s[] = DB_Table_XML::openTag('declaration', $indent);
        // Column declarations
        foreach ($this->col as $name => $col) {
            $type     = (isset($col['type'])) ? $col['type'] : null;
            $size     = (isset($col['size'])) ? $col['size'] : null;
            $scope    = (isset($col['scope'])) ? $col['scope'] : null;
            $require  = (isset($col['require'])) ? $col['require'] : null;
            $default  = (isset($col['set default'])) ? $col['set default'] : null;
            $line = '   ' . $name . '  ' . $type;
            $s[] = DB_Table_XML::openTag('field', $indent);
            $s[] = DB_Table_XML::lineElement('name', $name, $indent);
            $s[] = DB_Table_XML::lineElement('type', $type, $indent);
            if ($size) {
                $s[] = DB_Table_XML::lineElement('length', $size, $indent);
            }
            if ($require) {
                $require = (int) $require;
                $s[] = DB_Table_XML::lineElement('notnull', $require, $indent);
            }
            if (!($default === null)) {
               $s[] = DB_Table_XML::lineElement('set default', $default, $indent);
            }
            if ($this->auto_inc_col == $name) {
               $s[] = DB_Table_XML::lineElement('autoincrement', '1', $indent);
            }
            $s[] = DB_Table_XML::closeTag('field', $indent);
        }
        // Index declarations
        foreach ($this->idx as $name => $idx) {
            $s[] = DB_Table_XML::openTag('index', $indent);
            $cols = $idx['cols'];
            $type = $idx['type'];
            if (is_string($name)) {
                $s[] = DB_Table_XML::lineElement('name', $name, $indent);
            }
            if ($type == 'primary') {
                $s[] = DB_Table_XML::lineElement('primary', '1', $indent);
            } elseif ($type == 'unique') {
                $s[] = DB_Table_XML::lineElement('unique', '1', $indent);
            }
            if (is_string($cols)) {
                $cols = array($cols);
            }
            foreach ($cols as $col) {
                $s[] = DB_Table_XML::lineElement('field', $col, $indent);
            }
            $s[] = DB_Table_XML::closeTag('index', $indent);
        }
        // Foreign key references (if $this->_database is not null)
        if ($this->_database) {
            if (isset($this->_database->_ref[$this->table])) {
                $refs = $this->_database->_ref[$this->table];
                foreach ($refs as $rtable => $def) {
                    $fkey = $def['fkey']; // foreign key of referencing table
                    $rkey = $def['rkey']; // referenced/primary key
                    if (is_string($fkey)) {
                        $fkey = array($fkey);
                    }
                    if (is_string($rkey)) {
                        $rkey = array($rkey);
                    }
                    $on_delete = $def['on_delete']; // on-delete action
                    $on_update = $def['on_update']; // on-update action
                    $s[] = DB_Table_XML::openTag('foreign', $indent);
                    foreach ($fkey as $fcol) {
                        $s[] = DB_Table_XML::lineElement('field', $fcol, $indent);
                    }
                    $s[] = DB_Table_XML::openTag('references', $indent);
                    $s[] = DB_Table_XML::lineElement('table', $rtable, $indent);
                    if ($rkey) {
                        foreach ($rkey as $rcol) {
                            $s[] = DB_Table_XML::lineElement('field', $rcol,
                                                             $indent);
                        }
                    }
                    $s[] = DB_Table_XML::closeTag('references', $indent);
                    if ($on_delete) {
                        $s[] = DB_Table_XML::lineElement('ondelete', $on_delete,
                                                         $indent);
                    }
                    if ($on_update) {
                        $s[] = DB_Table_XML::lineElement('onupdate', $on_update,
                                                         $indent);
                    }
                    $s[] = DB_Table_XML::closeTag('foreign', $indent);
                }
            }
        }
        $s[] = DB_Table_XML::closeTag('declaration', $indent);
        $s[] = DB_Table_XML::closeTag('table', $indent);
        return implode("\n", $s);
    }

}
?>
