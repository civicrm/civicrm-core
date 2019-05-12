<?php
/**
 * File containing the ezcMailParserShutdownHandler class
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
 * ezcMailParserShutDownHandler removes temporary files
 * and directories when PHP shuts down.
 *
 * Example:
 * <code>
 * ezcMailParserShutdownHandler::registerForRemoval( "/tmp/file.txt" );
 * </code>
 *
 * The code above will result in file.txt being removed from the system
 * (if it still exists) when PHP shuts down.
 *
 * @package Mail
 * @version //autogen//
 * @access private
 */
class ezcMailParserShutdownHandler
{
    /**
     * Holds if the handler is registered or not.
     *
     * @var boolean
     */
    private static $isRegistered = false;

    /**
     * Holds the array of directories that are marked for removal
     * when PHP shuts down.
     *
     * @var array(string)
     */
    private static $directories = array();

    /**
     * Registers the directory $dir for removal when PHP shuts down.
     *
     * The directory and all of its contents will be removed recursively.
     *
     * @param string $dir
     */
    public static function registerForRemoval( $dir )
    {
        if ( self::$isRegistered === false )
        {
            register_shutdown_function( array( "ezcMailParserShutdownHandler", "shutdownCallback" ) );
            self::$isRegistered = true;
        }
        self::$directories[] = $dir;
    }

    /**
     * Recursively deletes all registered folders and any contents of the registered
     * directories.
     *
     * Files or directories that can't be deleted are left without warning.
     *
     * @return void
     */
    public static function shutdownCallback()
    {
        foreach ( self::$directories as $directory )
        {
            self::remove( $directory );
        }
    }

    /**
     * Recursively removes a directory and its contents or a file.
     *
     * Returns true on success and false on error.
     *
     * @param string $itemName
     * @return bool
     */
    public static function remove( $itemName )
    {
        $returnVar = true;
        if ( !is_dir( $itemName ) && file_exists( $itemName ) )
        {
            unlink( $itemName );
            return true;
        }

        if ( !file_exists( $itemName ) )
        {
            return true;
        }

        $dir = dir( $itemName );
        $item = $dir->read();
        while ( $item !== false )
        {
            if ( $item != '.' && $item != '..' )
            {
                self::remove( $dir->path . DIRECTORY_SEPARATOR . $item );
                $returnVar = false;
            }
            $item = $dir->read();
        }

        $dir->close();
        $returnVar = rmdir( $itemName ) && $returnVar ? true : false; // if rmdir succeeds and everything else succeeded
        return $returnVar;
    }
}

?>
