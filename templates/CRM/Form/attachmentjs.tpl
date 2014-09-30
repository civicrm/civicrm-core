<script type="text/javascript">
{literal}
  CRM.$(function($) {
    $('a.delete-attachment').off('.crmAttachments').on('click.crmAttachments', function(e) {
      var $el = $(this),
        $row = $el.closest('.crm-attachment-wrapper'),
        msg = '{/literal}{ts escape="js" 1="%1"}This will immediately delete the file %1. This action cannot be undone.{/ts}{literal}';
      CRM.confirm({
        title: $el.attr('title'),
        message: ts(msg, {1: '<em>' + $el.data('filename') + '</em>'})
      }).on('crmConfirm:yes', function() {
        var postUrl = {/literal}"{crmURL p='civicrm/file/delete' h=0 }"{literal};
        var request = $.post(postUrl, $el.data('args'));
        CRM.status({success: '{/literal}{ts escape="js"}Removed{/ts}{literal}'}, request);
        request.done(function() {
          $el.trigger('crmPopupFormSuccess');
          $row.remove();
        });
      });
      e.preventDefault();
    });
  });
{/literal}
</script>
