<?php

function civicrmVersion( ) {ldelim}
{include file="../../templates/CRM/common/version.tpl" assign=svnrevision}
  return array( 'version'  => '{$db_version}',
                'cms'      => '{$cms}',
                'revision' => '{$svnrevision}' );
{rdelim}



