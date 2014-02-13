<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * DB_Table_Base Base class for DB_Table and DB_Table_Database
 *
 * This utility class contains properties and methods that are common
 * to DB_Table and DB_Table database. These are all related to one of:
 *   - DB/MDB2 connection object [ $db and $backend properties ]
 *   - Error handling [ throwError() method, $error and $_primary_subclass ]
 *   - SELECT queries [ select*() methods, $sql & $fetchmode* properties]
 *   - buildSQL() and quote() SQL utilities
 *   - _swapModes() method 
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
 * @version  CVS: $Id: Base.php,v 1.4 2007/12/13 16:52:14 wiesemann Exp $
 * @link     http://pear.php.net/package/DB_Table
 */

require_once 'PEAR.php';

// {{{ DB_Table_Base

/**
 * Base class for DB_Table and DB_Table_Database
 *
 * @category Database
 * @package  DB_Table
 * @author   Paul M. Jones <pmjones@php.net>
 * @author   David C. Morse <morse@php.net>
 * @author   Mark Wiesemann <wiesemann@php.net>
 * @version  Release: 1.5.6
 * @link     http://pear.php.net/package/DB_Table
 */
class DB_Table_Base
{

    // {{{ properties

    /**
     * The PEAR DB/MDB2 object that connects to the database.
     *
     * @var    object
     * @access public
     */
    var $db = null;

    /**
     * The backend type, which must be 'db' or 'mdb2'
     *
     * @var    string
     * @access public
     */
    var $backend = null;

    /**
    * If there is an error on instantiation, this captures that error.
    *
    * This property is used only for errors encountered in the constructor
    * at instantiation time.  To check if there was an instantiation error...
    *
    * <code>
    * $obj = new DB_Table_*();
    * if ($obj->error) {
    *     // ... error handling code here ...
    * }
    * </code>
    *
    * @var    object PEAR_Error
    * @access public
    */
    var $error = null;

    /**
     * Baseline SELECT maps for buildSQL() and select*() methods.
     *
     * @var    array
     * @access public
     */
    var $sql = array();

    /**
     * Format of rows in sets returned by the select() method 
     *
     * This should be one of the DB/MDB2_FETCHMODE_* constant values, such as
     * MDB2_FETCHMODE_ASSOC, MDB2_FETCHMODE_ORDERED, or MDB2_FETCHMODE_OBJECT.
     * It determines whether select() returns represents individual rows as
     * associative arrays with column name keys, ordered/sequential arrays, 
     * or objects with column names mapped to properties. Use corresponding
     * DB_FETCHMODE_* constants for use with the DB backend. It has no effect
     * upon the return value of selectResult().
     *
     * If a 'fetchmode' element is set for a specific query array, the query 
     * fetchmode will override this DB_Table or DB_Table_Database property.
     * If no value is set for the query or the DB_Table_Base object, the value
     * or default set in the underlying DB/MDB2 object will be used.
     *
     * @var    int
     * @access public
     */
    var $fetchmode = null;

    /**
     * Class of objects to use for rows returned as objects by select()
     *
     * When fetchmode is DB/MDB2_FETCHMODE_OBJECT, use this class for each
     * returned row in rsults of select(). May be overridden by value of 
     * 'fetchmode_object_class'. If no class name is set in the query or 
     * the DB_Table_Base, defaults to that set in the DB/MDB2 object, or
     * to default of StdObject.
     *
     * @var    string
     * @access public
     */
    var $fetchmode_object_class = null;

    /**
     * Upper case name of primary subclass, 'DB_TABLE' or 'DB_TABLE_DATABASE'
     *
     * This should be set in the constructor of the child class, and is 
     * used in the DB_Table_Base::throwError() method to determine the
     * location of the relevant error codes and messages. Error codes and
     * error code messages are defined in class $this->_primary_subclass.
     * Messages are stored in $GLOBALS['_' . $this->_primary_subclass]['error']
     *
     * @var    string
     * @access private
     */
     var $_primary_subclass = null;

    // }}}
    // {{{ Methods

