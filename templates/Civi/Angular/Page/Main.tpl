{if $route}
  <script type="text/javascript">
    location.hash = '{$route}';
  </script>
{/if}
{literal}
<div ng-app="crmApp">
  <div ng-view></div>
</div>
{/literal}

