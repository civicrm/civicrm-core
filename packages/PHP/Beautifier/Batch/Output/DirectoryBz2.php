<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */
/**
* Compress all the files to one bz2 file
*
* PHP version 5
*
* LICENSE: This source file is subject to version 3.0 of the PHP license
* that is available through the world-wide-web at the following URI:
* http://www.php.net/license/3_0.txt.  If you did not receive a copy of
* the PHP License and are unable to obtain it through the web, please
* send a note to license@php.net so we can mail you a copy immediately.
* @category   PHP
* @package PHP_Beautifier
* @subpackage Batch
* @author Claudio Bustos <cdx@users.sourceforge.com>
* @copyright  2004-2006 Claudio Bustos
* @link     http://pear.php.net/package/PHP_Beautifier
* @link     http://beautifyphp.sourceforge.net
* @license    http://www.php.net/license/3_0.txt  PHP License 3.0
* @version    CVS: $Id:$
*/
/**
* Include PHP_Beautifier_Batch_DirectoryTar
*/
require_once ('DirectoryTar.php');
/**
* PHP_Beautifier_Batch_FilesGz
*
* Compress all the files to one bz2 file
*
* @category   PHP
* @package PHP_Beautifier
* @subpackage Batch
* @author Claudio Bustos <cdx@users.sourceforge.com>
* @copyright  2004-2006 Claudio Bustos
* @link     http://pear.php.net/package/PHP_Beautifier
* @link     http://beautifyphp.sourceforge.net
* @license    http://www.php.net/license/3_0.txt  PHP License 3.0
* @version    Release: 0.1.14
*/
class PHP_Beautifier_Batch_Output_DirectoryBz2 extends PHP_Beautifier_Batch_DirectoryTar {
    protected function getTar($sFileName) 
    {
        return new Archive_Tar($sFileName.'.tar.bz2', 'bz2');
    }
}
?>