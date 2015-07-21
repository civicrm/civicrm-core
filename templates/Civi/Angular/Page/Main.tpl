{if $snippet}
    <script type="text/javascript">
        location.hash = '{$route}';
    </script>
    {foreach from=$styles item=styleURL}
        <link rel="stylesheet" type="text/css" href="{$styleURL}" />
    {/foreach}
    {foreach from=$scripts item=scriptURL}
        <script type="text/javascript" src="{$scriptURL}"></script>
    {/foreach}
{/if}
{literal}
<div ng-app="crmApp">
  <div ng-view></div>
</div>
{/literal}

