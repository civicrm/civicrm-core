<?php

// vim: set et ts=4 sw=4 fdm=marker:

/**
 * DB_Table_Database relational database abstraction class
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
 * @author   David C. Morse <morse@php.net>
 * @license  http://opensource.org/licenses/bsd-license.php New BSD License
 * @version  CVS: $Id: Database.php,v 1.15 2007/12/13 16:52:14 wiesemann Exp $
 * @link     http://pear.php.net/package/DB_Table
 */

// {{{ Error code constants

/**
 * Parameter is not a DB/MDB2 object
 */
define('DB_TABLE_DATABASE_ERR_DB_OBJECT', -201);

/**
 * Error in addTable, parameter $table_obj is not a DB_Table object
 */
define('DB_TABLE_DATABASE_ERR_DBTABLE_OBJECT', -202);

/**
 * Error for table name that does not exist in the database
 */
define('DB_TABLE_DATABASE_ERR_NO_TBL', -203);

/**
 * Error for table name parameter that is not a string
 */
define('DB_TABLE_DATABASE_ERR_TBL_NOT_STRING', -204);

/**
 * Error in getCol for a non-existent column name
 */
define('DB_TABLE_DATABASE_ERR_NO_COL', -205);

/**
 * Error in getForeignCol for a non-existent foreign key column
 */
define('DB_TABLE_DATABASE_ERR_NO_FOREIGN_COL', -206);

/**
 * Error for column name that is not a string
 */
define('DB_TABLE_DATABASE_ERR_COL_NOT_STRING', -207);

/**
 * Error in addTable for multiple primary keys
 */
define('DB_TABLE_DATABASE_ERR_MULT_PKEY', -208);

/**
 * Error in addRef for a non-existent foreign key table
 */
define('DB_TABLE_DATABASE_ERR_NO_FTABLE', -209);

/**
 * Error in addRef for non-existence referenced table
 */
define('DB_TABLE_DATABASE_ERR_NO_RTABLE', -210);

/**
 * Error in addRef for null referenced key in a table with no primary key
 */
define('DB_TABLE_DATABASE_ERR_NO_PKEY', -211);

/**
 * Error in addRef for an invalid foreign key, neither string nor array
 */
define('DB_TABLE_DATABASE_ERR_FKEY', -212);

/**
 * Error in addRef for referenced key that is not a string, string foreign key
 */
define('DB_TABLE_DATABASE_ERR_RKEY_NOT_STRING', -213);

/**
 * Error in addRef for referenced key that is not an array, array foreign key
 */
define('DB_TABLE_DATABASE_ERR_RKEY_NOT_ARRAY', -214);

/**
 * Error in addRef for wrong number of columns in referenced key
 */
define('DB_TABLE_DATABASE_ERR_RKEY_COL_NUMBER', -215);

/**
 * Error in addRef for non-existence foreign key (referencing) column
 */
define('DB_TABLE_DATABASE_ERR_NO_FCOL', -216);

/**
 * Error in addRef for non-existence referenced column
 */
define('DB_TABLE_DATABASE_ERR_NO_RCOL', -217);

/**
 * Error in addRef for referencing and referenced columns of different types
 */
define('DB_TABLE_DATABASE_ERR_REF_TYPE', -218);

/**
 * Error in addRef for multiple references from one table to another
 */
define('DB_TABLE_DATABASE_ERR_MULT_REF', -219);

/**
 * Error due to invalid ON DELETE action name
 */
define('DB_TABLE_DATABASE_ERR_ON_DELETE_ACTION', -220);

/**
 * Error due to invalid ON UPDATE action name
 */
define('DB_TABLE_DATABASE_ERR_ON_UPDATE_ACTION', -221);

/**
 * Error in addLink due to missing required reference
 */
define('DB_TABLE_DATABASE_ERR_NO_REF_LINK', -222);

/**
 * Error in validCol for a column name that does not exist in the datase
 */
define('DB_TABLE_DATABASE_ERR_NO_COL_DB', -223);

/**
 * Error in validCol for column name that does not exist in the specified table
 */
define('DB_TABLE_DATABASE_ERR_NO_COL_TBL', -224);

/**
 * Error in a buildSQL or select* method for an undefined key of $this->sql
 */
define('DB_TABLE_DATABASE_ERR_SQL_UNDEF', -225);

/**
 * Error in a buildSQL or select* method for a key of $this->sql that is 
 * not a string
 */
define('DB_TABLE_DATABASE_ERR_SQL_NOT_STRING', -226);

/**
 * Error in buildFilter due to invalid match type 
 */
define('DB_TABLE_DATABASE_ERR_MATCH_TYPE', -227);

/**
 * Error in buildFilter due to invalid key for full match
 */
define('DB_TABLE_DATABASE_ERR_DATA_KEY', -228);

/**
 * Error in buildFilter due to invalid key for full match
 */
define('DB_TABLE_DATABASE_ERR_FILT_KEY', -229);

/**
 * Error in buildFilter due to invalid key for full match
 */
define('DB_TABLE_DATABASE_ERR_FULL_KEY', -230);

/**
 * Error in insert for a failed foreign key constraint
 */
define('DB_TABLE_DATABASE_ERR_FKEY_CONSTRAINT', -231);

/**
 * Error in delete due to a referentially triggered 'restrict' action
 */
define('DB_TABLE_DATABASE_ERR_RESTRICT_DELETE', -232);

/**
 * Error in update due to a referentially triggered 'restrict' action
 */
define('DB_TABLE_DATABASE_ERR_RESTRICT_UPDATE', -233);

/**
 * Error in fromXML for table with multiple auto_increment columns
 */
define('DB_TABLE_DATABASE_ERR_XML_MULT_AUTO_INC', -234);

/**
 * Error in autoJoin, column and tables parameter both null
 */
define('DB_TABLE_DATABASE_ERR_NO_COL_NO_TBL', -235);

/**
 * Error in autoJoin for ambiguous column name
 */
define('DB_TABLE_DATABASE_ERR_COL_NOT_UNIQUE', -236);

/**
 * Error in autoJoin for non-unique set of join conditions
 */
define('DB_TABLE_DATABASE_ERR_AMBIG_JOIN', -237);

/**
 * Error in autoJoin for failed construction of join 
 */
define('DB_TABLE_DATABASE_ERR_FAIL_JOIN', -238);

/**
 * Error in fromXML for PHP 4 (this function requires PHP 5)
 */
define('DB_TABLE_DATABASE_ERR_PHP_VERSION', -239);

/**
 * Error parsing XML string in fromXML
 */
define('DB_TABLE_DATABASE_ERR_XML_PARSE', -240);

// }}}
// {{{ Includes

/**
 * DB_Table_Base base class
 */
require_once 'DB/Table/Base.php';

/**
 * DB_Table table abstraction class
 */
require_once 'DB/Table.php';

/**
 * The PEAR class for errors
 */
require_once 'PEAR.php';

// }}}
// {{{ Error messages

/** 
 * US-English default error messages. If you want to internationalize, you can
 * set the translated messages via $GLOBALS['_DB_TABLE_DATABASE']['error']. 
 * You can also use DB_Table_Database::setErrorMessage(). Examples:
 * 
 * <code>
 * (1) $GLOBALS['_DB_TABLE_DATABASE']['error'] = array(
 *                                           DB_TABLE_DATABASE_ERR_.. => '...',
 *                                           DB_TABLE_DATABASE_ERR_.. => '...');
 * (2) DB_Table_Database::setErrorMessage(DB_TABLE_DATABASE_ERR_.., '...');
 *     DB_Table_Database::setErrorMessage(DB_TABLE_DATABASE_ERR_.., '...');
 * (3) DB_Table_Database::setErrorMessage(array(
 *                                        DB_TABLE_DATABASE_ERR_.. => '...');
 *                                        DB_TABLE_DATABASE_ERR_.. => '...');
 * (4) $obj =& new DB_Table();
 *     $obj->setErrorMessage(DB_TABLE_DATABASE_ERR_.., '...');
 *     $obj->setErrorMessage(DB_TABLE_DATABASE_ERR_.., '...');
 * (5) $obj =& new DB_Table();
 *     $obj->setErrorMessage(array(DB_TABLE_DATABASE_ERR_.. => '...');
 *                                 DB_TABLE_DATABASE_ERR_.. => '...');
 * </code>
 * 
 * For errors that can occur with-in the constructor call (i.e. e.g. creating
 * or altering the database table), only the code from examples (1) to (3)
 * will alter the default error messages early enough. For errors that can
 * occur later, examples (4) and (5) are also valid.
 */
$GLOBALS['_DB_TABLE_DATABASE']['default_error'] = array(
        DB_TABLE_DATABASE_ERR_DB_OBJECT =>
        'Invalid DB/MDB2 object parameter. Function',
        DB_TABLE_DATABASE_ERR_NO_TBL =>
        'Table does not exist in database. Method, Table =',
        DB_TABLE_DATABASE_ERR_TBL_NOT_STRING =>
        'Table name parameter is not a string in method',
        DB_TABLE_DATABASE_ERR_NO_COL =>
        'In getCol, non-existent column name parameter',
        DB_TABLE_DATABASE_ERR_NO_FOREIGN_COL =>
        'In getForeignCol, non-existent column name parameter',
        DB_TABLE_DATABASE_ERR_COL_NOT_STRING =>
        'Column name parameter is not a string in method',
        DB_TABLE_DATABASE_ERR_DBTABLE_OBJECT =>
        'Parameter of addTable is not a DB_Table object',
        DB_TABLE_DATABASE_ERR_MULT_PKEY =>
        'Multiple primary keys in one table detected in addTable. Table',
        DB_TABLE_DATABASE_ERR_NO_FTABLE =>
        'Foreign key reference from non-existent table in addRef. Reference',
        DB_TABLE_DATABASE_ERR_NO_RTABLE =>
        'Reference to a non-existent referenced table in addRef. Reference',
        DB_TABLE_DATABASE_ERR_NO_PKEY =>
        'Missing primary key of referenced table in addRef. Reference',
        DB_TABLE_DATABASE_ERR_FKEY =>
        'Foreign / referencing key is not a string or array in addRef',
        DB_TABLE_DATABASE_ERR_RKEY_NOT_STRING =>
        'Foreign key is a string, referenced key is not a string in addRef',
        DB_TABLE_DATABASE_ERR_RKEY_NOT_ARRAY =>
        'Foreign key is an array, referenced key is not an array in addRef',
        DB_TABLE_DATABASE_ERR_RKEY_COL_NUMBER =>
        'Wrong number of columns in referencing key in addRef',
        DB_TABLE_DATABASE_ERR_NO_FCOL =>
        'Nonexistent foreign / referencing key column in addRef. Reference',
        DB_TABLE_DATABASE_ERR_NO_RCOL =>
        'Nonexistent referenced key column in addRef. Reference',
        DB_TABLE_DATABASE_ERR_REF_TYPE =>
        'Different referencing and referenced column types in addRef. Reference',
        DB_TABLE_DATABASE_ERR_MULT_REF =>
        'Multiple references between two tables in addRef. Reference',
        DB_TABLE_DATABASE_ERR_ON_DELETE_ACTION =>
        'Invalid ON DELETE action. Reference',
        DB_TABLE_DATABASE_ERR_ON_UPDATE_ACTION =>
        'Invalid ON UPDATE action. Reference',
        DB_TABLE_DATABASE_ERR_NO_REF_LINK =>
        'Error in addLink due to missing required reference(s)',
        DB_TABLE_DATABASE_ERR_NO_COL_DB =>
        'In validCol, column name does not exist in database. Column',
        DB_TABLE_DATABASE_ERR_NO_COL_TBL =>
        'In validCol, column does not exist in specified table. Column',
        DB_TABLE_DATABASE_ERR_SQL_UNDEF =>
        'Query string is not a key of $sql property array. Key is',
        DB_TABLE_DATABASE_ERR_SQL_NOT_STRING =>
        'Query is neither an array nor a string',
        DB_TABLE_DATABASE_ERR_MATCH_TYPE =>
        'Invalid match parameter of buildFilter',
        DB_TABLE_DATABASE_ERR_DATA_KEY =>
        'Invalid data_key in buildFilter, neither string nor array',
        DB_TABLE_DATABASE_ERR_FILT_KEY =>
        'Incompatible data_key and filter_key in buildFilter',
        DB_TABLE_DATABASE_ERR_FULL_KEY =>
        'Invalid key value in buildFilter: Mixed null and not null',
        DB_TABLE_DATABASE_ERR_FKEY_CONSTRAINT =>
        'Foreign key constraint failure: Key does not reference any rows',
        DB_TABLE_DATABASE_ERR_RESTRICT_DELETE =>
        'Referentially trigger restrict of delete from table',
        DB_TABLE_DATABASE_ERR_RESTRICT_UPDATE =>
        'Referentially trigger restrict of update of table',
        DB_TABLE_DATABASE_ERR_NO_COL_NO_TBL =>
        'No columns or tables provided as parameters to autoJoin',
        DB_TABLE_DATABASE_ERR_COL_NOT_UNIQUE =>
        'Ambiguous column name in autoJoin. Column',
        DB_TABLE_DATABASE_ERR_AMBIG_JOIN =>
        'Ambiguous join in autoJoin, during join of table',
        DB_TABLE_DATABASE_ERR_FAIL_JOIN =>
        'Failed join in autoJoin, failed to join table',
        DB_TABLE_DATABASE_ERR_PHP_VERSION =>
        'PHP 5 is required for fromXML method. Interpreter version is',
        DB_TABLE_DATABASE_ERR_XML_PARSE =>
        'Error parsing XML in fromXML method'
    );

// merge default and user-defined error messages
if (!isset($GLOBALS['_DB_TABLE_DATABASE']['error'])) {
    $GLOBALS['_DB_TABLE_DATABASE']['error'] = array();
}
foreach ($GLOBALS['_DB_TABLE_DATABASE']['default_error'] as $code => $message) {
    if (!array_key_exists($code, $GLOBALS['_DB_TABLE_DATABASE']['error'])) {
        $GLOBALS['_DB_TABLE_DATABASE']['error'][$code] = $message;
    }
}

// }}}
// {{{ DB_Table_Database

