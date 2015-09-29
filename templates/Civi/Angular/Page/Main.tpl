{literal}
<script type="text/javascript">
  if (CRM.hasOwnProperty('angularRoute') && CRM.angularRoute) {
    location.hash = CRM.angularRoute;
  }
</script>

<div ng-app="crmApp">
  <div ng-view></div>
</div>
{/literal}

