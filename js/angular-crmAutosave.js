/// crmAutosave
(function(angular, $, _) {

  angular.module('crmAutosave', ['crmUtil']);

  // usage: <form crm-autosave="myCtrl.save()" crm-autosave-model="myModel">...</form>
  //
  // Automatically save changes. Don't save while the user is actively updating the model -- save
  // after a pause in user activity (e.g. after 2sec).
  //
  //  - crm-autosave="callback" -- A function to handle saving. Should return a promise.
  //                               If it's not a promise, then we'll assume that it completes successfully.
  //  - crm-autosave-interval="object" -- Interval spec. Default: {poll: 250, save: 5000}
  //  - crm-autosave-if="conditional" -- Only allow autosave when conditional returns true. Default: !form.$invalid
  //  - crm-autosave-model="object" -- (Re)schedule saves based on observed changes to object.
  //    We perform deep ispection on the model object. This could be a performance issue you had many concurrent
  //    autosave forms, but it should be fine with one.
  //
  // The call to the autosave function will cause the form to be marked as pristine (unless there's an error).
  angular.module('crmAutosave').directive('crmAutosave', function($interval, $timeout) {
    return {
      restrict: 'AE',
      require: '^form',
      link: function(scope, element, attrs, form) {
        var intervals = angular.extend({poll: 250, save: 5000}, scope.$eval(attrs.crmAutosaveInterval));
        var jobs = {poll: null, save: null}; // job handles used ot cancel/reschedule timeouts/intervals
        var lastSeenModel = null;
        var saving = false;

        // Determine if model has changed; (re)schedule the save.
        // This is a bit expensive and doesn't need to be continuous, so we use polling instead of watches.
        function checkChanges() {
          if (saving) {
            return;
          }
          var currentModel = scope.$eval(attrs.crmAutosaveModel);
          if (lastSeenModel === null) {
            lastSeenModel = angular.copy(currentModel);
          }
          else if (!angular.equals(currentModel, lastSeenModel)) {
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

          if (attrs.crmAutosaveIf) {
            if (!scope.$eval(attrs.crmAutosaveIf)) {
              return;
            }
          }
          else if (form.$invalid) {
            return;
          }

          saving = true;
          lastSeenModel = angular.copy(scope.$eval(attrs.crmAutosaveModel));

          // Set to pristine before saving -- not after saving.
          // If an eager user continues editing concurrent with the
          // save process, then the form should become dirty again.
          form.$setPristine();
          var res = scope.$eval(attrs.crmAutosave);
          if (res && res.then) {
            res.then(
              function() {
                saving = false;
              },
              function() {
                saving = false;
                form.$setDirty();
              }
            );
          }
          else {
            saving = false;
          }
        }

        jobs.poll = $interval(checkChanges, intervals.poll);
        element.on('$destroy', function() {
          if (jobs.poll) {
            $interval.cancel(jobs.poll);
            jobs.poll = null;
          }
          if (jobs.save) {
            $timeout.cancel(jobs.save);
            jobs.save = null;
          }
        });

      }
    };
  });

})(angular, CRM.$, CRM._);
