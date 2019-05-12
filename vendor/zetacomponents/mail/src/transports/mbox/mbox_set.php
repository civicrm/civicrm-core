<?php
/**
 * File containing the ezcMailMboxSet class
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
 * @package Mail
 * @version //autogen//
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 */

/**
 * ezcMailMboxSet is an internal class that fetches a series of mail
 * from an mbox file.
 *
 * The mbox set is constructed from a file pointer and iterates over all the
 * messages in an mbox file.
 *
 * @package Mail
 * @version //autogen//
 */
class ezcMailMboxSet implements ezcMailParserSet
{
    /**
     * Holds the filepointer to the mbox
     *
     * @var resource(filepointer)
     */
    private $fh;

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
     * Records whether we initialized the mbox or not
     *
     * @var bool
     */
    private $initialized = false;

    /**
     * Holds the current message positions.
     *
     * @var array(int=>int)
     */
    private $messagePositions = array();

    /**
     * Holds the current message position in array $messagePositions.
     *
     * @var int
     */
    private $currentMesssagePosition = 0;

    /**
     * Constructs a new mbox parser set.
     *
     * @throws ezcBaseFileIoException
     *         if $fh is not a filepointer resource.
     * @param resource(filepointer) $fh
     * @param array(int=>int) $messages
     */
    public function __construct( $fh, array $messages )
    {
        if ( !is_resource( $fh ) || get_resource_type( $fh ) != 'stream' )
        {
            throw new ezcBaseFileIoException( 'filepointer', ezcBaseFileException::READ, "The passed filepointer is not a stream resource." );
        }
        $this->fh = $fh;
        $this->initialized = false;
        $this->hasMoreMailData = true;
        $this->messagePositions = $messages;
        $this->currentMessagePosition = 0;
    }

    /**
     * Returns true if all the data has been fetched from this set.
     *
     * @return bool
     */
    public function isFinished()
    {
        return feof( $this->fh ) ? true : false;
    }

    /**
     * Returns one line of data from the current mail in the set
     * including the ending linebreak.
     *
     * Null is returned if there is no current mail in the set or
     * the end of the mail is reached.
     *
     * @return string
     */
    public function getNextLine()
    {
        if ( $this->currentMessagePosition === 0 )
        {
            $this->nextMail();
        }
        if ( $this->hasMoreMailData )
        {
            $data = fgets( $this->fh );
            if ( feof( $this->fh ) || substr( $data, 0, 5 ) === "From " )
            {
                $this->hasMoreMailData = false;

                return null;
            }
            return $data;
        }
        return null;
    }

    /**
     * Returns whether the set contains mails.
     *
     * @return bool
     */
    public function hasData()
    {
        return ( $this->hasMoreMailData === true && count( $this->messagePositions ) > 0 );
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
        // seek to next message if available
        if ( $this->currentMessagePosition > count( $this->messagePositions ) - 1 )
        {
            $this->hasMoreMailData = false;
            return false;
        }
        fseek( $this->fh, $this->messagePositions[$this->currentMessagePosition] );
        $this->currentMessagePosition++;
        $this->hasMoreMailData = true;

        return true;
    }

    /**
     * Returns message numbers for current set.
     *
     * @return array(int=>int)
     */
    public function getMessageNumbers()
    {
        return array_keys( $this->messagePositions );
    }
}
?>
