<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * Index, constraint and alter methods for DB_Table usage with
 * PEAR::DB as backend.
 * 
 * The code in this class was adopted from the MDB2 PEAR package.
 * 
 * PHP versions 4 and 5
 *
 * LICENSE:
 * 
 * Copyright (c) 1997-2007, Lorenzo Alberton <l.alberton@quipo.it>
 *                          Lukas Smith <smith@pooteeweet.org>
 *                          Paul M. Jones <pmjones@php.net>
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
 * @author   Lorenzo Alberton <l.alberton@quipo.it>
 * @author   Lukas Smith <smith@pooteeweet.org>
 * @author   Mark Wiesemann <wiesemann@php.net>
 * @license  http://opensource.org/licenses/bsd-license.php New BSD License
 * @version  CVS: $Id: sqlite.php,v 1.5 2007/12/13 16:52:15 wiesemann Exp $
 * @link     http://pear.php.net/package/DB_Table
 */

/**
 * Require DB_Table class
 */
require_once 'DB/Table.php';

/**
 * Index, constraint and alter methods for DB_Table usage with
 * PEAR::DB as backend.
 * 
 * The code in this class was adopted from the MDB2 PEAR package.
 * 
 * @category Database
 * @package  DB_Table
 * @author   Lorenzo Alberton <l.alberton@quipo.it>
 * @author   Lukas Smith <smith@pooteeweet.org>
 * @author   Mark Wiesemann <wiesemann@php.net>
 * @version  Release: 1.5.6
 * @link     http://pear.php.net/package/DB_Table
 */
class DB_Table_Manager_sqlite {

    /**
    * 
    * The PEAR DB object that connects to the database.
    * 
    * @access private
    * 
    * @var object
    * 
    */
    
    var $_db = null;


    /**
     * list all indexes in a table
     *
     * @param string    $table      name of table that should be used in method
     * @return mixed data array on success, a PEAR error on failure
     * @access public
     */
    function listTableIndexes($table)
    {
        $query = "SELECT sql FROM sqlite_master WHERE type='index' AND ";
        $query.= "LOWER(tbl_name)='".strtolower($table)."'";
        $query.= " AND sql NOT NULL ORDER BY name";
        $indexes = $this->_db->getCol($query);
        if (PEAR::isError($indexes)) {
            return $indexes;
        }

        $result = array();
        foreach ($indexes as $sql) {
            if (preg_match("/^create index ([^ ]*) on /i", $sql, $tmp)) {
                $result[$tmp[1]] = true;
            }
        }
        $result = array_change_key_case($result, CASE_LOWER);

        return array_keys($result);
    }


    /**
     * list all constraints in a table
     *
     * @param string    $table      name of table that should be used in method
     * @return mixed data array on success, a PEAR error on failure
     * @access public
     */
    function listTableConstraints($table)
    {
        $query = "SELECT sql FROM sqlite_master WHERE type='index' AND ";
        $query.= "LOWER(tbl_name)='".strtolower($table)."'";
        $query.= " AND sql NOT NULL ORDER BY name";
        $indexes = $this->_db->getCol($query);
        if (PEAR::isError($indexes)) {
            return $indexes;
        }

        $result = array();
        foreach ($indexes as $sql) {
            if (preg_match("/^create unique index ([^ ]*) on /i", $sql, $tmp)) {
                $result[$tmp[1]] = true;
            }
        }
        $result = array_change_key_case($result, CASE_LOWER);

        return array_keys($result);
    }


