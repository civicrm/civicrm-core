// https://civicrm.org/licensing
CRM.$(function($) {
  /**
   * Set the href for the "Fields" button associated with a select field
   * or hide the button if there was no value selected.
   */
  function setRelatedConfigLink($select, path, params) {
    var val = $select.val();
    if (val) {
      params[params.key] = val;
      // Most URLs expect reset=1
      params.reset = 1;
      params.action = 'browse';
      var url = CRM.url(path, params);
      $select.siblings('.crm-button').attr('href', url).show();
    }
    else {
      $select.siblings('.crm-button').attr('href', '#').hide();
    }
  }

  $('body')
    // Let administrators to view the related configuration
    // for a Profile or a PriceSet
    .on('change', 'select.crm-form-select-profile', function() {
      setRelatedConfigLink($(this), 'civicrm/admin/uf/group/field', {key: 'gid'});
    })
    .on('change', 'select.crm-form-select-priceset', function() {
      setRelatedConfigLink($(this), 'civicrm/admin/price/field', {key: 'sid'});
    });

  // Initial state (and additional profiles, ex: for Events)
  $('body').on('crmLoad', function() {
    $('select.crm-form-select-profile', this).each(function() {
      setRelatedConfigLink($(this), 'civicrm/admin/uf/group/field', {key: 'gid'});
    });
    $('select.crm-form-select-priceset', this).each(function() {
      setRelatedConfigLink($(this), 'civicrm/admin/price/field', {key: 'sid'});
    });
  });
});