/**
 * Relational database abstraction class
 *
 * DB_Table_Database is an abstraction class for a relational database.
 * It is a layer built on top of DB_Table, in which each table in a
 * database is represented as an instance of DB_Table. It provides: 
 *
 *   - an object-oriented representation of the database schema
 *   - automated construction of SQL commands for simple joins
 *   - an API for insert, update, and select commands very similar
 *     to that of DB_Table, with optional emulation of standard SQL
 *     foreign key integrity checks and referential triggered actions
 *     such as cascading deletes.
 *   - Serialization and unserialization of the database schema via 
 *     either php serialization or XML, using the MDB2 XML schema. 
 *
 * @category Database
 * @package  DB_Table
 * @author   David C. Morse <morse@php.net>
 * @version  Release: 1.5.6
 * @link     http://pear.php.net/package/DB_Table
 */
class DB_Table_Database extends DB_Table_Base
{

    // {{{ properties

    /**
     * Name of the database
     *
     * @var    string
     * @access public
     */
    var $name   = null;

    /**
     * Associative array of DB_Table object references. Keys are table names.
     *
     * Associative array in which keys are table names, values are references to
     * DB_Table objects.  Each referenced DB_Table object represents one table in
     * the database.
     *
     * @var    array
     * @access private
     */
    var $_table = array();

    /**
     * Array in which keys are table names, values are DB_Table subclass names.
     *
     * See the getTableSubclass() method docblock for further details. 
     * 
     * @var    array
     * @access private
     */
    var $_table_subclass = array();

    /**
     * Path to directory containing DB_Table subclass declaration files
     *
     * See the setTableSubclassPath() method docblock for further details. 
     * 
     * @var    string
     * @access private
     */
    var $_table_subclass_path = '';

    /**
     * Array in which keys are table names, values are primary keys.
     *
     * Each primary key value may be a column name string, a sequential array of
     * column name strings, or null. 
     *
     * See the getPrimaryKey() method docblock for details. 
     *
     * @var    array
     * @access private
     */
    var $_primary_key = array();

    /**
     * Associative array that maps column names keys to table names.
     *
     * Each key is the name string of a column in the database. Each value
     * is a numerical array containing the names of all tables that contain 
     * a column with that name. 
     *
     * See the getCol() method docblock for details.
     *
     * @var    array
     * @access private
     */
    var $_col = array();

    /**
     * Associative array that maps names of foreign key columns to table names
     *
     * Each key is the name string of a foreign key column. Each value is a
     * sequential array containing the names of all tables that contain a 
     * foreign key column with that name. 
     *
     * See the getForeignCol() method docblock for further details. 
     *
     * @var    array
     * @access private
     */
    var $_foreign_col = array();

    /**
     * Two-dimensional associative array of foreign key references. 
     *
     * Keys are pairs of table names (referencing table first, referenced
     * table second). Each value is an array containing information about 
     * the referencing and referenced keys, and about any referentially 
     * triggered actions (e.g., cascading delete). 
     *
     * See the getRef() docblock for further details. 
     *
     * @var    array
     * @access private
     */
    var $_ref = array();

    /**
     * Array in which each key is the names of a referenced tables, each value 
     * an sequential array containing names of referencing tables.
     *
     * See the docblock for the getRefTo() method for further discussion. 
     * 
     * @var    array
     * @access private
     */
    var $_ref_to = array();

    /**
     * Two-dimensional associative array of linking tables. 
     *
     * Two-dimensional associative array in which pairs of keys are names
     * of pairs of tables that are linked by one or more linking/association 
     * table. Each value is an array containing the names of all table that
     * link the tables specified by the pair of keys. A linking table is a 
     * table that creates a many-to-many relationship between two linked
     * tables, via foreign key references from the linking table to the two
     * linked tables. The $_link property is used by the autoJoin() method 
     * to join tables that are related only through such a linking table. 
     * 
     * See the getLink() method docblock for further details. 
     *
     * @var    array
     * @access private
     */
    var $_link = array();

    /**
     * Take on_update actions if $_act_on_update is true
     *
     * By default, on_update actions are enabled ($_act_on_update = true)
     *
     * @var    boolean
     * @access private
     */
    var $_act_on_update = true;

    /**
     * Take on_delete actions if $_act_on_delete is true
     *
     * By default, on_delete actions are enabled ($_act_on_delete = true)
     *
     * @var    boolean
     * @access private
     */
    var $_act_on_delete = true;

    /**
     * Validate foreign keys before insert or update if $_check_fkey is true
     *
     * By default, validation is disabled ($_check_fkey = false)
     *
     * @var    boolean
     * @access private
     */
    var $_check_fkey = false;

    /**
     * If the column keys in associative array return sets are fixed case
     * (all upper or lower case) this property should be set true. 
     *
     * The column keys in rows of associative array return sets may either 
     * preserve capitalization of the column names or they may be fixed case,
     * depending on the options set in the backend (DB/MDB2) and on phptype.
     * If these column names are returned with a fixed case (either upper 
     * or lower), $_fix_case must be set true in order for php emulation of
     * ON DELETE and ON UPDATE actions to work correctly. Otherwise, the
     * $_fix_case property should be false (the default).
     *
     * The choice between mixed or fixed case column keys may be made by using
     * using the setFixCase() method, which resets both the behavior of the
     * backend and the $_fix_case property. It may also be changed by using the 
     * setOption() method of the DB or MDB2 backend object to directly set the 
     * DB_PORTABILITY_LOWERCASE or MDB2_PORTABILITY_FIX_CASE bits of the 
     * DB/MDB2 'portability' option.
     *
     * By default, DB returns mixed case and MDB2 returns lower case. 
     * 
     * @see DB_Table_Database::setFixCase()
     * @see DB::setOption()
     * @see MDB2::setOption()
     *
     * @var    boolean
     * @access private
     */
    var $_fix_case = false;

    // }}}
    // {{{ Methods

    // {{{ function DB_Table_Database(&$db, $name)

    /**
     * Constructor
     *
     * If an error is encountered during instantiation, the error
     * message is stored in the $this->error property of the resulting
     * object. See $error property docblock for a discussion of error
     * handling. 
     * 
     * @param  object &$db   DB/MDB2 database connection object
     * @param  string $name the database name
     * @return object DB_Table_Database
     * @access public
     */
    function DB_Table_Database(&$db, $name)
    {
        // Is $db an DB/MDB2 object or null?
        if (is_a($db, 'db_common')) {
            $this->backend = 'db';
            $this->fetchmode = DB_FETCHMODE_ORDERED;
        } elseif (is_a($db, 'mdb2_driver_common')) {
            $this->backend = 'mdb2';
            $this->fetchmode = MDB2_FETCHMODE_ORDERED;
        } else {
            $code = DB_TABLE_DATABASE_ERR_DB_OBJECT ;
            $text = $GLOBALS['_DB_TABLE_DATABASE']['error'][$code]
                  . ' DB_Table_Database';
            $this->error = PEAR::throwError($text, $code);
            return;
        }
        $this->db  =& $db;
        $this->name = $name;

        $this->_primary_subclass = 'DB_TABLE_DATABASE';
        $this->setFixCase(false);
    }

    // }}}
    // {{{ function setDBconnection(&$db)

    /**
     * Set DB/MDB2 connection instance for database and all tables
     *
     * Assign a reference to the DB/MDB2 object $db to $this->db, set
     * $this->backend to 'db' or 'mdb2', and set the same pair of 
     * values for the $db and $backend properties of every DB_Table
     * object in the database.  
     *
     * @param  object  &$db DB/MDB2 connection object
     * @return boolean True on success (PEAR_Error on failure)
     *
     * @throws PEAR_Error if 
     *     $db is not a DB or MDB2 object(DB_TABLE_DATABASE_ERR_DB_OBJECT)
     *
     * @access public
     */
    function setDBconnection(&$db)
    {
        // Is the first argument a DB/MDB2 object ?
        if (is_subclass_of($db, 'DB_Common')) {
            $backend = 'db';
        } elseif (is_subclass_of($db, 'MDB2_Driver_Common')) {
            $backend = 'mdb2';
        } else {
            return $this->throwError(
                      DB_TABLE_DATABASE_ERR_DB_OBJECT,
                      "setDBconnection");
        }

        // Set db and backend for database and all of its tables
        $this->db =& $db;
        $this->backend = $backend;
        foreach ($this->_table as $name => $table) {
            $table->db =& $db;
            $table->backend = $backend;
        }
        return true;
    }

    // }}}
    // {{{ function setActOnDelete($flag = true)

    /**
     * Turns on (or off) automatic php emulation of SQL ON DELETE actions
     *
     * @param  bool $flag True to enable action, false to disable
     * @return void
     * @access public
     */
    function setActOnDelete($flag = true)
    {
        if ($flag) {
            $this->_act_on_delete = true;
        } else {
            $this->_act_on_delete = false;
        }
    }
    
    // }}}
    // {{{ function setActOnUpdate($flag = true)

    /**
     * Turns on (or off) automatic php emulation of ON UPDATE actions
     *
     * @param  bool $flag True to enable action, false to disable
     * @return void
     * @access public
     */
    function setActOnUpdate($flag = true)
    {
        if ($flag) {
            $this->_act_on_update = true;
        } else {
            $this->_act_on_update = false;
        }
    }
    
    // }}}
    // {{{ function setCheckFKey($flag = true)

    /**
     * Turns on (or off) validation of foreign key values on insert and update
     *
     * @param  bool $flag True to enable foreign key validation, false to disable
     * @return void
     * @access public
     */
    function setCheckFKey($flag = true)
    {
        if ($flag) {
            $this->_check_fkey = true;
        } else {
            $this->_check_fkey = false;
        }
    }

    // }}}
    // {{{ function setFixCase($flag = false) 

    /**
     * Sets backend option such that column keys in associative array return
     * sets are converted to fixed case, if true, or mixed case, if false.
     * 
     * Sets the DB/MDB2 'portability' option, and sets $this->_fix_case = $flag.
     * Because it sets an option in the underlying DB/MDB2 connection object, 
     * this effects the behavior of all objects that share the connection.
     * 
     * @param  bool $flag True for fixed lower case, false for mixed
     * @return void
     * @access public
     */
    function setFixCase($flag = false) 
    {
        $flag = (bool) $flag;
        $option = $this->db->getOption('portability');
        if ($this->backend == 'db') {
            $option = $option | DB_PORTABILITY_LOWERCASE;
            if (!$flag) {
                $option = $option ^ DB_PORTABILITY_LOWERCASE;
            }
        } else {
            $option = $option | MDB2_PORTABILITY_FIX_CASE;
            if (!$flag) {
                $option = $option ^ MDB2_PORTABILITY_FIX_CASE;
            }
        } 
        $this->db->setOption('portability', $option);
        $this->_fix_case = $flag;
    }
    
    // }}}
    // {{{ function &getDBInstance() 

    /**
     * Return reference to $this->db DB/MDB2 object wrapped by $this
     *
     * @return object Reference to DB/MDB2 object
     * @access public
     */
    function &getDBInstance() 
    {
        return $this->db;
    }

    // }}}
    // {{{ function getTable($name = null) 

    /**
     * Returns all or part of $_table property array
     *
     * If $name is absent or null, return entire $_table property array.
     * If $name is a table name, return $this->_table[$name] DB_Table object
     * reference
     *
     * The $_table property is an associative array in which keys are table
     * name strings and values are references to DB_Table objects. Each of 
     * the referenced objects represents one table in the database.
     *
     * @param  string $name Name of table
     * @return mixed  $_table property, or one element of $_table 
     *                (PEAR_Error on failure)
     *
     * @throws PEAR_Error if:
     *    - $name is not a string ( DB_TABLE_DATABASE_ERR_TBL_NOT_STRING )
     *    - $name is not valid table name ( DB_TABLE_DATABASE_ERR_NO_TBL )
     * 
     * @access public
     */
    function getTable($name = null) 
    {
        if (is_null($name)) {
            return $this->_table;
        } elseif (is_string($name)) {
            if (isset($this->_table[$name])) {
                return $this->_table[$name];
            } else {
                return $this->throwError(
                          DB_TABLE_DATABASE_ERR_NO_TBL,
                          "getTable, $name");
            }
        } else {
            return $this->throwError(
                      DB_TABLE_DATABASE_ERR_TBL_NOT_STRING,
                      "getTable");
        }
    }

    // }}}
    // {{{ function getPrimaryKey($name = null) 

    /**
     * Returns all or part of the $_primary_key property array
     *
     * If $name is null, return the $this->_primary_key property array
     * If $name is a table name, return $this->_primary_key[$name]
     *
     * The $_primary_key property is an associative array in which each key
     * a table name, and each value is the primary key of that table. Each
     * primary key value may be a column name string, a sequential array of 
     * column name strings (for a multi-column key), or null (if no primary
     * key has been declared).
     *
     * @param  string $name Name of table
     * @return mixed  $this->primary_key array or $this->_primary_key[$name]
     *                (PEAR_Error on failure)
     *
     * @throws PEAR_Error if:
     *    - $name is not a string ( DB_TABLE_DATABASE_ERR_TBL_NOT_STRING )
     *    - $name is not valid table name ( DB_TABLE_DATABASE_ERR_NO_TBL )
     * 
     * @access public
     */
    function getPrimaryKey($name = null) 
    {
        if (is_null($name)) {
            return $this->_primary_key;
        } elseif (is_string($name)) {
            if (isset($this->_primary_key[$name])) {
                return $this->_primary_key[$name];
            } else {
                return $this->throwError(
                          DB_TABLE_DATABASE_ERR_NO_TBL,
                          "getPrimaryKey, $name");
            }
        } else {
            return $this->throwError(
                      DB_TABLE_DATABASE_ERR_TBL_NOT_STRING,
                      "getPrimaryKey");
        }
    }

    // }}}
    // {{{ function getTableSubclass($name = null) 

