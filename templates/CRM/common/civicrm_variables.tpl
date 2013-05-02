{* This contains the variables that various jQuery functions need, for instance to define the ajax url to call *}
<script type="text/javascript">
civicrm = new Object;
civicrm.config = {ldelim}
  relativeURL : "{$config->civiRelativeURL}",
  frameworkBaseURL     : "{$config->userFrameworkBaseURL}",
  frameworkResourceURL: "{$config->userFrameworkResourceURL}",
  restURL = "{crmURL p='civicrm/ajax/rest' q='json=1' h=0}"
{rdelim};
</script>
