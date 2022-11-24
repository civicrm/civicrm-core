<div id='crm-import-progress'></div>
{literal}
  <script>
    CRM.$(function($) {
      var target = '#crm-import-progress';
      var url = CRM.vars.civiimport.url;
      // Load the snippet into the container
      CRM.loadPage(url, {
        target: target,
        block: false
      })
      window.setInterval(function () {
        if (document.hasFocus()) {
          CRM.$(target).crmSnippet('refresh');
        }
      }, 1000);
    });
  </script>
{/literal}
