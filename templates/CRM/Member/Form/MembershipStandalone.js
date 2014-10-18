CRM.$(function($) {
  var alert, memberResults = [];

  function fetchMemberships() {
    var cid = $("#contact_id").val();
    if (cid) {
      CRM.api3('Membership', 'get', {'sequential': 1, 'contact_id': cid})
        .done(function (data) {
          memberResults = data['values'] || [];
          checkExistingMemOrg();
        });
    } else {
      memberResults = [];
    }
  }

  fetchMemberships();

  $("#contact_id").change(fetchMemberships);
  $("select[name='membership_type_id[0]']").change(checkExistingMemOrg);

  function checkExistingMemOrg () {
    alert && alert.close && alert.close();
    var selectedorg = $("select[name='membership_type_id[0]']").val();
    if (memberResults.length && selectedorg) {
      $.each(memberResults, function() {
        if (this['membership_type_id'] in CRM.existingMems.typeorgs) {
          if (CRM.existingMems.typeorgs[this['membership_type_id']] == selectedorg) {
            if(this['status_id'] in CRM.existingMems.statuses) {
              var membership_status = CRM.existingMems.statuses[this['status_id']];
              var andEndDate = '';
              if (this.end_date) {
                andEndDate = ' ' + ts("and end date of %1", {1:this.end_date});
              }
              
              var renewUrl = CRM.url('civicrm/contact/view/membership',
                "reset=1&action=renew&cid="+this.contact_id+"&id="+this['id']+"&context=membership&selectedChild=member"
              );

              var membershipTab = CRM.url('civicrm/contact/view',
                "reset=1&force=1&cid="+this.contact_id+"&selectedChild=member"
              );

              var org = $('option:selected', "select[name='membership_type_id[0]']").text();
              
              alert = CRM.alert(
                ts('This contact has an existing %1 membership at %2 with %3 status%4.', {1: CRM.existingMems.memtypes[this.membership_type_id], 2: org, 3: membership_status, 4: andEndDate})
                  + '<ul><li><a href="' + renewUrl + '">'
                  + ts('Renew the existing membership instead')
                  + '</a></li><li><a href="' + membershipTab + '">'
                  + ts('View all existing and / or expired memberships for this contact')
                  + '</a></li></ul>',
                ts('Duplicate Membership?'), 'alert');
              return false;
            }
          }
        }
      });
    }
  }
});