    /**
     * Returns all or part of the $_table_subclass property array
     *
     * If $name is null, return the $this->_table_subclass property array
     * If $name is a table name, return $this->_table_subclass[$name]
     *
     * The $_table_subclass property is an associative array in which each key
     * is a table name string, and each value is the name of the corresponding 
     * subclass of DB_Table. The value is null if the table is an instance of 
     * DB_Table itself. 
     *
     * Subclass names are set within the addTable method by applying the 
     * built in get_class() function to a DB_Table object. The class names 
     * returned by get_class() are stored unmodified. In PHP 4, get_class
     * converts all class names to lower case. In PHP 5, it preserves the 
     * capitalization of the name used in the class definition. 
     *
     * For autoloading of class definitions to work properly in the 
     * __wakeup() method, the base name of each subclass definition
     * file (excluding the .php extension) should thus be a identical
     * to the class name in PHP 5, and a lower case version of the 
     * class name in PHP 4 or 
     * 
     * @param  string $name Name of table
     * @return mixed  $_table_subclass array or $this->_table_subclass[$name]
     *                (PEAR_Error on failure)
     *
     * @throws PEAR_Error if:
     *    - $name is not a string ( DB_TABLE_DATABASE_TBL_NOT_STRING )
     *    - $name is not valid table name ( DB_TABLE_DATABASE_NO_TBL )
     *
     * @access public
     * 
     @ @see DB_Table_Database::__wakeup()
     */
    function getTableSubclass($name = null) 
    {
        if (is_null($name)) {
            return $this->_table_subclass;
        } elseif (is_string($name)) {
            if (isset($this->_table_subclass[$name])) {
                return $this->_table_subclass[$name];
            } else {
                return $this->throwError(
                          DB_TABLE_DATABASE_ERR_NO_TBL,
                          "getTableSubclass, $name");
            }
        } else {
            return $this->throwError(
                      DB_TABLE_DATABASE_ERR_TBL_NOT_STRING,
                      "getTableSubclass");
        }
    }

    // }}}
    // {{{ function getCol($column_name = null) 

    /**
     * Returns all or part of the $_col property array
     *
     * If $column_name is null, return $_col property array
     * If $column_name is valid, return $_col[$column_name] subarray
     *
     * The $_col property is an associative array in which each key is the
     * name of a column in the database, and each value is a numerical array 
     * containing the names of all tables that contain a column with that 
     * name.
     *
     * @param string $column_name a column name string
     * @return mixed $this->_col property array or $this->_col[$column_name]
     *               (PEAR_Error on failure)
     *
     * @throws PEAR_Error if:
     *    - $column_name is not a string (DB_TABLE_DATABASE_ERR_COL_NOT_STRING)
     *    - $column_name is not valid column name (DB_TABLE_DATABASE_NO_COL)
     *
     * @access public
     */
    function getCol($column_name = null) 
    {
        if (is_null($column_name)) {
            return $this->_col;
        } elseif (is_string($column_name)) {
            if (isset($this->_col[$column_name])) {
                return $this->_col[$column_name];
            } else {
                return $this->throwError(
                          DB_TABLE_DATABASE_ERR_NO_COL,
                          "'$column_name'");
            }
        } else {
            return $this->throwError(
                       DB_TABLE_DATABASE_ERR_COL_NOT_STRING,
                       'getCol');
        }
    }

    // }}}
    // {{{ function getForeignCol($column_name = null) 

    /**
     * Returns all or part of the $_foreign_col property array
     *
     * If $column_name is null, return $this->_foreign_col property array
     * If $column_name is valid, return $this->_foreign_col[$column_name] 
     *
     * The $_foreign_col property is an associative array in which each 
     * key is the name string of a foreign key column, and each value is a
     * sequential array containing the names of all tables that contain a 
     * foreign key column with that name. 
     *
     * If a column $column in a referencing table $ftable is part of the 
     * foreign key for references to two or more different referenced tables
     * tables, the name $ftable will also appear multiple times in the array 
     * $this->_foreign_col[$column].
     *
     * Returns a PEAR_Error with the following DB_TABLE_DATABASE_* error
     * codes if:
     *    - $column_name is not a string ( _COL_NOT_STRING )
     *    - $column_name is not valid foreign column name ( _NO_FOREIGN_COL )
     *
     * @param  string column name string for foreign key column
     * @return array  $_foreign_col property array
     * @access public
     */
    function getForeignCol($column_name = null) 
    {
        if (is_null($column_name)) {
            return $this->_foreign_col;
        } elseif (is_string($column_name)) {
            if (isset($this->_foreign_col[$column_name])) {
                return $this->_foreign_col[$column_name];
            } else {
                return $this->throwError(
                          DB_TABLE_DATABASE_ERR_NO_FOREIGN_COL,
                          $column_name);
            }
        } else {
            return $this->throwError(
                      DB_TABLE_DATABASE_ERR_COL_NOT_STRING,
                      'getForeignCol');
        }
    }

    // }}}
    // {{{ function getRef($table1 = null, $table2 = null) 

    /**
     * Returns all or part of the $_ref two-dimensional property array
     *
     * Returns $this->_ref 2D property array if $table1 and $table2 are null.
     * Returns $this->_ref[$table1] subarray if only $table2 is null.
     * Returns $this->_ref[$table1][$table2] if both parameters are present.
     *
     * Returns null if $table1 is a table that references no others, or 
     * if $table1 and $table2 are both valid table names, but there is no 
     * reference from $table1 to $table2.
     * 
     * The $_ref property is a two-dimensional associative array in which
     * the keys are pairs of table names, each value is an array containing 
     * information about referenced and referencing keys, and referentially
     * triggered actions (if any).  An element of the $_ref array is of the 
     * form $ref[$ftable][$rtable] = $reference, where $ftable is the name 
     * of a referencing (or foreign key) table and $rtable is the name of 
     * a corresponding referenced table. The value $reference is an array 
     * $reference = array($fkey, $rkey, $on_delete, $on_update) in which
     * $fkey and $rkey are the foreign (or referencing) and referenced 
     * keys, respectively: Foreign key $fkey of table $ftable references
     * key $rkey of table $rtable. The values of $fkey and $rkey must either 
     * both be valid column name strings for columns of the same type, or 
     * they may both be sequential arrays of column name names, with equal 
     * numbers of columns of corresponding types, for multi-column keys. The 
     * $on_delete and $on_update values may be either null or string values 
     * that indicate actions to be taken upon deletion or updating of a 
     * referenced row (e.g., cascading deletes). A null value of $on_delete
     * or $on_update indicates that no referentially triggered action will 
     * be taken. See addRef() for further details about allowed values of
     * these action strings. 
     *
     * @param  string $table1 name of referencing table
     * @param  string $table2 name of referenced table
     * @return mixed $ref property array, sub-array, or value
     * 
     * @throws a PEAR_Error if:
     *    - $table1 or $table2 is not a string (.._DATABASE_ERR_TBL_NOT_STRING)
     *    - $table1 or $table2 is not a table name (.._DATABASE_ERR_NO_TBL)
     *
     * @access public
     */
    function getRef($table1 = null, $table2 = null) 
    {
        if (is_null($table1)) {
            return $this->_ref;
        } elseif (is_string($table1)) {
            if (isset($this->_ref[$table1])) {
                if (is_null($table2)) {
                    return $this->_ref[$table1];
                } elseif (is_string($table2)) {
                    if (isset($this->_ref[$table1][$table2])) {
                        return $this->_ref[$table1][$table2];
                    } else {
                        if (isset($this->_table[$table2])) {
                            // Valid table names but no references to
                            return null;
                        } else {
                            // Invalid table name
                            return $this->throwError(
                                      DB_TABLE_DATABASE_ERR_NO_TBL,
                                      "getRef, $table2");
                        }
                    }
                } else {
                    return $this->throwError(
                              DB_TABLE_DATABASE_ERR_TBL_NOT_STRING,
                              "getRef");
                }
            } else {
                if (isset($this->_table[$table1])) {
                    // Valid table name, but no references from
                    return null;
                } else {
                    // Invalid table name
                    return $this->throwError(
                              DB_TABLE_DATABASE_ERR_NO_TBL,
                              "getRef, $table1");
                }
            }
        } else {
            return $this->throwError(
                       DB_TABLE_DATABASE_ERR_TBL_NOT_STRING,
                       "getRef");
        }

    }

    // }}}
    // {{{ function getRefTo($table_name = null)

    /**
     * Returns all or part of the $_ref_to property array
     *
     * Returns $this->_ref_to property array if $table_name is null.
     * Returns $this->_ref_to[$table_name] if $table_name is not null.
     *
     * The $_ref_to property is an associative array in which each key
     * is the name of a referenced table, and each value is a sequential
     * array containing the names of all tables that contain foreign keys
     * that reference that table. Each element is thus of the form
     * $_ref_to[$rtable] = array($ftable1, $ftable2,...), where
     * $ftable1, $ftable2, ... are the names of tables that reference 
     * the table named $rtable.
     *
     * @param string $table_name name of table
     * @return mixed $_ref_to property array or subarray 
     *               (PEAR_Error on failure)
     * 
     * @throws PEAR_Error if:
     *    - $table_name is not a string ( .._DATABASE_ERR_TBL_NOT_STRING )
     *    - $table_name is not a table name ( .._DATABASE_ERR_NO_TBL )
     *
     * @access public
     */
    function getRefTo($table_name = null)
    {
        if (is_null($table_name)) {
            return $this->_ref_to;
        } elseif (is_string($table_name)) {
            if (isset($this->_ref_to[$table_name])) {
                return $this->_ref_to[$table_name];
            } else {
                if (isset($this->_table[$table_name])) {
                    // Valid table name, but no references to
                    return null;
                } else {
                    // Invalid table name
                    return $this->throwError(
                              DB_TABLE_DATABASE_ERR_NO_TBL,
                              "getRefTo, $table_name");
                }
            }
        } else {
            return $this->throwError(
                      DB_TABLE_DATABASE_ERR_TBL_NOT_STRING,
                      "getRefTo");
        }
    }

    // }}}
    // {{{ function getLink($table1 = null, $table2 = null) 

    /**
     * Returns all or part of the $link two-dimensional property array
     *
     * Returns $this->_link 2D property array if $table1 and $table2 are null.
     * Returns $this->_link[$table1] subarray if only $table2 is null.
     * Returns $this->_link[$table1][$table2] if both parameters are present.
     *
     * Returns null if $table1 is a valid table with links to no others, or 
     * if $table1 and $table2 are both valid table names but there is no 
     * link between them.
     * 
     * The $_link property is a two-dimensional associative array with 
     * elements of the form $this->_link[$table1][$table2] = array($link1, ...), 
     * in which the value is an array containing the names of all tables 
     * that `link' tables named $table1 and $table2, and thereby create a
     * many-to-many relationship between these two tables. 
     *
     * The $_link property is used in the autoJoin method to join tables
     * that are related by a many-to-many relationship via a linking table,
     * rather than via a direct foreign key reference. A table that is
     * declared to be linking table for tables $table1 and $table2 must 
     * contain foreign keys that reference both of these tables. 
     *
     * Each binary link in a database is listed twice in $_link, in
     * $_link[$table1][$table2] and in $_link[$table2][$table1]. If a
     * linking table contains foreign key references to N tables, with
     * N > 2, each of the resulting binary links is listed separately.
     * For example, a table with references to 3 tables A, B, and C can 
     * create three binary links (AB, AC, and BC) and six entries in the 
     * link property array (i.e., in $_link[A][B], $_link[B][A], ... ).
     *
     * Linking tables may be added to the $_link property by using the 
     * addLink method or deleted using the delLink method. Alternatively, 
     * all possible linking tables can be identified and added to the 
     * $_link array at once by the addAllLinks() method.
     *
     * @param string $table1 name of linked table
     * @param string $table2 name of linked table
     * @return mixed $_link property array, sub-array, or value
     *
     * @throws PEAR_Error:
     *    - $table1 or $table2 is not a string (..DATABASE_ERR_TBL_NOT_STRING)
     *    - $table1 or $table2 is not a table name (..DATABASE_ERR_NO_TBL)
     *
     * @access public
     */
    function getLink($table1 = null, $table2 = null) 
    {
        if (is_null($table1)) {
            return $this->_link;
        } elseif (is_string($table1)) {
            if (isset($this->_link[$table1])) {
                if (is_null($table2)) {
                    return $this->_link[$table1];
                } elseif (is_string($table2)) {
                    if (isset($this->_link[$table1][$table2])) {
                        return $this->_link[$table1][$table2];
                    } else {
                        if (isset($this->_table[$table2])) {
                            // Valid table names, but no links
                            return null;
                        } else {
                            // Invalid 2nd table name string
                            return $this->throwError(
                                      DB_TABLE_DATABASE_ERR_NO_TBL,
                                      "getLink, $table2");
                        }
                    }
                } else {
                    return $this->throwError(
                              DB_TABLE_DATABASE_ERR_TBL_NOT_STRING,
                              "getLink");
                }
            } else {
                if (isset($this->_table[$table1])) {
                    // Valid first table name, but no links
                    return null;
                } else {
                    // Invalid 1st table name string
                    return $this->throwError(
                              DB_TABLE_DATABASE_ERR_NO_TBL,
                              "getLink, $table1");
                }
            }
        } else {
            return $this->throwError(
                      DB_TABLE_DATABASE_ERR_TBL_NOT_STRING,
                      "getLink");
        }
    }

    // }}}
    // {{{ function setTableSubclassPath($path) 

    /**
     * Sets path to a directory containing DB_Table subclass definitions.
     *
     * This method sets the $_table_subclass_path string property. The value of
     * this property is the path to the directory containing DB_Table subclass 
     * definitions, without a trailing directory separator. 
     *  
     * This path may be used by the __wakeup(), if necessary, in an attempt to 
     * autoload class definitions when unserializing a DB_Table_Database object 
     * and its child DB_Table objects. If a DB_Table subclass $subclass_name
     * has not been defined when it is needed in DB_Table_Database::__wakeup(), 
     * to unserialize an instance of this class, the __wakeup() method attempts
     * to include a class definition file from this directory, as follows:
     * <code>
     *     $dir = $this->_table_subclass_path;
     *     require_once $dir . '/' . $subclass . '.php';
     * </code>
     * See the getTableSubclass() docblock for a discusion of capitalization
     * conventions in PHP 4 and 5 for subclass file names. 
     * 
     * @param string $path path to directory containing class definitions
     * @return void
     * @access public
     *
     * @see DB_Table_Database::getTableSubclass()
     */
    function setTableSubclassPath($path) 
    {
        $this->_table_subclass_path = $path; 
    }

