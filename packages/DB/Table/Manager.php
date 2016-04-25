<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * Creates, checks or alters tables from DB_Table definitions.
 * 
 * DB_Table_Manager provides database automated table creation
 * facilities.
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
 * @version  CVS: $Id: Manager.php,v 1.40 2008/12/25 19:56:35 wiesemann Exp $
 * @link     http://pear.php.net/package/DB_Table
 */

require_once 'DB/Table.php';


/**
* Valid types for the different data types in the different DBMS.
*/
$GLOBALS['_DB_TABLE']['valid_type'] = array(
    'fbsql' => array(  // currently not supported
        'boolean'   => '',
        'char'      => '',
        'varchar'   => '',
        'smallint'  => '',
        'integer'   => '',
        'bigint'    => '',
        'decimal'   => '',
        'single'    => '',
        'double'    => '',
        'clob'      => '',
        'date'      => '',
        'time'      => '',
        'timestamp' => ''
    ),
    'ibase' => array(
        'boolean'   => array('char', 'integer', 'real', 'smallint'),
        'char'      => array('char', 'varchar'),
        'varchar'   => 'varchar',
        'smallint'  => array('integer', 'smallint'),
        'integer'   => 'integer',
        'bigint'    => array('bigint', 'integer'),
        'decimal'   => 'numeric',
        'single'    => array('double precision', 'float'),
        'double'    => 'double precision',
        'clob'      => 'blob',
        'date'      => 'date',
        'time'      => 'time',
        'timestamp' => 'timestamp'
    ),
    'mssql' => array(  // currently not supported
        'boolean'   => '',
        'char'      => '',
        'varchar'   => '',
        'smallint'  => '',
        'integer'   => '',
        'bigint'    => '',
        'decimal'   => '',
        'single'    => '',
        'double'    => '',
        'clob'      => '',
        'date'      => '',
        'time'      => '',
        'timestamp' => ''
    ),
    'mysql' => array(
        'boolean'   => array('char', 'decimal', 'int', 'real', 'tinyint'),
        'char'      => array('char', 'string', 'varchar'),
        'varchar'   => array('char', 'string', 'varchar'),
        'smallint'  => array('smallint', 'int'),
        'integer'   => 'int',
        'bigint'    => array('int', 'bigint'),
        'decimal'   => array('decimal', 'real'),
        'single'    => array('double', 'real'),
        'double'    => array('double', 'real'),
        'clob'      => array('blob', 'longtext', 'tinytext', 'text', 'mediumtext'),
        'date'      => array('char', 'date', 'string'),
        'time'      => array('char', 'string', 'time'),
        'timestamp' => array('char', 'datetime', 'string')
    ),
    'mysqli' => array(
        'boolean'   => array('char', 'decimal', 'tinyint'),
        'char'      => array('char', 'varchar'),
        'varchar'   => array('char', 'varchar'),
        'smallint'  => array('smallint', 'int'),
        'integer'   => 'int',
        'bigint'    => array('int', 'bigint'),
        'decimal'   => 'decimal',
        'single'    => array('double', 'float'),
        'double'    => 'double',
        'clob'      => array('blob', 'longtext', 'tinytext', 'text', 'mediumtext'),
        'date'      => array('char', 'date', 'varchar'),
        'time'      => array('char', 'time', 'varchar'),
        'timestamp' => array('char', 'datetime', 'varchar')
    ),
    'oci8' => array(
        'boolean'   => 'number',
        'char'      => array('char', 'varchar2'),
        'varchar'   => 'varchar2',
        'smallint'  => 'number',
        'integer'   => 'number',
        'bigint'    => 'number',
        'decimal'   => 'number',
        'single'    => array('float', 'number'),
        'double'    => array('float', 'number'),
        'clob'      => 'clob',
        'date'      => array('char', 'date'),
        'time'      => array('char', 'date'),
        'timestamp' => array('char', 'date')
    ),
    'pgsql' => array(
        'boolean'   => array('bool', 'numeric'),
        'char'      => array('bpchar', 'varchar'),
        'varchar'   => 'varchar',
        'smallint'  => array('int2', 'int4'),
        'integer'   => 'int4',
        'bigint'    => array('int4', 'int8'),
        'decimal'   => 'numeric',
        'single'    => array('float4', 'float8'),
        'double'    => 'float8',
        'clob'      => array('oid', 'text'),
        'date'      => array('bpchar', 'date'),
        'time'      => array('bpchar', 'time'),
        'timestamp' => array('bpchar', 'timestamp')
    ),
    'sqlite' => array(
        'boolean'   => 'boolean',
        'char'      => 'char',
        'varchar'   => array('char', 'varchar'),
        'smallint'  => array('int', 'smallint'),
        'integer'   => array('int', 'integer'),
        'bigint'    => array('int', 'bigint'),
        'decimal'   => array('decimal', 'numeric'),
        'single'    => array('double', 'float'),
        'double'    => 'double',
        'clob'      => array('clob', 'longtext'),
        'date'      => 'date',
        'time'      => 'time',
        'timestamp' => array('datetime', 'timestamp')
    ),
);

/**
* Mapping between DB_Table and MDB2 data types.
*/
$GLOBALS['_DB_TABLE']['mdb2_type'] = array(
    'boolean'   => 'boolean',
    'char'      => 'text',
    'varchar'   => 'text',
    'smallint'  => 'integer',
    'integer'   => 'integer',
    'bigint'    => 'integer',
    'decimal'   => 'decimal',
    'single'    => 'float',
    'double'    => 'float',
    'clob'      => 'clob',
    'date'      => 'date',
    'time'      => 'time',
    'timestamp' => 'timestamp'
);

/**
 * Creates, checks or alters tables from DB_Table definitions.
 * 
 * DB_Table_Manager provides database automated table creation
 * facilities.
 * 
 * @category Database
 * @package  DB_Table
 * @author   Paul M. Jones <pmjones@php.net>
 * @author   David C. Morse <morse@php.net>
 * @author   Mark Wiesemann <wiesemann@php.net>
 * @version  Release: 1.5.6
 * @link     http://pear.php.net/package/DB_Table
 */
class DB_Table_Manager {


   /**
    * 
    * Create the table based on DB_Table column and index arrays.
    * 
    * @static
    * 
    * @access public
    * 
    * @param object &$db A PEAR DB/MDB2 object.
    * 
    * @param string $table The table name to connect to in the database.
    * 
    * @param mixed $column_set A DB_Table $this->col array.
    * 
    * @param mixed $index_set A DB_Table $this->idx array.
    * 
    * @return mixed Boolean false if there was no attempt to create the
    * table, boolean true if the attempt succeeded, and a PEAR_Error if
    * the attempt failed.
    * 
    */