    /**
     * Specialized version of throwError() modeled on PEAR_Error.
     * 
     * Throws a PEAR_Error with an error message based on an error code 
     * and corresponding error message defined in $this->_primary_subclass
     * 
     * @param string $code  An error code constant 
     * @param string $extra Extra text for the error (in addition to the 
     *                      regular error message).
     * @return object PEAR_Error
     * @access public
     * @static
     */
    function &throwError($code, $extra = null)
    {
        // get the error message text based on the error code
        $index = '_' . $this->_primary_subclass;
        $text = $this->_primary_subclass . " Error - \n" 
              . $GLOBALS[$index]['error'][$code];
        
        // add any additional error text
        if ($extra) {
            $text .= ' ' . $extra;
        }
        
        // done!
        $error = PEAR::throwError($text, $code);
        return $error;
    }
   
    /**
     * Overwrites one or more error messages, e.g., to internationalize them.
     * 
     * May be used to change messages stored in global array $GLOBALS[$class_key]
     * @param mixed $code If string, the error message with code $code will be
     *                    overwritten by $message. If array, each key is a code
     *                    and each value is a new message. 
     * 
     * @param string $message Only used if $key is not an array.
     * @return void
     * @access public
     */
    function setErrorMessage($code, $message = null) {
        $index = '_' . $this->_primary_subclass;
        if (is_array($code)) {
            foreach ($code as $single_code => $single_message) {
                $GLOBALS[$index]['error'][$single_code] = $single_message;
            }
        } else {
            $GLOBALS[$index]['error'][$code] = $message;
        }
    }