    // }}}
    // {{{ function addTable(&$table_obj)

    /**
     * Adds a table to the database.
     *
     * Creates references between $this DB_Table_Database object and
     * the child DB_Table object, by adding a reference to $table_obj
     * to the $this->_table array, and setting $table_obj->database =
     * $this. 
     *
     * Adds the primary key to $this->_primary_key array. The relevant
     * element of $this->_primary_key is set to null if no primary key 
     * index is declared. Returns an error if more than one primary key
     * is declared.
     *
     * Returns true on success, and PEAR error on failure. Returns the
     * following DB_TABLE_DATABASE_ERR_* error codes if:
     *    - $table_obj is not a DB_Table ( _DBTABLE_OBJECT )
     *    - more than one primary key is defined  ( _ERR_MULT_PKEY )
     *
     * @param  object &$table_obj the DB_Table object (reference)
     * @return boolean true on success (PEAR_Error on failure)
     * @access public
     */
    function addTable(&$table_obj)
    {
        // Check that $table_obj is a DB_Table object 
        // Identify subclass name, if any
        if (is_subclass_of($table_obj, 'DB_Table')) {
            $subclass = get_class($table_obj);
        } elseif (is_a($table_obj, 'DB_Table')) {
            $subclass = null;
        } else {
            return $this->throwError(
                      DB_TABLE_DATABASE_ERR_DBTABLE_OBJECT);
        }

        // Identify table name and table object (sub)class name
        $table = $table_obj->table;
        
        // Set $this->_primary_key[$table] 
        $this->_primary_key[$table] = null;
        foreach ($table_obj->idx as $idx_name => $idx_def) {
            if ($idx_def['type'] == 'primary') {
                if (is_null($this->_primary_key[$table])) {
                    $this->_primary_key[$table] = $idx_def['cols'];
                } else {
                    // More than one primary key defined in the table
                    unset($this->_primary_key[$table]);
                    return $this->throwError(
                              DB_TABLE_DATABASE_ERR_MULT_PKEY, $table);
                }
            }
        }

        // Add references between $this parent and child table object
        $this->_table[$table] =& $table_obj;
        $table_obj->setDatabaseInstance($this); 

        // Add subclass name (if any) to $this->_table_subclass
        $this->_table_subclass[$table] = $subclass;

        // Set shared properties
        $table_obj->db =& $this->db;
        $table_obj->backend   = $this->backend;
        $table_obj->fetchmode = $this->fetchmode;

        // Add all columns to $_col property
        foreach ($table_obj->col as $key => $def) {
            if (!isset($this->_col[$key])) {
                $this->_col[$key] = array();
            }
            $this->_col[$key][] = $table;
        }

        return true;
    }

    // }}}
    // {{{ function deleteTable($table) 

    /**
     * Deletes a table from $this database object.
     *
     * Removes all dependencies on $table from the database model. The table 
     * is removed from $_table and $_primary_key properties. Its columns are
     * removed from the $_col and $_foreign_col properties. References to
     * and from the table are removed from the $_ref, $_ref_to, and $_link
     * properties. Referencing columns are removed from $_foreign_col.
     * 
     * @param  string $table name of table to be deleted
     * @return void
     * @access public
     */
    function deleteTable($table) 
    {
        if (isset($this->_table[$table])) {
            $table_obj =& $this->_table[$table];
        } else {
            return;
        }

        // Remove reference to database from table object
        $null_instance = null;
        $table_obj->setDatabaseInstance($null_instance);

        // Remove columns from $_col and $_foreign_col property arrays
        foreach ($table_obj->col as $column => $def) {
            $key = array_search($table, $this->_col[$column]);
            if (is_integer($key)) {
                unset($this->_col[$column][$key]);
                if (count($this->_col[$column]) == 0) {
                    unset($this->_col[$column]);
                } else {
                    $new = array_values($this->_col[$column]);
                    $this->_col[$column] = $new;
                }
            }
            if (isset($this->_foreign_col[$column])) {
                $key = array_search($table, $this->_foreign_col[$column]);
                if (is_integer($key)) {
                    unset($this->_foreign_col[$column][$key]);
                    if (count($this->_foreign_col[$column]) == 0) {
                        unset($this->_foreign_col[$column]);
                    } else {
                        $new = array_values($this->_foreign_col[$column]);
                        $this->_foreign_col[$column] = $new;
                    }
                }
            }
        }

        // Remove all references involving the deleted table.
        // Corresponding links are removed from $this->_link by deleteRef 
        // Referencing columns are removed from $this->_foreign_col by deleteRef
        foreach ($this->_ref as $ftable => $referenced) {
            foreach ($referenced as $rtable => $ref) {
                if ($ftable == $table || $rtable == $table) {
                    $this->deleteRef($ftable, $rtable);
                } 
            } 
        }

        // Remove table from $this->_table and $this->_primary_key 
        unset($this->_table[$table]);
        unset($this->_primary_key[$table]);
    }

    // }}}
    // {{{ function addRef($ftable, $fkey, $rtable, [$rkey], [$on_delete], [$on_update])

    /**
     * Adds a foreign key reference to the database.
     *
     * Adds a reference from foreign key $fkey of table $ftable to
     * referenced key $rkey of table named $rtable to the $this->_ref
     * property. The values of $fkey and $rkey (if not null) may either 
     * both be column name strings (for single column keys) or they 
     * may both be numerically indexed arrays of corresponding column 
     * names (for multi-column keys). If $rkey is null (the default), 
     * the referenced key taken to be the primary key of $rtable, if 
     * any.
     *
     * The $on_delete and $on_update parameters may be either be null, 
     * or may have string values 'restrict', 'cascade', 'set null', or 
     * 'set default' that indicate referentially triggered actions to be 
     * taken deletion or updating of referenced row in $rtable. Each of 
     * these actions corresponds to a standard SQL action (e.g., cascading 
     * delete) that may be taken upon referencing rows of table $ftable 
     * when a referenced row of $rtable is deleted or updated.  A PHP 
     * null value for either parameter (the default) signifies that no
     * such action will be taken upon deletion or updating. 
     *
     * There may no more than one reference from a table to another, though
     * reference may contain multiple columns. 
     *
     * Returns true on success, and PEAR error on failure. Returns the
     * following DB_TABLE_DATABASE_ERR_* error codes if:
     *   - $ftable does not exist ( _NO_FTABLE )
     *   - $rtable does not exist ( _NO_RTABLE )
     *   - $rkey is null and $rtable has no primary key ( _NO_PKEY )
     *   - $fkey is neither a string nor an array ( _FKEY )
     *   - $rkey is not a string, $fkey is a string ( _RKEY_NOT_STRING )
     *   - $rkey is not an array, $fkey is an array ( _RKEY_NOT_ARRAY )
     *   - A column of $fkey does not exist ( _NO_FCOL )
     *   - A column of $rkey does not exist ( _NO_RCOL )
     *   - A column of $fkey and $rkey have different types ( _REF_TYPE )
     *   - A reference from $ftable to $rtable already exists ( _MULT_REF )
     * 
     * @param  string  $ftable    name of foreign/referencing table
     * @param  mixed   $fkey      foreign key in referencing table 
     * @param  string  $rtable    name of referenced table
     * @param  mixed   $rkey      referenced key in referenced table
     * @param  string  $on_delete action upon delete of a referenced row.
     * @param  string  $on_update action upon update of a referenced row.
     * @return boolean true on success (PEAR_Error on failure)
     * @access public
     */
    function addRef($ftable, $fkey, $rtable, $rkey = null,
                             $on_delete = null, $on_update = null)
    {
        // Check existence of $ftable is a key in $this->_table.
        if (isset($this->_table[$ftable])) {
            $ftable_obj =& $this->_table[$ftable];
        } else {
            return $this->throwError(
                      DB_TABLE_DATABASE_ERR_NO_FTABLE,
                      "$ftable => $rtable");
        }

        // Check existence of referenced table
        if (isset($this->_table[$rtable])) {
            $rtable_obj =& $this->_table[$rtable];
        } else {
            return $this->throwError(
                      DB_TABLE_DATABASE_ERR_NO_RTABLE,
                      "$ftable => $rtable");
        }

        // If referenced key is null, set it to the primary key
        if (!$rkey) {
            if (isset($this->_primary_key[$rtable])) {
                $rkey = $this->_primary_key[$rtable];
            } else {
                // Error: null referenced key and no primary key
                return $this->throwError(
                          DB_TABLE_DATABASE_ERR_NO_PKEY,
                          "$ftable => $rtable");
            }
        }

        // Check $fkey and $rkey types and compatibility
        if (is_string($fkey)) {
            if (!is_string($rkey)) {
                return $this->throwError(
                          DB_TABLE_DATABASE_ERR_RKEY_NOT_STRING,
                          "$ftable => $rtable");
            }
            if (!isset($ftable_obj->col[$fkey])) {
                return $this->throwError(
                          DB_TABLE_DATABASE_ERR_NO_FCOL,
                          "$ftable.$fkey => $rtable.$rkey");
            }
            if (!isset($rtable_obj->col[$rkey])) {
                return $this->throwError(
                          DB_TABLE_DATABASE_ERR_NO_RCOL,
                          "$ftable.$fkey => $rtable.$rkey");
            }
            $ftype = $ftable_obj->col[$fkey]['type'];
            $rtype = $rtable_obj->col[$rkey]['type'];
            if (!($rtype == $ftype)) {
                return $this->throwError(
                          DB_TABLE_DATABASE_ERR_REF_TYPE,
                          "$ftable.$fkey => $rtable.$rkey");
            }
        } elseif (is_array($fkey)) {
            if (!is_array($rkey)) {
                return $this->throwError(
                          DB_TABLE_DATABASE_ERR_RKEY_NOT_ARRAY,
                          "$ftable => $rtable");
            }
            if (!(count($fkey) == count($rkey))) {
                return $this->throwError(
                          DB_TABLE_DATABASE_ERR_RKEY_COL_NUMBER,
                          "$ftable => $rtable");
            }
            for ($i=0 ; $i < count($rkey) ; $i++) {
                $fcol = $fkey[$i];
                $rcol = $rkey[$i];
                if (!isset($ftable_obj->col[$fcol])) {
                    return $this->throwError(
                              DB_TABLE_DATABASE_ERR_NO_FCOL,
                              "$ftable.$fcol => $rtable.$rcol");
                }
                if (!isset($rtable_obj->col[$rcol])) {
                    return $this->throwError(
                              DB_TABLE_DATABASE_ERR_NO_RCOL,
                              "$ftable.$fcol => $rtable.$rcol");
                }
                $ftype = $ftable_obj->col[$fcol]['type'];
                $rtype = $rtable_obj->col[$rcol]['type'];
                if (!($rtype == $ftype)) {
                    return $this->throwError(
                              DB_TABLE_DATABASE_ERR_REF_TYPE,
                              "$ftable.$fcol => $rtable.$rcol");
                }
            }
        } else {
            return $this->throwError(
                      DB_TABLE_DATABASE_ERR_FKEY,
                      "$ftable => $rtable");
        }

        // Check validity of on_delete and on_update actions
        $valid_actions = 
               array(null, 'cascade', 'set null', 'set default', 'restrict');
        if (!in_array($on_delete, $valid_actions)) {
            return $this->throwError(
                   DB_TABLE_DATABASE_ERR_ON_DELETE_ACTION,
                   "$ftable => $rtable");
        }
        if (!in_array($on_update, $valid_actions)) {
            return $this->throwError(
                   DB_TABLE_DATABASE_ERR_ON_UPDATE_ACTION,
                   "$ftable => $rtable");
        }

        // Add reference to $this->_ref;
        $ref = array(
               'fkey' => $fkey, 
               'rkey' => $rkey,
               'on_delete' => $on_delete, 
               'on_update' => $on_update);
        if (!isset($this->_ref[$ftable])) {
            $this->_ref[$ftable] = array();
        } else {
             if (isset($this->_ref[$ftable][$rtable])) {
                 // Multiple references from $ftable to $rtable
                 return $this->throwError(
                           DB_TABLE_DATABASE_ERR_MULT_REF,
                           "$ftable => $rtable");
             }
        }
        $this->_ref[$ftable][$rtable] = $ref;

        // Add referencing table $ftable to $ref_to property
        if (!isset($this->_ref_to[$rtable])) {
            $this->_ref_to[$rtable] = array();
        }
        $this->_ref_to[$rtable][] = $ftable;

        // Add foreign key columns to $this->_foreign_col
        if (is_string($fkey)) {
            if (!isset($this->_foreign_col[$fkey])) {
                $this->_foreign_col[$fkey] = array();
            }
            $this->_foreign_col[$fkey][] = $ftable;
        } elseif (is_array($fkey)) {
            foreach ($fkey as $fcol) {
                if (!isset($this->_foreign_col[$fcol])) {
                    $this->_foreign_col[$fcol] = array();
                }
                $this->_foreign_col[$fcol][] = $ftable;
            }
        }

        // Normal completion
        return true;
    }

    // }}}
    // {{{ function deleteRef($ftable, $rtable) 
 
