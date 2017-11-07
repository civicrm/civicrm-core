/// crmAutosave
(function(angular, $, _) {

  angular.module('crmAutosave', CRM.angRequires('crmAutosave'));

  // usage:
  //   var autosave = new CrmAutosaveCtrl({
  //     save: function           -- A function to handle saving. Should return a promise.
  //                                 If it's not a promise, then we'll assume that it completes successfully.
  //     saveIf: function         -- Only allow autosave when conditional returns true. Default: !form.$invalid
  //     model: object|function   -- (Re)schedule saves based on observed changes to object. We perform deep
  //                                 inspection on the model object. This could be a performance issue you
  //                                 had many concurrent autosave forms or a particularly large model, but
  //                                 it should be fine with typical usage.
  //     interval: object         -- Interval spec. Default: {poll: 250, save: 5000}
  //     form: object|function    -- FormController or its getter
  //   });
  //   autosave.start();
  //   $scope.$on('$destroy', autosave.stop);
  // Note: if the save operation itself
  angular.module('crmAutosave').service('CrmAutosaveCtrl', function($interval, $timeout, $q) {
    function CrmAutosaveCtrl(options) {
      var intervals = angular.extend({poll: 250, save: 5000}, options.interval);
      var jobs = {poll: null, save: null}; // job handles used ot cancel/reschedule timeouts/intervals
      var lastSeenModel = null;
      var saving = false;

      // Determine if model has changed; (re)schedule the save.
      // This is a bit expensive and doesn't need to be continuous, so we use polling instead of watches.
      function checkChanges() {
        if (saving) {
          return;
        }
        var currentModel = _.isFunction(options.model) ? options.model() : options.model;
        if (!angular.equals(currentModel, lastSeenModel)) {
          lastSeenModel = angular.copy(currentModel);
          if (jobs.save) {
            $timeout.cancel(jobs.save);
          }
          jobs.save = $timeout(doAutosave, intervals.save);
        }
      }

      function doAutosave() {
        jobs.save = null;
        if (saving) {
          return;
        }

        var form = _.isFunction(options.form) ? options.form() : options.form;

        if (options.saveIf) {
          if (!options.saveIf()) {
            return;
          }
        }
        else if (form && form.$invalid) {
          return;
        }

        saving = true;
        lastSeenModel = angular.copy(_.isFunction(options.model) ? options.model() : options.model);

        // Set to pristine before saving -- not after saving.
        // If an eager user continues editing concurrent with the
        // save process, then the form should become dirty again.
        if (form) {
          form.$setPristine();
        }
        var res = options.save();
        if (res && res.then) {
          res.then(
            function() {
              saving = false;
            },
            function() {
              saving = false;
              if (form) {
                form.$setDirty();
              }
            }
          );
        }
        else {
          saving = false;
        }
      }

      var self = this;

      this.start = function() {
        if (!jobs.poll) {
          lastSeenModel = angular.copy(_.isFunction(options.model) ? options.model() : options.model);
          jobs.poll = $interval(checkChanges, intervals.poll);
        }
      };

      this.stop = function() {
        if (jobs.poll) {
          $interval.cancel(jobs.poll);
          jobs.poll = null;
        }
        if (jobs.save) {
          $timeout.cancel(jobs.save);
          jobs.save = null;
        }
      };

      this.suspend = function(p) {
        self.stop();
        return p.finally(self.start);
      };
    }

    return CrmAutosaveCtrl;
  });

})(angular, CRM.$, CRM._);