    /**
     * Returns SQL SELECT string constructed from sql query array
     *
     * @param mixed  $query  SELECT query array, or key string of $this->sql
     * @param string $filter SQL snippet to AND with default WHERE clause
     * @param string $order  SQL snippet to override default ORDER BY clause
     * @param int    $start  The row number from which to start result set
     * @param int    $count  The number of rows to list in the result set.
     *
     * @return string SQL SELECT command string (or PEAR_Error on failure)
     *
     * @access public
     */
    function buildSQL($query, $filter = null, $order = null, 
                              $start = null, $count = null)
    {

        // Is $query a query array or a key of $this->sql ?
        if (!is_array($query)) {
            if (is_string($query)) {
                if (isset($this->sql[$query])) {
                    $query = $this->sql[$query];
                } else {
                    return $this->throwError(
                           constant($this->_primary_subclass . '_ERR_SQL_UNDEF'),
                           $query);
                }
            } else {
                return $this->throwError(
                       constant($this->_primary_subclass . '_ERR_SQL_NOT_STRING'));
            }
        }
       
        // Construct SQL command from parts
        $s = array();
        if (isset($query['select'])) {
            $s[] = 'SELECT ' . $query['select'];
        } else {
            $s[] = 'SELECT *';
        }
        if (isset($query['from'])) {
            $s[] = 'FROM ' . $query['from'];
        } elseif ($this->_primary_subclass == 'DB_TABLE') {
            $s[] = 'FROM ' . $this->table;
        }
        if (isset($query['join'])) {
            $s[] = $query['join'];
        }
        if (isset($query['where'])) {
            if ($filter) {
                $s[] = 'WHERE ( ' . $query['where'] . ' )';
                $s[] = '  AND ( '. $filter . ' )';
            } else {
                $s[] = 'WHERE ' . $query['where'];
            }
        } elseif ($filter) {
            $s[] = 'WHERE ' . $filter;
        }
        if (isset($query['group'])) {
            $s[] = 'GROUP BY ' . $query['group'];
        }
        if (isset($query['having'])) {
            $s[] = 'HAVING '. $query['having'];
        }
        // If $order parameter is set, override 'order' element
        if (!is_null($order)) {
            $s[] = 'ORDER BY '. $order;
        } elseif (isset($query['order'])) {
            $s[] = 'ORDER BY ' . $query['order'];
        }
        $cmd = implode("\n", $s);
        
        // add LIMIT if requested
        if (!is_null($start) && !is_null($count)) {
            $db =& $this->db;
            if ($this->backend == 'mdb2') {
                $db->setLimit($count, $start);
            } else {
                $cmd = $db->modifyLimitQuery(
                            $cmd, $start, $count);
            }
        }

        // Return command string
        return $cmd;
    }

  
    /**
     * Selects rows using one of the DB/MDB2 get*() methods.
     *
     * @param string $query SQL SELECT query array, or a key of the
     *                          $this->sql property array.
     * @param string $filter    SQL snippet to AND with default WHERE clause
     * @param string $order     SQL snippet to override default ORDER BY clause
     * @param int    $start     The row number from which to start result set
     * @param int    $count     The number of rows to list in the result set.
     * @param array  $params    Parameters for placeholder substitutions, if any
     * @return mixed  An array of records from the table if anything but 
     *                ('getOne'), a single value (if 'getOne'), or a PEAR_Error
     * @see DB::getAll()
     * @see MDB2::getAll()
     * @see DB::getAssoc()
     * @see MDB2::getAssoc()
     * @see DB::getCol()
     * @see MDB2::getCol()
     * @see DB::getOne()
     * @see MDB2::getOne()
     * @see DB::getRow()
     * @see MDB2::getRow()
     * @see DB_Table_Base::_swapModes()
     * @access public
     */
    function select($query, $filter = null, $order = null,
                            $start = null, $count = null, $params = array())
    {

        // Is $query a query array or a key of $this->sql ?
        // On output from this block, $query is an array
        if (!is_array($query)) {
            if (is_string($query)) {
                if (isset($this->sql[$query])) {
                    $query = $this->sql[$query];
                } else {
                    return $this->throwError(
                          constant($this->_primary_subclass . '_ERR_SQL_UNDEF'),
                          $query);
                }
            } else {
                return $this->throwError(
                    constant($this->_primary_subclass . '_ERR_SQL_NOT_STRING'));
            }
        }

        // build the base command
        $sql = $this->buildSQL($query, $filter, $order, $start, $count);
        if (PEAR::isError($sql)) {
            return $sql;
        }

        // set the get*() method name
        if (isset($query['get'])) {
            $method = ucwords(strtolower(trim($query['get'])));
            $method = "get$method";
        } else {
            $method = 'getAll';
        }

        // DB_Table assumes you are using a shared PEAR DB/MDB2 object.
        // Record fetchmode settings, to be restored before returning.
        $db =& $this->db;
        $restore_mode = $db->fetchmode;
        if ($this->backend == 'mdb2') {
            $restore_class = $db->getOption('fetch_class');
        } else {
            $restore_class = $db->fetchmode_object_class;
        }

        // swap modes
        $fetchmode = $this->fetchmode;
        $fetchmode_object_class = $this->fetchmode_object_class;
        if (isset($query['fetchmode'])) {
            $fetchmode = $query['fetchmode'];
        }
        if (isset($query['fetchmode_object_class'])) {
            $fetchmode_object_class = $query['fetchmode_object_class'];
        }
        $this->_swapModes($fetchmode, $fetchmode_object_class);

        // make sure params is an array
        if (!is_null($params)) {
            $params = (array) $params;
        }

        // get the result
        if ($this->backend == 'mdb2') {
            $result = $db->extended->$method($sql, null, $params);
        } else {
            switch ($method) {

                case 'getCol':
                    $result = $db->$method($sql, 0, $params);
                    break;

                case 'getAssoc':
                    $result = $db->$method($sql, false, $params);
                    break;

                default:
                    $result = $db->$method($sql, $params);
                    break;

            }
        }

        // restore old fetch_mode and fetch_object_class back
        $this->_swapModes($restore_mode, $restore_class);

        return $result;
    }


