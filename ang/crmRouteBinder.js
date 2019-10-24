(function(angular, $, _) {
  angular.module('crmRouteBinder', CRM.angRequires('crmRouteBinder'));

  // While processing a change from the $watch()'d data, we set the "pendingUpdates" flag
  // so that automated URL changes don't cause a reload.
  var pendingUpdates = null, activeTimer = null, registered = false, ignorable = {};

  function registerGlobalListener($injector) {
    if (registered) return;
    registered = true;

    $injector.get('$rootScope').$on('$routeUpdate', function () {
      // Only reload if someone else -- like the user or an <a href> -- changed URL.
      if (null === pendingUpdates) {
        $injector.get('$route').reload();
      }
    });
  }

  var formats = {
    json: {
      watcher: '$watchCollection',
      decode: angular.fromJson,
      encode: angular.toJson,
      default: {}
    },
    raw: {
      watcher: '$watch',
      decode: function(v) { return v; },
      encode: function(v) { return v; },
      default: ''
    },
    int: {
      watcher: '$watch',
      decode: function(v) { return parseInt(v); },
      encode: function(v) { return v; },
      default: 0
    },
    bool: {
      watcher: '$watch',
      decode: function(v) { return v === '1'; },
      encode: function(v) { return v ? '1' : '0'; },
      default: false
    }
  };

  angular.module('crmRouteBinder').config(function ($provide) {
    $provide.decorator('$rootScope', function ($delegate, $injector, $parse) {
      Object.getPrototypeOf($delegate).$bindToRoute = function (options) {
        registerGlobalListener($injector);

        options.format = options.format || 'json';
        var fmt = _.clone(formats[options.format]);
        if (options.deep) {
          fmt.watcher = '$watch';
        }
        if (options.default === undefined) {
          options.default = fmt.default;
        }
        var value,
          _scope = this,
          $route = $injector.get('$route'),
          $timeout = $injector.get('$timeout');

        if (options.param in $route.current.params) {
          value = fmt.decode($route.current.params[options.param]);
        }
        else {
          value = _.cloneDeep(options.default);
          ignorable[options.param] = fmt.encode(options.default);
        }
        $parse(options.expr).assign(_scope, value);

        // Keep the URL bar up-to-date.
        _scope[fmt.watcher](options.expr, function (newValue) {
          var encValue = fmt.encode(newValue);
          if (!_.isEqual(newValue, options.default) && $route.current.params[options.param] === encValue) {
            return;
          }

          pendingUpdates = pendingUpdates || {};
          pendingUpdates[options.param] = encValue;
          var p = angular.extend({}, $route.current.params, pendingUpdates);

          angular.forEach(ignorable, function(v, k) {
            if (p[k] === v) {
              delete p[k];
            }
          });

          // Remove params from url if they equal their defaults
          if (_.isEqual(newValue, options.default)) {
            p[options.param] = null;
          }

          $route.updateParams(p);

          if (activeTimer) $timeout.cancel(activeTimer);
          activeTimer = $timeout(function () {
            pendingUpdates = null;
            activeTimer = null;
            ignorable = {};
          }, 50);
        }, options.deep);
      };

      return $delegate;
    });
  });

})(angular, CRM.$, CRM._);