    /**
     * get the structure of an index into an array
     *
     * @param string    $table      name of table that should be used in method
     * @param string    $index_name name of index that should be used in method
     * @return mixed data array on success, a PEAR error on failure
     * @access public
     */
    function getTableIndexDefinition($table, $index_name)
    {
        $query = "SELECT sql FROM sqlite_master WHERE type='index' AND ";
        $query.= "LOWER(name)='".strtolower($index_name)."' AND LOWER(tbl_name)='".strtolower($table)."'";
        $query.= " AND sql NOT NULL ORDER BY name";
        $sql = $this->_db->getOne($query);
        if (PEAR::isError($sql)) {
            return $sql;
        }

        $sql = strtolower($sql);
        $start_pos = strpos($sql, '(');
        $end_pos = strrpos($sql, ')');
        $column_names = substr($sql, $start_pos+1, $end_pos-$start_pos-1);
        $column_names = split(',', $column_names);

        $definition = array();
        $count = count($column_names);
        for ($i=0; $i<$count; ++$i) {
            $column_name = strtok($column_names[$i]," ");
            $collation = strtok(" ");
            $definition['fields'][$column_name] = array();
            if (!empty($collation)) {
                $definition['fields'][$column_name]['sorting'] =
                    ($collation=='ASC' ? 'ascending' : 'descending');
            }
        }

        return $definition;
    }


    /**
     * get the structure of a constraint into an array
     *
     * @param string    $table      name of table that should be used in method
     * @param string    $index_name name of index that should be used in method
     * @return mixed data array on success, a PEAR error on failure
     * @access public
     */
    function getTableConstraintDefinition($table, $index_name)
    {
        $query = "SELECT sql FROM sqlite_master WHERE type='index' AND ";
        $query.= "LOWER(name)='".strtolower($index_name)."' AND LOWER(tbl_name)='".strtolower($table)."'";
        $query.= " AND sql NOT NULL ORDER BY name";
        $sql = $this->_db->getOne($query);
        if (PEAR::isError($sql)) {
            return $sql;
        }

        $sql = strtolower($sql);
        $start_pos = strpos($sql, '(');
        $end_pos = strrpos($sql, ')');
        $column_names = substr($sql, $start_pos+1, $end_pos-$start_pos-1);
        $column_names = split(',', $column_names);

        $definition = array();
        $definition['unique'] = true;
        $count = count($column_names);
        for ($i=0; $i<$count; ++$i) {
            $column_name = strtok($column_names[$i]," ");
            $collation = strtok(" ");
            $definition['fields'][$column_name] = array();
            if (!empty($collation)) {
                $definition['fields'][$column_name]['sorting'] =
                    ($collation=='ASC' ? 'ascending' : 'descending');
            }
        }

        return $definition;
    }

    
    /**
     * drop existing index
     *
     * @param string    $table         name of table that should be used in method
     * @param string    $name         name of the index to be dropped
     * @return mixed DB_OK on success, a PEAR error on failure
     * @access public
     */
    function dropIndex($table, $name)
    {
        return $this->_db->query("DROP INDEX $name");
    }


    /**
     * drop existing constraint
     *
     * @param string    $table         name of table that should be used in method
     * @param string    $name         name of the constraint to be dropped
     * @return mixed DB_OK on success, a PEAR error on failure
     * @access public
     */
    function dropConstraint($table, $name)
    {
        if (strtolower($name) == 'primary') {
            return DB_Table::throwError(
                DB_TABLE_ERR_ALTER_INDEX_IMPOS,
                '(primary)'
            );
        }

        return $this->_db->query("DROP INDEX $name");
    }