    function create(&$db, $table, $column_set, $index_set)
    {
        if (is_subclass_of($db, 'db_common')) {
            $backend = 'db';
        } elseif (is_subclass_of($db, 'mdb2_driver_common')) {
            $backend = 'mdb2';
            $db->loadModule('Manager');
        }
        $phptype = $db->phptype;

        // columns to be created
        $column = array();

        // max. value for scope (only used with MDB2 as backend)
        $max_scope = 0;
        
        // indexes to be created
        $indexes = array();
        
        // check the table name
        $name_check = DB_Table_Manager::_validateTableName($table);
        if (PEAR::isError($name_check)) {
            return $name_check;
        }
        
        
        // -------------------------------------------------------------
        // 
        // validate each column mapping and build the individual
        // definitions, and note column indexes as we go.
        //
        
        if (is_null($column_set)) {
            $column_set = array();
        }
        
        foreach ($column_set as $colname => $val) {
            
            $colname = trim($colname);
            
            // check the column name
            $name_check = DB_Table_Manager::_validateColumnName($colname);
            if (PEAR::isError($name_check)) {
                return $name_check;
            }
            
            
            // prepare variables
            $type    = (isset($val['type']))    ? $val['type']    : null;
            $size    = (isset($val['size']))    ? $val['size']    : null;
            $scope   = (isset($val['scope']))   ? $val['scope']   : null;
            $require = (isset($val['require'])) ? $val['require'] : null;
            $default = (isset($val['default'])) ? $val['default'] : null;

            if ($backend == 'mdb2') {

                // get the declaration string
                $result = DB_Table_Manager::getDeclareMDB2($type,
                    $size, $scope, $require, $default, $max_scope);

                // did it work?
                if (PEAR::isError($result)) {
                    $result->userinfo .= " ('$colname')";
                    return $result;
                }

                // add the declaration to the array of all columns
                $column[$colname] = $result;

            } else {

                // get the declaration string
                $result = DB_Table_Manager::getDeclare($phptype, $type,
                    $size, $scope, $require, $default);

                // did it work?
                if (PEAR::isError($result)) {
                    $result->userinfo .= " ('$colname')";
                    return $result;
                }

                // add the declaration to the array of all columns
                $column[] = "$colname $result";

            }

        }
        
        
        // -------------------------------------------------------------
        // 
        // validate the indexes.
        //
        
        if (is_null($index_set)) {
            $index_set = array();
        }

        $count_primary_keys = 0;

        foreach ($index_set as $idxname => $val) {
            
            list($type, $cols) = DB_Table_Manager::_getIndexTypeAndColumns($val, $idxname);

            $newIdxName = '';

            // check the index definition
            $index_check = DB_Table_Manager::_validateIndexName($idxname,
                $table, $phptype, $type, $cols, $column_set, $newIdxName);
            if (PEAR::isError($index_check)) {
                return $index_check;
            }

            // check number of primary keys (only one is allowed)
            if ($type == 'primary') {
                // SQLite does not support primary keys
                if ($phptype == 'sqlite') {
                    return DB_Table::throwError(DB_TABLE_ERR_DECLARE_PRIM_SQLITE);
                }
                $count_primary_keys++;
            }
            if ($count_primary_keys > 1) {
                return DB_Table::throwError(DB_TABLE_ERR_DECLARE_PRIMARY);
            }

            // create index entry
            if ($backend == 'mdb2') {

                // array with column names as keys
                $idx_cols = array();
                foreach ($cols as $col) {
                    $idx_cols[$col] = array();
                }

                switch ($type) {
                    case 'primary':
                        $indexes['primary'][$newIdxName] =
                            array('fields'  => $idx_cols,
                                  'primary' => true);
                        break;
                    case 'unique':
                        $indexes['unique'][$newIdxName] =
                            array('fields' => $idx_cols,
                                  'unique' => true);
                        break;
                    case 'normal':
                        $indexes['normal'][$newIdxName] =
                            array('fields' => $idx_cols);
                        break;
                }
                
            } else {

                $indexes[] = DB_Table_Manager::getDeclareForIndex($phptype,
                    $type, $newIdxName, $table, $cols);

            }
            
        }
        
        
        // -------------------------------------------------------------
        // 
        // now for the real action: create the table and indexes!
        //
        if ($backend == 'mdb2') {

            // save user defined 'decimal_places' option
            $decimal_places = $db->getOption('decimal_places');
            $db->setOption('decimal_places', $max_scope);

            // attempt to create the table
            $result = $db->manager->createTable($table, $column);
            // restore user defined 'decimal_places' option
            $db->setOption('decimal_places', $decimal_places);
            if (PEAR::isError($result)) {
                return $result;
            }

        } else {

            // build the CREATE TABLE command
            $cmd = "CREATE TABLE $table (\n\t";
            $cmd .= implode(",\n\t", $column);
            $cmd .= "\n)";

            // attempt to create the table
            $result = $db->query($cmd);
            if (PEAR::isError($result)) {
                return $result;
            }

        }

        $result = DB_Table_Manager::_createIndexesAndContraints($db, $backend,
                                                                $table, $indexes);
        if (PEAR::isError($result)) {
            return $result;
        }

        // we're done!
        return true;
    }


   /**
    * 
    * Verify whether the table and columns exist, whether the columns
    * have the right type and whether the indexes exist.
    * 
    * @static
    * 
    * @access public
    * 
    * @param object &$db A PEAR DB/MDB2 object.
    * 
    * @param string $table The table name to connect to in the database.
    * 
    * @param mixed $column_set A DB_Table $this->col array.
    * 
    * @param mixed $index_set A DB_Table $this->idx array.
    * 
    * @return mixed Boolean true if the verification was successful, and a
    * PEAR_Error if verification failed.
    * 
    */

    function verify(&$db, $table, $column_set, $index_set)
    {
        if (is_subclass_of($db, 'db_common')) {
            $backend = 'db';
            $reverse =& $db;
            $table_info_mode = DB_TABLEINFO_FULL;
            $table_info_error = DB_ERROR_NEED_MORE_DATA;
        } elseif (is_subclass_of($db, 'mdb2_driver_common')) {
            $backend = 'mdb2';
            $reverse =& $this->db->loadModule('Reverse');
            $table_info_mode = MDB2_TABLEINFO_FULL;
            $table_info_error = MDB2_ERROR_NEED_MORE_DATA;
        }
        $phptype = $db->phptype;

        // check #1: does the table exist?

        // check the table name
        $name_check = DB_Table_Manager::_validateTableName($table);
        if (PEAR::isError($name_check)) {
            return $name_check;
        }

        // get table info
        $tableInfo = $reverse->tableInfo($table, $table_info_mode);
        if (PEAR::isError($tableInfo)) {
            if ($tableInfo->getCode() == $table_info_error) {
                return DB_Table::throwError(
                    DB_TABLE_ERR_VER_TABLE_MISSING,
                    "(table='$table')"
                );
            }
            return $tableInfo;
        }
        $tableInfoOrder = array_change_key_case($tableInfo['order'], CASE_LOWER);

        if (is_null($column_set)) {
            $column_set = array();
        }

        foreach ($column_set as $colname => $val) {
            $colname = strtolower(trim($colname));
            
            // check the column name
            $name_check = DB_Table_Manager::_validateColumnName($colname);
            if (PEAR::isError($name_check)) {
                return $name_check;
            }

            // check #2: do all columns exist?
            $column_exists = DB_Table_Manager::_columnExists($colname,
                $tableInfoOrder, 'verify');
            if (PEAR::isError($column_exists)) {
                return $column_exists;
            }

            // check #3: do all columns have the right type?

            // check whether the column type is a known type
            $type_check = DB_Table_Manager::_validateColumnType($phptype, $val['type']);
            if (PEAR::isError($type_check)) {
                return $type_check;
            }

            // check whether the column has the right type
            $type_check = DB_Table_Manager::_checkColumnType($phptype,
                $colname, $val['type'], $tableInfoOrder, $tableInfo, 'verify');
            if (PEAR::isError($type_check)) {
                return $type_check;
            }

        }

        // check #4: do all indexes exist?
        $table_indexes = DB_Table_Manager::getIndexes($db, $table);
        if (PEAR::isError($table_indexes)) {
            return $table_indexes;
        }

        if (is_null($index_set)) {
            $index_set = array();
        }
        
        foreach ($index_set as $idxname => $val) {
          
            list($type, $cols) = DB_Table_Manager::_getIndexTypeAndColumns($val, $idxname);

            $newIdxName = '';

            // check the index definition
            $index_check = DB_Table_Manager::_validateIndexName($idxname,
                $table, $phptype, $type, $cols, $column_set, $newIdxName);
            if (PEAR::isError($index_check)) {
                return $index_check;
            }

            // check whether the index has the right type and has all
            // specified columns
            $index_check = DB_Table_Manager::_checkIndex($idxname, $newIdxName,
                $type, $cols, $table_indexes, 'verify');
            if (PEAR::isError($index_check)) {
                return $index_check;
            }

        }

        return true;
    }


