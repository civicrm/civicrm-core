<?php
/**
 * PHPUnit
 *
 * Copyright (c) 2002-2010, Sebastian Bergmann <sb@sebastian-bergmann.de>.
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions
 * are met:
 *
 *   * Redistributions of source code must retain the above copyright
 *     notice, this list of conditions and the following disclaimer.
 *
 *   * Redistributions in binary form must reproduce the above copyright
 *     notice, this list of conditions and the following disclaimer in
 *     the documentation and/or other materials provided with the
 *     distribution.
 *
 *   * Neither the name of Sebastian Bergmann nor the names of his
 *     contributors may be used to endorse or promote products derived
 *     from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS
 * FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
 * COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT,
 * INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING,
 * BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER
 * CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT
 * LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN
 * ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 *
 * @package    DbUnit
 * @author     Nils Adermann <naderman@naderman.de>
 * @copyright  2002-2010 Sebastian Bergmann <sb@sebastian-bergmann.de>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @link       http://www.phpunit.de/
 * @since      File available since Release 1.1.0
 */

/**
 * Provides functionality to retrieve meta data from a Microsoft SQL Server database.
 *
 * @package    DbUnit
 * @author     Nils Adermann <naderman@naderman.de>
 * @copyright  2002-2010 Sebastian Bergmann <sb@sebastian-bergmann.de>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @version    Release: 1.1.2
 * @link       http://www.phpunit.de/
 * @since      Class available since Release 1.1.0
 */
class PHPUnit_Extensions_Database_DB_MetaData_SqlSrv extends PHPUnit_Extensions_Database_DB_MetaData
{
    /**
     * No character used to quote schema objects.
     * @var string
     */
    protected $schemaObjectQuoteChar = '';

    /**
     * The command used to perform a TRUNCATE operation.
     * @var string
     */
    protected $truncateCommand = 'TRUNCATE TABLE';

    /**
     * Returns an array containing the names of all the tables in the database.
     *
     * @return array
     */
    public function getTableNames()
    {
        $query = "SELECT name
                    FROM sysobjects
                   WHERE type='U'";

        $statement = $this->pdo->prepare($query);
        $statement->execute();

        $tableNames = array();
        while (($tableName = $statement->fetchColumn(0))) {
            $tableNames[] = $tableName;
        }

        return $tableNames;
    }

    /**
     * Returns an array containing the names of all the columns in the
     * $tableName table.
     *
     * @param string $tableName
     * @return array
     */
    public function getTableColumns($tableName)
    {
        $query = "SELECT c.name
                    FROM syscolumns c
               LEFT JOIN sysobjects o ON c.id = o.id
                   WHERE o.name = '$tableName'";

        $statement = $this->pdo->prepare($query);
        $statement->execute();

        $columnNames = array();
        while (($columnName = $statement->fetchColumn(0))) {
            $columnNames[] = $columnName;
        }

        return $columnNames;
    }

    /**
     * Returns an array containing the names of all the primary key columns in
     * the $tableName table.
     *
     * @param string $tableName
     * @return array
     */
    public function getTablePrimaryKeys($tableName)
    {
        $query     = "EXEC sp_statistics '$tableName'";
        $statement = $this->pdo->prepare($query);
        $statement->execute();
        $statement->setFetchMode(PDO::FETCH_ASSOC);

        $columnNames = array();
        while (($column = $statement->fetch())) {
            if ($column['TYPE'] == 1) {
                $columnNames[] = $column['COLUMN_NAME'];
            }
        }

        return $columnNames;
    }

    /**
    * Allow overwriting identities for the given table.
    *
    * @param string $tableName
    */
    public function disablePrimaryKeys($tableName)
    {
        try {
            $query = "SET IDENTITY_INSERT $tableName ON";
            $this->pdo->exec($query);
        }
        catch (PDOException $e) {
            // ignore the error here - can happen if primary key is not an identity
        }
    }

    /**
    * Reenable auto creation of identities for the given table.
    *
    * @param string $tableName
    */
    public function enablePrimaryKeys($tableName)
    {
        try {
            $query = "SET IDENTITY_INSERT $tableName OFF";
            $this->pdo->exec($query);
        }
        catch (PDOException $e) {
            // ignore the error here - can happen if primary key is not an identity
        }
    }
}
