CRM.$(function($) {
  $(document)
	.on('dialogopen', function(e) {
      // J3 - Make footer admin bar hide behind popup windows (CRM-15723)
      $('#status').css('z-index', '100');
    })
});
