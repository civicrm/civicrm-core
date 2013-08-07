(function($, CRM) {

  /**
   * Usage:
   *
   * cj('.my-link').crmRevisionLink({
   *   'reportId': 123, // CRM_Report_Utils_Report::getInstanceIDForValue('logging/contact/summary'),
   *   'tableName': 'my_table',
   *   'contactId': 123
   * ));
   *
   * Note: This file is used by CivHR
  */
  $.fn.crmRevisionLink = function(options) {
    return this.each(function(){
      var $dialog = $('<div><div class="revision-content"></div></div>');
      $('body').append($dialog);
      $(this).on("click", function() {
        $dialog.show();
        $dialog.dialog({
          title: ts("Revisions"),
          modal: true,
          width: "680px",
          bgiframe: true,
          overlay: { opacity: 0.5, background: "black" },
          open:function() {
            var ajaxurl = CRM.url("civicrm/report/instance/" + options.reportId);
            cj.ajax({
              data: "reset=1&snippet=4&section=2&altered_contact_id_op=eq&altered_contact_id_value="+options.contactId+"&log_type_table_op=has&log_type_table_value=" + options.tableName,
              url:  ajaxurl,
              success: function (data) {
                $dialog.find(".revision-content").html(data);
                if (!$dialog.find(".revision-content .report-layout").length) {
                  $dialog.find(".revision-content").html("Sorry, couldn't find any revisions.");
                }
              }
            });
          },
          buttons: {
            "Done": function() {
              $(this).dialog("destroy");
            }
          }
        });
        return false;
      });
    }); // this.each
  }; // fn.crmRevisionLink

})(jQuery, CRM);