   /**
    * 
    * Alter columns and indexes of a table based on DB_Table column and index
    * arrays.
    * 
    * @static
    * 
    * @access public
    * 
    * @param object &$db A PEAR DB/MDB2 object.
    * 
    * @param string $table The table name to connect to in the database.
    * 
    * @param mixed $column_set A DB_Table $this->col array.
    * 
    * @param mixed $index_set A DB_Table $this->idx array.
    * 
    * @return bool|object True if altering was successful or a PEAR_Error on
    * failure.
    * 
    */

    function alter(&$db, $table, $column_set, $index_set)
    {
        $phptype = $db->phptype;

        if (is_subclass_of($db, 'db_common')) {
            $backend = 'db';
            $reverse =& $db;
            // workaround for missing index and constraint information methods
            // in PEAR::DB ==> use adopted code from MDB2's driver classes
            require_once 'DB/Table/Manager/' . $phptype . '.php';
            $classname = 'DB_Table_Manager_' . $phptype;
            $dbtm = new $classname();
            $dbtm->_db =& $db;  // pass database instance to the 'workaround' class
            $manager =& $dbtm;
            $table_info_mode = DB_TABLEINFO_FULL;
            $ok_const = DB_OK;
        } elseif (is_subclass_of($db, 'mdb2_driver_common')) {
            $backend = 'mdb2';
            $db->loadModule('Reverse');
            $manager =& $db->manager;
            $reverse =& $db->reverse;
            $table_info_mode = MDB2_TABLEINFO_FULL;
            $ok_const = MDB2_OK;
        }

        // get table info
        $tableInfo = $reverse->tableInfo($table, $table_info_mode);
        if (PEAR::isError($tableInfo)) {
            return $tableInfo;
        }
        $tableInfoOrder = array_change_key_case($tableInfo['order'], CASE_LOWER);

        // emulate MDB2 Reverse extension for PEAR::DB as backend
        if (is_subclass_of($db, 'db_common')) {
            $reverse =& $dbtm;
        }

        // check (and alter) columns
        if (is_null($column_set)) {
            $column_set = array();
        }

        foreach ($column_set as $colname => $val) {
            $colname = strtolower(trim($colname));
            
            // check the column name
            $name_check = DB_Table_Manager::_validateColumnName($colname);
            if (PEAR::isError($name_check)) {
                return $name_check;
            }

            // check the column's existence
            $column_exists = DB_Table_Manager::_columnExists($colname,
                $tableInfoOrder, 'alter');
            if (PEAR::isError($column_exists)) {
                return $column_exists;
            }
            if ($column_exists === false) {  // add the column
                $definition = DB_Table_Manager::_getColumnDefinition($backend,
                    $phptype, $val);
                if (PEAR::isError($definition)) {
                    return $definition;
                }
                $changes = array('add' => array($colname => $definition));
                if (array_key_exists('debug', $GLOBALS['_DB_TABLE'])) {
                    echo "(alter) New table field will be added ($colname):\n";
                    var_dump($changes);
                    echo "\n";
                }
                $result = $manager->alterTable($table, $changes, false);
                if (PEAR::isError($result)) {
                    return $result;
                }
                continue;
            }

            // check whether the column type is a known type
            $type_check = DB_Table_Manager::_validateColumnType($phptype, $val['type']);
            if (PEAR::isError($type_check)) {
                return $type_check;
            }

            // check whether the column has the right type
            $type_check = DB_Table_Manager::_checkColumnType($phptype,
                $colname, $val['type'], $tableInfoOrder, $tableInfo, 'alter');
            if (PEAR::isError($type_check)) {
                return $type_check;
            }
            if ($type_check === false) {  // change the column type
                $definition = DB_Table_Manager::_getColumnDefinition($backend,
                    $phptype, $val);
                if (PEAR::isError($definition)) {
                    return $definition;
                }
                $changes = array('change' =>
                    array($colname => array('type' => null,
                                            'definition' => $definition)));
                if (array_key_exists('debug', $GLOBALS['_DB_TABLE'])) {
                    echo "(alter) Table field's type will be changed ($colname):\n";
                    var_dump($changes);
                    echo "\n";
                }
                $result = $manager->alterTable($table, $changes, false);
                if (PEAR::isError($result)) {
                    return $result;
                }
                continue;
            }

        }

        // get information about indexes / constraints
        $table_indexes = DB_Table_Manager::getIndexes($db, $table);
        if (PEAR::isError($table_indexes)) {
            return $table_indexes;
        }

        // check (and alter) indexes / constraints
        if (is_null($index_set)) {
            $index_set = array();
        }
        
        foreach ($index_set as $idxname => $val) {
          
            list($type, $cols) = DB_Table_Manager::_getIndexTypeAndColumns($val, $idxname);

            $newIdxName = '';

            // check the index definition
            $index_check = DB_Table_Manager::_validateIndexName($idxname,
                $table, $phptype, $type, $cols, $column_set, $newIdxName);
            if (PEAR::isError($index_check)) {
                return $index_check;
            }

            // check whether the index has the right type and has all
            // specified columns
            $index_check = DB_Table_Manager::_checkIndex($idxname, $newIdxName,
                $type, $cols, $table_indexes, 'alter');
            if (PEAR::isError($index_check)) {
                return $index_check;
            }
            if ($index_check === false) {  // (1) drop wrong index/constraint
                                           // (2) add right index/constraint
                if ($backend == 'mdb2') {
                    // save user defined 'idxname_format' option
                    $idxname_format = $db->getOption('idxname_format');
                    $db->setOption('idxname_format', '%s');
                }
                // drop index/constraint only if it exists
                foreach (array('normal', 'unique', 'primary') as $idx_type) {
                    if (array_key_exists(strtolower($newIdxName),
                                         $table_indexes[$idx_type])) {
                        if (array_key_exists('debug', $GLOBALS['_DB_TABLE'])) {
                            echo "(alter) Index/constraint will be deleted (name: '$newIdxName', type: '$idx_type').\n";
                        }
                        if ($idx_type == 'normal') {
                            $result = $manager->dropIndex($table, $newIdxName);
                        } else {
                            $result = $manager->dropConstraint($table, $newIdxName);
                        }
                        if (PEAR::isError($result)) {
                            if ($backend == 'mdb2') {
                                // restore user defined 'idxname_format' option
                                $db->setOption('idxname_format', $idxname_format);
                            }
                            return $result;
                        }
                        break;
                    }
                }

                // prepare index/constraint definition
                $indexes = array();
                if ($backend == 'mdb2') {

                    // array with column names as keys
                    $idx_cols = array();
                    foreach ($cols as $col) {
                        $idx_cols[$col] = array();
                    }

                    switch ($type) {
                        case 'primary':
                            $indexes['primary'][$newIdxName] =
                                array('fields'  => $idx_cols,
                                      'primary' => true);
                            break;
                        case 'unique':
                            $indexes['unique'][$newIdxName] =
                                array('fields' => $idx_cols,
                                      'unique' => true);
                            break;
                        case 'normal':
                            $indexes['normal'][$newIdxName] =
                                array('fields' => $idx_cols);
                            break;
                    }

                } else {

                    $indexes[] = DB_Table_Manager::getDeclareForIndex($phptype,
                        $type, $newIdxName, $table, $cols);

                }

                // create index/constraint
                if (array_key_exists('debug', $GLOBALS['_DB_TABLE'])) {
                    echo "(alter) New index/constraint will be created (name: '$newIdxName', type: '$type'):\n";
                    var_dump($indexes);
                    echo "\n";
                }
                $result = DB_Table_Manager::_createIndexesAndContraints(
                    $db, $backend, $table, $indexes);
                if ($backend == 'mdb2') {
                    // restore user defined 'idxname_format' option
                    $db->setOption('idxname_format', $idxname_format);
                }
                if (PEAR::isError($result)) {
                    return $result;
                }

                continue;
            }

        }

        return true;
    }


