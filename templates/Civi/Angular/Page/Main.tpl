{literal}
<script type="text/javascript">
  if (CRM.hasOwnProperty('angularRoute') && CRM.angularRoute) {
    location.hash = CRM.angularRoute;
  }
</script>

<crm-angular-js modules="crmApp">
  <div ng-view></div>
</crm-angular-js>
{/literal}

