(function($) {
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
      var select = this;

      var matchingUfGroups;
      if (options.groupTypeFilter) {
        matchingUfGroups = ufGroupCollection.subcollection({
          filter: function(ufGroupModel) {
            return ufGroupModel.checkGroupType(options.groupTypeFilter);
          }
        });
      } else {
        matchingUfGroups = ufGroupCollection;
      }

      var view = new CRM.ProfileSelector.View({
        ufGroupId: $(select).val(),
        ufGroupCollection: matchingUfGroups,
        ufEntities: options.entities
      });
      view.on('change:ufGroupId', function() {
        $(select).val(view.getUfGroupId()).change();
      })
      view.render();
      $(select).after(view.el);
      setTimeout(function() {
        view.doPreview();
      }, 100);
    });
  };

  // FIXME: this needs a better place to live
  CRM.scanProfileSelectors = function() {
    $('.crm-profile-selector').each(function(){
      $(this).crmProfileSelector({
        groupTypeFilter: $(this).attr('data-group-type'),
        entities: eval('(' + $(this).attr('data-entities') + ')')
      });
    });
  };

})(cj);
