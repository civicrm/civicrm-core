<?php
/**
 * File containing the ezcMailTransport class
 *
 * @package Mail
 * @version 1.7beta1
 * @copyright Copyright (C) 2005-2009 eZ Systems AS. All rights reserved.
 * @license http://ez.no/licenses/new_bsd New BSD License
 */

/**
 * Interface for classes that implement a mail transport.
 *
 * Subclasses must implement the send() method.
 *
 * @package Mail
 * @version 1.7beta1
 */
interface ezcMailTransport
{
    /**
     * Sends the contents of $mail.
     *
     * @param ezcMail $mail
     */
    public function send( ezcMail $mail );
}
?>
