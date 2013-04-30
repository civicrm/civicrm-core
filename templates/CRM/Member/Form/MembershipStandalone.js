cj(function($) {
  memberResults = new Array;
  $("input[name='contact[1]']").result( function() {
    var contact_id = cj("input[name='contact_select_id[1]']").val();
    CRM.api('Membership', 'get', {'sequential': 1, 'contact_id': contact_id},
      {success: function(data) {
        if (data['values']) {
          memberResults = data['values'];
          checkExistingMemOrg();
        }
      }});
    });
      
  checkExistingMemOrg();

  $("select[name='membership_type_id[0]']").change( checkExistingMemOrg );

  function checkExistingMemOrg () {
    if (memberResults) {
      var selectedorg = $("select[name='membership_type_id[0]']").val();
      $.each(memberResults, function() {
        if (this['membership_type_id'] in CRM.existingMems.typeorgs) {
          if (CRM.existingMems.typeorgs[this['membership_type_id']] == selectedorg) {
            if(this['status_id'] in CRM.existingMems.statuses) {
              var membership_status = CRM.existingMems.statuses[this['status_id']];
              var andEndDate = '';
              var endDate = this.membership_end_date;
              if (endDate) {
                andEndDate = ' ' + ts("and end date of %1", {1:endDate});
              }
              
              var renewUrl = CRM.url('civicrm/contact/view/membership',
                "reset=1&action=renew&cid="+this.contact_id+"&id="+this['id']+"&context=membership&selectedChild=member"
              );

              var membershipTab = CRM.url('civicrm/contact/view',
                "reset=1&force=1&cid="+this.contact_id+"&selectedChild=member"
              );
              
              CRM.alert(ts('This contact has an existing %1 membership record with %2 status%3.<ul><li><a href="%4">Renew the existing membership instead</a></li><li><a href="%5">View all existing and / or expired memberships for this contact</a></li></ul>', {1:CRM.existingMems.memtypes[this.membership_type_id], 2:membership_status, 3:andEndDate, 4:renewUrl, 5:membershipTab}), ts('Duplicate Membership?'), 'alert');
            }
          }
        }
      });
    }
  }
});