   /**
    * 
    * Check whether a table exists.
    * 
    * @static
    * 
    * @access public
    * 
    * @param object &$db A PEAR DB/MDB2 object.
    * 
    * @param string $table The table name that should be checked.
    * 
    * @return bool|object True if the table exists, false if not, or a
    * PEAR_Error on failure.
    * 
    */

    function tableExists(&$db, $table)
    {
        if (is_subclass_of($db, 'db_common')) {
            $list = $db->getListOf('tables');
        } elseif (is_subclass_of($db, 'mdb2_driver_common')) {
            $db->loadModule('Manager');
            $list = $db->manager->listTables();
        }
        if (PEAR::isError($list)) {
            return $list;
        }
        array_walk($list, create_function('&$value,$key',
                                          '$value = trim(strtolower($value));'));
        return in_array(strtolower($table), $list);
    }


   /**
    * 
    * Get the column declaration string for a DB_Table column.
    * 
    * @static
    * 
    * @access public
    * 
    * @param string $phptype The DB/MDB2 phptype key.
    * 
    * @param string $coltype The DB_Table column type.
    * 
    * @param int $size The size for the column (needed for string and
    * decimal).
    * 
    * @param int $scope The scope for the column (needed for decimal).
    * 
    * @param bool $require True if the column should be NOT NULL, false
    * allowed to be NULL.
    * 
    * @param string $default The SQL calculation for a default value.
    * 
    * @return string|object A declaration string on success, or a
    * PEAR_Error on failure.
    * 
    */

    function getDeclare($phptype, $coltype, $size = null, $scope = null,
        $require = null, $default = null)
    {
        // validate char/varchar/decimal type declaration
        $validation = DB_Table_Manager::_validateTypeDeclaration($coltype, $size,
                                                                 $scope);
        if (PEAR::isError($validation)) {
            return $validation;
        }
        
        // map of column types and declarations for this RDBMS
        $map = $GLOBALS['_DB_TABLE']['type'][$phptype];
        
        // is it a recognized column type?
        $types = array_keys($map);
        if (! in_array($coltype, $types)) {
            return DB_Table::throwError(
                DB_TABLE_ERR_DECLARE_TYPE,
                "('$coltype')"
            );
        }
        
        // basic declaration
        switch ($coltype) {
    
        case 'char':
        case 'varchar':
            $declare = $map[$coltype] . "($size)";
            break;
        
        case 'decimal':
            $declare = $map[$coltype] . "($size,$scope)";
            break;
        
        default:
            $declare = $map[$coltype];
            break;
        
        }
        
        // set the "NULL"/"NOT NULL" portion
        $null = ' NULL';
        if ($phptype == 'ibase') {  // Firebird does not like 'NULL'
            $null = '';             // in CREATE TABLE
        }
        if ($phptype == 'pgsql') {  // PostgreSQL does not like 'NULL'
            $null = '';             // in ALTER TABLE
        }
        $declare .= ($require) ? ' NOT NULL' : $null;
        
        // set the "DEFAULT" portion
        if ($default) {
            switch ($coltype) {        
                case 'char':
                case 'varchar':
                case 'clob':
                    $declare .= " DEFAULT '$default'";
                    break;

                default:
                    $declare .= " DEFAULT $default";
                    break;
            }
        }
        
        // done
        return $declare;
    }


   /**
    * 
    * Get the column declaration string for a DB_Table column.
    * 
    * @static
    * 
    * @access public
    * 
    * @param string $coltype The DB_Table column type.
    * 
    * @param int $size The size for the column (needed for string and
    * decimal).
    * 
    * @param int $scope The scope for the column (needed for decimal).
    * 
    * @param bool $require True if the column should be NOT NULL, false
    * allowed to be NULL.
    * 
    * @param string $default The SQL calculation for a default value.
    * 
    * @param int $max_scope The maximal scope for all table column
    * (pass-by-reference).
    * 
    * @return string|object A MDB2 column definition array on success, or a
    * PEAR_Error on failure.
    * 
    */

    function getDeclareMDB2($coltype, $size = null, $scope = null,
        $require = null, $default = null, &$max_scope)
    {
        // validate char/varchar/decimal type declaration
        $validation = DB_Table_Manager::_validateTypeDeclaration($coltype, $size,
                                                                 $scope);
        if (PEAR::isError($validation)) {
            return $validation;
        }

        // map of MDB2 column types
        $map = $GLOBALS['_DB_TABLE']['mdb2_type'];
        
        // is it a recognized column type?
        $types = array_keys($map);
        if (! in_array($coltype, $types)) {
            return DB_Table::throwError(
                DB_TABLE_ERR_DECLARE_TYPE,
                "('$coltype')"
            );
        }

        // build declaration array
        $new_column = array(
            'type'    => $map[$coltype],
            'notnull' => $require
        );

        if ($size) {
            $new_column['length'] = $size;
        }

        // determine integer length to be used in MDB2
        if (in_array($coltype, array('smallint', 'integer', 'bigint'))) {
            switch ($coltype) {
                case 'smallint':
                    $new_column['length'] = 2;
                    break;
                case 'integer':
                    $new_column['length'] = 4;
                    break;
                case 'bigint':
                    $new_column['length'] = 5;
                    break;
            }
        }

        if ($scope) {
            $max_scope = max($max_scope, $scope);
        }

        if ($default) {
            $new_column['default'] = $default;
        }

        return $new_column;
    }


   /**
    * 
    * Get the index declaration string for a DB_Table index.
    * 
    * @static
    * 
    * @access public
    * 
    * @param string $phptype The DB phptype key.
    * 
    * @param string $type The index type.
    * 
    * @param string $idxname The index name.
    * 
    * @param string $table The table name.
    * 
    * @param mixed $cols Array with the column names for the index.
    * 
    * @return string A declaration string.
    * 
    */

    function getDeclareForIndex($phptype, $type, $idxname, $table, $cols)
    {
        // string of column names
        $colstring = implode(', ', $cols);

        switch ($type) {

            case 'primary':
                switch ($phptype) {
                    case 'ibase':
                    case 'oci8':
                    case 'pgsql':
                        $declare  = "ALTER TABLE $table ADD";
                        $declare .= " CONSTRAINT $idxname";
                        $declare .= " PRIMARY KEY ($colstring)";
                        break;
                    case 'mysql':
                    case 'mysqli':
                        $declare  = "ALTER TABLE $table ADD PRIMARY KEY";
                        $declare .= " ($colstring)";
                        break;
                    case 'sqlite':
                        // currently not possible
                        break;
                }
                break;

            case 'unique':
                $declare = "CREATE UNIQUE INDEX $idxname ON $table ($colstring)";
                break;

            case 'normal':
                $declare = "CREATE INDEX $idxname ON $table ($colstring)";
                break;

        }
        
        return $declare;
    }


   /**
    * 
    * Return the definition array for a column.
    * 
    * @access private
    * 
    * @param string $backend The name of the backend ('db' or 'mdb2').
    * 
    * @param string $phptype The DB/MDB2 phptype key.
    * 
    * @param mixed $column A single DB_Table column definition array.
    * 
    * @return mixed|object Declaration string (DB), declaration array (MDB2) or a
    * PEAR_Error with a description about the invalidity, otherwise.
    * 
    */

    function _getColumnDefinition($backend, $phptype, $column)
    {
        static $max_scope;

        // prepare variables
        $type    = (isset($column['type']))    ? $column['type']    : null;
        $size    = (isset($column['size']))    ? $column['size']    : null;
        $scope   = (isset($column['scope']))   ? $column['scope']   : null;
        $require = (isset($column['require'])) ? $column['require'] : null;
        $default = (isset($column['default'])) ? $column['default'] : null;

        if ($backend == 'db') {
            return DB_Table_Manager::getDeclare($phptype, $type,
                    $size, $scope, $require, $default);
        } else {
            return DB_Table_Manager::getDeclareMDB2($type,
                    $size, $scope, $require, $default, $max_scope);
        }
    }


