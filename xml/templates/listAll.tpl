<?php
// (GenCodeChecksum:{$genCodeChecksum})

return [
{foreach from=$tables key=tableName item=table}
  '{$table.className}' => [
    'name' => '{$table.objectName}',
    'class' => '{$table.className}',
    'table' => '{$tableName}',
  ],
{/foreach}
];
