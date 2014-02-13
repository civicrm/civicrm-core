<?php
/**
* Interface for StreamWrappers
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
* @subpackage StreamWrapper
* @author Claudio Bustos <cdx@users.sourceforge.com>
* @copyright  2004-2006 Claudio Bustos
* @link     http://pear.php.net/package/PHP_Beautifier
* @link     http://beautifyphp.sourceforge.net
* @license    http://www.php.net/license/3_0.txt  PHP License 3.0
* @version    CVS: $Id:$
*/
/**
* Interface for StreamWrapper
* Read the documentation for streams wrappers on php manual.
*
* @category   PHP
* @package PHP_Beautifier
* @subpackage StreamWrapper
* @author Claudio Bustos <cdx@users.sourceforge.com>
* @copyright  2004-2006 Claudio Bustos
* @link     http://pear.php.net/package/PHP_Beautifier
* @link     http://beautifyphp.sourceforge.net
* @license    http://www.php.net/license/3_0.txt  PHP License 3.0
* @version    Release: 0.1.14
*/
interface PHP_Beautifier_StreamWrapper_Interface {
    function stream_open($sPath, $sMode, $iOptions, &$sOpenedPath);
    function stream_close();
    function stream_read($iCount);
    function stream_write($sData);
    function stream_eof();
    function stream_tell();
    function stream_seek($iOffset, $iWhence);
    function stream_flush();
    function stream_stat();
    function unlink($sPath);
    function dir_opendir($sPath, $iOptions);
    function dir_readdir();
    function dir_rewinddir();
    function dir_closedir();
}
require_once ('StreamWrapper/Tarz.php');
?>