   /**
    * 
    * Check char/varchar/decimal type declarations for validity.
    * 
    * @access private
    * 
    * @param string $coltype The DB_Table column type.
    * 
    * @param int $size The size for the column (needed for string and
    * decimal).
    * 
    * @param int $scope The scope for the column (needed for decimal).
    * 
    * @return bool|object Boolean true if the type declaration is valid or a
    * PEAR_Error with a description about the invalidity, otherwise.
    * 
    */

    function _validateTypeDeclaration($coltype, $size, $scope)
    {
        // validate char and varchar: does it have a size?
        if (($coltype == 'char' || $coltype == 'varchar') &&
            ($size < 1 || $size > 255) ) {
            return DB_Table::throwError(
                DB_TABLE_ERR_DECLARE_STRING,
                "(size='$size')"
            );
        }
        
        // validate decimal: does it have a size and scope?
        if ($coltype == 'decimal' &&
            ($size < 1 || $size > 255 || $scope < 0 || $scope > $size)) {
            return DB_Table::throwError(
                DB_TABLE_ERR_DECLARE_DECIMAL,
                "(size='$size' scope='$scope')"
            );
        }

        return true;
    }


   /**
    * 
    * Check a table name for validity.
    * 
    * @access private
    * 
    * @param string $tablename The table name.
    * 
    * @return bool|object Boolean true if the table name is valid or a
    * PEAR_Error with a description about the invalidity, otherwise.
    * 
    */

    function _validateTableName($tablename)
    {
        // is the table name too long?
        if (   $GLOBALS['_DB_TABLE']['disable_length_check'] === false
            && strlen($tablename) > 30
           ) {
            return DB_Table::throwError(
                DB_TABLE_ERR_TABLE_STRLEN,
                " ('$tablename')"
            );
        }

        return true;
    }


   /**
    * 
    * Check a column name for validity.
    * 
    * @access private
    * 
    * @param string $colname The column name.
    * 
    * @return bool|object Boolean true if the column name is valid or a
    * PEAR_Error with a description about the invalidity, otherwise.
    * 
    */

    function _validateColumnName($colname)
    {
        // column name cannot be a reserved keyword
        $reserved = in_array(
            strtoupper($colname),
            $GLOBALS['_DB_TABLE']['reserved']
        );

        if ($reserved) {
            return DB_Table::throwError(
                DB_TABLE_ERR_DECLARE_COLNAME,
                " ('$colname')"
            );
        }
 
        // column name must be no longer than 30 chars
        if (   $GLOBALS['_DB_TABLE']['disable_length_check'] === false
            && strlen($colname) > 30
           ) {
            return DB_Table::throwError(
                DB_TABLE_ERR_DECLARE_STRLEN,
                "('$colname')"
            );
        }

        return true;
    }


   /**
    * 
    * Check whether a column exists.
    * 
    * @access private
    * 
    * @param string $colname The column name.
    * 
    * @param mixed $tableInfoOrder Array with columns in the table (result
    * from tableInfo(), shortened to key 'order').
    * 
    * @param string $mode The name of the calling function, this can be either
    * 'verify' or 'alter'.
    * 
    * @return bool|object Boolean true if the column exists.
    * Otherwise, either boolean false (case 'alter') or a PEAR_Error
    * (case 'verify').
    * 
    */

    function _columnExists($colname, $tableInfoOrder, $mode)
    {
        if (array_key_exists($colname, $tableInfoOrder)) {
            return true;
        }

        switch ($mode) {

            case 'alter':
                return false;

            case 'verify':
                return DB_Table::throwError(
                    DB_TABLE_ERR_VER_COLUMN_MISSING,
                    "(column='$colname')"
                );

        }
    }


   /**
    * 
    * Check whether a column type is a known type.
    * 
    * @access private
    * 
    * @param string $phptype The DB/MDB2 phptype key.
    * 
    * @param string $type The column type.
    * 
    * @return bool|object Boolean true if the column type is a known type
    * or a PEAR_Error, otherwise.
    * 
    */

    function _validateColumnType($phptype, $type)
    {
        // map of valid types for the current RDBMS
        $map = $GLOBALS['_DB_TABLE']['valid_type'][$phptype];

        // is it a recognized column type?
        $types = array_keys($map);
        if (!in_array($type, $types)) {
            return DB_Table::throwError(
                DB_TABLE_ERR_DECLARE_TYPE,
                "('" . $type . "')"
            );
        }

        return true;
    }


   /**
    * 
    * Check whether a column has the right type.
    * 
    * @access private
    * 
    * @param string $phptype The DB/MDB2 phptype key.
    *
    * @param string $colname The column name.
    * 
    * @param string $coltype The column type.
    * 
    * @param mixed $tableInfoOrder Array with columns in the table (result
    * from tableInfo(), shortened to key 'order').
    * 
    * @param mixed $tableInfo Array with information about the table (result
    * from tableInfo()).
    * 
    * @param string $mode The name of the calling function, this can be either
    * 'verify' or 'alter'.
    * 
    * @return bool|object Boolean true if the column has the right type.
    * Otherwise, either boolean false (case 'alter') or a PEAR_Error
    * (case 'verify').
    * 
    */

    function _checkColumnType($phptype, $colname, $coltype, $tableInfoOrder,
        $tableInfo, $mode)
    {
        // map of valid types for the current RDBMS
        $map = $GLOBALS['_DB_TABLE']['valid_type'][$phptype];

        // get the column type from tableInfo()
        $colindex = $tableInfoOrder[$colname];
        $type = strtolower($tableInfo[$colindex]['type']);

        // workaround for possibly wrong detected column type (taken from MDB2)
        if ($type == 'unknown' && ($phptype == 'mysql' || $phptype == 'mysqli')) {
            $type = 'decimal';
        }

        // strip size information (e.g. NUMERIC(9,2) => NUMERIC) if given
        if (($pos = strpos($type, '(')) !== false) {
            $type = substr($type, 0, $pos);
        }

        // is the type valid for the given DB_Table column type?
        if (in_array($type, (array)$map[$coltype])) {
            return true;
        }

        switch ($mode) {

            case 'alter':
                return false;

            case 'verify':
                return DB_Table::throwError(
                    DB_TABLE_ERR_VER_COLUMN_TYPE,
                    "(column='$colname', type='$type')"
                );

        }
    }


   /**
    * 
    * Return the index type and the columns belonging to this index.
    * 
    * @access private
    * 
    * @param mixed $idx_def The index definition.
    * 
    * @return mixed Array with the index type and the columns belonging to
    * this index.
    * 
    */

    function _getIndexTypeAndColumns($idx_def, $idxname)
    {
        $type = '';
        $cols = '';
        if (is_string($idx_def)) {
            // shorthand for index names: colname => index_type
            $type = trim($idx_def);
            $cols = trim($idxname);
        } elseif (is_array($idx_def)) {
            // normal: index_name => array('type' => ..., 'cols' => ...)
            $type = (isset($idx_def['type'])) ? $idx_def['type'] : 'normal';
            $cols = (isset($idx_def['cols'])) ? $idx_def['cols'] : null;
        }

        return array($type, $cols);
    }


   /**
    * 
    * Check an index name for validity.
    * 
    * @access private
    * 
    * @param string $idxname The index name.
    * 
    * @param string $table The table name.
    * 
    * @param string $phptype The DB/MDB2 phptype key.
    * 
    * @param string $type The index type.
    * 
    * @param mixed $cols The column names for the index. Will become an array
    * if it is not an array.
    * 
    * @param mixed $column_set A DB_Table $this->col array.
    * 
    * @param string $newIdxName The new index name (prefixed with the table
    * name, suffixed with '_idx').
    * 
    * @return bool|object Boolean true if the index name is valid or a
    * PEAR_Error with a description about the invalidity, otherwise.
    * 
    */

