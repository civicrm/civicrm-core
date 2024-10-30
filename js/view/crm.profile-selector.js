(function($, _, Backbone) {
  if (!CRM.ProfileSelector) CRM.ProfileSelector = {};

  CRM.ProfileSelector.Option = Backbone.Marionette.ItemView.extend({
    template: '#profile_selector_option_template',
    tagName: 'option',
    modelEvents: {
      'change:title': 'render'
    },
    onRender: function() {
      this.$el.attr('value', this.model.get('id'));
    }
  });

  CRM.ProfileSelector.Select = Backbone.Marionette.CollectionView.extend({
    tagName: 'select',
    itemView: CRM.ProfileSelector.Option
  });

  /**
   * Render a pane with 'Select/Preview/Edit/Copy/Create' functionality for profiles.
   *
   * Note: This view works with a ufGroupCollection, and it creates popups for a
   * ufGroupModel. These are related but not facilely. The ufGroupModels in the
   * ufGroupCollection are never passed to the popup, and the models from the
   * popup are never added to the collection. This is because the popup works
   * with temporary, local copies -- but the collection reflects the actual list
   * on the server.
   *
   * options:
   *  - ufGroupId: int, the default selection
   *  - ufGroupCollection: the profiles which can be selected
   *  - ufEntities: hard-coded entity list used with any new/existing forms
   *    (this may be removed when the form-runtime is updated to support hand-picking
   *    entities for each form)
   */
  CRM.ProfileSelector.View = Backbone.Marionette.Layout.extend({
    template: '#profile_selector_template',
    regions: {
      selectRegion: '.crm-profile-selector-select'
    },
    events: {
      'change .crm-profile-selector-select select': 'onChangeUfGroupId',
      'click .crm-profile-selector-edit': 'doEdit',
      'click .crm-profile-selector-copy': 'doCopy',
      'click .crm-profile-selector-create': 'doCreate',
      'click .crm-profile-selector-preview': 'doShowPreview',
      // prevent interaction with preview form
      'click .crm-profile-selector-preview-pane': false,
      'crmLoad .crm-profile-selector-preview-pane': 'disableForm'
    },
    /** @var Marionette.View which specifically builds on jQuery-UI's dialog */
    activeDialog: null,
    onRender: function() {
      var view = new CRM.ProfileSelector.Select({
        collection: this.options.ufGroupCollection
      });
      this.selectRegion.show(view);
      this.setUfGroupId(this.options.ufGroupId, {silent: true});
      this.toggleButtons();
      this.$('.crm-profile-selector-select select').css('width', '25em').crmSelect2();
      this.doShowPreview();
    },
    onChangeUfGroupId: function(event) {
      this.options.ufGroupId = $(event.target).val();
      this.trigger('change:ufGroupId', this);
      this.toggleButtons();
      this.doPreview();
    },
    toggleButtons: function() {
      this.$('.crm-profile-selector-edit,.crm-profile-selector-copy').prop('disabled', !this.hasUfGroupId());
    },
    hasUfGroupId: function() {
      return (this.getUfGroupId() && this.getUfGroupId() !== '') ? true : false;
    },
    setUfGroupId: function(value, options) {
      this.options.ufGroupId = value;
      this.$('.crm-profile-selector-select select').val(value);
      this.$('.crm-profile-selector-select select').select2('val', value, (!options || !options.silent));
    },
    getUfGroupId: function() {
      return this.options.ufGroupId;
    },
    doPreview: function() {
      var $pane = this.$('.crm-profile-selector-preview-pane');
      if (!this.hasUfGroupId()) {
        $pane.html($('#profile_selector_empty_preview_template').html());
      } else {
        CRM.loadPage(CRM.url("civicrm/ajax/inline", {class_name: 'CRM_UF_Form_Inline_PreviewById', id: this.getUfGroupId()}), {target: $pane});
      }
    },
    doShowPreview: function() {
      var $preview = this.$('.crm-profile-selector-preview');
      var $pane = this.$('.crm-profile-selector-preview-pane');
      if ($preview.hasClass('crm-profile-selector-preview-show')) {
        $preview.removeClass('crm-profile-selector-preview-show');
        $preview.find('.crm-i').removeClass('fa-television').addClass('fa-times');
        $pane.show();
      } else {
        $preview.addClass('crm-profile-selector-preview-show');
        $preview.find('.crm-i').removeClass('fa-times').addClass('fa-television');
        $pane.hide();
      }
    },
    disableForm: function() {
      this.$(':input', '.crm-profile-selector-preview-pane').not('.select2-input').prop('readOnly', true);
    },
    doEdit: function(e) {
      e.preventDefault();
      var profileSelectorView = this;
      var designerDialog = new CRM.Designer.DesignerDialog({
        findCreateUfGroupModel: function(options) {
          var ufID = profileSelectorView.getUfGroupId();
          // Retrieve UF group and fields from the api
          CRM.api('UFGroup', 'getsingle', {id: ufID, "api.UFField.get": 1}, {
            success: function(formData) {
              // Note: With chaining, API returns some extraneous keys that aren't part of UFGroupModel
              var ufGroupModel = new CRM.UF.UFGroupModel(_.pick(formData, _.keys(CRM.UF.UFGroupModel.prototype.schema)));
              ufGroupModel.setUFGroupModel(ufGroupModel.calculateContactEntityType(), profileSelectorView.options.ufEntities);
              ufGroupModel.getRel('ufFieldCollection').reset(_.values(formData["api.UFField.get"].values));
              options.onLoad(ufGroupModel);
            }
          });
        }
      });
      CRM.designerApp.vent.on('ufSaved', this.onSave, this);
      this.setDialog(designerDialog);
    },
    doCopy: function(e) {
      e.preventDefault();
      // This is largely the same as doEdit, but we ultimately pass in a deepCopy of the ufGroupModel.
      var profileSelectorView = this;
      var designerDialog = new CRM.Designer.DesignerDialog({
        findCreateUfGroupModel: function(options) {
          var ufID = profileSelectorView.getUfGroupId();
          // Retrieve UF group and fields from the api
          CRM.api('UFGroup', 'getsingle', {id: ufID, "api.UFField.get": 1}, {
            success: function(formData) {
              // Note: With chaining, API returns some extraneous keys that aren't part of UFGroupModel
              var ufGroupModel = new CRM.UF.UFGroupModel(_.pick(formData, _.keys(CRM.UF.UFGroupModel.prototype.schema)));
              ufGroupModel.setUFGroupModel(ufGroupModel.calculateContactEntityType(), profileSelectorView.options.ufEntities);
              ufGroupModel.getRel('ufFieldCollection').reset(_.values(formData["api.UFField.get"].values));
              options.onLoad(ufGroupModel.deepCopy());
            }
          });
        }
      });
      CRM.designerApp.vent.on('ufSaved', this.onSave, this);
      this.setDialog(designerDialog);
    },
    doCreate: function(e) {
      e.preventDefault();
      var profileSelectorView = this;
      var designerDialog = new CRM.Designer.DesignerDialog({
        findCreateUfGroupModel: function(options) {
          // Initialize new UF group
          var ufGroupModel = new CRM.UF.UFGroupModel();
          ufGroupModel.getRel('ufEntityCollection').reset(profileSelectorView.options.ufEntities);
          options.onLoad(ufGroupModel);
        }
      });
      CRM.designerApp.vent.on('ufSaved', this.onSave, this);
      this.setDialog(designerDialog);
    },
    onSave: function() {
      CRM.designerApp.vent.off('ufSaved', this.onSave, this);
      var ufGroupId = this.activeDialog.model.get('id');
      var modelFromCollection = this.options.ufGroupCollection.get(ufGroupId);
      if (modelFromCollection) {
        // copy in changes to UFGroup
        modelFromCollection.set(this.activeDialog.model.toStrictJSON());
      } else {
        // add in new UFGroup
        modelFromCollection = new CRM.UF.UFGroupModel(this.activeDialog.model.toStrictJSON());
        this.options.ufGroupCollection.add(modelFromCollection);
      }
      this.setUfGroupId(ufGroupId);
      this.doPreview();
    },
    setDialog: function(view) {
      if (this.activeDialog) {
        this.activeDialog.close();
      }
      this.activeDialog = view;
      view.render();
    }
  });
})(CRM.$, CRM._, CRM.BB);