    /**
     * Selects rows as a DB_Result/MDB2_Result_* object.
     *
     * @param string $query  The name of the SQL SELECT to use from the
     *                       $this->sql property array.
     * @param string $filter SQL snippet to AND to the default WHERE clause
     * @param string $order  SQL snippet to override default ORDER BY clause
     * @param int    $start  The record number from which to start result set
     * @param int    $count  The number of records to list in result set.
     * @param array $params  Parameters for placeholder substitutions, if any.
     * @return object DB_Result/MDB2_Result_* object on success
     *                (PEAR_Error on failure)
     * @see DB_Table::_swapModes()
     * @access public
     */
    function selectResult($query, $filter = null, $order = null,
                   $start = null, $count = null, $params = array())
    {
        // Is $query a query array or a key of $this->sql ?
        // On output from this block, $query is an array
        if (!is_array($query)) {
            if (is_string($query)) {
                if (isset($this->sql[$query])) {
                    $query = $this->sql[$query];
                } else {
                    return $this->throwError(
                           constant($this->_primary_subclass . '_ERR_SQL_UNDEF'),
                           $query);
                }
            } else {
                return $this->throwError(
                       constant($this->_primary_subclass . '_ERR_SQL_NOT_STRING'));
            }
        }
       
        // build the base command
        $sql = $this->buildSQL($query, $filter, $order, $start, $count);
        if (PEAR::isError($sql)) {
            return $sql;
        }

        // DB_Table assumes you are using a shared PEAR DB/MDB2 object.
        // Record fetchmode settings, to be restored afterwards.
        $db =& $this->db;
        $restore_mode = $db->fetchmode;
        if ($this->backend == 'mdb2') {
            $restore_class = $db->getOption('fetch_class');
        } else {
            $restore_class = $db->fetchmode_object_class;
        }

        // swap modes
        $fetchmode = $this->fetchmode;
        $fetchmode_object_class = $this->fetchmode_object_class;
        if (isset($query['fetchmode'])) {
            $fetchmode = $query['fetchmode'];
        }
        if (isset($query['fetchmode_object_class'])) {
            $fetchmode_object_class = $query['fetchmode_object_class'];
        }
        $this->_swapModes($fetchmode, $fetchmode_object_class);

        // make sure params is an array
        if (!is_null($params)) {
            $params = (array) $params;
        }

        // get the result
        if ($this->backend == 'mdb2') {
            $stmt =& $db->prepare($sql);
            if (PEAR::isError($stmt)) {
                return $stmt;
            }
            $result =& $stmt->execute($params);
        } else {
            $result =& $db->query($sql, $params);
        }

        // swap modes back
        $this->_swapModes($restore_mode, $restore_class);

        // return the result
        return $result;
    }


    /**
     * Counts the number of rows which will be returned by a query.
     *
     * This function works identically to {@link select()}, but it
     * returns the number of rows returned by a query instead of the
     * query results themselves.
     *
     * @author Ian Eure <ian@php.net>
     * @param string $query  The name of the SQL SELECT to use from the
     *                       $this->sql property array.
     * @param string $filter Ad-hoc SQL snippet to AND with the default
     *                       SELECT WHERE clause.
     * @param string $order  Ad-hoc SQL snippet to override the default
     *                       SELECT ORDER BY clause.
     * @param int    $start  Row number from which to start listing in result
     * @param int    $count  Number of rows to list in result set
     * @param array  $params Parameters to use in placeholder substitutions
     *                       (if any).
     * @return int   Number of records from the table (or PEAR_Error on failure)
     *
     * @see DB_Table::select()
     * @access public
     */
    function selectCount($query, $filter = null, $order = null,
                       $start = null, $count = null, $params = array())
    {

        // Is $query a query array or a key of $this->sql ?
        if (is_array($query)) {
            $sql_key = null;
            $count_query = $query;
        } else {
            if (is_string($query)) {
                if (isset($this->sql[$query])) {
                    $sql_key = $query;
                    $count_query = $this->sql[$query];
                } else {
                    return $this->throwError(
                           constant($this->_primary_subclass . '_ERR_SQL_UNDEF'), 
                           $query);
                }
            } else {
                return $this->throwError(
                       constant($this->_primary_subclass . '_ERR_SQL_NOT_STRING'));
            }
        }

        // Use Table name as default 'from' if child class is DB_TABLE
        if ($this->_primary_subclass == 'DB_TABLE') {
            if (!isset($query['from'])) {
                $count_query['from'] = $this->table;
            }
        }

        // If the query is a stored query in $this->sql, then create a corresponding
        // key for the count query, or check if the count-query already exists
        $ready = false;
        if ($sql_key) {
            // Create an sql key name for this count-query
            $count_key = '__count_' . $sql_key;
            // Check if a this count query alread exists in $this->sql
            if (isset($this->sql[$count_key])) {
                $ready = true;
            }
        }

        // If a count-query does not already exist, create $count_query array
        if ($ready) {

            $count_query = $this->sql[$count_key];

        } else {

            // Is a count-field set for the query?
            if (!isset($count_query['count']) || 
                trim($count_query['count']) == '') {
                $count_query['count'] = '*';
            }

            // Replace the SELECT fields with a COUNT() command
            $count_query['select'] = "COUNT({$count_query['count']})";

            // Replace the 'get' key so we only get one result item
            $count_query['get'] = 'one';

            // Create a new count-query in $this->sql
            if ($sql_key) {
                $this->sql[$count_key] = $count_query;
            }

        }

        // Retrieve the count results
        return $this->select($count_query, $filter, $order,
                             $start, $count, $params);

    }

