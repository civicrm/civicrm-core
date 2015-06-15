<?php

function civicrmVersion() {ldelim}
  return array( 'version'  => '{$db_version}',
                'cms'      => '{$cms}',
                'revision' => '{$svnrevision}' );
{rdelim}
