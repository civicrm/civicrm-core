// http://civicrm.org/licensing
CRM.$(function($) {
  $('body')
    .off('.crmExpandRow')
    .on('click.crmExpandRow', 'a.crm-expand-row', function(e) {
      var $row = $(this).closest('tr');
      if ($(this).hasClass('expanded')) {
        $row.next('.crm-child-row').children('td').children('div.crm-ajax-container')
          .slideUp('fast', function() {$(this).closest('.crm-child-row').remove();});
      } else {
        var count = $('td', $row).length,
          $newRow = $('<tr class="crm-child-row"><td colspan="' + count + '"><div></div></td></tr>')
            .insertAfter($row);
        CRM.loadPage(this.href, {target: $('div', $newRow).animate({minHeight: '3em'}, 'fast')});
      }
      $(this).toggleClass('expanded');
      e.preventDefault();
    });
});
