(function($) {
  var CRM = (window.CRM) ? (window.CRM) : (window.CRM = {});
  if (!CRM.Backbone) CRM.Backbone = {};

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