    /**
     * Changes the $this->db PEAR DB/MDB2 object fetchmode and
     * fetchmode_object_class.
     *
     * @param string $new_mode A DB/MDB2_FETCHMODE_* constant.  If null,
     * defaults to whatever the DB/MDB2 object is currently using.
     *
     * @param string $new_class The object class to use for results when
     * the $db object is in DB/MDB2_FETCHMODE_OBJECT fetch mode.  If null,
     * defaults to whatever the the DB/MDB2 object is currently using.
     *
     * @return void
     * @access private
     */
    function _swapModes($new_mode, $new_class)
    {
        // get the old (current) mode and class
        $db =& $this->db;
        $old_mode = $db->fetchmode;
        if ($this->backend == 'mdb2') {
            $old_class = $db->getOption('fetch_class');
        } else {
            $old_class = $db->fetchmode_object_class;
        }

        // don't need to swap anything if the new modes are both
        // null or if the old and new modes already match.
        if ((is_null($new_mode) && is_null($new_class)) ||
            ($old_mode == $new_mode && $old_class == $new_class)) {
            return;
        }

        // set the default new mode
        if (is_null($new_mode)) {
            $new_mode = $old_mode;
        }

        // set the default new class
        if (is_null($new_class)) {
            $new_class = $old_class;
        }

        // swap modes
        $db->setFetchMode($new_mode, $new_class);
    }


    /**
     * Returns SQL condition equating columns to literal values.
     *
     * The parameter $data is an associative array in which keys are
     * column names and values are corresponding values. The method
     * returns an SQL string that is true if the value of every 
     * specified database columns is equal to the corresponding 
     * value in $data. 
     * 
     * For example, if:
     * <code>
     *     $data = array( 'c1' => 'thing', 'c2' => 23, 'c3' => 0.32 )
     * </code>
     * then buildFilter($data) returns a string 
     * <code>
     *     c1 => 'thing' AND c2 => 23 AND c3 = 0.32
     * </code>
     * in which string values are replaced by SQL literal values, 
     * quoted and escaped as necessary.
     * 
     * Values are quoted and escaped as appropriate for each data 
     * type and the backend RDBMS, using the MDB2::quote() or
     * DB::smartQuote() method. The behavior depends on the PHP type
     * of the value: string values are quoted and escaped, while 
     * integer and float numerical values are not. Boolean values
     * in $data are represented as 0 or 1, consistent with the way 
     * booleans are stored by DB_Table. 
     *
     * Null values: The treatment of null values in $data depends upon 
     * the value of the $match parameter . If $match == 'simple', an 
     * empty string is returned if any $value of $data with a key in 
     * $data_key is null. If $match == 'partial', the returned SQL 
     * expression equates only the relevant non-null values of $data 
     * to the values of corresponding database columns. If 
     * $match == 'full', the function returns an empty string if all 
     * of the relevant values of data are null, and returns a 
     * PEAR_Error if some of the selected values are null and others 
     * are not null.
     *
     * @param array $data associative array, keys are column names
     * @return string SQL expression equating values in $data to 
     *                values of columns named by keys.
     * @access public
     */
    function buildFilter($data, $match = 'simple')
    {
        // Check $match type value
        if (!in_array($match, array('simple', 'partial', 'full'))) {
            return $this->throwError(
                            DB_TABLE_DATABASE_ERR_MATCH_TYPE);
        }

        if (count($data) == 0) {
            return '';
        }
        $filter = array();
        foreach ($data as $key => $value) {
            if (!is_null($value)) {
                if ($match == 'full' && isset($found_null)) {
                    return $this->throwError(
                              DB_TABLE_DATABASE_ERR_FULL_KEY);
                }
                if (is_bool($value)) {
                   $value = $value ? '1' : '0';
                } else {
                    if ($this->backend == 'mdb2') {
                        $value = $this->db->quote($value);
                    } else {
                        $value = $this->db->quoteSmart($value);
                    }
                }
                $filter[] = "$key = $value";
            } else {
                if ($match == 'simple') {
                    return ''; // if any value in $data is null
                } elseif ($match == 'full') {
                    $found_null = true;
                }
            }
        }
        return implode(' AND ', $filter);
    }

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
