<?php
/**
 * File containing the ezcMailFileSet class
 *
 * Licensed to the Apache Software Foundation (ASF) under one
 * or more contributor license agreements.  See the NOTICE file
 * distributed with this work for additional information
 * regarding copyright ownership.  The ASF licenses this file
 * to you under the Apache License, Version 2.0 (the
 * "License"); you may not use this file except in compliance
 * with the License.  You may obtain a copy of the License at
 * 
 *   http://www.apache.org/licenses/LICENSE-2.0
 * 
 * Unless required by applicable law or agreed to in writing,
 * software distributed under the License is distributed on an
 * "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY
 * KIND, either express or implied.  See the License for the
 * specific language governing permissions and limitations
 * under the License.
 *
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 * @version //autogentag//
 * @package Mail
 */

/**
 * ezcMailFileSet is an internal class that can be used to parse mail directly
 * from files on disk.
 *
 * Each file should contain only one mail message in RFC822 format. Bad files
 * or non-existing files are ignored.
 *
 * Example:
 *
 * <code>
 * $set = new ezcMailFileSet( array( 'path/to/mail/rfc822message.mail' ) );
 * $parser = new ezcMailParser();
 * $mail = $parser->parseMail( $set );
 * </code>
 *
 * @package Mail
 * @version //autogen//
 */
class ezcMailFileSet implements ezcMailParserSet
{
    /**
     * Holds the pointer to the file currently being parsed.
     *
     * @var filepointer
     */
    private $fp = null;

    /**
     * Holds the list of files that the set should serve.
     *
     * @var array(string)
     */
    private $files = array();

    /**
     * This variable is true if there is more data in the mail that is being fetched.
     *
     * It is false if there is no mail being fetched currently or if all the data of the current mail
     * has been fetched.
     *
     * @var bool
     */
    private $hasMoreMailData = false;

    /**
     * Constructs a new set that servers the files specified by $files.
     *
     * The set will start on the first file in the the array.
     *
     * @param array(string) $files
     */
    public function __construct( array $files )
    {
        $this->files = $files;
        reset( $this->files );
        $this->hasMoreMailData = false;
    }

    /**
     * Destructs the set.
     *
     * Closes any open files.
     */
    public function __destruct()
    {
        if ( is_resource( $this->fp ) )
        {
            fclose( $this->fp );
            $this->fp = null;
        }
    }

    /**
     * Returns whether the file set contains files
     *
     * @return bool
     */
    public function hasData()
    {
        if ( count( $this->files ) >= 1 )
        {
            if ( $this->files[0] === 'php://stdin' || filesize( $this->files[0] ) > 0 )
            {
                return true;
            }
        }
        return false;
    }

    /**
     * Returns one line of data from the current mail in the set.
     *
     * Null is returned if there is no current mail in the set or
     * the end of the mail is reached,
     *
     * @return string
     */
    public function getNextLine()
    {
        if ( $this->hasMoreMailData === false )
        {
            $this->nextMail();
        }
        // finished?
        if ( $this->fp == null || feof( $this->fp ) )
        {
            if ( $this->fp != null )
            {
                fclose( $this->fp );
                $this->fp = null;
            }
            return null;
        }

        // get one line
        $next = fgets( $this->fp );
        if ( $next == "" && feof( $this->fp ) )
        {
            return null;
        }
        return $next;
    }

    /**
     * Moves the set to the next mail and returns true upon success.
     *
     * False is returned if there are no more mail in the set.
     *
     * @return bool
     */
    public function nextMail()
    {
        if ( $this->hasMoreMailData === false )
        {
            $this->hasMoreMailData = true;
            return $this->openFile( true );
        }
        return $this->openFile();
    }

    /**
     * Opens the next file in the set and returns true on success.
     *
     * @param bool $isFirst
     * @return bool
     */
    private function openFile( $isFirst = false )
    {
        // cleanup file pointer if needed
        if ( $this->fp != null )
        {
            fclose( $this->fp );
            $this->fp = null;
        }

        // open the new file
        $file = $isFirst ? current( $this->files ) : next( $this->files );

        // loop until we can open a file.
        while ( $this->fp == null && $file !== false )
        {
            if ( $file === 'php://stdin' || file_exists( $file ) )
            {
                $fp = fopen( $file, 'r' );
                if ( $fp !== false )
                {
                    $this->fp = $fp;
                    return true;
                }
            }
            $file = next( $this->files );
        }
        return false;
    }
}
?>
