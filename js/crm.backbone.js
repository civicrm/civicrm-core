(function($, _) {
  if (!CRM.Backbone) CRM.Backbone = {};

  /**
   * Backbone.sync provider which uses CRM.api() for I/O.
   * To support CRUD operations, model classes must be defined with a "crmEntityName" property.
   * To load collections using API queries, set the "crmCriteria" property or override the
   * method "toCrmCriteria".
   *
   * @param method Accepts normal Backbone.sync methods; also accepts "crm-replace"
   * @param model
   * @param options
   * @see tests/qunit/crm-backbone
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
          CRM.api(model.crmEntityName, model.toCrmAction('get'), model.toCrmCriteria(), apiOptions);
          break;
        // replace all entities matching "x.crmCriteria" with new entities in "x.models"
        case 'crm-replace':
          var params = this.toCrmCriteria();
          params.version = 3;
          params.values = this.toJSON();
          CRM.api(model.crmEntityName, model.toCrmAction('replace'), params, apiOptions);
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
          if (data.count == 1) {
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
          var params = model.toJSON();
          params.options || (params.options = {});
          params.options.reload = 1;
          if (!model._isDuplicate) {
            CRM.api(model.crmEntityName, model.toCrmAction('create'), params, apiOptions);
          } else {
            CRM.api(model.crmEntityName, model.toCrmAction('duplicate'), params, apiOptions);
          }
          break;
        case 'read':
        case 'delete':
          var apiAction = (method == 'delete') ? 'delete' : 'get';
          var params = model.toCrmCriteria();
          if (!params.id) {
            apiOptions.error({is_error: 1, error_message: 'Missing ID for ' + model.crmEntityName});
            return;
          }
          CRM.api(model.crmEntityName, model.toCrmAction(apiAction), params, apiOptions);
          break;
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
   * @see tests/qunit/crm-backbone
   */
  CRM.Backbone.extendModel = function(ModelClass, crmEntityName) {
    // Defaults - if specified in ModelClass, preserve
    _.defaults(ModelClass.prototype, {
      crmEntityName: crmEntityName,
      crmActions: {}, // map: string backboneActionName => string serverSideActionName
      crmReturn: null, // array: list of fields to return
      toCrmAction: function(action) {
        return this.crmActions[action] ? this.crmActions[action] : action;
      },
      toCrmCriteria: function() {
        var result = (this.get('id')) ? {id: this.get('id')} : {};
        if (this.crmReturn != null) {
          result.return = this.crmReturn;
        }
        return result;
      },
      duplicate: function() {
        var newModel = new ModelClass(this.toJSON());
        newModel._isDuplicate = true;
        if (newModel.setModified) newModel.setModified();
        newModel.listenTo(newModel, 'sync', function(){
          // may get called on subsequent resaves -- don't care!
          delete newModel._isDuplicate;
        });
        return newModel;
      }
    });
    // Overrides - if specified in ModelClass, replace
    _.extend(ModelClass.prototype, {
      sync: CRM.Backbone.sync
    });
  };

  /**
   * Configure a model class to track whether a model has unsaved changes.
   *
   * Methods:
   *  - setModified() - flag the model as modified/dirty
   *  - isSaved() - return true if there have been no changes to the data since the last fetch or save
   * Events:
   *  - saved(object model, bool is_saved) - triggered whenever isSaved() value would change
   *
   *  Note: You should not directly call isSaved() within the context of the success/error/sync callback;
   *  I haven't found a way to make isSaved() behave correctly within these callbacks without patching
   *  Backbone. Instead, attach an event listener to the 'saved' event.
   *
   * @param ModelClass
   */
  CRM.Backbone.trackSaved = function(ModelClass) {
    // Retain references to some of the original class's functions
    var Parent = _.pick(ModelClass.prototype, 'initialize', 'save', 'fetch');

    // Private callback
    var onSyncSuccess = function() {
      this._modified = false;
      if (this._oldModified.length > 0) {
        this._oldModified.pop();
      }
      this.trigger('saved', this, this.isSaved());
    };
    var onSaveError = function() {
      if (this._oldModified.length > 0) {
        this._modified = this._oldModified.pop();
        this.trigger('saved', this, this.isSaved());
      }
    };

    // Defaults - if specified in ModelClass, preserve
    _.defaults(ModelClass.prototype, {
      isSaved: function() {
        var result = !this.isNew() && !this.isModified();
        return result;
      },
      isModified: function() {
        return this._modified;
      },
      _saved_onchange: function(model, options) {
        if (options.parse) return;
        // console.log('change', model.changedAttributes(), model.previousAttributes());
        this.setModified();
      },
      setModified: function() {
        var oldModified = this._modified;
        this._modified = true;
        if (!oldModified) {
          this.trigger('saved', this, this.isSaved());
        }
      }
    });

    // Overrides - if specified in ModelClass, replace
    _.extend(ModelClass.prototype, {
      initialize: function(options) {
        this._modified = false;
        this._oldModified = [];
        this.listenTo(this, 'change', this._saved_onchange);
        this.listenTo(this, 'error', onSaveError);
        this.listenTo(this, 'sync', onSyncSuccess);
        if (Parent.initialize) {
          return Parent.initialize.apply(this, arguments);
        }
      },
      save: function() {
        // we'll assume success
        this._oldModified.push(this._modified);
        return Parent.save.apply(this, arguments);
      },
      fetch: function() {
        this._oldModified.push(this._modified);
        return Parent.fetch.apply(this, arguments);
      }
    });
  };

  /**
   * Configure a model class to support client-side soft deletion.
   * One can call "model.setDeleted(BOOLEAN)" to flag an entity for
   * deletion (or not) -- however, deletion will be deferred until save()
   * is called.
   *
   * Methods:
   *   setSoftDeleted(boolean) - flag the model as deleted (or not-deleted)
   *   isSoftDeleted() - determine whether model has been soft-deleted
   * Events:
   *   softDelete(model, is_deleted) -- change value of is_deleted
   *
   * @param ModelClass
   */
  CRM.Backbone.trackSoftDelete = function(ModelClass) {
    // Retain references to some of the original class's functions
    var Parent = _.pick(ModelClass.prototype, 'save');

    // Defaults - if specified in ModelClass, preserve
    _.defaults(ModelClass.prototype, {
      is_soft_deleted: false,
      setSoftDeleted: function(is_deleted) {
        if (this.is_soft_deleted != is_deleted) {
          this.is_soft_deleted = is_deleted;
          this.trigger('softDelete', this, is_deleted);
          if (this.setModified) this.setModified(); // FIXME: ugly interaction, trackSoftDelete-trackSaved
        }
      },
      isSoftDeleted: function() {
        return this.is_soft_deleted;
      }
    });

    // Overrides - if specified in ModelClass, replace
    _.extend(ModelClass.prototype, {
      save: function(attributes, options) {
        if (this.isSoftDeleted()) {
          return this.destroy(options);
        } else {
          return Parent.save.apply(this, arguments);
        }
      }
    });
  };

    /**
   * Connect a "collection" class to CiviCRM's APIv3
   *
   * Note: the collection supports a special property, crmCriteria, which is an array of
   * query options to send to the API.
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
   * // Use class (with passive criteria)
   * var c = new ContactCollection([], {
   *   crmCriteria: {contact_type: 'Organization'}
   * });
   * c.fetch();
   * c.get(123).set('property', 'value');
   * c.get(456).setDeleted(true);
   * c.save();
   *
   * // Use class (with active criteria)
   * var criteriaModel = new SomeModel({
   *     contact_type: 'Organization'
   * });
   * var c = new ContactCollection([], {
   *   crmCriteriaModel: criteriaModel
   * });
   * c.fetch();
   * c.get(123).set('property', 'value');
   * c.get(456).setDeleted(true);
   * c.save();
   * @endcode
   *
   *
   * @param Class CollectionClass
   * @see tests/qunit/crm-backbone
   */
  CRM.Backbone.extendCollection = function(CollectionClass) {
    var origInit = CollectionClass.prototype.initialize;
    // Defaults - if specified in CollectionClass, preserve
    _.defaults(CollectionClass.prototype, {
      crmEntityName: CollectionClass.prototype.model.prototype.crmEntityName,
      crmActions: {}, // map: string backboneActionName => string serverSideActionName
      toCrmAction: function(action) {
        return this.crmActions[action] ? this.crmActions[action] : action;
      },
      toCrmCriteria: function() {
        var result = (this.crmCriteria) ? _.extend({}, this.crmCriteria) : {};
        if (this.crmReturn != null) {
          result.return = this.crmReturn;
        } else if (this.model && this.model.prototype.crmReturn != null) {
          result.return = this.model.prototype.crmReturn;
        }
        return result;
      },

      /**
       * Get an object which represents this collection's criteria
       * as a live model. Any changes to the model will be applied
       * to the collection, and the collection will be refreshed.
       *
       * @param criteriaModelClass
       */
      setCriteriaModel: function(criteriaModel) {
        var collection = this;
        this.crmCriteria = criteriaModel.toJSON();
        this.listenTo(criteriaModel, 'change', function() {
          collection.crmCriteria = criteriaModel.toJSON();
          collection.debouncedFetch();
        });
      },

      debouncedFetch: _.debounce(function() {
        this.fetch({reset: true});
      }, 100),

      /**
       * Reconcile the server's collection with the client's collection.
       * New/modified items from the client will be saved/updated on the
       * server. Deleted items from the client will be deleted on the
       * server.
       *
       * @param Object options - accepts "success" and "error" callbacks
       */
      save: function(options) {
        options || (options = {});
        var collection = this;
        var success = options.success;
        options.success = function(resp) {
          // Ensure attributes are restored during synchronous saves.
          collection.reset(resp, options);
          if (success) success(collection, resp, options);
          // collection.trigger('sync', collection, resp, options);
        };
        wrapError(collection, options);

        return this.sync('crm-replace', this, options)
      }
    });
    // Overrides - if specified in CollectionClass, replace
    _.extend(CollectionClass.prototype, {
      sync: CRM.Backbone.sync,
      initialize: function(models, options) {
        options || (options = {});
        if (options.crmCriteriaModel) {
          this.setCriteriaModel(options.crmCriteriaModel);
        } else if (options.crmCriteria) {
          this.crmCriteria = options.crmCriteria;
        }
        if (options.crmActions) {
          this.crmActions = _.extend(this.crmActions, options.crmActions);
        }
        if (origInit) {
          return origInit.apply(this, arguments);
        }
      },
      toJSON: function() {
        var result = [];
        // filter models list, excluding any soft-deleted items
        this.each(function(model) {
          // if model doesn't track soft-deletes
          // or if model tracks soft-deletes and wasn't soft-deleted
          if (!model.isSoftDeleted || !model.isSoftDeleted()) {
            result.push(model.toJSON());
          }
        });
        return result;
      }
    });
  };

  /**
   * Find a single record, or create a new record.
   *
   * @param Object options:
   *   - CollectionClass: class
   *   - crmCriteria: Object values to search/default on
   *   - defaults: Object values to put on newly created model (if needed)
   *   - success: function(model)
   *   - error: function(collection, error)
   */
   CRM.Backbone.findCreate = function(options) {
     options || (options = {});
     var collection = new options.CollectionClass([], {
       crmCriteria: options.crmCriteria
     });
     collection.fetch({
      success: function(collection) {
        if (collection.length == 0) {
          var attrs = _.extend({}, collection.crmCriteria, options.defaults || {});
          var model = collection._prepareModel(attrs, options);
          options.success(model);
        } else if (collection.length == 1) {
          options.success(collection.first());
        } else {
          options.error(collection, {
            is_error: 1,
            error_message: 'Too many matches'
          });
        }
      },
      error: function(collection, errorData) {
        if (options.error) {
          options.error(collection, errorData);
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
      _.each(result, function(value, key) {
        if (!schema[key]) {
          delete result[key];
        }
      });
      return result;
    },
    setRel: function(key, value, options) {
      this.rels = this.rels || {};
      if (this.rels[key] != value) {
        this.rels[key] = value;
        this.trigger("rel:" + key, value);
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
      collection.each(function(model) {
        collection._copyToChild(model);
      });
    },
    _copyToChild: function(model) {
      _.each(this.rels, function(relValue, relKey) {
        model.setRel(relKey, relValue, {silent: true});
      });
    },
    setRel: function(key, value, options) {
      this.rels = this.rels || {};
      if (this.rels[key] != value) {
        this.rels[key] = value;
        this.trigger("rel:" + key, value);
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

  // Wrap an optional error callback with a fallback error event.
  var wrapError = function (model, options) {
    var error = options.error;
    options.error = function(resp) {
      if (error) error(model, resp, optio)
      model.trigger('error', model, resp, options);
    };
  };
})(CRM.$, CRM._);
