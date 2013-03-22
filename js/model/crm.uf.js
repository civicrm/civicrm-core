(function($) {
  var CRM = (window.CRM) ? (window.CRM) : (window.CRM = {});
  if (!CRM.UF) CRM.UF = {};

  var YESNO = [
    {val: 0, label: ts('No')},
    {val: 1, label: ts('Yes')}
  ];

  var VISIBILITY = [
    {val: 'User and User Admin Only', label: ts('User and User Admin Only'), isInSelectorAllowed: false},
    {val: 'Public Pages', label: ts('Public Pages'), isInSelectorAllowed: true},
    {val: 'Public Pages and Listings', label: ts('Public Pages and Listings'), isInSelectorAllowed: true}
  ];

  var LOCATION_TYPES = _.map(CRM.PseudoConstant.locationType, function(value, key) {
    return {val: key, label: value};
  });
  LOCATION_TYPES.unshift({val: '', label: ts('Primary')});
  var DEFAULT_LOCATION_TYPE_ID = '';

  var PHONE_TYPES = _.map(CRM.PseudoConstant.phoneType, function(value, key) {
    return {val: key, label: value};
  });
  var DEFAULT_PHONE_TYPE_ID = PHONE_TYPES[0].val;

  /**
   * Add a help link to a form label
   */
  function addHelp(title, options) {
    return title + ' <a href="#" onclick=\'CRM.help("' + title + '", ' + JSON.stringify(options) + '); return false;\' title="' + ts('%1 Help', {1: title}) + '" class="helpicon"></a>';
  }

  function watchChanges() {
    CRM.designerApp.vent.trigger('ufUnsaved', true);
  }

  /**
   * Parse a "group_type" expression
   *
   * @param string groupTypeExpr example: "Individual,Activity\0ActivityType:2:28"
   *   Note: I've seen problems where HTML "&#00;" != JS '\0', so we support ';;' as an equivalent delimiter
   * @return Object example: {coreTypes: {"Individual":true,"Activity":true}, subTypes: {"ActivityType":{2: true, 28:true}]}}
   */
  CRM.UF.parseTypeList = function(groupTypeExpr) {
    var typeList = {coreTypes: {}, subTypes:{}};
    // The API may have automatically converted a string with '\0' to an array
    var parts = _.isArray(groupTypeExpr) ? groupTypeExpr : groupTypeExpr.replace(';;','\0').split('\0');
    var coreTypesExpr = parts[0];
    var subTypesExpr = parts[1];

    if (coreTypesExpr && coreTypesExpr != '') {
      _.each(coreTypesExpr.split(','), function(coreType){
        typeList.coreTypes[coreType] = true;
      });
    }

    if (subTypesExpr && subTypesExpr != '') {
      var subTypes = subTypesExpr.split(':');
      var subTypeKey = subTypes.shift();
      typeList.subTypes[subTypeKey] = {};
      _.each(subTypes, function(subTypeId){
        typeList.subTypes[subTypeKey][subTypeId] = true;
      });
    }
    return typeList;
  };

  /**
   * This function is a hack for generating simulated values of "entity_name"
   * in the form-field model.
   *
   * @param {string} field_type
   * @return {string}
   */
  CRM.UF.guessEntityName = function(field_type) {
    switch (field_type) {
      case 'Contact':
      case 'Individual':
      case 'Organization':
        return 'contact_1';
      case 'Activity':
        return 'activity_1';
      case 'Contribution':
        return 'contribution_1';
      case 'Membership':
        return 'membership_1';
      case 'Participant':
        return 'participant_1';
      default:
        throw "Cannot guess entity name for field_type=" + field_type;
    }
  }

  /**
   * Represents a field in a customizable form.
   */
  CRM.UF.UFFieldModel = CRM.Backbone.Model.extend({
    /**
     * Backbone.Form descripton of the field to which this refers
     */
    defaults: {
      help_pre: '',
      help_post: '',
      /**
       * @var bool, non-persistent indication of whether this field is unique or duplicate
       * within its UFFieldCollection
       */
      is_duplicate: false
    },
    schema: {
      'id': {
        type: 'Number'
      },
      'uf_group_id': {
        type: 'Number'
      },
      'entity_name': {
        // pseudo-field
        type: 'Text'
      },
      'field_name': {
        type: 'Text'
      },
      'field_type': {
        type: 'Select',
        options: ['Contact', 'Individual', 'Organization', 'Contribution', 'Membership', 'Participant', 'Activity']
      },
      'help_post': {
        title: addHelp(ts('Field Post Help'), {id: "help", file:"CRM/UF/Form/Field"}),
        type: 'TextArea'
      },
      'help_pre': {
        title: addHelp(ts('Field Pre Help'), {id: "help", file:"CRM/UF/Form/Field"}),
        type: 'TextArea'
      },
      'in_selector': {
        title: addHelp(ts('Results Columns?'), {id: "in_selector", file:"CRM/UF/Form/Field"}),
        type: 'Select',
        options: YESNO
      },
      'is_active': {
        title: addHelp(ts('Active?'), {id: "is_active", file:"CRM/UF/Form/Field"}),
        type: 'Select',
        options: YESNO
      },
      'is_multi_summary': {
        title: ts("Include in multi-record listing?"),
        type: 'Select',
        options: YESNO
      },
      'is_required': {
        title: addHelp(ts('Required?'), {id: "is_required", file:"CRM/UF/Form/Field"}),
        type: 'Select',
        options: YESNO
      },
      'is_reserved': {
        type: 'Select',
        options: YESNO
      },
      'is_searchable': {
        title: addHelp(ts("Searchable"), {id: "is_searchable", file:"CRM/UF/Form/Field"}),
        type: 'Select',
        options: YESNO
      },
      'is_view': {
        title: addHelp(ts('View Only?'), {id: "is_view", file:"CRM/UF/Form/Field"}),
        type: 'Select',
        options: YESNO
      },
      'label': {
        title: ts('Field Label'),
        type: 'Text'
      },
      'location_type_id': {
        title: ts('Location Type'),
        type: 'Select',
        options: LOCATION_TYPES
      },
      'phone_type_id': {
        title: ts('Phone Type'),
        type: 'Select',
        options: PHONE_TYPES
      },
      'visibility': {
        title: addHelp(ts('Visibility'), {id: "visibility", file:"CRM/UF/Form/Field"}),
        type: 'Select',
        options: VISIBILITY
      },
      'weight': {
        type: 'Number'
      }
    },
    initialize: function() {
      this.set('entity_name', CRM.UF.guessEntityName(this.get('field_type')));
      this.on("rel:ufGroupModel", this.applyDefaults, this);
      this.on('change', watchChanges);
    },
    applyDefaults: function() {
      var fieldSchema = this.getFieldSchema();
      if (fieldSchema && fieldSchema.civiIsLocation && !this.get('location_type_id')) {
        this.set('location_type_id', DEFAULT_LOCATION_TYPE_ID);
      }
      if (fieldSchema && fieldSchema.civiIsPhone && !this.get('phone_type_id')) {
        this.set('phone_type_id', DEFAULT_PHONE_TYPE_ID);
      }
    },
    isInSelectorAllowed: function() {
      var visibility = _.first(_.where(VISIBILITY, {val: this.get('visibility')}));
      return visibility.isInSelectorAllowed;
    },
    getFieldSchema: function() {
      return this.getRel('ufGroupModel').getFieldSchema(this.get('entity_name'), this.get('field_name'));
    },
    /**
     * Create a uniqueness signature. Ideally, each UFField in a UFGroup should
     * have a unique signature.
     *
     * @return {String}
     */
    getSignature: function() {
      return this.get("entity_name")
        + '::' + this.get("field_name")
        + '::' + (this.get("location_type_id") ? this.get("location_type_id") : '')
        + '::' + (this.get("phone_type_id") ? this.get("phone_type_id") : '');
    },

    /**
     * This is like destroy(), but it only destroys the item on the client-side;
     * it does not trigger REST or Backbone.sync() operations.
     *
     * @return {Boolean}
     */
    destroyLocal: function() {
      this.trigger('destroy', this, this.collection, {});
      return false;
    }
  });

  /**
   * Represents a list of fields in a customizable form
   *
   * options:
   *  - uf_group_id: int
   */
  CRM.UF.UFFieldCollection = CRM.Backbone.Collection.extend({
    model: CRM.UF.UFFieldModel,
    uf_group_id: null, // int
    initialize: function(models, options) {
      options = options || {};
      this.uf_group_id = options.uf_group_id;
      this.initializeCopyToChildrenRelation('ufGroupModel', options.ufGroupModel, models);
      this.on('add', this.watchDuplicates, this);
      this.on('remove', this.unwatchDuplicates, this);
      this.on('change', watchChanges);
      this.on('add', watchChanges);
      this.on('remove', watchChanges);
    },
    getFieldsByName: function(entityName, fieldName) {
      return this.filter(function(ufFieldModel) {
        return (ufFieldModel.get('entity_name') == entityName && ufFieldModel.get('field_name') == fieldName);
      });
    },
    toSortedJSON: function() {
      var fields = this.map(function(ufFieldModel){
        return ufFieldModel.toStrictJSON();
      });
      return _.sortBy(fields, function(ufFieldJSON){
        return parseInt(ufFieldJSON.weight);
      });
    },
    isAddable: function(ufFieldModel) {
      var entity_name = ufFieldModel.get('entity_name'),
        field_name = ufFieldModel.get('field_name'),
        fieldSchema = this.getRel('ufGroupModel').getFieldSchema(ufFieldModel.get('entity_name'), ufFieldModel.get('field_name'));

      if (! fieldSchema) {
        return false;
      }
      var fields = this.getFieldsByName(entity_name, field_name);
      var limit = 1;
      if (fieldSchema.civiIsLocation) {
        limit *= LOCATION_TYPES.length;
      }
      if (fieldSchema.civiIsPhone) {
        limit *= PHONE_TYPES.length;
      }
      return fields.length < limit;
    },
    watchDuplicates: function(model, collection, options) {
      model.on('change:location_type_id', this.markDuplicates, this);
      model.on('change:phone_type_id', this.markDuplicates, this);
      this.markDuplicates();
    },
    unwatchDuplicates: function(model, collection, options) {
      model.off('change:location_type_id', this.markDuplicates, this);
      model.off('change:phone_type_id', this.markDuplicates, this);
      this.markDuplicates();
    },
    hasDuplicates: function() {
      var firstDupe = this.find(function(ufFieldModel){
        return ufFieldModel.get('is_duplicate');
      });
      return firstDupe ? true : false;
    },
    /**
     *
     */
    markDuplicates: function() {
      var ufFieldModelsByKey = this.groupBy(function(ufFieldModel) {
        return ufFieldModel.getSignature();
      });
      this.each(function(ufFieldModel){
        var is_duplicate = ufFieldModelsByKey[ufFieldModel.getSignature()].length > 1;
        if (is_duplicate != ufFieldModel.get('is_duplicate')) {
          ufFieldModel.set('is_duplicate', is_duplicate);
        }
      });
    }
  });

  /**
   * Represents an entity in a customizable form
   */
  CRM.UF.UFEntityModel = CRM.Backbone.Model.extend({
    schema: {
      'id': {
        // title: ts(''),
        type: 'Number'
      },
      'entity_name': {
        title: ts('Entity Name'),
        help: ts('Symbolic name which referenced in the fields'),
        type: 'Text'
      },
      'entity_type': {
        title: ts('Entity Type'),
        type: 'Select',
        options: ['IndividualModel', 'ActivityModel']
      },
      'entity_sub_type': {
        // Use '*' to match all subtypes; use an int to match a specific type id; use empty-string to match none
        title: ts('Sub Type'),
        type: 'Text'
      }
    },
    defaults: {
      entity_sub_type: '*'
    },
    initialize: function() {
    },
    /**
     * Get a list of all fields that can be used with this entity.
     *
     * @return {Object} keys are field names; values are fieldSchemas
     */
    getFieldSchemas: function() {
      var ufEntityModel = this;
      var modelClass= this.getModelClass();

      if (this.get('entity_sub_type') == '*') {
        return _.clone(modelClass.prototype.schema);
      }

      var result = {};
      _.each(modelClass.prototype.schema, function(fieldSchema, fieldName){
        var section = modelClass.prototype.sections[fieldSchema.section];
        if (ufEntityModel.isSectionEnabled(section)) {
          result[fieldName] = fieldSchema;
        }
      });
      return result;
    },
    isSectionEnabled: function(section) {
      return (!section || !section.extends_entity_column_value || _.contains(section.extends_entity_column_value, this.get('entity_sub_type')));
    },
    getSections: function() {
      var ufEntityModel = this;
      var result = {};
      _.each(ufEntityModel.getModelClass().prototype.sections, function(section, sectionKey){
        if (ufEntityModel.isSectionEnabled(section)) {
          result[sectionKey] = section;
        }
      });
      return result;
    },
    getModelClass: function() {
      return CRM.Schema[this.get('entity_type')];
    }
});

  /**
   * Represents a list of entities in a customizable form
   *
   * options:
   *  - ufGroupModel: UFGroupModel
   */
  CRM.UF.UFEntityCollection = CRM.Backbone.Collection.extend({
    model: CRM.UF.UFEntityModel,
    byName: {},
    initialize: function(models, options) {
      options = options || {};
      this.initializeCopyToChildrenRelation('ufGroupModel', options.ufGroupModel, models);
    },
    /**
     *
     * @param name
     * @return {UFEntityModel} if found; otherwise, null
     */
    getByName: function(name) {
      // TODO consider indexing
      return this.find(function(ufEntityModel){
        return ufEntityModel.get('entity_name') == name;
      });
    }
  });

  /**
   * Represents a customizable form
   */
  CRM.UF.UFGroupModel = CRM.Backbone.Model.extend({
    defaults: {
      title: ts('Unnamed Profile'),
      is_active: 1
    },
    schema: {
      'id': {
        // title: ts(''),
        type: 'Number'
      },
      'name': {
        // title: ts(''),
        type: 'Text'
      },
      'title': {
        title: ts('Profile Name'),
        help: ts(''),
        type: 'Text',
        validators: ['required']
      },
      'group_type': {
        // For a description of group_type, see CRM_Core_BAO_UFGroup::updateGroupTypes
        // title: ts(''),
        type: 'Text'
      },
      'add_captcha': {
        title: ts('Include reCAPTCHA?'),
        help: ts('FIXME'),
        type: 'Select',
        options: YESNO
      },
      'add_to_group_id': {
        title: ts('Add new contacts to a Group?'),
        help: ts('Select a group if you are using this profile for adding new contacts, AND you want the new contacts to be automatically assigned to a group.'),
        type: 'Number'
      },
      'cancel_URL': {
        title: ts('Cancel Redirect URL'),
        help: ts('If you are using this profile as a contact signup or edit form, and want to redirect the user to a static URL if they click the Cancel button - enter the complete URL here. If this field is left blank, the built-in Profile form will be redisplayed.'),
        type: 'Text'
      },
      'created_date': {
        //title: ts(''),
        type: 'Text'// FIXME
      },
      'created_id': {
        //title: ts(''),
        type: 'Number'
      },
      'help_post': {
        title: ts('Post-form Help'),
        help: ts('Explanatory text displayed at the end of the form.')
          + ts('Note that this help text is displayed on profile create/edit screens only.'),
        type: 'TextArea'
      },
      'help_pre': {
        title: ts('Pre-form Help '),
        help: ts('Explanatory text displayed at the beginning of the form.')
          + ts('Note that this help text is displayed on profile create/edit screens only.'),
        type: 'TextArea'
      },
      'is_active': {
        title: ts('Is this CiviCRM Profile active?'),
        type: 'Select',
        options: YESNO
      },
      'is_cms_user': {
        title: ts('Drupal user account registration option?'),// FIXME
        help: ts('FIXME'),
        type: 'Select',
        options: YESNO // FIXME
      },
      'is_edit_link': {
        title: ts('Include profile edit links in search results?'),
        help: ts('Check this box if you want to include a link in the listings to Edit profile fields. Only users with permission to edit the contact will see this link.'),
        type: 'Select',
        options: YESNO
      },
      'is_map': {
        title: ts('Enable mapping for this profile?'),
        help: ts('If enabled, a Map link is included on the profile listings rows and detail screens for any contacts whose records include sufficient location data for your mapping provider.'),
        type: 'Select',
        options: YESNO
      },
      'is_proximity_search': {
        title: ts('Proximity search'),
        help: ts('FIXME'),
        type: 'Select',
        options: YESNO // FIXME
      },
      'is_reserved': {
        // title: ts(''),
        type: 'Select',
        options: YESNO
      },
      'is_uf_link': {
        title: ts('Include Drupal user account information links in search results?'), // FIXME
        help: ts('FIXME'),
        type: 'Select',
        options: YESNO
      },
      'is_update_dupe': {
        title: ts('What to do upon duplicate match'),
        help: ts('FIXME'),
        type: 'Select',
        options: YESNO // FIXME
      },
      'limit_listings_group_id': {
        title: ts('Limit listings to a specific Group?'),
        help: ts('Select a group if you are using this profile for search and listings, AND you want to limit the listings to members of a specific group.'),
        type: 'Number'
      },
      'notify': {
        title: ts('Notify when profile form is submitted?'),
        help: ts('If you want member(s) of your organization to receive a notification email whenever this Profile form is used to enter or update contact information, enter one or more email addresses here. Multiple email addresses should be separated by a comma (e.g. jane@example.org, paula@example.org). The first email address listed will be used as the FROM address in the notifications.'),
        type: 'TextArea'
      },
      'post_URL': {
        title: ts('Redirect URL'),
        help: ts("If you are using this profile as a contact signup or edit form, and want to redirect the user to a static URL after they've submitted the form, you can also use contact tokens in URL - enter the complete URL here. If this field is left blank, the built-in Profile form will be redisplayed with a generic status message - 'Your contact information has been saved.'"),
        type: 'Text'
      },
      'weight': {
        title: ts('Order'),
        help: ts('Weight controls the order in which profiles are presented when more than one profile is included in User Registration or My Account screens. Enter a positive or negative integer - lower numbers are displayed ahead of higher numbers.'),
        type: 'Number'
        // FIXME positive int
      }
    },
    initialize: function() {
      var ufGroupModel = this;

      if (!this.getRel('ufEntityCollection')) {
        var ufEntityCollection = new CRM.UF.UFEntityCollection([], {
          ufGroupModel: this,
          silent: false
        });
        this.setRel('ufEntityCollection', ufEntityCollection);
      }

      if (!this.getRel('ufFieldCollection')) {
        var ufFieldCollection = new CRM.UF.UFFieldCollection([], {
          uf_group_id: this.id,
          ufGroupModel: this
        });
        this.setRel('ufFieldCollection', ufFieldCollection);
      }

      if (!this.getRel('paletteFieldCollection')) {
        var paletteFieldCollection = new CRM.Designer.PaletteFieldCollection([], {
          ufGroupModel: this
        });
        paletteFieldCollection.sync = function(method, model, options) {
          options || (options = {});
          // console.log(method, model, options);
          switch (method) {
            case 'read':
              var success = options.success;
              options.success = function(resp, status, xhr) {
                if (success) success(resp, status, xhr);
                model.trigger('sync', model, resp, options);
              };
              success(ufGroupModel.buildPaletteFields());

              break;
            case 'create':
            case 'update':
            case 'delete':
            default:
              throw 'Unsupported method: ' + method;
          }
        };
        this.setRel('paletteFieldCollection', paletteFieldCollection);
      }

      this.getRel('ufEntityCollection').on('reset', this.resetEntities, this);
      this.resetEntities();

      this.on('change', watchChanges);
    },
    /**
     * Generate a copy of this UFGroupModel and its fields, with all ID's removed. The result
     * is suitable for a new, identical UFGroup.
     *
     * @return {CRM.UF.UFGroupModel}
     */
    deepCopy: function() {
      var copy = new CRM.UF.UFGroupModel(_.omit(this.toStrictJSON(), ['id','created_id','created_date','is_reserved','group_type']));
      copy.getRel('ufEntityCollection').reset(
        this.getRel('ufEntityCollection').toJSON()
        // FIXME: for configurable entities, omit ['id', 'uf_group_id']
      );
      copy.getRel('ufFieldCollection').reset(
        this.getRel('ufFieldCollection').map(function(ufFieldModel) {
          return _.omit(ufFieldModel.toStrictJSON(), ['id', 'uf_group_id']);
        })
      );
      copy.set('title', ts('%1 (Copy)', {
        1: copy.get('title')
      }));
      return copy;
    },
    getModelClass: function(entity_name) {
      var ufEntity = this.getRel('ufEntityCollection').getByName(entity_name);
      if (!ufEntity) throw 'Failed to locate entity: ' + entity_name;
      return ufEntity.getModelClass();
    },
    getFieldSchema: function(entity_name, field_name) {
      var modelClass = this.getModelClass(entity_name);
      var fieldSchema = modelClass.prototype.schema[field_name];
      if (!fieldSchema) {
        if (console.log) {
          console.log('Failed to locate field: ' + entity_name + "." + field_name);
        }
        return null;
      }
      return fieldSchema;
    },
    /**
     * Check that the group_type contains *only* the types listed in validTypes
     *
     * @param string validTypesExpr
     * @return {Boolean}
     */
    checkGroupType: function(validTypesExpr) {
      var allMatched = true;
      if (! this.get('group_type') || this.get('group_type') == '') {
        return true;
      }

      var actualTypes = CRM.UF.parseTypeList(this.get('group_type'));
      var validTypes = CRM.UF.parseTypeList(validTypesExpr);

      // Every actual.coreType is a valid.coreType
      _.each(actualTypes.coreTypes, function(ignore, actualCoreType) {
        if (! validTypes.coreTypes[actualCoreType]) {
          allMatched = false;
        }
      });

      // Every actual.subType is a valid.subType
      _.each(actualTypes.subTypes, function(actualSubTypeIds, actualSubTypeKey) {
        if (!validTypes.subTypes[actualSubTypeKey]) {
          allMatched = false;
          return;
        }
        // actualSubTypeIds is a list of all subtypes which can be used by group,
        // so it's sufficient to match any one of them
        var subTypeMatched = false;
        _.each(actualSubTypeIds, function(ignore, actualSubTypeId) {
          if (validTypes.subTypes[actualSubTypeKey][actualSubTypeId]) {
            subTypeMatched = true;
          }
        });
        allMatched = allMatched && subTypeMatched;
      });
      return allMatched;
    },
    resetEntities: function() {
      var ufGroupModel = this;
      ufGroupModel.getRel('ufFieldCollection').each(function(ufFieldModel){
        if (!ufFieldModel.getFieldSchema()) {
          CRM.alert(ts('The data model no longer includes field "%1"! All references to the field have been removed.', {
            1: ufFieldModel.get('entity_name') + "." + ufFieldModel.get('field_name')
          }), '', 'alert', {expires: false});
          ufFieldModel.destroyLocal();
        }
      });
      this.getRel('paletteFieldCollection').reset(this.buildPaletteFields());
    },
    /**
     *
     * @return {Array} of PaletteFieldModel
     */
    buildPaletteFields: function() {
      // rebuild list of fields; reuse old instances of PaletteFieldModel and create new ones
      // as appropriate
      // Note: The system as a whole is ill-defined in cases where we have an existing
      // UFField that references a model field that disappears.

      var ufGroupModel = this;

      var oldPaletteFieldModelsBySig = {};
      this.getRel('paletteFieldCollection').each(function(paletteFieldModel){
        oldPaletteFieldModelsBySig[paletteFieldModel.get("entityName") + '::' + paletteFieldModel.get("fieldName")] = paletteFieldModel;
      });

      var newPaletteFieldModels = [];
      this.getRel('ufEntityCollection').each(function(ufEntityModel){
        var modelClass = ufEntityModel.getModelClass();
        _.each(ufEntityModel.getFieldSchemas(), function(value, key, list) {
          var model = oldPaletteFieldModelsBySig[ufEntityModel.get('entity_name') + '::' + key];
          if (!model) {
            model = new CRM.Designer.PaletteFieldModel({
              modelClass: modelClass,
              entityName: ufEntityModel.get('entity_name'),
              fieldName: key
            });
          }
          newPaletteFieldModels.push(model);
        });
      });

      return newPaletteFieldModels;
    }
  });

  /**
   * Represents a list of customizable form
   */
  CRM.UF.UFGroupCollection = CRM.Backbone.Collection.extend({
    model: CRM.UF.UFGroupModel
  });
})(cj);