    function _validateIndexName($idxname, $table, $phptype, $type, &$cols,
                                $column_set, &$newIdxName)
    {
        // index name cannot be a reserved keyword
        $reserved = in_array(
            strtoupper($idxname),
            $GLOBALS['_DB_TABLE']['reserved']
        );

        if ($reserved && !($type == 'primary' && $idxname == 'PRIMARY')) {
            return DB_Table::throwError(
                DB_TABLE_ERR_DECLARE_IDXNAME,
                "('$idxname')"
            );
        }

        // are there any columns for the index?
        if (! $cols) {
            return DB_Table::throwError(
                DB_TABLE_ERR_IDX_NO_COLS,
                "('$idxname')"
            );
        }

        // are there any CLOB columns, or any columns that are not
        // in the schema?
        settype($cols, 'array');
        $valid_cols = array_keys($column_set);
        foreach ($cols as $colname) {

            if (! in_array($colname, $valid_cols)) {
                return DB_Table::throwError(
                    DB_TABLE_ERR_IDX_COL_UNDEF,
                    "'$idxname' ('$colname')"
                );
            }

            if ($column_set[$colname]['type'] == 'clob') {
                return DB_Table::throwError(
                    DB_TABLE_ERR_IDX_COL_CLOB,
                    "'$idxname' ('$colname')"
                );
            }

        }

        // we prefix all index names with the table name,
        // and suffix all index names with '_idx'.  this
        // is to soothe PostgreSQL, which demands that index
        // names not collide, even when they indexes are on
        // different tables.
        $newIdxName = $table . '_' . $idxname . '_idx';

        // MySQL requires the primary key to be named 'primary', therefore let's
        // ignore the user defined name
        if (($phptype == 'mysql' || $phptype == 'mysqli') && $type == 'primary') {
            $newIdxName = 'primary';
        }
            
        // now check the length; must be under 30 chars to
        // soothe Oracle.
        if (   $GLOBALS['_DB_TABLE']['disable_length_check'] === false
            && strlen($newIdxName) > 30
           ) {
            return DB_Table::throwError(
                DB_TABLE_ERR_IDX_STRLEN,
                "'$idxname' ('$newIdxName')"
            );
        }

        // check index type
        if ($type != 'primary' && $type != 'unique' && $type != 'normal') {
            return DB_Table::throwError(
                DB_TABLE_ERR_IDX_TYPE,
                "'$idxname' ('$type')"
            );
        }

        return true;
    }


   /**
    * 
    * Return all indexes for a table.
    * 
    * @access public
    * 
    * @param object &$db A PEAR DB/MDB2 object.
    * 
    * @param string $table The table name.
    * 
    * @return mixed Array with all indexes or a PEAR_Error when an error
    * occured.
    * 
    */

    function getIndexes(&$db, $table)
    {
        if (is_subclass_of($db, 'db_common')) {
            $backend = 'db';
            // workaround for missing index and constraint information methods
            // in PEAR::DB ==> use adopted code from MDB2's driver classes
            require_once 'DB/Table/Manager/' . $db->phptype . '.php';
            $classname = 'DB_Table_Manager_' . $db->phptype;
            $dbtm = new $classname();
            $dbtm->_db =& $db;  // pass database instance to the 'workaround' class
            $manager =& $dbtm;
            $reverse =& $dbtm;
        } elseif (is_subclass_of($db, 'mdb2_driver_common')) {
            $backend = 'mdb2';
            $manager =& $db->manager;
            $reverse =& $db->reverse;
        }

        $indexes = array('normal'  => array(),
                         'primary' => array(),
                         'unique'  => array()
                        );

        // save user defined 'idxname_format' option (MDB2 only)
        if ($backend == 'mdb2') {
            $idxname_format = $db->getOption('idxname_format');
            $db->setOption('idxname_format', '%s');
        }

        // get table constraints
        $table_indexes_tmp = $manager->listTableConstraints($table);
        if (PEAR::isError($table_indexes_tmp)) {
            // restore user defined 'idxname_format' option (MDB2 only)
            if ($backend == 'mdb2') {
               $db->setOption('idxname_format', $idxname_format);
            }
            return $table_indexes_tmp;
        }

        // get fields of table constraints
        foreach ($table_indexes_tmp as $table_idx_tmp) {
            $index_fields = $reverse->getTableConstraintDefinition($table,
                                                              $table_idx_tmp);
            if (PEAR::isError($index_fields)) {
                // restore user defined 'idxname_format' option (MDB2 only)
                if ($backend == 'mdb2') {
                    $db->setOption('idxname_format', $idxname_format);
                }
                return $index_fields;
            }
            // get the first key of $index_fields that has boolean true value
            foreach ($index_fields as $index_type => $value) {
                if ($value === true) {
                    break;
                }
            }
            $indexes[$index_type][$table_idx_tmp] = array_keys($index_fields['fields']);
        }

        // get table indexes
        $table_indexes_tmp = $manager->listTableIndexes($table);
        if (PEAR::isError($table_indexes_tmp)) {
            // restore user defined 'idxname_format' option (MDB2 only)
            if ($backend == 'mdb2') {
                $db->setOption('idxname_format', $idxname_format);
            }
            return $table_indexes_tmp;
        }

        // get fields of table indexes
        foreach ($table_indexes_tmp as $table_idx_tmp) {
            $index_fields = $reverse->getTableIndexDefinition($table,
                                                         $table_idx_tmp);
            if (PEAR::isError($index_fields)) {
                // restore user defined 'idxname_format' option (MDB2 only)
                if ($backend == 'mdb2') {
                    $db->setOption('idxname_format', $idxname_format);
                }
                return $index_fields;
            }
            $indexes['normal'][$table_idx_tmp] = array_keys($index_fields['fields']);
        }

        // restore user defined 'idxname_format' option (MDB2 only)
        if ($backend == 'mdb2') {
            $db->setOption('idxname_format', $idxname_format);
        }

        return $indexes;
    }


   /**
    * 
    * Check whether an index has the right type and has all specified columns.
    * 
    * @access private
    * 
    * @param string $idxname The index name.
    * 
    * @param string $newIdxName The prefixed and suffixed index name.
    * 
    * @param string $type The index type.
    * 
    * @param mixed $cols The column names for the index.
    * 
    * @param mixed $table_indexes Array with all indexes of the table.
    * 
    * @param string $mode The name of the calling function, this can be either
    * 'verify' or 'alter'.
    * 
    * @return bool|object Boolean true if the index has the right type and all
    * specified columns. Otherwise, either boolean false (case 'alter') or a
    * PEAR_Error (case 'verify').
    * 
    */

    function _checkIndex($idxname, $newIdxName, $type, $cols, &$table_indexes, $mode)
    {
        $index_found = false;

        foreach ($table_indexes[$type] as $index_name => $index_fields) {
            if (strtolower($index_name) == strtolower($newIdxName)) {
                $index_found = true;
                array_walk($cols, create_function('&$value,$key',
                                  '$value = trim(strtolower($value));'));
                array_walk($index_fields, create_function('&$value,$key',
                                  '$value = trim(strtolower($value));'));
                foreach ($index_fields as $index_field) {
                    if (($key = array_search($index_field, $cols)) !== false) {
                        unset($cols[$key]);
                    }
                }
                break;
            }
        }

        if (!$index_found) {
            return ($mode == 'alter') ? false : DB_Table::throwError(
                DB_TABLE_ERR_VER_IDX_MISSING,
                "'$idxname' ('$newIdxName')"
            );
        }

        if (count($cols) > 0) {
            // string of column names
            $colstring = implode(', ', $cols);
            return ($mode == 'alter') ? false : DB_Table::throwError(
                DB_TABLE_ERR_VER_IDX_COL_MISSING,
                "'$idxname' ($colstring)"
            );
        }

        return true;
    }


