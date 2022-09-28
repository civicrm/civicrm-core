(function(angular, $, _) {
  "use strict";

  angular.module('afAdmin').controller('afAdminList', function($scope, afforms, crmApi4, crmStatus, afGui) {
    var ts = $scope.ts = CRM.ts('org.civicrm.afform_admin'),
      ctrl = $scope.$ctrl = this;
    this.sortField = 'title';
    this.sortDir = false;

    $scope.crmUrl = CRM.url;

    $scope.searchCreateLinks = {};

    this.tabs = CRM.afAdmin.afform_type;
    $scope.types = _.indexBy(ctrl.tabs, 'name');
    _.each(['form', 'block', 'search'], function(type) {
      if ($scope.types[type]) {
        if (type === 'form') {
          $scope.types.form.default = '#create/form/Individual';
        }
      }
    });
    $scope.types.system.options = false;

    this.afforms = _.transform(afforms, function(afforms, afform) {
      afform.type = afform.type || 'system';
      // Aggregate a couple fields for the "Placement" column
      afform.placement = [];
      if (afform.is_dashlet) {
        afform.placement.push(ts('Dashboard'));
      }
      if (afform['contact_summary:label']) {
        afform.placement.push(afform['contact_summary:label']);
      }
      afforms[afform.type] = afforms[afform.type] || [];
      afforms[afform.type].push(afform);
    }, {});

    // Load aggregated submission stats for each form
    crmApi4('AfformSubmission', 'get', {
      select: ['afform_name', 'MAX(submission_date) AS last_submission', 'COUNT(id) AS submission_count'],
      groupBy: ['afform_name']
    }).then(function(submissions) {
      _.each(submissions, function(submission) {
        var afform = _.findWhere(afforms, {name: submission.afform_name}) || {};
        afform.last_submission = CRM.utils.formatDate(submission.last_submission);
        afform.submission_count = submission.submission_count;
      });
    });

    // Change sort field/direction when clicking a column header
    this.sortBy = function(col) {
      ctrl.sortDir = ctrl.sortField === col ? !ctrl.sortDir : false;
      ctrl.sortField = col;
    };

    $scope.$bindToRoute({
      expr: '$ctrl.tab',
      param: 'tab',
      format: 'raw'
    });

    if (!ctrl.tab) {
      ctrl.tab = ctrl.tabs[0].name;
    }

    this.createLinks = function() {
      // Reset search input in dropdown
      $scope.searchCreateLinks.label = '';
      // A value means it's alredy loaded. Null means it's loading.
      if ($scope.types[ctrl.tab].options || $scope.types[ctrl.tab].options === null) {
        return;
      }
      $scope.types[ctrl.tab].options = null;
      var links = [];

      if (ctrl.tab === 'form') {
        _.each(CRM.afGuiEditor.entities, function(entity, name) {
          if (entity.type === 'primary') {
            links.push({
              url: '#create/form/' + name,
              label: entity.label,
              icon: entity.icon
            });
          }
        });
        $scope.types.form.options = _.sortBy(links, 'Label');
      }

      if (ctrl.tab === 'block') {
        _.each(CRM.afGuiEditor.entities, function(entity, name) {
          if (true) { // FIXME: What conditions do we use for block entities?
            links.push({
              url: '#create/block/' + name,
              label: entity.label,
              icon: entity.icon || 'fa-cog'
            });
          }
        });
        $scope.types.block.options = _.sortBy(links, function(item) {
          return item.url === '#create/block/*' ? '0' : item.label;
        });
        // Add divider after the * entity (content block)
        $scope.types.block.options.splice(1, 0, {'class': 'divider', label: ''});
      }

      if (ctrl.tab === 'search') {
        afGui.getAllSearchDisplays().then(function(links) {
          $scope.types.search.options = links;
        });
      }
    };

    this.revert = function(afform) {
      var index = _.findIndex(ctrl.afforms[ctrl.tab], {name: afform.name});
      if (index > -1) {
        var apiOps = [['Afform', 'revert', {where: [['name', '=', afform.name]]}]];
        if (afform.has_base) {
          apiOps.push(['Afform', 'get', {
            where: [['name', '=', afform.name]],
            select: ['name', 'title', 'type', 'is_public', 'server_route', 'has_local', 'has_base', 'base_module', 'base_module:label']
          }, 0]);
        }
        var apiCall = crmStatus(
          afform.has_base ? {start: ts('Reverting...')} : {start: ts('Deleting...'), success: ts('Deleted')},
          crmApi4(apiOps)
        );
        if (afform.has_base) {
          afform.has_local = false;
          apiCall.then(function(result) {
            ctrl.afforms[ctrl.tab][index] = result[1];
          });
        } else {
          ctrl.afforms[ctrl.tab].splice(index, 1);
        }
      }
    };
  });

})(angular, CRM.$, CRM._);