    /**
     * Deletes one reference from database model
     *
     * Removes reference from referencing (foreign key) table named
     * $ftable to referenced table named $rtable. Unsets relevant elements
     * of the $ref, $_ref_to, and $_link property arrays, and removes the
     * foreign key columns of $ftable from the $_foreign_col property. 
     *
     * Does nothing, silently, if no such reference exists, i.e., if 
     * $this->_ref[$ftable][$rtable] is not set.
     *
     * @param $ftable name of referencing (foreign key) table
     * @param $rtable name of referenced table
     * @return void
     * @access public
     */ 
    function deleteRef($ftable, $rtable) 
    {
        // Delete from $_ref property
        if (isset($this->_ref[$ftable])) {
            if (isset($this->_ref[$ftable][$rtable])) {
                $fkey = $this->_ref[$ftable][$rtable]['fkey'];
                unset($this->_ref[$ftable][$rtable]);
            } else {
                // No such reference, abort silently
                return;
            }
        }

        // Remove foreign key columns from $foreign_col property
        if (isset($fkey)) {
            if (is_string($fkey)) {
                $fkey = array($fkey);
            }
            foreach ($fkey as $column) {
                if (isset($this->_foreign_col[$column])) {
                    $key = array_search($ftable, 
                                        $this->_foreign_col[$column]);
                    if (is_integer($key)) {
                        unset($this->_foreign_col[$column][$key]);
                        if (count($this->_foreign_col[$column]) == 0) {
                            unset($this->_foreign_col[$column]);
                        } else {
                            $new = array_values($this->_foreign_col[$column]);
                            $this->_foreign_col[$column] = $new;
                        }
                    }
                }
            }
        }

        // Delete from $_ref_to property
        if (isset($this->_ref_to[$rtable])) {
            $key = array_search($ftable, $this->_ref_to[$rtable]);
            // Unset element 
            unset($this->_ref_to[$rtable][$key]);
            if (count($this->_ref_to[$rtable]) == 0) {
                unset($this->_ref_to[$rtable]);
            } else {
                // Redefine numerical keys of remaining elements
                $ref_to = array_values($this->_ref_to[$rtable]);
                $this->_ref_to[$rtable] = $ref_to;
            }
        }

        // Delete all relevant links from $_link property
        if (isset($this->_link[$rtable])) {
            foreach ($this->_link[$rtable] as $table2 => $links) {
                if (in_array($ftable, $links)) {
                    $this->deleteLink($rtable, $table2, $ftable);
                }
            }
        }
    }

    // }}}
    // {{{ function setOnDelete($ftable, $rtable, $action)
 
    /**
     * Modifies the on delete action for one foreign key reference.
     *
     * Modifies the value of the on_delete action associated with a reference
     * from $ftable to $rtable. The parameter action may be one of the action
     * strings 'cascade', 'restrict', 'set null', or 'set default', or it may
     * be php null. A null value of $action indicates that no action should be
     * taken upon deletion of a referenced row. 
     *
     * Returns true on success, and PEAR error on failure. Returns the error
     * code DB_TABLE_DATABASE_ERR_REF_TRIG_ACTION if $action is a neither a 
     * valid action string nor null. Returns true, and does nothing, if 
     * $this->_ref[$ftable][$rtable] is not set. 
     *
     * @param  string $ftable  name of referencing (foreign key) table
     * @param  string $rtable  name of referenced table
     * @param  string $action  on delete action (action string or null)
     * @return boolean true on normal completion (PEAR_Error on failure)
     * @access public
     */ 
    function setOnDelete($ftable, $rtable, $action)
    {
        $valid_actions = 
             array(null, 'cascade', 'set null', 'set default', 'restrict');

        if (isset($this->_ref[$ftable])) {
            if (isset($this->_ref[$ftable][$rtable])) {
                if (!in_array($action, $valid_actions)) {
                    return $this->throwError(
                           DB_TABLE_DATABASE_ERR_REF_ON_DELETE_ACTION,
                           "$ftable => $rtable");
                }
                $this->_ref[$ftable][$rtable]['on_delete'] = $action;
            }
        }
        return true;
    }

    // }}}
    // {{{ function setOnUpdate($ftable, $rtable, $action)
 
    /**
     * Modifies on update action for one foreign key reference.
     *
     * Similar to setOnDelete. See setOnDelete for further details.
     *
     * @param string $ftable  name of referencing (foreign key) table
     * @param string $rtable  name of referenced table
     * @param array  $action  on update action (action string or null)
     * @return boolean true on normal completion (PEAR_Error on failure)
     * @access public
     */ 
    function setOnUpdate($ftable, $rtable, $action)
    {
        $valid_actions = 
             array(null, 'cascade', 'set null', 'set default', 'restrict');

        if (isset($this->_ref[$ftable])) {
            if (isset($this->_ref[$ftable][$rtable])) {
                if (!in_array($action, $valid_actions)) {
                    return $this->throwError(
                           DB_TABLE_DATABASE_ERR_REF_ON_UPDATE_ACTION,
                           "$ftable => $rtable");
                }
                $this->_ref[$ftable][$rtable]['on_update'] = $action;
            }
        }
        return true;
    }

    // }}}
    // {{{ function addLink($table1, $table2, $link)
 
    /**
     * Identifies a linking/association table that links two others
     *
     * Adds table name $link to $this->_link[$table1][$table2] and 
     * to $this->_link[$table2][$table1].
     *
     * Returns true on success, and PEAR error on failure. Returns the
     * following DB_TABLE_DATABASE_ERR_* error codes if:
     *   - $ftable does not exist ( _NO_FTABLE )
     *   - $rtable does not exist ( _NO_RTABLE )
     *
     * @param  string $table1 name of 1st linked table
     * @param  string $table2 name of 2nd linked table
     * @param  string $link   name of linking/association table.
     * @return boolean true on success (PEAR_Error on failure)
     * @access public
     */
    function addLink($table1, $table2, $link)
    {

        // Check for existence of all three tables
        if (is_string($table1)) {
            if (!isset($this->_table[$table1])) {
                return $this->throwError(
                          DB_TABLE_DATABASE_ERR_NO_TBL,
                          "addLink, $table1");
            }
        } else {
            return $this->throwError(
                      DB_TABLE_DATABASE_ERR_NO_TBL,
                      "addLink, $table1");
        }
        if (!isset($this->_table[$table2])) {
            return $this->throwError(
                      DB_TABLE_DATABASE_ERR_NO_TBL,
                      "addLink, $table2");
        }
        if (!isset($this->_table[$link])) {
            return $this->throwError(
                      DB_TABLE_DATABASE_ERR_NO_TBL,
                      "addLink, $link");
        }
        if (!isset($this->_ref[$link])) {
            return $this->throwError(
                      DB_TABLE_DATABASE_ERR_NO_REF_LINK,
                      "$link => $table1, $table2");
        } else {
            if (!isset($this->_ref[$link][$table1])) {
                return $this->throwError(
                          DB_TABLE_DATABASE_ERR_NO_REF_LINK,
                          "$link => $table1");
            }
            if (!isset($this->_ref[$link][$table2])) {
                return $this->throwError(
                          DB_TABLE_DATABASE_ERR_NO_REF_LINK,
                          "$link => $table2");
            }
        }

        // Add $this_link[$table1][$table2]
        if (!key_exists($table1, $this->_link)) {
            $this->_link[$table1] = array();
        }
        if (!key_exists($table2, $this->_link[$table1])) {
            $this->_link[$table1][$table2] = array();
        }
        $this->_link[$table1][$table2][] = $link;

        // Add $this_link[$table2][$table1]
        if (!key_exists($table2, $this->_link)) {
            $this->_link[$table2] = array();
        }
        if (!key_exists($table1, $this->_link[$table2])) {
            $this->_link[$table2][$table1] = array();
        } 
        $this->_link[$table2][$table1][] = $link;
    }

    // }}}
    // {{{ function addAllLink()
 
    /**
     * Adds all possible linking tables to the $_link property array
     *
     * Identifies all potential linking tables in the datbase, and adds
     * them all to the $_link property.  Table $link is taken to be a 
     * link between tables $table1 and $table2 if it contains foreign 
     * key references to both $table1 and $table2. 
     *
     * @return void
     * @access public
     */
    function addAllLinks()
    {
        foreach ($this->_table as $link => $link_obj) {
            if (isset($this->_ref[$link])) {
                $ref  = $this->_ref[$link];
                $n     = count($ref);
                $names = array_keys($ref);
                if ($n > 1) {
                    $is_link = true;
                } else {
                    $is_link = false;
                }
                if ($is_link) {
                    if ($n == 2) {
                        $table1 = $names[0];
                        $table2 = $names[1];
                        $this->addLink($table1, $table2, $link);
                    } elseif ($n > 2) {
                        for ($i=1 ; $i < $n; $i++) {
                            for ($j=0 ; $j < $i; $j++) {
                                $table1 = $names[$j];
                                $table2 = $names[$i];
                                $this->addLink($table1, $table2, $link);
                            }
                        }
                    }
                }
            }
        }
    }

    // }}}
    // {{{ function deleteLink($table1, $table2, $link = null)
 
    /**
     * Removes a link between two tables from the $_link property
     *
     * If $link is not null, remove table $link from the list of links
     * between $table1 and $table2, if present. If $link is null, delete
     * all links between $table1 and $table2. 
     *
     * @param  string $table1 name of 1st linked table
     * @param  string $table2 name of 2nd linked table
     * @param  string $link   name of linking table
     * @return void
     * @access public
     */
    function deleteLink($table1, $table2, $link = null)
    {
        if (isset($this->_link[$table1])) {
            if (isset($this->_link[$table1][$table2])) {
                if ($link) {
                    // Find numerical key of $link in _link[$table1][$table2]
                    $key = array_search($link, $this->_link[$table1][$table2]);
                    if (is_integer($key)) {
                        unset($this->_link[$table1][$table2][$key]);
                        if (count($this->_link[$table1][$table2]) == 0) {
                            unset($this->_link[$table1][$table2]);
                            unset($this->_link[$table2][$table1]);
                            if (count($this->_link[$table1]) == 0) {
                                unset($this->_link[$table1]);
                            }
                            if (count($this->_link[$table2]) == 0) {
                                unset($this->_link[$table2]);
                            }
                        } else { 
                            // Reset remaining indices sequentially from zero
                            $new = array_values($this->_link[$table1][$table2]);
                            $this->_link[$table1][$table2] = $new;
                            $this->_link[$table2][$table1] = $new;
                        }
                    }
                } else {
                    unset($this->_link[$table1][$table2]);
                    unset($this->_link[$table2][$table1]);
                    if (count($this->_link[$table1]) == 0) {
                        unset($this->_link[$table1]);
                    }
                    if (count($this->_link[$table2]) == 0) {
                        unset($this->_link[$table2]);
                    }
                }
            }
        }
    }

    // }}}
    // {{{ function validCol($col, $from = null)
 
    /**
     * Validates and (if necessary) disambiguates a column name.
     *
     * The parameter $col is a string may be either a column name or
     * a column name qualified by a table name, using the SQL syntax
     * "$table.$column". If $col contains a table name, and is valid,
     * an array($table, $column) is returned.  If $col is not qualified 
     * by a column name, an array array($table, $column) is returned,
     * in which $table is either the name of one table, or an array
     * containing the names of two or more tables containing a column 
     * named $col. 
     *
     * The $from parameter, if present, is a numerical array of
     * names of tables with which $col should be associated, if no
     * explicit table name is provided, and if possible. If one 
     * or more of the tables in $from contains a column $col, the 
     * returned table or set of tables is restricted to those in 
     * array $from.
     *
     * If the table name remains ambiguous after testing for tables in
     * the $from set, and $col is not a foreign key in one or more of 
     * the remaining tables, the returned table or set of tables is 
     * restricted to those in which $col is not a foreign key. 
     *
     * Returns a PEAR_Error with the following DB_TABLE_DATABASE_ERR_* error
     * codes if:
     *    - column $col does not exist in the database ( _NO_COL_DB )
     *    - column $col does not exist in the specified table ( _NO_COL_TBL )
     * 
     * @param  string $col  column name, optionally qualified by a table name
     * @param  array  $from array of tables from which $col should be chosen,
     *                      if possible.
     * @return array  array($table, $column), or PEAR_Error on failure
     *                $column is a string, $table is a string or array
     * @access public
     */
    function validCol($col, $from = null)
    {
        $col = explode('.',trim($col));
        if (count($col) == 1) { 
            // Parameter $col is a column name with no table name
            $column = $col[0];
            // Does $column exist in database ?
            if (!isset($this->_col[$column])) {
                return $this->throwError(
                          DB_TABLE_DATABASE_ERR_NO_COL_DB, 
                          "$column");
            }
            $table = $this->_col[$column];
            // If $table is not unique, try restricting to arrays in $from
            if (count($table) > 1 && $from) {
                $ptable = array_intersect($table, $from);
                if (count($ptable) > 0) {
                    $table = array_values($ptable);
                }
            }
            // If count($table)>1, try excluding foreign key columns
            if (count($table) > 1 && isset($this->_foreign_col[$column])) {
                $ptable = array_diff($table, $this->_foreign_col[$column]);
                if (count($ptable) > 0) {
                    $table = array_values($ptable);
                }
            }
            // If only one table remains, set $table = table name string
            if (count($table) == 1) {
                $table = $table[0];
            }
        } elseif (count($col) == 2) { 
            // parameter $col is qualified by a table name
            $table  = $col[0];
            $column = $col[1];
            if (isset($this->_table[$table])) {
                 $table_obj =& $this->_table[$table];
                 $col_array = $table_obj->col;
                 if (!isset($col_array[$column])) {
                     return $this->throwError(
                               DB_TABLE_DATABASE_ERR_NO_COL_TBL,
                               "$table.$column");
                 }
            } else {
                return $this->throwError(
                          DB_TABLE_DATABASE_ERR_NO_TBL, "validCol, $table");
            }
        }
        return array($table, $column);
    }
 
    // }}}
    // {{{ function createTables($flag = 'safe')

    /**
     * Creates all the tables in a database in a RDBMS
     *
     * Note: this method creates all the tables in a database, but does
     * NOT create the parent database or set it to the current or default
     * database -- the database must exist before the method is called.
     *
     * If creation of any table fails, the method immediately returns the
     * PEAR error returned by DB_Table::create($flag).
     *
     * @param mixed $flag The automatic database creation mode, which is
     *                    applied to each table in the database. It can have
     *                    values:
     *                    - 'safe' to create a table only if it does not exist
     *                    - 'drop' to drop and recreate any existing table 
                             with the same name
     *
     * @return boolean true on sucess (PEAR_Error on failure of any table)
     * @access public
     *
     * @see DB_Table::create()
     */
    function createTables($flag = 'safe')
    {
        foreach ($this->_table as $name => $table) {
            $result = $table->create($flag);
            if (PEAR::isError($result)) {
                return $result;
            }
        }
        return true;
    }

    // }}}
    // {{{ function validForeignKeys($table_name, $data)

