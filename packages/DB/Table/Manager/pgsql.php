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
 * Copyright (c) 1997-2007, Paul Cooper <pgc@ucecom.com>
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
 * @author   Paul Cooper <pgc@ucecom.com>
 * @author   Mark Wiesemann <wiesemann@php.net>
 * @license  http://opensource.org/licenses/bsd-license.php New BSD License
 * @version  CVS: $Id: pgsql.php,v 1.5 2007/12/13 16:52:15 wiesemann Exp $
 * @link     http://pear.php.net/package/DB_Table
 */

/**
 * require DB_Table class
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
 * @author   Paul Cooper <pgc@ucecom.com>
 * @author   Mark Wiesemann <wiesemann@php.net>
 * @version  Release: 1.5.6
 * @link     http://pear.php.net/package/DB_Table
 */
class DB_Table_Manager_pgsql {

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
        $subquery = "SELECT indexrelid FROM pg_index, pg_class";
        $subquery.= " WHERE pg_class.relname='$table' AND pg_class.oid=pg_index.indrelid AND indisunique != 't' AND indisprimary != 't'";
        $query = "SELECT relname FROM pg_class WHERE oid IN ($subquery)";
        $indexes = $this->_db->getCol($query);
        if (PEAR::isError($indexes)) {
            return $indexes;
        }

        $result = array();
        foreach ($indexes as $index) {
            $result[$index] = true;
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
        $subquery = "SELECT indexrelid FROM pg_index, pg_class";
        $subquery.= " WHERE pg_class.relname='$table' AND pg_class.oid=pg_index.indrelid AND (indisunique = 't' OR indisprimary = 't')";
        $query = "SELECT relname FROM pg_class WHERE oid IN ($subquery)";
        $constraints = $this->_db->getCol($query);
        if (PEAR::isError($constraints)) {
            return $constraints;
        }

        $result = array();
        foreach ($constraints as $constraint) {
            $result[$constraint] = true;
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
        $query = "SELECT relname, indkey FROM pg_index, pg_class
            WHERE pg_class.relname = ".$this->_db->quoteSmart($index_name)." AND pg_class.oid = pg_index.indexrelid
               AND indisunique != 't' AND indisprimary != 't'";
        $row = $this->_db->getRow($query, null, DB_FETCHMODE_ASSOC);
        if (PEAR::isError($row)) {
            return $row;
        }

        $columns = $this->_listTableFields($table);

        $definition = array();

        $index_column_numbers = explode(' ', $row['indkey']);

        foreach ($index_column_numbers as $number) {
            $definition['fields'][$columns[($number - 1)]] = array('sorting' => 'ascending');
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
        $query = "SELECT relname, indisunique, indisprimary, indkey FROM pg_index, pg_class
            WHERE pg_class.relname = ".$this->_db->quoteSmart($index_name)." AND pg_class.oid = pg_index.indexrelid
              AND (indisunique = 't' OR indisprimary = 't')";
        $row = $this->_db->getRow($query, null, DB_FETCHMODE_ASSOC);
        if (PEAR::isError($row)) {
            return $row;
        }

        $columns = $this->_listTableFields($table);

        $definition = array();
        if ($row['indisprimary'] == 't') {
            $definition['primary'] = true;
        } elseif ($row['indisunique'] == 't') {
            $definition['unique'] = true;
        }

        $index_column_numbers = explode(' ', $row['indkey']);

        foreach ($index_column_numbers as $number) {
            $definition['fields'][$columns[($number - 1)]] = array('sorting' => 'ascending');
        }
        return $definition;
    }

    /**
     * list all fields in a tables in the current database
     *
     * @param string $table name of table that should be used in method
     * @return mixed data array on success, a PEAR error on failure
     * @access private
     */
    function _listTableFields($table)
    {
        $table = $this->_db->quoteIdentifier($table);
        $result2 = $this->_db->query("SELECT * FROM $table");
        if (PEAR::isError($result2)) {
            return $result2;
        }
        $columns = array();
        $numcols = $result2->numCols();
        for ($column = 0; $column < $numcols; $column++) {
            $column_name = @pg_field_name($result2->result, $column);
            $columns[$column_name] = $column;
        }
        $result2->free();
        return array_flip($columns);
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
        $name = $this->_db->quoteIdentifier($name);
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
        $table = $this->_db->quoteIdentifier($table);
        $name = $this->_db->quoteIdentifier($name);
        return $this->_db->query("ALTER TABLE $table DROP CONSTRAINT $name");
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
        foreach ($changes as $change_name => $change) {
            switch ($change_name) {
            case 'add':
            case 'remove':
            case 'change':
            case 'name':
            case 'rename':
                break;
            default:
                return DB_Table::throwError(DB_TABLE_ERR_ALTER_TABLE_IMPOS);
            }
        }

        if (array_key_exists('add', $changes)) {
            foreach ($changes['add'] as $field_name => $field) {
                $query = 'ADD ' . $field_name . ' ' . $field;
                $result = $this->_db->query("ALTER TABLE $name $query");
                if (PEAR::isError($result)) {
                    return $result;
                }
            }
        }

        if (array_key_exists('remove', $changes)) {
            foreach ($changes['remove'] as $field_name => $field) {
                $field_name = $this->_db->quoteIdentifier($field_name);
                $query = 'DROP ' . $field_name;
                $result = $this->_db->query("ALTER TABLE $name $query");
                if (PEAR::isError($result)) {
                    return $result;
                }
            }
        }

        if (array_key_exists('change', $changes)) {
            foreach ($changes['change'] as $field_name => $field) {
                $field_name = $this->_db->quoteIdentifier($field_name);
                if (array_key_exists('type', $field)) {
                    $query = "ALTER $field_name TYPE " . $field['definition'];
                    $result = $this->_db->query("ALTER TABLE $name $query");
                    if (PEAR::isError($result)) {
                        return $result;
                    }
                }
/* default / notnull changes not (yet) supported in DB_Table                
                if (array_key_exists('default', $field)) {
                    $query = "ALTER $field_name SET DEFAULT ".$db->quote($field['definition']['default'], $field['definition']['type']);
                    $result = $db->exec("ALTER TABLE $name $query");
                    if (PEAR::isError($result)) {
                        return $result;
                    }
                }
                if (array_key_exists('notnull', $field)) {
                    $query.= "ALTER $field_name ".($field['definition']['notnull'] ? "SET" : "DROP").' NOT NULL';
                    $result = $db->exec("ALTER TABLE $name $query");
                    if (PEAR::isError($result)) {
                        return $result;
                    }
                }
*/
            }
        }

        if (array_key_exists('rename', $changes)) {
            foreach ($changes['rename'] as $field_name => $field) {
                $field_name = $this->_db->quoteIdentifier($field_name);
                $result = $this->_db->query("ALTER TABLE $name RENAME COLUMN $field_name TO ".$this->_db->quoteIdentifier($field['name']));
                if (PEAR::isError($result)) {
                    return $result;
                }
            }
        }

        $name = $this->_db->quoteIdentifier($name);
        if (array_key_exists('name', $changes)) {
            $change_name = $this->_db->quoteIdentifier($changes['name']);
            $result = $this->_db->query("ALTER TABLE $name RENAME TO ".$change_name);
            if (PEAR::isError($result)) {
                return $result;
            }
        }

        return DB_OK;
    }

}

?>
