<crm-angular-js modules="{$block.module}">
  <{$block.wrapper|default:'form'} id="bootstrap-theme">
    <{$block.directive} options='{$afformOptions|@json_encode}'></{$block.directive}>
  </{$block.wrapper|default:'form'}>
</crm-angular-js>