   /**
    * 
    * Create indexes and contraints.
    * 
    * @access private
    * 
    * @param object &$db A PEAR DB/MDB2 object.
    * 
    * @param string $backend The name of the backend ('db' or 'mdb2').
    * 
    * @param string $table The table name.
    * 
    * @param mixed $indexes An array with index and constraint definitions.
    * 
    * @return bool|object Boolean true on success or a PEAR_Error with a
    * description about the invalidity, otherwise.
    * 
    */

    function _createIndexesAndContraints($db, $backend, $table, $indexes)
    {
        if ($backend == 'mdb2') {

            // save user defined 'idxname_format' option
            $idxname_format = $db->getOption('idxname_format');
            $db->setOption('idxname_format', '%s');

            // attempt to create the primary key
            if (!array_key_exists('primary', $indexes)) {
                $indexes['primary'] = array();
            }
            foreach ($indexes['primary'] as $name => $definition) {
                $result = $db->manager->createConstraint($table, $name, $definition);
                if (PEAR::isError($result)) {
                    // restore user defined 'idxname_format' option
                    $db->setOption('idxname_format', $idxname_format);
                    return $result;
                }
            }

            // attempt to create the unique indexes / constraints
            if (!array_key_exists('unique', $indexes)) {
                $indexes['unique'] = array();
            }
            foreach ($indexes['unique'] as $name => $definition) {
                $result = $db->manager->createConstraint($table, $name, $definition);
                if (PEAR::isError($result)) {
                    // restore user defined 'idxname_format' option
                    $db->setOption('idxname_format', $idxname_format);
                    return $result;
                }
            }

            // attempt to create the normal indexes
            if (!array_key_exists('normal', $indexes)) {
                $indexes['normal'] = array();
            }
            foreach ($indexes['normal'] as $name => $definition) {
                $result = $db->manager->createIndex($table, $name, $definition);
                if (PEAR::isError($result)) {
                    // restore user defined 'idxname_format' option
                    $db->setOption('idxname_format', $idxname_format);
                    return $result;
                }
            }

            // restore user defined 'idxname_format' option
            $db->setOption('idxname_format', $idxname_format);

        } else {

            // attempt to create the indexes
            foreach ($indexes as $cmd) {
                $result = $db->query($cmd);
                if (PEAR::isError($result)) {
                    return $result;
                }
            }

        }

        return true;

    }

}


