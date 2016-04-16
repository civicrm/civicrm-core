<?php
/**
 * File containing the ezcMailTransportException class
 *
 * @package Mail
 * @version //autogen//
 * @copyright Copyright (C) 2005-2009 eZ Systems AS. All rights reserved.
 * @license http://ez.no/licenses/new_bsd New BSD License
 */

/**
 * Transport exceptions are thrown when either sending or receiving
 * mail transports fail to do their job properly.
 *
 * @package Mail
 * @version //autogen//
 */
class ezcMailTransportException extends ezcMailException
{
    /**
     * Constructs an ezcMailTransportException with low level information $message.
     *
     * @param string $message
     */
    public function __construct( $message = '' )
    {
        parent::__construct( "An error occured while sending or receiving mail. " . $message );
    }
}
?>