    /**
     * Check validity of any foreign key values in associative array $data
     * containing values to be inserted or updated in table $table_name.
     *
     * Returns true if each foreign key in $data matches a row in the
     * referenced table, or if there are no foreign key columns in $data.  
     * Returns a PEAR_Error if any foreign key column in associative array 
     * $data (which may contain a full or partial row of $table_name), does 
     * not match the the value of the referenced column in any row of the 
     * referenced table.
     *
     * @param $table_name name of the referencing table containing $data
     * @param @data       associative array containing all or part of a row
     *                    of data of $table_name, with column name keys.
     * @return bool true if all foreign keys are valid, returns PEAR_Error
     *              if foreign keys are invalid or if an error is thrown 
     *              by a required query
     * 
     * @throws PEAR error if:
     *    - Error thrown by _buildFKeyFilter method (bubbles up)
     *    - Error thrown by select method for required query (bubbles up)
     *
     * @access public
     */
    function validForeignKeys($table_name, $data)
    {
        if (isset($this->_ref[$table_name])) {
            foreach ($this->_ref[$table_name] as $rtable_name => $ref) {
                $fkey = $ref['fkey'];
                $rkey = $ref['rkey'];
                $rtable_obj =& $this->_table[$rtable_name];

                // Construct select where clause for referenced rows,
                // $filter = '' if $data contains no foreign key columns,
                $filter = $this->_buildFKeyFilter($data, $fkey, $rkey);
                if (PEAR::isError($filter)) {
                    return $filter;
                }

                // If inserted data contain FK columns referenced by rtable,
                // select referenced row of rtable, return error if none is
                // found
                if ($filter) {
                    $sql = array('select'=> '*',
                                 'from'  => $rtable_name,
                                 'where' => $filter);
                    $referenced_rows = $this->select($sql);
                    // Check for failed query
                    if (PEAR::isError($referenced_rows)) {
                        return $referenced_rows;
                    }
                    // Check for failed foreign key constraint
                    if (count($referenced_rows) == 0) {
                        return $this->throwError( 
                               DB_TABLE_DATABASE_ERR_FKEY_CONSTRAINT);
                    }
                }
            }
        }
        return true;
    }

    // }}}
    // {{{ function insert($table_name, $data)

    /**
     * Inserts a single table row 
     *
     * Wrapper for insert method of the corresponding DB_Table object.
     *
     * Data will be validated before insertion using validForeignKey(),
     * if foreign key validation in enabled.
     *
     * @param string $table_name Name of table into which to insert data
     * @param array $data Associative array, in which each key is a column
     *                     name and each value is that column's value.
     *                     This is the data that will be inserted into
     *                     the table. Data is checked against the column
     *                     names and data types for validity.
     * @return boolean true on success (PEAR_Error on failure)
     * @access public
     */
    function insert($table_name, $data)
    {
        // Dereference table object
        if (isset($this->_table[$table_name])) {
            $table_obj =& $this->_table[$table_name];
        } else {
            return $this->throwError(
                       DB_TABLE_DATABASE_ERR_NO_TBL,
                       "insert, $table_name");
        }

        // Insert into $table_obj
        $result = $table_obj->insert($data);

        // Return value: true or PEAR_Error
        if (PEAR::isError($result)) {
            return $result;
        } else {
            return true;
        }

    }

    // }}}
    // {{{ function autoValidInsert($flag = true)

    /**
     * Turns on or off automatic validation of inserted data for all tables
     *
     * @param bool $flag true to turn on auto-validation, false to turn off.
     * @return void
     * @access public
     */
    function autoValidInsert($flag = true)
    {
        foreach ($this->_table as $table_obj) {
           $table_obj->autoValidInsert($flag);
        }
    }

    // }}}
    // {{{ function update($table_name, $data, $where)

    /**
     * Updates all row(s) of table that match a custom where clause.
     *
     * Wrapper for insert method of the corresponding DB_Table object.
     * 
     * Data will be validated before insertion using validForeignKey(),
     * if foreign key validation in enabled.
     *
     * Implements any required ON UPDATE actions on tables that 
     * reference updated columns, if on update actions are enabled.
     *
     * @param string $table_name name of table to update
     * @param array  $data  associative array in which keys are names of
     *                       columns to be updated values are new values.
     * @param string $where SQL WHERE clause that limits the set of
     *                       records to update.
     * @return boolean true on success (PEAR_Error on failure)
     * @access public
     */
    function update($table_name, $data, $where)
    {
        // Dereference table object
        if (isset($this->_table[$table_name])) {
            $table_obj =& $this->_table[$table_name];
        } else {
            return $this->throwError(
                       DB_TABLE_DATABASE_ERR_NO_TBL,
                       "update, $table_name");
        }

        // Apply update
        $result = $table_obj->update($data, $where);

        // Return value: true or PEAR_Error
        if (PEAR::isError($result)) {
            return $result;
        } else {
            return true;
        }

    }

    // }}}
    // {{{ function autoValidUpdate($flag = true)

    /**
     * Turns on (or off) automatic validation of updated data for all tables.
     *
     * @param  bool $flag true to turn on auto-validation, false to turn off
     * @return void
     * @access public
     */
    function autoValidUpdate($flag = true)
    {
        foreach ($this->_table as $table_obj) {
            $table_obj->autoValidUpdate($flag);
        }
    }

    // }}}
    // {{{ function onUpdateAction(&$table_obj, $data, $where)

    /**
     * Implements any ON UPDATE actions triggered by updating of rows of
     * $table_obj that match logical condition $where.
     *
     * This method is called by the DB_Table::update() method if the table
     * has a parent DB_Table_Database object, and if ON UPDATE actions are
     * enabled in the database object. It is called indirectly by the
     * DB_Table_Database::delete() method, which is simply a wrapper for
     * the DB_Table method. 
     *
     * @param  object &$table_obj Reference to a DB_Table object
     * @param  array  $data  Data to updated, column name keys, data values
     * @param  string $where SQL logical condition for updated rows
     * @return boolean true on success (PEAR_Error on failure)
     * @access public
     */
    function onUpdateAction(&$table_obj, $data, $where)
    {
        $table_name = $table_obj->table;
        if ($this->_act_on_update and isset($this->_ref_to[$table_name])) {
            $update_rows = null;
            foreach ($this->_ref_to[$table_name] as $ftable_name) {
                $ref    = $this->_ref[$ftable_name][$table_name];
                $action = isset($ref['on_update']) ? $ref['on_update'] : null;
                if (is_null($action)) {
                   continue;
                }
                $rtable_obj =& $this->_table[$table_name];
                $ftable_obj =& $this->_table[$ftable_name];
                $fkey = $ref['fkey'];
                $rkey = $ref['rkey'];

                // Check if any column(s) of referenced $rkey are updated
                $rkey_updated = false;
                foreach ($data as $key => $value) {
                    if (is_string($rkey)){
                        if ($key == $rkey) {
                            $rkey_updated = true;
                            break;
                        }
                    } else {
                        if (in_array($key, $rkey)) {
                            $rkey_updated = true;
                            break;
                        }
                    }
                }

                // If $rkey is not updated, continue to next referencing table
                if (!$rkey_updated) {
                    continue;
                }

                // Select rows to be updated, if not done previously
                if ($update_rows === null) {
                    if ($this->backend == 'mdb2') {
                        $fetchmode_assoc = MDB2_FETCHMODE_ASSOC;
                    } else {
                        $fetchmode_assoc = DB_FETCHMODE_ASSOC;
                    }
                    $sql = array('select' => '*',
                                 'from'   => $table_name,
                                 'where'  => $where,
                                 'fetchmode' => $fetchmode_assoc);
                    $update_rows = $this->select($sql);
                    if (PEAR::isError($update_rows)) {
                        return $update_rows;
                    }
                }

                // Construct $fdata array if cascade, set null, or set default
                $fdata = null;
                if ($action == 'cascade') {
                    if (is_string($rkey)) {
                        if (array_key_exists($rkey, $data)) {
                            $fdata = array($fkey => $data[$rkey]);
                        }
                    } else {
                        $fdata = array();
                        for ($i=0; $i < count($rkey); $i++) {
                            $rcol = $rkey[$i];
                            $fcol = $fkey[$i];
                            if (array_key_exists($rcol, $data)) {
                                $fdata[$fcol] = $data[$rcol];
                            }
                        }
                        if (count($fdata) == 0) {
                           $fdata = null;
                        }
                    }
                } elseif ($action == 'set null' or $action == 'set default') {
                    if (is_string($fkey)) {
                        if ($action == 'set default') {
                            $value = isset($ftable_obj->col[$fkey]['default'])
                                   ? $ftable_obj->col[$fkey]['default'] : null;
                        } else {
                            $value = null;
                        }
                        $fdata = array($fkey => $value);
                    } else {
                        $fdata = array();
                        foreach ($fkey as $fcol) {
                            if ($action == 'set default') {
                                $value = isset($ftable_obj->col[$fcol]['default'])
                                      ? $ftable_obj->col[$fcol]['default'] : null;
                            } else {
                                $value = null;
                            }
                            $fdata[$fcol] = $value;
                        }
                        if (count($fdata) == 0) {
                           $fdata = null;
                        }
                    }
                } elseif ($action == 'restrict') {
                    $fdata = true;
                } elseif ($action == null) {
                    $fdata = null;
                } else {
                    return $this->throwError(
                        DB_TABLE_DATABASE_ERR_ON_UPDATE_ACTION,
                        "$ftable_name => $table_name");
                }

                if (!is_null($fdata)) {

                    // Loop over rows to be updated from $table
                    foreach ($update_rows as $update_row) {

                        // If necessary, restore case of column names
                        if ($this->_fix_case) {
                            $cols = array_keys($table_obj->col);
                            $update_row = $this->_replaceKeys($update_row, $cols);
                        }

                        // Construct filter for rows that reference $update_row
                        $filter = $this->_buildFKeyFilter($update_row, 
                                                          $rkey, $fkey);
    
                        // Apply action to foreign/referencing rows
                        if ($action == 'restrict') {
                            $sql = array('select'=>'*',
                                         'from'  => $ftable_name,
                                         'where' => $filter);
                            $frows = $this->select($sql);
                            if (PEAR::isError($frows)) {
                                return $frows;
                            }
                            if (count($frows) > 0) {
                                 return $this->throwError(
                                        DB_TABLE_DATABASE_ERR_RESTRICT_UPDATE,
                                        $table_name);
                            }
                        } else {
                            // If 'cascade', 'set null', or 'set default',
                            // then update the referencing foreign key.
                            // Note: Turn off foreign key validity check
                            // during update, then restore original value
                            $check_fkey = $this->_check_fkey;
                            $this->_check_fkey = false;
                            $result = $this->update($ftable_name, $fdata, 
                                                    $filter);
                            $this->_check_fkey = $check_fkey;
                            if (PEAR::isError($result)) {
                                return $result;
                            }
                        }
                    } // foreach ($update_row)
                } // if (!is_null($fdata))

            } // foreach loop over referencing tables
        } // end if

        // Normal completion
        return true;

    }

    // }}}
    // {{{ function autoRecast($flag = true)

    /**
     * Turns on (or off) automatic recasting of insert and update data
     * for all tables
     *
     * @param bool $flag True to automatically recast insert and update
     *                   data, in all tables, false to not do so.
     * @return void
     * @access public
     */
    function autoRecast($flag = true)
    {
        foreach ($this->_table as $table_obj) {
            $table_obj->autoRecast($flag);
        }
    }

    // }}}
    // {{{ function autoInc($flag = true)

    /**
     * Turns on (or off) php implementation of auto-incrementing on insertion
     * for all tables
     *
     * @param bool $flag True to turn on auto-incrementing, false to turn off
     * @return void
     * @access public
     */
    function autoInc($flag = true)
    {
        foreach ($this->_table as $table_obj) {
            $table_obj->auto_inc = $flag;
        }
    }

    // }}}
    // {{{ function delete($table_name, $where)

    /**
     * Deletes all row(s) of table that match a custom where clause.
     *
     * Wrapper for insert method of the corresponding DB_Table object.
     *
     * Implements any required ON DELETE action on tables that reference
     * deleted rows, if on delete actions are enabled.
     *
     * @param string $table_name name of table from which to delete
     * @param string $where      SQL WHERE clause that limits the set
     *                           of records to delete
     * @return boolean true on success (PEAR_Error on failure)
     * @access public
     */
    function delete($table_name, $where)
    {
        // Dereference table object
        if (isset($this->_table[$table_name])) {
            $table_obj =& $this->_table[$table_name];
        } else {
            return $this->throwError(
                       DB_TABLE_DATABASE_ERR_NO_TBL,
                       "delete, $table_name");
        }

        // Delete from $table_obj
        $result = $table_obj->delete($where);

        // Return value: true or PEAR_Error
        if (PEAR::isError($result)) {
            return $result;
        } else {
            return true;
        }

    }

    // }}}
    // {{{ function onDeleteAction(&$table_obj, $where)

