/*! backbone.collectionsubset - v0.1.2 - 2012-12-20
* https://github.com/anthonyshort/backbone.collectionsubset
* Copyright (c) 2012 Anthony Short; Licensed MIT */
// Patched for civicrm by colemanw - added global import of _ and Backbone variables
(function(_, Backbone) {

  Backbone.CollectionSubset = (function() {

    CollectionSubset.extend = Backbone.Model.extend;

    _.extend(CollectionSubset.prototype, Backbone.Events);

    function CollectionSubset(options) {
      if (options == null) {
        options = {};
      }
      options = _.defaults(options, {
        refresh: true,
        triggers: null,
        filter: function() {
          return true;
        },
        name: null,
        child: null,
        parent: null
      });
      this.triggers = options.triggers ? options.triggers.split(' ') : [];
      if (!options.child) {
        options.child = new options.parent.constructor;
      }
      this.setParent(options.parent);
      this.setChild(options.child);
      this.setFilter(options.filter);
      if (options.model) {
        this.child.model = options.model;
      }
      if (options.refresh) {
        this.refresh();
      }
      this.name = options.name;
    }

    CollectionSubset.prototype.setParent = function(collection) {
      var _ref,
        _this = this;
      if ((_ref = this.parent) != null) {
        _ref.off(null, null, this);
      }
      this.parent = collection;
      this.parent.on('add', this._onParentAdd, this);
      this.parent.on('remove', this._onParentRemove, this);
      this.parent.on('reset', this._onParentReset, this);
      this.parent.on('change', this._onParentChange, this);
      this.parent.on('dispose', this.dispose, this);
      this.parent.on('loading', (function() {
        return _this.child.trigger('loading');
      }), this);
      return this.parent.on('ready', (function() {
        return _this.child.trigger('ready');
      }), this);
    };

    CollectionSubset.prototype.setChild = function(collection) {
      var _ref;
      if ((_ref = this.child) != null) {
        _ref.off(null, null, this);
      }
      this.child = collection;
      this.child.on('add', this._onChildAdd, this);
      this.child.on('reset', this._onChildReset, this);
      this.child.on('dispose', this.dispose, this);
      this.child.superset = this.parent;
      this.child.filterer = this;
      this.child.url = this.parent.url;
      return this.child.model = this.parent.model;
    };

    CollectionSubset.prototype.setFilter = function(fn) {
      var filter;
      filter = function(model) {
        var matchesFilter, matchesParentFilter;
        matchesFilter = fn.call(this, model);
        matchesParentFilter = this.parent.filterer ? this.parent.filterer.filter(model) : true;
        return matchesFilter && matchesParentFilter;
      };
      return this.filter = _.bind(filter, this);
    };

    CollectionSubset.prototype.refresh = function(options) {
      var models;
      if (options == null) {
        options = {};
      }
      models = this.parent.filter(this.filter);
      this.child.reset(models, {
        subset: this
      });
      return this.child.trigger('refresh');
    };

    CollectionSubset.prototype._replaceChildModel = function(parentModel) {
      var childModel, index;
      childModel = this._getByCid(this.child, parentModel.cid);
      if (childModel === parentModel) {
        return;
      }
      if (_.isUndefined(childModel)) {
        return this.child.add(parentModel, {
          subset: this
        });
      } else {
        index = this.child.indexOf(childModel);
        this.child.remove(childModel);
        return this.child.add(parentModel, {
          at: index,
          subset: this
        });
      }
    };

    CollectionSubset.prototype._onParentAdd = function(model, collection, options) {
      if (options && options.subset === this) {
        return;
      }
      if (this.filter(model)) {
        return this._replaceChildModel(model);
      }
    };

    CollectionSubset.prototype._onParentRemove = function(model, collection, options) {
      return this.child.remove(model, options);
    };

    CollectionSubset.prototype._onParentReset = function(collection, options) {
      return this.refresh();
    };

    CollectionSubset.prototype._onParentChange = function(model, changes) {
      if (!this.triggerMatched(model)) {
        return;
      }
      if (this.filter(model)) {
        return this.child.add(model);
      } else {
        return this.child.remove(model);
      }
    };

    CollectionSubset.prototype._onChildAdd = function(model, collection, options) {
      var parentModel;
      if (options && options.subset === this) {
        return;
      }
      this.parent.add(model);
      parentModel = this._getByCid(this.parent, model.cid);
      if (!parentModel) {
        return;
      }
      if (this.filter(parentModel)) {
        return this._replaceChildModel(parentModel);
      } else {
        return this.child.remove(model);
      }
    };

    CollectionSubset.prototype._onChildReset = function(collection, options) {
      if (options && options.subset === this) {
        return;
      }
      this.parent.add(this.child.models);
      return this.refresh();
    };

    CollectionSubset.prototype._getByCid = function(model, cid) {
      var fn;
      fn = model.getByCid || model.get;
      return fn.apply(model, [cid]);
    };

    CollectionSubset.prototype.triggerMatched = function(model) {
      var changedAttrs;
      if (this.triggers.length === 0) {
        return true;
      }
      if (!model.hasChanged()) {
        return false;
      }
      changedAttrs = _.keys(model.changedAttributes());
      return _.intersection(this.triggers, changedAttrs).length > 0;
    };

    CollectionSubset.prototype.dispose = function() {
      var prop, _base, _i, _len, _ref;
      if (this.disposed) {
        return;
      }
      this.trigger('dispose', this);
      this.parent.off(null, null, this);
      this.child.off(null, null, this);
      if (typeof (_base = this.child).dispose === "function") {
        _base.dispose();
      }
      this.off();
      _ref = ['parent', 'child', 'options'];
      for (_i = 0, _len = _ref.length; _i < _len; _i++) {
        prop = _ref[_i];
        delete this[prop];
      }
      return this.disposed = true;
    };

    return CollectionSubset;

  })();

  Backbone.Collection.prototype.subcollection = function(options) {
    var subset;
    if (options == null) {
      options = {};
    }
    _.defaults(options, {
      child: new this.constructor,
      parent: this
    });
    subset = new Backbone.CollectionSubset(options);
    return subset.child;
  };

  if (typeof module !== "undefined" && module !== null) {
    module.exports = Backbone.CollectionSubset;
  }

}(_, Backbone));
