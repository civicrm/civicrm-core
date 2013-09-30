(function($, CRM) {

  /**
   * Setup a contact widget for use in CRUD forms. The form element stores a contact ID
   * but displays a name/label.
   *
   * Usage:
   *   <INPUT type="text" name="my_contact_id" value="123" />
   *   <SCRIPT type="text/javascript">
   *     $('[name=my_contact_id]').crmContactField();
   *     $('[name=my_contact_id]').on('change', function(){
   *       console.log("the new contact id is ", $(this).val());
   *     });
   *   </SCRIPT>
   *
   * Note: The given form element is the canonical representation of the selected contact-ID.
   * To display/enter the contact by name, the contact-ID field will be hidden, and a helper
   * widget will be inserted. The values will be synced between the two.
   *
   * Cases to consider/test:
   *  - When initializing, the read the contact-ID and set the contact-label
   *  - When unsetting(blanking) the contact-label, also unset (blank) the contact-ID
   *  - If third party code updates the hidden value, then one must trigger a 'change'
   *    event to update the visible widget.
   */
  $.fn.crmContactField = function() {
    return this.each(function(){
      var urlParams = 'className=CRM_Contact_Page_AJAX&fnName=getContactList&json=1';
      var hiddenEl = this;
      var widgetEl = $('<input type="text" />');

      var activeContactId = null;
      // For organization autocomplete
      if($(this).attr('urlParam')) {
        var param = $(this).attr('urlParam');
        var urlParams = urlParams + '&' + param;
      }
      var contactUrl = CRM.url('civicrm/ajax/rest', urlParams);
      var setContactId = function(newContactId) {
        if (newContactId != $(hiddenEl).val()) {
          $(hiddenEl).val(newContactId);
          $(hiddenEl).trigger('change');
        }
        if (activeContactId != newContactId) {
          activeContactId = newContactId;

          if (activeContactId) {
            // lookup the name
            $(widgetEl).css({visibility: 'hidden'}); // don't allow input during ajax
            $.ajax({
              url     : contactUrl + '&id=' + newContactId,
              async   : false,
              success : function(html){
                var htmlText = html.split( '|' , 2);
                $(widgetEl).val(htmlText[0]);
                $(widgetEl).css({visibility: 'visible'});
              }
            });
          } else {
            // there is no name to lookup - just show a blank
            $(widgetEl).val('');
          }
        }
      };

      $(hiddenEl).after(widgetEl);
      $(hiddenEl).hide();
      $(widgetEl).autocomplete(contactUrl, {
        width: 200,
        selectFirst: false,
        minChars: 1,
        matchContains: true,
        delay: 400
      }).result(function(event, data) {
          activeContactId = data[1];
          setContactId(activeContactId);
        }).bind('change blur', function() {
          if (! $(widgetEl).val()) {
            activeContactId = '';
            setContactId(activeContactId);
          }
        });

      $(hiddenEl).bind('change', function(){
        setContactId($(this).val());
      });
      setContactId($(hiddenEl).val());
    });
  };

})(jQuery, CRM);
