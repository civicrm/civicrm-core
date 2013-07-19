(function($) {
  var CRM = (window.CRM) ? (window.CRM) : (window.CRM = {});
  if (!CRM.Backbone) CRM.Backbone = {};

  /**
   * Backbone.sync provider which uses CRM.api() for I/O.
   * To support CRUD operations, model classes must be defined with a "crmEntityName" property.
   * To load collections using API queries, set the "crmCriteria" property or override the
   * method "toCrmCriteria".
   *
   * @param method
   * @param model
   * @param options
   */
  CRM.Backbone.sync = function(method, model, options) {
    var isCollection = _.isArray(model.models);

    if (isCollection) {
      var apiOptions = {
        success: function(data) {
          // unwrap data
          options.success(_.toArray(data.values));
        },
        error: function(data) {
          // CRM.api displays errors by default, but Backbone.sync
          // protocol requires us to override "error". This restores
          // the default behavior.
          $().crmError(data.error_message, ts('Error'));
          options.error(data);
        }
      };
      switch (method) {
        case 'read':
          CRM.api(model.crmEntityName, 'get', model.toCrmCriteria(), apiOptions);
          break;
        default:
          apiOptions.error({is_error: 1, error_message: "CRM.Backbone.sync(" + method + ") not implemented for collections"});
          break;
      }
    } else {
      // callback options to pass to CRM.api
      var apiOptions = {
        success: function(data) {
          // unwrap data
          var values = _.toArray(data['values']);
          if (values.length == 1) {
            options.success(values[0]);
          } else {
            data.is_error = 1;
            data.error_message = ts("Expected exactly one response");
            apiOptions.error(data);
          }
        },
        error: function(data) {
          // CRM.api displays errors by default, but Backbone.sync
          // protocol requires us to override "error". This restores
          // the default behavior.
          $().crmError(data.error_message, ts('Error'));
          options.error(data);
        }
      };
      switch (method) {
        case 'create': // pass-through
        case 'update':
          CRM.api(model.crmEntityName, 'create', model.toJSON(), apiOptions);
          break;
        case 'read':
          var params = model.toCrmCriteria();
          if (!params.id) {
            apiOptions.error({is_error: 1, error_message: 'Missing ID for ' + model.crmEntityName});
            return;
          }
          CRM.api(model.crmEntityName, 'get', params, apiOptions);
          break;
        case 'delete':
        default:
          apiOptions.error({is_error: 1, error_message: "CRM.Backbone.sync(" + method + ") not implemented for models"});
      }
    }
  };

  /**
   * Connect a "model" class to CiviCRM's APIv3
   *
   * @code
   * // Setup class
   * var ContactModel = Backbone.Model.extend({});
   * CRM.Backbone.extendModel(ContactModel, "Contact");
   *
   * // Use class
   * c = new ContactModel({id: 3});
   * c.fetch();
   * @endcode
   *
   * @param Class ModelClass
   * @param string crmEntityName APIv3 entity name, such as "Contact" or "CustomField"
   */
  CRM.Backbone.extendModel = function(ModelClass, crmEntityName) {
    // Defaults - if specified in ModelClass, preserve
    _.defaults(ModelClass.prototype, {
      crmEntityName: crmEntityName,
      toCrmCriteria: function() {
        return (this.get('id')) ? {id: this.get('id')} : {};
      }
    });
    // Overrides - if specified in ModelClass, replace
    _.extend(ModelClass.prototype, {
      sync: CRM.Backbone.sync
    });
  };

  /**
   * Connect a "collection" class to CiviCRM's APIv3
   *
   * Note: the collection supports a special property, crmCriteria, which is an array of
   * query options to send to the API
   *
   * @code
   * // Setup class
   * var ContactModel = Backbone.Model.extend({});
   * CRM.Backbone.extendModel(ContactModel, "Contact");
   * var ContactCollection = Backbone.Collection.extend({
   *   model: ContactModel
   * });
   * CRM.Backbone.extendCollection(ContactCollection);
   *
   * // Use class
   * var c = new ContactCollection([], {
   *   crmCriteria: {contact_type: 'Organization'}
   * });
   * c.fetch();
   * @endcode
   *
   * @param Class CollectionClass
   */
  CRM.Backbone.extendCollection = function(CollectionClass) {
    var origInit = CollectionClass.prototype.initialize;
    // Defaults - if specified in CollectionClass, preserve
    _.defaults(CollectionClass.prototype, {
      crmEntityName: CollectionClass.prototype.model.prototype.crmEntityName,
      toCrmCriteria: function() {
        return this.crmCriteria || {};
      }
    });
    // Overrides - if specified in CollectionClass, replace
    _.extend(CollectionClass.prototype, {
      sync: CRM.Backbone.sync,
      initialize: function(models, options) {
        options || (options = {});
        if (options.crmCriteria) {
          this.crmCriteria = options.crmCriteria;
        }
        if (origInit) {
          return origInit.apply(this, arguments);
        }
      }
    });
  };

  CRM.Backbone.Model = Backbone.Model.extend({
    /**
     * Return JSON version of model -- but only include fields that are
     * listed in the 'schema'.
     *
     * @return {*}
     */
    toStrictJSON: function() {
      var schema = this.schema;
      var result = this.toJSON();
      _.each(result, function(value, key){
        if (! schema[key]) {
          delete result[key];
        }
      });
      return result;
    },
    setRel: function(key, value, options) {
      this.rels = this.rels || {};
      if (this.rels[key] != value) {
        this.rels[key] = value;
        this.trigger("rel:"+key, value);
      }
    },
    getRel: function(key) {
      return this.rels ? this.rels[key] : null;
    }
  });

  CRM.Backbone.Collection = Backbone.Collection.extend({
    /**
     * Store 'key' on this.rel and automatically copy it to
     * any children.
     *
     * @param key
     * @param value
     * @param initialModels
     */
    initializeCopyToChildrenRelation: function(key, value, initialModels) {
      this.setRel(key, value, {silent: true});
      this.on('reset', this._copyToChildren, this);
      this.on('add', this._copyToChild, this);
    },
    _copyToChildren: function() {
      var collection = this;
      collection.each(function(model){
        collection._copyToChild(model);
      });
    },
    _copyToChild: function(model) {
      _.each(this.rels, function(relValue, relKey){
        model.setRel(relKey, relValue, {silent: true});
      });
    },
    setRel: function(key, value, options) {
      this.rels = this.rels || {};
      if (this.rels[key] != value) {
        this.rels[key] = value;
        this.trigger("rel:"+key, value);
      }
    },
    getRel: function(key) {
      return this.rels ? this.rels[key] : null;
    }
  });

  /*
  CRM.Backbone.Form = Backbone.Form.extend({
    validate: function() {
      // Add support for form-level validators
      var errors = Backbone.Form.prototype.validate.apply(this, []) || {};
      var self = this;
      if (this.validators) {
        _.each(this.validators, function(validator) {
          var modelErrors = validator(this.getValue());

          // The following if() has been copied-pasted from the parent's
          // handling of model-validators. They are similar in that the errors are
          // probably keyed by field names... but not necessarily, so we use _others
          // as a fallback.
          if (modelErrors) {
            var isDictionary = _.isObject(modelErrors) && !_.isArray(modelErrors);

            //If errors are not in object form then just store on the error object
            if (!isDictionary) {
              errors._others = errors._others || [];
              errors._others.push(modelErrors);
            }

            //Merge programmatic errors (requires model.validate() to return an object e.g. { fieldKey: 'error' })
            if (isDictionary) {
              _.each(modelErrors, function(val, key) {
                //Set error on field if there isn't one already
                if (self.fields[key] && !errors[key]) {
                  self.fields[key].setError(val);
                  errors[key] = val;
                }

                else {
                  //Otherwise add to '_others' key
                  errors._others = errors._others || [];
                  var tmpErr = {};
                  tmpErr[key] = val;
                  errors._others.push(tmpErr);
                }
              });
            }
          }

        });
      }
      return _.isEmpty(errors) ? null : errors;
    }
  });
  */
})(cj);