    /**
     * Implements ON DELETE actions triggered by deletion of rows of
     * $table_obj that match logical condition $where.
     *
     * This method is called by the DB_Table::delete() method if the table
     * has a parent DB_Table_Database object, and if ON DELETE actions are
     * enabled in the database object. It is called indirectly by the
     * DB_Table_Database::delete() method, which is simply a wrapper for
     * the DB_Table method. 
     *
     * @param  object &$table_obj Reference to a DB_Table object
     * @param  string $where SQL logical condition for deleted rows
     * @return boolean true on success (PEAR_Error on failure)
     * @access public
     */
    function onDeleteAction(&$table_obj, $where)
    {
        $table_name = $table_obj->table;
        if ($this->_act_on_delete and isset($this->_ref_to[$table_name])) {
            $delete_rows = null;
            foreach ($this->_ref_to[$table_name] as $ftable_name) {
                $ref    = $this->_ref[$ftable_name][$table_name];
                $action = $ref['on_delete'];
                if (is_null($action)) {
                   continue;
                } 
                $ftable_obj =& $this->_table[$ftable_name];
                $rtable_obj =& $this->_table[$table_name];
                $fkey = $ref['fkey'];
                $rkey = $ref['rkey'];

                // Select rows to be deleted, if not done previously
                if ($delete_rows === null) {
                    if ($this->backend == 'mdb2') {
                        $fetchmode_assoc = MDB2_FETCHMODE_ASSOC;
                    } else {
                        $fetchmode_assoc = DB_FETCHMODE_ASSOC;
                    }
                    $sql = array('select' => '*',
                                 'from'   => $table_name,
                                 'where'  => $where,
                                 'fetchmode' => $fetchmode_assoc);
                    $delete_rows = $this->select($sql);
                    if (PEAR::isError($delete_rows)) {
                        return $delete_rows;
                    }
                }

                // If set null or set default, construct update $fdata
                // $fdata contains data for updating referencing rows
                if ($action == 'set null' or $action == 'set default') {
                    if (is_string($fkey)) {
                        if ($action == 'set default') {
                            $value = isset($ftable_obj->col[$fkey]['default'])
                                   ? $ftable_obj->col[$fkey]['default'] : null;
                        } else {
                            $value = null;
                        }
                        $fdata = array($fkey => $value);
                    } else {
                        $fdata = array();
                        foreach ($fkey as $fcol) {
                            if ($action == 'set default') {
                                $value = isset($ftable_obj->col[$fcol]['default'])
                                      ? $ftable_obj->col[$fcol]['default'] : null;
                            } else {
                                $value = null;
                            }
                            $fdata[$fcol] = $value;
                        }
                    }
                }

                // Loop over rows to be deleted from $table_name
                foreach ($delete_rows as $delete_row) {

                    // If necessary, restore case of $delete_row column names
                    if ($this->_fix_case) {
                        $cols = array_keys($table_obj->col);
                        $delete_row = $this->_replaceKeys($delete_row, $cols);
                    }

                    // Construct filter for referencing rows in $ftable_name
                    $filter = $this->_buildFKeyFilter($delete_row, 
                                                      $rkey, $fkey);

                    // Apply action for one deleted row
                    if ($action == 'restrict') {
                        // Select for referencing rows throw error if found
                        $sql = array('select'=>'*',
                                     'from'  => $ftable_name,
                                     'where' => $filter);
                        $frows = $this->select($sql);
                        if (PEAR::isError($frows)) {
                            return $frows;
                        }
                        if (count($frows) > 0) {
                             return $this->throwError(
                                       DB_TABLE_DATABASE_ERR_RESTRICT_DELETE,
                                       $table_name);
                        }
                    } elseif ($action == 'cascade') {
                        // Delete referencing rows
                        // Note: Recursion on delete
                        $result = $this->delete($ftable_name, $filter);
                        if (PEAR::isError($result)) {
                            return $result;
                        }
                    } elseif ($action == 'set null' OR $action == 'set default') {
                        // Update referencing rows, using $fdata
                        // Note: Turn off foreign key validity check during
                        // update of referencing key to null or default, then
                        // restore $this->_check_fkey to original value
                        $check_fkey = $this->_check_fkey;
                        $this->_check_fkey = false;
                        $result = $this->update($ftable_name, $fdata, $filter);
                        $this->_check_fkey = $check_fkey;
                        #$result = $ftable_obj->update($fdata, $filter);
                        if (PEAR::isError($result)) {
                            return $result;
                        }
                    } else {
                        // Invalid $action name, throw Error
                        return $this->throwError(
                           DB_TABLE_DATABASE_ERR_ON_DELETE_ACTION,
                           "$ftable_name => $table_name");
                    }
                } // end foreach ($delete_rows)

            } // end foreach ($this->_ref_to[...] as $ftable_name)
        } // end if 

        // Normal completion
        return true; 

    }

    // }}}
    // {{{ function _replaceKeys($data, $keys) 

    /**
     * Returns array in which keys of associative array $data are replaced 
     * by values of sequential array $keys.
     *
     * This function is used by the onDeleteAction() and onUpdateAction() 
     * methods to restore the case of column names in associative arrays 
     * that are returned from an automatically generated query "SELECT * 
     * FROM $table WHERE ...", when these column name keys are returned 
     * with a fixed case. In this usage, $keys is a sequential array of 
     * the names of all columns in $table. 
     *
     * @param  array $data associative array
     * @param  array $key  numerical array of replacement key names
     * @return array associative array in which keys of $data have been 
     *               replaced by the values of array $keys.
     * @access private
     */
    function _replaceKeys($data, $keys) 
    {
        $new_data = array();
        $i = 0;
        foreach ($data as $old_key => $value) {
            $new_key = $keys[$i];
            $new_data[$new_key] = $value;
            $i = $i + 1;
        }
        return $new_data;
    }

    // }}}
    // {{{ function autoJoin($cols = null, $tables = null, $filter = null)

    /**
     * Builds a select command involving joined tables from 
     * a list of column names and/or a list of table names.
     *
     * Returns an query array of the form used in $this->buildSQL,
     * constructed on the basis of a sequential array $cols of
     * column names and/or a sequential array $tables of table
     * names.  The 'FROM' clause in the resulting SQL contains 
     * all the table listed in the $tables parameter and all 
     * those containing the columns listed in the $cols array,
     * as well as any linking tables required to establish 
     * many to many relationships between these tables. The
     * 'WHERE' clause is constructed so as to create an inner
     * join of these tables.
     *
     * The $cols parameter is a sequential array in which the
     * values are column names. Column names may be qualified
     * by a table name, using the SQL table.column syntax, but
     * need not be qualified if they are unambiguous. The 
     * values in $cols can only be column names, and may not 
     * be functions or more complicated SQL expressions. If
     * cols is null, the resulting SQL command will start with 
     * 'SELECT * FROM ...' .
     *
     * The $tables parameter is a sequential array in which the
     * values are table names. If $tables is null, the FROM
     * clause is constructed from the tables containing the
     * columns in the $cols. 
     * 
     * The $params array is an associative array can have
     * 'filter', and 'order' keys, which are both optional.
     * A value $params['filter'] is an condition string to
     * add (i.e., AND) to the automatically constructed set
     * of join conditions. A value $params['order'] is an
     * SQL 'ORDER BY' clause, with no 'ORDER BY' prefix.
     *
     * The function returns an associative array with keys
     * ('select', 'from', 'where', ['order']), for which the
     * associated values are strings containing the SELECT,
     * FROM, WHERE and (optionally) ORDER BY clauses of the
     * select statement. The entire SELECT command string
     * can be obtained by passing the resulting array to
     * the buildSQL method.
     *
     * @param  array $cols   sequential array of column names
     * @param  array $tables sequential array of table names
     * @param  array $filter SQL logical expression to be added 
     *                       (ANDed) to the where clause
     * @return array sql query array for select statement
     * @access public
     */
    function autoJoin($cols = null, $tables = null, $filter = null)
    {
        // initialize array containing clauses of select statement
        $query = array();

        if (is_null($tables)) {
            if (is_null($cols)) {
                return $this->throwError(
                          DB_TABLE_DATABASE_ERR_NO_COL_NO_TBL);
            }
            $tables = array();
        }

        if (!$cols) {
            // If no columns specified, SELECT * FROM ...
            $query['select'] = '*';
        } else {

            // Qualify unqualified columns with table names
            $all_tables = $tables;
            foreach ($cols as $key => $col) {
                $col_array  = $this->validCol($col, $tables);
                if (PEAR::isError($col_array)) {
                     return $col_array;
                }
                $table  = $col_array[0];
                $column = $col_array[1];
                if (is_array($table)) {
                    return $this->throwError(
                           DB_TABLE_DATABASE_ERR_COL_NOT_UNIQUE, $col);
                }
                $cols[$key] = "$table.$column";
                if (!in_array($table, $all_tables)) {
                    $all_tables[] = $table;
                }
            }
            $tables = $all_tables;

            // Construct select clause
            $query['select'] = implode(', ', $cols);

        }

        // Construct array $joins of join conditions
        $n_tables = count($tables);
        if ($n_tables == 1) {
            $query['from'] = $tables[0];
        } else {
            $join_tables = array($tables[0]); // list of joined tables
            $link_tables = array();           // list of required linking tables
            $joins       = array();           // list of join conditions
            // Initialize linked list of unjoined tables
            $next  = array();                  
            for ( $i=1 ; $i < $n_tables-1 ; $i++) {
                $next[$tables[$i]]   = $tables[$i+1];
                $prev[$tables[$i+1]] = $tables[$i];
            }
            $next[$tables[$n_tables-1]] = $tables[1];
            $prev[$tables[1]] = $tables[$n_tables-1];
            $n_remain = $n_tables - 1;
            $head     = $tables[1];
            $table1   = $tables[1];
            $joined   = false;
            $direct   = true;
            while ($n_remain > 0) {

                if ($direct) {

                    // Search for references from table1 to joined tables
                    if (isset($this->_ref[$table1])) {
                        $list = $this->_ref[$table1];
                        foreach ($list as $table2 => $def) {
                            if (in_array($table2, $join_tables)) {
                                if ($joined) {
                                    return $this->throwError(
                                           DB_TABLE_DATABASE_ERR_AMBIG_JOIN,
                                           $table1);
                                }
                                $fkey = $def['fkey'];
                                $rkey = $def['rkey'];
                                if (is_string($fkey)) {
                                    $joins[] = "$table1.$fkey = $table2.$rkey";
                                } else {
                                    for ($i=0; $i < count($fkey); $i++ ) {
                                        $fcol = $fkey[$i];
                                        $rcol = $rkey[$i];
                                        $joins[] = 
                                               "$table1.$fcol = $table2.$rcol";
                                    }
                                }
                                $joined  = true;
                            }
                        }
                    }

                    // Search for references to table1 from joined tables
                    if (isset($this->_ref_to[$table1])) {
                        $list = $this->_ref_to[$table1];
                        foreach ($list as $table2) {
                            if (in_array($table2, $join_tables)) {
                                if ($joined) {
                                    return $this->throwError(
                                              DB_TABLE_DATABASE_ERR_AMBIG_JOIN,
                                              $table1);
                                }
                                $def  = $this->_ref[$table2][$table1];
                                $fkey = $def['fkey'];
                                $rkey = $def['rkey'];
                                if (is_string($fkey)) {
                                    $joins[] = "$table2.$fkey = $table1.$rkey";
                                } else {
                                    for ($i=0; $i < count($fkey); $i++ ) {
                                        $fcol = $fkey[$i];
                                        $rcol = $rkey[$i];
                                        $joins[] = 
                                               "$table2.$fcol = $table1.$rcol";
                                    }
                                }
                                $joined  = true;
                            }
                        }
                    }

                } else {

                    // Search for indirect linking table to table1
                    if (isset($this->_link[$table1])) {
                        foreach ($this->_link[$table1] as $table2 => $links) {
                            if (in_array($table2, $join_tables)) {
                                $n_link = count($links);
                                if ($n_link > 1) {
                                    return $this->throwError(
                                      DB_TABLE_DATABASE_ERR_AMBIG_JOIN,
                                      $table1);
                                }
                                if ($joined and $n_link > 0) {
                                    return $this->throwError(
                                      DB_TABLE_DATABASE_ERR_AMBIG_JOIN,
                                      $table1);
                                }
                                $link  = $links[0];
                                $def1  = $this->_ref[$link][$table1];
                                $fkey1 = $def1['fkey'];
                                $rkey1 = $def1['rkey'];
                                if (is_string($fkey1)) {
                                    $joins[] = "$link.$fkey1 = $table1.$rkey1";
                                } else {
                                    for ($i=0; $i < count($fkey1); $i++ ) {
                                        $fcol1 = $fkey1[$i];
                                        $rcol1 = $rkey1[$i];
                                        $joins[] = 
                                               "$link.$fcol1 = $table1.$rcol1";
                                    }
                                }
                                $def2  = $this->_ref[$link][$table2];
                                $fkey2 = $def2['fkey'];
                                $rkey2 = $def2['rkey'];
                                if (is_string($fkey2)) {
                                    $joins[] = "$link.$fkey2 = $table2.$rkey2";
                                } else {
                                    for ($i=0; $i < count($fkey2); $i++ ) {
                                        $fcol2 = $fkey2[$i];
                                        $rcol2 = $rkey2[$i];
                                        $joins[] = 
                                              "$link.$fcol2 = $table2.$rcol2";
                                    }
                                }
                                $link_tables[] = $link;
                                $joined = true;
                            }
                        }
                    }

                }

                if ($joined) {
                    $join_tables[] = $table1;
                    $n_remain = $n_remain - 1;
                    if ($n_remain > 0) {
                        $head   = $next[$table1];
                        $tail   = $prev[$table1];
                        $prev[$head] = $tail;
                        $next[$tail] = $head;
                        $table1 = $head;
                        $joined = false;
                        $direct = true;
                    }
                } else {
                    $table1 = $next[$table1];
                    if ($table1 == $head) {
                        if ($direct) {
                            $direct = false;
                        } else {
                            return $this->throwError(
                                   DB_TABLE_DATABASE_ERR_FAIL_JOIN,$table1);
                        }
                    }
                }

            }

            // Add any required linking tables to $tables array 
            if ($link_tables) {
                foreach ($link_tables as $link) {
                    if (!in_array($link, $tables)) {
                        $tables[] = $link;
                    }
                }
            }

            // Construct from clause from $tables array
            $query['from'] = implode(', ', $tables);

            // Construct where clause from $joins array
            $query['where'] = implode("\n  AND ", $joins);

        }

        // Add $filter condition, if present
        if ($filter) {
           if (isset($query['where'])) {
               $query['where'] = '( ' . $query['where'] . " )\n" .
                           '  AND ( ' . $filter . ')';
           } else {
               $query['where'] = $filter;
           }
        }

        return $query;
    }

    // }}}
    // {{{ function _buildFKeyFilter($data, $data_key = null, $filt_key = null, $match = 'simple')