/**
* List of all reserved words for all supported databases. Yes, this is a
* monster of a list.
*/
if (! isset($GLOBALS['_DB_TABLE']['reserved'])) {
    $GLOBALS['_DB_TABLE']['reserved'] = array(
        '_ROWID_',
        'ABSOLUTE',
        'ACCESS',
        'ACTION',
        'ADD',
        'ADMIN',
        'AFTER',
        'AGGREGATE',
        'ALIAS',
        'ALL',
        'ALLOCATE',
        'ALTER',
        'ANALYSE',
        'ANALYZE',
        'AND',
        'ANY',
        'ARE',
        'ARRAY',
        'AS',
        'ASC',
        'ASENSITIVE',
        'ASSERTION',
        'AT',
        'AUDIT',
        'AUTHORIZATION',
        'AUTO_INCREMENT',
        'AVG',
        'BACKUP',
        'BDB',
        'BEFORE',
        'BEGIN',
        'BERKELEYDB',
        'BETWEEN',
        'BIGINT',
        'BINARY',
        'BIT',
        'BIT_LENGTH',
        'BLOB',
        'BOOLEAN',
        'BOTH',
        'BREADTH',
        'BREAK',
        'BROWSE',
        'BULK',
        'BY',
        'CALL',
        'CASCADE',
        'CASCADED',
        'CASE',
        'CAST',
        'CATALOG',
        'CHANGE',
        'CHAR',
        'CHAR_LENGTH',
        'CHARACTER',
        'CHARACTER_LENGTH',
        'CHECK',
        'CHECKPOINT',
        'CLASS',
        'CLOB',
        'CLOSE',
        'CLUSTER',
        'CLUSTERED',
        'COALESCE',
        'COLLATE',
        'COLLATION',
        'COLUMN',
        'COLUMNS',
        'COMMENT',
        'COMMIT',
        'COMPLETION',
        'COMPRESS',
        'COMPUTE',
        'CONDITION',
        'CONNECT',
        'CONNECTION',
        'CONSTRAINT',
        'CONSTRAINTS',
        'CONSTRUCTOR',
        'CONTAINS',
        'CONTAINSTABLE',
        'CONTINUE',
        'CONVERT',
        'CORRESPONDING',
        'COUNT',
        'CREATE',
        'CROSS',
        'CUBE',
        'CURRENT',
        'CURRENT_DATE',
        'CURRENT_PATH',
        'CURRENT_ROLE',
        'CURRENT_TIME',
        'CURRENT_TIMESTAMP',
        'CURRENT_USER',
        'CURSOR',
        'CYCLE',
        'DATA',
        'DATABASE',
        'DATABASES',
        'DATE',
        'DAY',
        'DAY_HOUR',
        'DAY_MICROSECOND',
        'DAY_MINUTE',
        'DAY_SECOND',
        'DBCC',
        'DEALLOCATE',
        'DEC',
        'DECIMAL',
        'DECLARE',
        'DEFAULT',
        'DEFERRABLE',
        'DEFERRED',
        'DELAYED',
        'DELETE',
        'DENY',
        'DEPTH',
        'DEREF',
        'DESC',
        'DESCRIBE',
        'DESCRIPTOR',
        'DESTROY',
        'DESTRUCTOR',
        'DETERMINISTIC',
        'DIAGNOSTICS',
        'DICTIONARY',
        'DISCONNECT',
        'DISK',
        'DISTINCT',
        'DISTINCTROW',
        'DISTRIBUTED',
        'DIV',
        'DO',
        'DOMAIN',
        'DOUBLE',
        'DROP',
        'DUMMY',
        'DUMP',
        'DYNAMIC',
        'EACH',
        'ELSE',
        'ELSEIF',
        'ENCLOSED',
        'END',
        'END-EXEC',
        'EQUALS',
        'ERRLVL',
        'ESCAPE',
        'ESCAPED',
        'EVERY',
        'EXCEPT',
        'EXCEPTION',
        'EXCLUSIVE',
        'EXEC',
        'EXECUTE',
        'EXISTS',
        'EXIT',
        'EXPLAIN',
        'EXTERNAL',
        'EXTRACT',
        'FALSE',
        'FETCH',
        'FIELDS',
        'FILE',
        'FILLFACTOR',
        'FIRST',
        'FLOAT',
        'FOR',
        'FORCE',
        'FOREIGN',
        'FOUND',
        'FRAC_SECOND',
        'FREE',
        'FREETEXT',
        'FREETEXTTABLE',
        'FREEZE',
        'FROM',
        'FULL',
        'FULLTEXT',
        'FUNCTION',
        'GENERAL',
        'GET',
        'GLOB',
        'GLOBAL',
        'GO',
        'GOTO',
        'GRANT',
        'GROUP',
        'GROUPING',
        'HAVING',
        'HIGH_PRIORITY',
        'HOLDLOCK',
        'HOST',
        'HOUR',
        'HOUR_MICROSECOND',
        'HOUR_MINUTE',
        'HOUR_SECOND',
        'IDENTIFIED',
        'IDENTITY',
        'IDENTITY_INSERT',
        'IDENTITYCOL',
        'IF',
        'IGNORE',
        'ILIKE',
        'IMMEDIATE',
        'IN',
        'INCREMENT',
        'INDEX',
        'INDICATOR',
        'INFILE',
        'INITIAL',
        'INITIALIZE',
        'INITIALLY',
        'INNER',
        'INNODB',
        'INOUT',
        'INPUT',
        'INSENSITIVE',
        'INSERT',
        'INT',
        'INTEGER',
        'INTERSECT',
        'INTERVAL',
        'INTO',
        'IO_THREAD',
        'IS',
        'ISNULL',
        'ISOLATION',
        'ITERATE',
        'JOIN',
        'KEY',
        'KEYS',
        'KILL',
        'LANGUAGE',
        'LARGE',
        'LAST',
        'LATERAL',
        'LEADING',
        'LEAVE',
        'LEFT',
        'LESS',
        'LEVEL',
        'LIKE',
        'LIMIT',
        'LINENO',
        'LINES',
        'LOAD',
        'LOCAL',
        'LOCALTIME',
        'LOCALTIMESTAMP',
        'LOCATOR',
        'LOCK',
        'LONG',
        'LONGBLOB',
        'LONGTEXT',
        'LOOP',
        'LOW_PRIORITY',
        'LOWER',
        'MAIN',
        'MAP',
        'MASTER_SERVER_ID',
        'MATCH',
        'MAX',
        'MAXEXTENTS',
        'MEDIUMBLOB',
        'MEDIUMINT',
        'MEDIUMTEXT',
        'MIDDLEINT',
        'MIN',
        'MINUS',
        'MINUTE',
        'MINUTE_MICROSECOND',
        'MINUTE_SECOND',
        'MLSLABEL',
        'MOD',
        'MODE',
        'MODIFIES',
        'MODIFY',
        'MODULE',
        'MONTH',
        'NAMES',
        'NATIONAL',
        'NATURAL',
        'NCHAR',
        'NCLOB',
        'NEW',
        'NEXT',
        'NO',
        'NO_WRITE_TO_BINLOG',
        'NOAUDIT',
        'NOCHECK',
        'NOCOMPRESS',
        'NONCLUSTERED',
        'NONE',
        'NOT',
        'NOTNULL',
        'NOWAIT',
        'NULL',
        'NULLIF',
        'NUMBER',
        'NUMERIC',
        'OBJECT',
        'OCTET_LENGTH',
        'OF',
        'OFF',
        'OFFLINE',
        'OFFSET',
        'OFFSETS',
        'OID',
        'OLD',
        'ON',
        'ONLINE',
        'ONLY',
        'OPEN',
        'OPENDATASOURCE',
        'OPENQUERY',
        'OPENROWSET',
        'OPENXML',
        'OPERATION',
        'OPTIMIZE',
        'OPTION',
        'OPTIONALLY',
        'OR',
        'ORDER',
        'ORDINALITY',
        'OUT',
        'OUTER',
        'OUTFILE',
        'OUTPUT',
        'OVER',
        'OVERLAPS',
        'PAD',
        'PARAMETER',
        'PARAMETERS',
        'PARTIAL',
        'PATH',
        'PCTFREE',
        'PERCENT',
        'PLACING',
        'PLAN',
        'POSITION',
        'POSTFIX',
        'PRECISION',
        'PREFIX',
        'PREORDER',
        'PREPARE',
        'PRESERVE',
        'PRIMARY',
        'PRINT',
        'PRIOR',
        'PRIVILEGES',
        'PROC',
        'PROCEDURE',
        'PUBLIC',
        'PURGE',
        'RAISERROR',
        'RAW',
        'READ',
        'READS',
        'READTEXT',
        'REAL',
        'RECONFIGURE',
        'RECURSIVE',
        'REF',
        'REFERENCES',
        'REFERENCING',
        'REGEXP',
        'RELATIVE',
        'RENAME',
        'REPEAT',
        'REPLACE',
        'REPLICATION',
        'REQUIRE',
        'RESOURCE',
        'RESTORE',
        'RESTRICT',
        'RESULT',
        'RETURN',
        'RETURNS',
        'REVOKE',
        'RIGHT',
        'RLIKE',
        'ROLE',
        'ROLLBACK',
        'ROLLUP',
        'ROUTINE',
        'ROW',
        'ROWCOUNT',
        'ROWGUIDCOL',
        'ROWID',
        'ROWNUM',
        'ROWS',
        'RULE',
        'SAVE',
        'SAVEPOINT',
        'SCHEMA',
        'SCOPE',
        'SCROLL',
        'SEARCH',
        'SECOND',
        'SECOND_MICROSECOND',
        'SECTION',
        'SELECT',
        'SENSITIVE',
        'SEPARATOR',
        'SEQUENCE',
        'SESSION',
        'SESSION_USER',
        'SET',
        'SETS',
        'SETUSER',
        'SHARE',
        'SHOW',
        'SHUTDOWN',
        'SIMILAR',
        'SIZE',
        'SMALLINT',
        'SOME',
        'SONAME',
        'SPACE',
        'SPATIAL',
        'SPECIFIC',
        'SPECIFICTYPE',
        'SQL',
        'SQL_BIG_RESULT',
        'SQL_CALC_FOUND_ROWS',
        'SQL_SMALL_RESULT',
        'SQL_TSI_DAY',
        'SQL_TSI_FRAC_SECOND',
        'SQL_TSI_HOUR',
        'SQL_TSI_MINUTE',
        'SQL_TSI_MONTH',
        'SQL_TSI_QUARTER',
        'SQL_TSI_SECOND',
        'SQL_TSI_WEEK',
        'SQL_TSI_YEAR',
        'SQLCODE',
        'SQLERROR',
        'SQLEXCEPTION',
        'SQLITE_MASTER',
        'SQLITE_TEMP_MASTER',
        'SQLSTATE',
        'SQLWARNING',
        'SSL',
        'START',
        'STARTING',
        'STATE',
        'STATEMENT',
        'STATIC',
        'STATISTICS',
        'STRAIGHT_JOIN',
        'STRIPED',
        'STRUCTURE',
        'SUBSTRING',
        'SUCCESSFUL',
        'SUM',
        'SYNONYM',
        'SYSDATE',
        'SYSTEM_USER',
        'TABLE',
        'TABLES',
        'TEMPORARY',
        'TERMINATE',
        'TERMINATED',
        'TEXTSIZE',
        'THAN',
        'THEN',
        'TIME',
        'TIMESTAMP',
        'TIMESTAMPADD',
        'TIMESTAMPDIFF',
        'TIMEZONE_HOUR',
        'TIMEZONE_MINUTE',
        'TINYBLOB',
        'TINYINT',
        'TINYTEXT',
        'TO',
        'TOP',
        'TRAILING',
        'TRAN',
        'TRANSACTION',
        'TRANSLATE',
        'TRANSLATION',
        'TREAT',
        'TRIGGER',
        'TRIM',
        'TRUE',
        'TRUNCATE',
        'TSEQUAL',
        'UID',
        'UNDER',
        'UNDO',
        'UNION',
        'UNIQUE',
        'UNKNOWN',
        'UNLOCK',
        'UNNEST',
        'UNSIGNED',
        'UPDATE',
        'UPDATETEXT',
        'UPPER',
        'USAGE',
        'USE',
        'USER',
        'USER_RESOURCES',
        'USING',
        'UTC_DATE',
        'UTC_TIME',
        'UTC_TIMESTAMP',
        'VALIDATE',
        'VALUE',
        'VALUES',
        'VARBINARY',
        'VARCHAR',
        'VARCHAR2',
        'VARCHARACTER',
        'VARIABLE',
        'VARYING',
        'VERBOSE',
        'VIEW',
        'WAITFOR',
        'WHEN',
        'WHENEVER',
        'WHERE',
        'WHILE',
        'WITH',
        'WITHOUT',
        'WORK',
        'WRITE',
        'WRITETEXT',
        'XOR',
        'YEAR',
        'YEAR_MONTH',
        'ZEROFILL',
        'ZONE',
    );
}
        
?>
