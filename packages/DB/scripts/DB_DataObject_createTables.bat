@ECHO OFF
REM $Id: DB_DataObject_createTables.bat,v 1.1 2003/09/08 20:43:31 arnaud Exp $
REM BATCH FILE TO EXECUTE PEAR::DB_DATAOBJECT createTables.php script

IF '%1' == '' (
    ECHO **************************************************
    ECHO * Please pass the name of the ini file
    ECHO * to process at the only parameter
    ECHO *
    ECHO * e.g.: DB_DataObject_createTables my_ini_file.ini
    ECHO **************************************************
    GOTO :EOF
)

%PHP_PEAR_PHP_BIN% %PHP_PEAR_INSTALL_DIR%\DB\DataObject\createTables.php %1