    /**
     * Returns WHERE clause equating values of $data array to database column 
     * values
     *
     * Usage: The function is designed to return an SQL logical 
     * expression that equates the values of a set of foreign key columns in
     * associative array $data, which is a row to be inserted or updated in
     * one table, to the values of the corresponding columns of a referenced 
     * table. In this usage, $data_key is the foreign key (a column name or
     * numerical array of column names), and $filt_key is the corresponding
     * referenced key. 
     * 
     * Parameters: Parameter $data is an associative array containing data to 
     * be inserted into or used to update one row of a database table, in which
     * array keys are column names. When present, $data_key contains either
     * the name of a single array key of interest, or a numerical array of such
     * keys. These are usually the names of the columns of a foreign key in 
     * that table. When, $data_key is null or absent, it is taken to be equal 
     * to an array containing all of the keys of $data. When present, $filt_key
     * contains either a string or a numerical array of strings that are 
     * aliases for the keys in $data_key.  These are usually the names of the
     * corresponding columns in the referenced table. When $filt_key is null 
     * or absent, it is equated with $data_key internally.  The function 
     * returns an SQL logical expression that equates the values in $data 
     * whose keys are specified by $data_key, to the values of database 
     * columns whose names are specified in $filt_key. 
     *
     * General case: _buildFKeyFilter returns a SQL logical expression that 
     * equates the values of $data whose keys are given in $data_key with the 
     * values of database columns with names given in $filt_key. For example,
     * if
     * <code>
     *    $data = array( 'k1' => $v1, 'k2' => $v2, ... , 'k10' => $v10 );
     *    $data_key = array('k2', 'k5', 'k7');
     *    $filt_key = array('c2', 'c5', 'c7');
     * </code>
     * then buildFilter($data, $data_key, $filt_key) returns a string
     * <code>
     *    "c2 = $v2 AND c5 = $v5 AND c7 = $v7" 
     * </code>
     * in which the values $v2, $v5, $v7 are replaced by properly quoted 
     * SQL literal values. If, in the above example, $data_key = 'k5' 
     * and $filt_key = 'c5', then the function will return
     * <code>
     *    "c5 = $v5" 
     * </code>
     * where (again) $v5 is replaced by an SQL literal. 
     *
     * Simple case: If parameters $data_key and $filt_key are null, the 
     * behavior is the same as that of the DB_Table_Base::buildFilter() method. 
     * For example, if
     * <code>
     *     $data = array( 'c1' => $v1, 'c2' => $v2, 'c3' => $v3)
     * </code>
     * then _buildFKeyFilter($data) returns a string 
     * <code>
     *     "c1 => $val1 AND c2 => $val2 AND c3 = $v3"
     * </code>
     * in which the values $v1, $v2, $v3 are replaced by SQL literal values,
     * quoted and escaped as appropriate for each data type and the backend.
     *
     * Quoting is done by the DB_Table_Database::quote() method, based on
     * the php type of the values in $array.  The treatment of null values 
     * in $data depends upon the value of the $match parameter.
     *
     * Null values: The treatment to null values in $data depends upon 
     * the value of the $match parameter . If $match == 'simple', an empty
     * string is returned if any $value of $data with a key in $data_key
     * is null. If $match == 'partial', the returned SQL expression 
     * equates only the relevant non-null values of $data to the values of
     * corresponding database columns. If $match == 'full', the function
     * returns an empty string if all of the relevant values of data are
     * null, and returns a PEAR_Error if some of the selected values are
     * null and others are not null.
     *
     * @param array $data     associative array, keys are column names
     * @param mixed $data_key string or numerical array of strings, in which
     *                        values are a set of keys of interest in $data
     * @param mixed $data_key string or numerical array of strings, in which
     *                        values are names of a corresponding set of
     *                        database column names.
     * @return string SQL expression equating values in $data, for which keys
     *                also appear in $data_key, to values of corresponding 
     *                database columns named in $filt_key.
     * @access private
     */
    function _buildFKeyFilter($data, $data_key = null, $filt_key = null, 
                              $match = 'simple')
    {
        // Check $match type value
        if (!in_array($match, array('simple', 'partial', 'full'))) {
            return $this->throwError(
                            DB_TABLE_DATABASE_ERR_MATCH_TYPE);
        }

        // Simple case: Build filter from $data array alone
        if (is_null($data_key) && is_null($filt_key)) {
            return $this->buildFilter($data, $match);
        }

        // Defaults for $data_key and $filt_key:
        if (is_null($data_key)) {
            $data_key = array_keys($data);
        }
        if (is_null($filt_key)) {
            $filt_key = $data_key;
        }

        // General case: $data_key and/or $filt_key not null
        if (is_string($data_key)) {
            if (!is_string($filt_key)) {
                 return $this->throwError(
                            DB_TABLE_DATABASE_ERR_FILT_KEY);
            }
            if (array_key_exists($data_key, $data)) {
                $value = $data[$data_key];
                if (!is_null($value)) {
                    $value = (string) $this->quote($data[$data_key]);
                    return "$filt_key = $value";
                } else {
                    return '';
                }
            } else {
                return '';
            }
        } elseif (is_array($data_key)) {
            if (!is_array($filt_key)) {
                return $this->throwError(
                          DB_TABLE_DATABASE_ERR_FILT_KEY);
            }
            $filter = array();
            for ($i=0; $i < count($data_key); $i++) {
                $data_col = $data_key[$i];
                $filt_col = $filt_key[$i];
                if (array_key_exists($data_col, $data)) {
                    $value = $data[$data_col];
                    if (!is_null($value)) {
                        if ($match == 'full' && isset($found_null)) {
                            return $this->throwError(
                                      DB_TABLE_DATABASE_ERR_FULL_KEY);
                        }
                        $value = $this->quote($value);
                        $filter[] = "$filt_col = $value";
                    } else {
                        $found_null = true;
                    }
                }
            }
            if ($match == 'simple' && isset($found_null)) {
                return '';
            }
            if (count($filter) == 0) {
                return '';
            }
            return implode(' AND ', $filter);
        } else {
            // Invalid data key
            return $this->throwError(
                      DB_TABLE_DATABASE_ERR_DATA_KEY);
        }
    }

    // }}}
    // {{{ function quote($value)

    /**
     * Returns SQL literal string representation of a php value
     *
     * Calls MDB2::quote() or DB_Common::quoteSmart() to enquote and
     * escape string values. If $value is: 
     *    - a string, return the string enquoted and escaped
     *    - a number, return cast of number to string, without quotes
     *    - a boolean, return '1' for true and '0' for false
     *    - null, return the string 'NULL'
     * 
     * @param  mixed  $value 
     * @return string Representation of value as an SQL literal
     * 
     * @access public
     *
     * @see DB_Common::quoteSmart()
     * @see MDB2::quote()
     */
    function quote($value)
    {
        if (is_bool($value)) {
           return $value ? '1' : '0';
        } 
        if ($this->backend == 'mdb2') {
            $value = $this->db->quote($value);
        } else {
            $value = $this->db->quoteSmart($value);
        }
        return (string) $value;
    }
    
    // }}}
    // {{{ function __sleep()

    /**
     * Serializes all table references and sets $db = null, $backend = null
     *
     * @return array names of all properties
     * @access public
     */
    function __sleep()
    {
        $this->db      = null;
        $this->backend = null;
        // needed in setDatabaseInstance, where null is passed by reference
        $null_variable  = null;
        foreach ($this->_table as $name => $table_obj) {
            $table_obj->db = null;
            $table_obj->setDatabaseInstance($null_variable);
            $this->_table[$name] = serialize($table_obj);
        }
        return array_keys(get_object_vars($this));
    }

    // }}}
    // {{{ function __wakeup()

    /**
     * Unserializes DB_Table_Database object and all child DB_Table objects
     *
     * Immediately after unserialization, a DB_Table_Database object 
     * has null $db and $backend properties, as do all of its child 
     * DB_Table objects. The DB_Table_Database::setDB method should 
     * be called immediately after unserialization to re-establish 
     * the database connection, like so:
     * <code>
     *    $db_object = unserialize($serialized_db);
     *    $db_object->setDB($conn);
     * </code>
     * where $conn is a DB/MDB2 object.  This establishes a DB/MDB2
     * connection for both the parent database and all child tables.
     *
     * This method unserializes all of the child DB_Table objects of
     * a DB_Table_Database object. It must thus have access to the 
     * definitions of the associated DB_Table subclasses. These are
     * listed in the $_table_subclass property. If a required subclass 
     * named $subclass is not defined, the __wakeup() method attempts 
     * to autoload a file "$subclass.php" in the directory specified
     * by $this->table_subclass_path. 
     *
     * @return void
     * @access public
     */
    function __wakeup()
    {
        foreach ($this->_table as $name => $table_string) {

            // Check for subclass definition, and autoload if necessary.
            $subclass = $this->_table_subclass[$name];
            if (!is_null($subclass)) {
                if (!class_exists($subclass)) {
                    $dir = $this->_table_subclass_path;
                    require_once $dir . '/' . $subclass . '.php';
                }
            }
            // Unserialize table
            $table_obj = unserialize($table_string);
            // Reset references between database and table objects
            $table_obj->setDatabaseInstance($this);
            $this->_table[$name] = $table_obj;
        }
    }

    // }}}
    // {{{ function toXML()

    /**
     * Returns XML string representation of database declaration
     *
     * @param  string $indent string of whitespace, prefix to each line
     * @return string XML string representation
     * @access public
     */
    function toXML($indent = '') {
        require_once 'DB/Table/XML.php';
        $s = array();
        $s[] = DB_Table_XML::openTag('database', $indent);
        foreach ($this->_table as $name => $table_obj) {
            $s[] = $table_obj->toXML($indent);
        }
        $s[] = DB_Table_XML::closeTag('database', $indent);
        return implode("\n", $s);
    }

    // }}}
    // {{{ function fromXML($xml_string, $conn)

    /**
     * Returns a DB_Table_Database object constructed from an XML string
     *
     * Uses the MDB2 XML schema for a database element, including a new
     * syntax for foreign key indices. 
     *
     * NOTE: This function requires PHP 5. It throws an error if used
     * with PHP 4. 
     *
     * @param  string XML string representation
     * @return object DB_Table_Database object on success (PEAR_Error on failure)
     *
     * @throws PEAR_Error if:
     *    - PHP version is not >= 5.0.0 (...DATABASE_ERR_PHP_VERSION )
     *    - Parsing by simpleXML fails (...DATABASE_ERR_XML_PARSE )
     *
     * @access public
     */
    function fromXML($xml_string, $conn)
    {
        // Check PHP version. Throw error if not >= PHP 5.0.0
        $version = phpversion();
        if (version_compare($version, '5.0.0', "<")) {
            return $this->throwError(
                   DB_TABLE_DATABASE_ERR_PHP_VERSION,
                   $version);
        }

        $xml = simplexml_load_string($xml_string);
        if ($xml == false) {
            return $this->throwError(
                   DB_TABLE_DATABASE_ERR_XML_PARSE);
        }
    
        // Instantiate database object
        $database_name = (string) $xml->name;
        $database_obj  = new DB_Table_Database($conn, $database_name);
   
        // Create array of foreign key references
        $ref = array();
   
        // Loop over tables
        foreach ($xml->table as $table) {
            $table_name = (string) $table->name;
    
            // Instantiate table object
            $table_obj = new DB_Table($conn, $table_name);
        
            // Add columns to table object
            $declaration = $table->declaration;
            foreach ($declaration->field as $field) {
                $col_name = (string) $field->name;
                $type     = (string) $field->type;
                $def = array('type' => $type);
                if (isset($field->length)) {
                    $def['size'] = (integer) $field->length;
                }
                if (isset($field->notnull)) {
                    if ($field->notnull) {
                        $def['require'] = true;
                    } else {
                        $def['require'] = false;
                    }
                }
                if (isset($field->default)) {
                    $def['default'] = $field->default;
                }
                if (isset($field->autoincrement)) {
                    if (is_null($table_obj->auto_inc_col)) {
                        $table_obj->auto_inc_col = $col_name;
                    } else {
                        return $this->throwError(
                                  DB_TABLE_DATABASE_ERR_XML_MULT_AUTO_INC);
                    }
                }
                $table_obj->col[$col_name] = $def;
            }
        
            // Add indices 
            foreach ($declaration->index as $index) {
                if (isset($index->name)) {
                    $name = (string) $index->name;
                } else {
                    $name = null;
                }
                $def = array();
                if (isset($index->primary)) {
                    $def['type'] = 'primary';
                } elseif (isset($index->unique)) {
                    $def['type'] = 'unique';
                } else {
                    $def['type'] = 'normal';
                }
                foreach ($index->field as $field) {
                    $def['cols'][] = (string) $field;
                }
                if ($name) {
                    $table_obj->idx[$name] = $def;
                } else {
                    $table_obj->idx[] = $def;
                }
            }

            // Add table object to database object
            $database_obj->addTable($table_obj);

            // Foreign key references
            foreach ($declaration->foreign as $foreign) {
                if (isset($foreign->name)) {
                    $name = (string) $foreign->name;
                } else {
                    $name = null;
                }
                $fkey = array();
                foreach ($foreign->field as $field) {
                    $fkey[] = (string) $field;
                }
                if (count($fkey) == 1) {
                    $fkey = $fkey[0];
                }
                $rtable = (string) $foreign->references->table;
                if (isset($foreign->references->field)) {
                    $rkey = array();
                    foreach ($foreign->references->field as $field) {
                        $rkey[] = (string) $field;
                    }
                    if (count($rkey)==1) {
                        $rkey = $rkey[0];
                    }
                } else {
                    $rkey = null;
                }
                if (isset($foreign->ondelete)) {
                    $on_delete = (string) $foreign->ondelete;
                } else {
                    $on_delete = null;
                }
                if (isset($foreign->onupdate)) {
                    $on_update = (string) $foreign->onupdate;
                } else {
                    $on_update = null;
                }

                // Add reference definition to $ref array
                $def = array();
                $def['fkey'] = $fkey;
                $def['rkey'] = $rkey;
                $def['on_delete'] = $on_delete;
                $def['on_update'] = $on_update;
                if (!isset($ref[$table_name])) {
                    $ref[$table_name] = array();
                }
                $ref[$table_name][$rtable] = $def;

            }

            // Release variable $table_obj to refer to another table
            unset($table_obj);
        }

        // Add all references to database object
        foreach ($ref as $ftable => $list) {
            foreach ($list as $rtable => $def) {
                $fkey = $def['fkey'];
                $rkey = $def['rkey'];
                $on_delete = $def['on_delete'];
                $on_update = $def['on_update'];
                $database_obj->addRef($ftable, $fkey, $rtable, $rkey,
                                      $on_delete, $on_update);
            }
        }

        return $database_obj;
    }

    // }}}

    // }}}
}

// }}}

/* Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * c-hanging-comment-ender-p: nil
 * End:
 */

?>