    /**
     * alter an existing table
     *
     * @param string $name         name of the table that is intended to be changed.
     * @param array $changes     associative array that contains the details of each type
     *                             of change that is intended to be performed. The types of
     *                             changes that are currently supported are defined as follows:
     *
     *                             name
     *
     *                                New name for the table.
     *
     *                            add
     *
     *                                Associative array with the names of fields to be added as
     *                                 indexes of the array. The value of each entry of the array
     *                                 should be set to another associative array with the properties
     *                                 of the fields to be added. The properties of the fields should
     *                                 be the same as defined by the Metabase parser.
     *
     *
     *                            remove
     *
     *                                Associative array with the names of fields to be removed as indexes
     *                                 of the array. Currently the values assigned to each entry are ignored.
     *                                 An empty array should be used for future compatibility.
     *
     *                            rename
     *
     *                                Associative array with the names of fields to be renamed as indexes
     *                                 of the array. The value of each entry of the array should be set to
     *                                 another associative array with the entry named name with the new
     *                                 field name and the entry named Declaration that is expected to contain
     *                                 the portion of the field declaration already in DBMS specific SQL code
     *                                 as it is used in the CREATE TABLE statement.
     *
     *                            change
     *
     *                                Associative array with the names of the fields to be changed as indexes
     *                                 of the array. Keep in mind that if it is intended to change either the
     *                                 name of a field and any other properties, the change array entries
     *                                 should have the new names of the fields as array indexes.
     *
     *                                The value of each entry of the array should be set to another associative
     *                                 array with the properties of the fields to that are meant to be changed as
     *                                 array entries. These entries should be assigned to the new values of the
     *                                 respective properties. The properties of the fields should be the same
     *                                 as defined by the Metabase parser.
     *
     *                            Example
     *                                array(
     *                                    'name' => 'userlist',
     *                                    'add' => array(
     *                                        'quota' => array(
     *                                            'type' => 'integer',
     *                                            'unsigned' => 1
     *                                        )
     *                                    ),
     *                                    'remove' => array(
     *                                        'file_limit' => array(),
     *                                        'time_limit' => array()
     *                                    ),
     *                                    'change' => array(
     *                                        'name' => array(
     *                                            'length' => '20',
     *                                            'definition' => array(
     *                                                'type' => 'text',
     *                                                'length' => 20,
     *                                            ),
     *                                        )
     *                                    ),
     *                                    'rename' => array(
     *                                        'sex' => array(
     *                                            'name' => 'gender',
     *                                            'definition' => array(
     *                                                'type' => 'text',
     *                                                'length' => 1,
     *                                                'default' => 'M',
     *                                            ),
     *                                        )
     *                                    )
     *                                )
     *
     * @param boolean $check     (ignored in DB_Table)
     * @access public
     *
     * @return mixed DB_OK on success, a PEAR error on failure
     */
    function alterTable($name, $changes, $check)
    {
        $version = $this->_getServerVersion();
        foreach ($changes as $change_name => $change) {
            switch ($change_name) {
            case 'add':
                if ($version['major'] >= 3 && $version['minor'] >= 1) {
                    break;
                }
            case 'name':
                if ($version['major'] >= 3 && $version['minor'] >= 1) {
                    break;
                }
            case 'remove':
            case 'change':
            case 'rename':
            default:
                return DB_Table::throwError(DB_TABLE_ERR_ALTER_TABLE_IMPOS);
            }
        }

        $query = '';
        if (array_key_exists('name', $changes)) {
            $change_name = $this->_db->quoteIdentifier($changes['name']);
            $query .= 'RENAME TO ' . $change_name;
        }

        if (array_key_exists('add', $changes)) {
            foreach ($changes['add'] as $field_name => $field) {
                if ($query) {
                    $query.= ', ';
                }
                $query.= 'ADD COLUMN ' . $field_name . ' ' . $field;
            }
        }

        if (!$query) {
            return DB_OK;
        }

        $name = $this->_db->quoteIdentifier($name);
        return $this->_db->query("ALTER TABLE $name $query");
    }


    /**
     * return version information about the server
     *
     * @param string     $native  determines if the raw version string should be returned
     * @return mixed array/string with version information or MDB2 error object
     * @access private
     */
    function _getServerVersion($native = false)
    {
        if (!function_exists('sqlite_libversion')) {
            return 0;  // error
        }
        $server_info = sqlite_libversion();
        if (!$native) {
            $tmp = explode('.', $server_info, 3);
            $server_info = array(
                'major' => isset($tmp[0]) ? $tmp[0] : null,
                'minor' => isset($tmp[1]) ? $tmp[1] : null,
                'patch' => isset($tmp[2]) ? $tmp[2] : null,
                'extra' => null,
                'native' => $server_info,
            );
        }
        return $server_info;
    }

}

?>
