(function($, _) {
  if (!CRM.Designer) CRM.Designer = {};

  // TODO Optimize this class
  CRM.Designer.PaletteFieldModel = CRM.Backbone.Model.extend({
    defaults: {
      /**
       * @var {string} required; a form-specific binding to an entity instance (eg 'student', 'mother')
       */
      entityName: null,

      /**
       * @var {string}
       */
      fieldName: null
    },
    initialize: function() {
    },
    getFieldSchema: function() {
      return this.getRel('ufGroupModel').getFieldSchema(this.get('entityName'), this.get('fieldName'));
    },
    getLabel: function() {
      // Note: if fieldSchema were a bit tighter, then we need to get a label from PaletteFieldModel at all
      return this.getFieldSchema().title || this.get('fieldName');
    },
    getSectionName: function() {
      // Note: if fieldSchema were a bit tighter, then we need to get a section from PaletteFieldModel at all
      return this.getFieldSchema().section || 'default';
    },
    getSection: function() {
      return this.getRel('ufGroupModel').getModelClass(this.get('entityName')).prototype.sections[this.getSectionName()];
    },
    /**
     * Add a new UFField model to a UFFieldCollection (if doing so is legal).
     * If it fails, display an alert.
     *
     * @param {int} ufGroupId
     * @param {CRM.UF.UFFieldCollection} ufFieldCollection
     * @param {Object} addOptions
     * @return {CRM.UF.UFFieldModel} or null (if the field is not addable)
     */
    addToUFCollection: function(ufFieldCollection, addOptions) {
      var name, paletteFieldModel = this;
      var ufFieldModel = paletteFieldModel.createUFFieldModel(ufFieldCollection.getRel('ufGroupModel'));
      ufFieldModel.set('uf_group_id', ufFieldCollection.uf_group_id);
      if (!ufFieldCollection.isAddable(ufFieldModel)) {
        CRM.alert(
          ts('The field "%1" is already included.', {
            1: paletteFieldModel.getLabel()
          }),
          ts('Duplicate'),
          'alert'
        );
        return null;
      }
      ufFieldCollection.add(ufFieldModel, addOptions);
      // Load metadata and set defaults
      // TODO: currently only works for custom fields
      name = this.get('fieldName').split('_');
      if (name[0] === 'custom') {
        CRM.api('custom_field', 'getsingle', {id: name[1]}, {success: function(field) {
          ufFieldModel.set(_.pick(field, 'help_pre', 'help_post', 'is_required'));
        }});
      }
      return ufFieldModel;
    },
    createUFFieldModel: function(ufGroupModel) {
      var model = new CRM.UF.UFFieldModel({
        is_active: 1,
        label: this.getLabel(),
        entity_name: this.get('entityName'),
        field_type: this.getFieldSchema().civiFieldType,
        field_name: this.get('fieldName')
      });
      return model;
    }
  });

  /**
   *
   * options:
   *  - ufGroupModel: UFGroupModel
   */
  CRM.Designer.PaletteFieldCollection = CRM.Backbone.Collection.extend({
    model: CRM.Designer.PaletteFieldModel,
    initialize: function(models, options) {
      this.initializeCopyToChildrenRelation('ufGroupModel', options.ufGroupModel, models);
    },

    /**
     * Look up a palette-field
     *
     * @param entityName
     * @param fieldName
     * @return {CRM.Designer.PaletteFieldModel}
     */
    getFieldByName: function(entityName, fieldName) {
      if (fieldName.indexOf('formatting') === 0) {
        fieldName = 'formatting';
      }
      return this.find(function(paletteFieldModel) {
        return ((!entityName || paletteFieldModel.get('entityName') == entityName) && paletteFieldModel.get('fieldName') == fieldName);
      });
    },

    /**
     * Get a list of all fields, grouped into sections by "entityName+sectionName".
     *
     * @return {Object} keys are sections ("entityName+sectionName"); values are CRM.Designer.PaletteFieldModel
     */
    getFieldsByEntitySection: function() {
      // TODO cache
      var fieldsByEntitySection = this.groupBy(function(paletteFieldModel) {
        return paletteFieldModel.get('entityName') + '-' + paletteFieldModel.getSectionName();
      });
      return fieldsByEntitySection;
    }
  });
})(CRM.$, CRM._);
