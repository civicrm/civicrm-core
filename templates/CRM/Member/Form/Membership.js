cj(function($) {
  checkExistingMemOrg();

  $("select[name='membership_type_id[0]']").change( checkExistingMemOrg );

  function checkExistingMemOrg () {
    var selectedorg = $("select[name='membership_type_id[0]']").val();
    if (selectedorg in CRM.existingMems.memberorgs) {
      var andEndDate = '';
      var endDate = CRM.existingMems.memberorgs[selectedorg].membership_end_date;
      if (endDate) {
        andEndDate = ' ' + ts("and end date of %1", {1:endDate});
      }
      CRM.alert(ts('This contact has an existing %1 membership record with %2 status%3.<ul><li><a href="%4">Renew the existing membership instead</a></li><li><a href="%5">View all existing and / or expired memberships for this contact</a></li></ul>', {1:CRM.existingMems.memberorgs[selectedorg].membership_type, 2:CRM.existingMems.memberorgs[selectedorg].membership_status, 3:andEndDate, 4:CRM.existingMems.memberorgs[selectedorg].renewUrl, 5:CRM.existingMems.memberorgs[selectedorg].membershipTab}), ts('Duplicate Membership?'), 'alert');
    }
  }
});
