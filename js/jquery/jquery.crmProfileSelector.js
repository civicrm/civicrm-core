(function($, _) {
  var ufGroupCollection = new CRM.UF.UFGroupCollection(_.sortBy(CRM.initialProfileList.values, 'title'));
  //var ufGroupCollection = new CRM.UF.UFGroupCollection(CRM.initialProfileList.values, {
  //  comparator: 'title' // no point, this doesn't work with subcollections
  //});
  ufGroupCollection.unshift(new CRM.UF.UFGroupModel({
    id: '',
    title: ts('- select -')
  }));

  /**
   * Example:
   * <input type="text" value="{$profileId}" class="crm-profile-selector" />
   * ...
   * cj('.crm-profile-selector').crmProfileSelector({
   *   groupTypeFilter: "Contact,Individual,Activity;;ActivityType:7",
   *   entities: "contact_1:IndividualModel,activity_1:ActivityModel"
   * });
   *
   * Note: The system does not currently support dynamic entities -- it only supports
   * a couple of entities named "contact_1" and "activity_1". See also
   * CRM.UF.guessEntityName().
   */
  $.fn.crmProfileSelector = function(options) {
    return this.each(function() {
      // Hide the existing <SELECT> and instead construct a ProfileSelector view.
      // Keep them synchronized.
      var matchingUfGroups,
        $select = $(this).hide().addClass('rendered');

      var validTypesId = [];
      var usedByFilter = null;
      if (options.groupTypeFilter) {
        matchingUfGroups = ufGroupCollection.subcollection({
          filter: function(ufGroupModel) {
            //CRM-16915 - filter with module used by the profile
            if (!$.isEmptyObject(options.usedByFilter)) {
              usedByFilter = options.usedByFilter;
            }
            return ufGroupModel.checkGroupType(options.groupTypeFilter, options.allowAllSubtypes, usedByFilter);
          }
        });
      } else {
        matchingUfGroups = ufGroupCollection;
      }

      //CRM-15427 check for valid subtypes raise a warning if not valid
      if (options.allowAllSubtypes && $.isEmptyObject(validTypesId)) {
        validTypes = ufGroupCollection.subcollection({
          filter: function(ufGroupModel) {
            return ufGroupModel.checkGroupType(options.groupTypeFilter);
          }
        });
        _.each(validTypes.models, function(validTypesattr) {
          validTypesId.push(validTypesattr.id);
        });
      }
      if (!$.isEmptyObject(validTypesId) && $.inArray($select.val(), validTypesId) == -1) {
        var civiComponent;
        if (options.groupTypeFilter.indexOf('Membership') !== -1) {
          civiComponent = 'Membership';
        }
        else if (options.groupTypeFilter.indexOf('Participant') !== -1) {
          civiComponent = 'Event';
        }
        else {
          civiComponent = 'Contribution';
        }
        CRM.alert(ts('The selected profile is using a custom field which is not assigned to the "%1" being configured.', {1: civiComponent}), ts('Warning'));
      }
      var view = new CRM.ProfileSelector.View({
        ufGroupId: $select.val(),
        ufGroupCollection: matchingUfGroups,
        ufEntities: options.entities
      });
      view.on('change:ufGroupId', function() {
        $select.val(view.getUfGroupId()).change();
      });
      view.render();
      $select.after(view.el);
      setTimeout(function() {
        view.doPreview();
      }, 100);
    });
  };

  $('#crm-container').on('crmLoad', function() {
    $('.crm-profile-selector:not(.rendered)', this).each(function() {
      $(this).crmProfileSelector({
        groupTypeFilter: $(this).data('groupType'),
        entities: $(this).data('entities'),
        //CRM-15427
        allowAllSubtypes: $(this).data('default'),
        usedByFilter: $(this).data('usedfor')
      });
    });
  });

})(CRM.$, CRM._);
