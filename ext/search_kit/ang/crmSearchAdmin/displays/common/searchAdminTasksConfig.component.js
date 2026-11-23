(function(angular, $) {
  "use strict";

  angular.module('crmSearchAdmin').component('searchAdminTasksConfig', {
    bindings: {
      display: '<',
      apiEntity: '<',
      apiParams: '<'
    },
    require: {
      crmSearchAdmin: '^crmSearchAdmin'
    },
    templateUrl: '~/crmSearchAdmin/displays/common/searchAdminTasksConfig.html',
    controller: function($scope, $timeout, $q, searchMeta) {
      const ts = $scope.ts = CRM.ts('org.civicrm.search_kit'),
        ctrl = this;

      function getEnabledEntityJoins() {
        const validJoins = new Set(ctrl.actionableEntities.map((e) => e.join));
        if (Array.isArray(ctrl.display.settings.actions_entities)) {
          const valid = ctrl.display.settings.actions_entities.filter((j) => validJoins.has(j));
          if (valid.length || ctrl.display.settings.actions_entities.length === 0) {
            return valid;
          }
        }
        if (!ctrl.actionableEntities.length) {
          return [];
        }
        return [validJoins.has('') ? '' : ctrl.actionableEntities[0].join];
      }

      function loadTasks() {
        ctrl.actionableEntities = ctrl.crmSearchAdmin.getActionableEntities();
        const enabledJoins = getEnabledEntityJoins();
        const enabledEntities = ctrl.actionableEntities.filter((e) => enabledJoins.includes(e.join));
        ctrl.enabledEntityCount = enabledEntities.length;
        $q.all(enabledEntities.map((entityInfo) =>
          searchMeta.getSearchTasks(entityInfo.entity).then((tasks) => ({entityInfo, tasks}))
        )).then((results) => {
          const allTasks = [];
          const taskGroups = [];
          let downloadTask = null;
          results.forEach(({entityInfo, tasks}) => {
            const prefix = entityInfo.join ? entityInfo.join + ':' : '';
            const groupTasks = [];
            tasks.forEach((task) => {
              if (task.name === 'download') {
                if (!downloadTask) {
                  downloadTask = structuredClone(task);
                }
                return;
              }
              const namespacedTask = Object.assign({}, task, {
                name: prefix + task.name,
                unprefixedName: task.name,
                join: entityInfo.join,
                entityLabel: entityInfo.label
              });
              groupTasks.push(namespacedTask);
              allTasks.push(namespacedTask);
            });
            if (groupTasks.length) {
              taskGroups.push({
                join: entityInfo.join,
                label: entityInfo.label,
                tasks: groupTasks
              });
            }
          });
          if (downloadTask || !enabledEntities.length) {
            downloadTask = downloadTask || {name: 'download', title: ts('Download Spreadsheet')};
            allTasks.push(downloadTask);
            if (taskGroups.length === 1) {
              taskGroups[0].tasks.push(downloadTask);
              taskGroups[0].tasks.sort((a, b) => a.title.localeCompare(b.title));
            } else {
              taskGroups.push({
                join: null,
                label: '',
                tasks: [downloadTask]
              });
            }
          }
          ctrl.allTasks = allTasks;
          ctrl.taskGroups = taskGroups;
        });
      }

      this.$onInit = function() {
        loadTasks();
        $scope.$watch('$ctrl.apiParams', loadTasks, true);
        // Set default mode for editing old searches that were created before this setting was added
        if (this.display.settings.actions) {
          this.display.settings.actions_display_mode = this.display.settings.actions_display_mode || 'menu';
        }
      };

      this.toggleActions = function() {
        this.display.settings.actions = !this.display.settings.actions;
        if (!this.display.settings.actions) {
          delete this.display.settings.actions_display_mode;
          delete this.display.settings.actions_entities;
        } else {
          this.display.settings.actions_display_mode = 'menu';
          loadTasks();
        }
        ctrl.menuOpen = false;
        ctrl.entitiesMenuOpen = false;
      };

      this.isEntityEnabled = function(join) {
        return getEnabledEntityJoins().includes(join);
      };

      this.toggleEntity = function(join) {
        $timeout(function() {
          const current = getEnabledEntityJoins();
          const idx = current.indexOf(join);
          if (idx > -1) {
            current.splice(idx, 1);
            // Remove unchecked entity's tasks from actions array if present
            if (Array.isArray(ctrl.display.settings.actions)) {
              const prefix = join ? join + ':' : '';
              ctrl.display.settings.actions = ctrl.display.settings.actions.filter((name) => {
                return name === 'download' || (prefix ? !name.startsWith(prefix) : name.includes(':'));
              });
            }
          } else {
            current.push(join);
            if (Array.isArray(ctrl.display.settings.actions)) {
              const entityInfo = ctrl.actionableEntities.find((e) => e.join === join);
              if (entityInfo) {
                searchMeta.getSearchTasks(entityInfo.entity).then((tasks) => {
                  const prefix = join ? join + ':' : '';
                  tasks.forEach((task) => {
                    if (task.name !== 'download') {
                      const fullName = prefix + task.name;
                      if (!ctrl.display.settings.actions.includes(fullName)) {
                        ctrl.display.settings.actions.push(fullName);
                      }
                    }
                  });
                });
              }
            }
          }
          ctrl.display.settings.actions_entities = current;
          loadTasks();
        });
      };

      this.getEnabledEntitiesLabel = function() {
        const enabled = getEnabledEntityJoins();
        if (!enabled.length) {
          return ts('None');
        }
        return ctrl.actionableEntities
          .filter((e) => enabled.includes(e.join))
          .map((e) => e.label)
          .join(', ');
      };

      this.toggleTask = function(name) {
        // Timeout waits for checkbox state to change, otherwise checkbox 'checked' property gets out-of-sync with angular model
        $timeout(function() {
          // Disabling one when all enabled, convert to array
          if (typeof ctrl.display.settings.actions === 'boolean') {
            ctrl.display.settings.actions = ctrl.allTasks.map((task) => task.name);
          }
          // Remove enabled task
          if (ctrl.display.settings.actions.includes(name)) {
            for (let pos = ctrl.display.settings.actions.indexOf(name); pos > -1; pos = ctrl.display.settings.actions.indexOf(name)) {
              ctrl.display.settings.actions.splice(pos, 1);
            }
          }
          // Add disabled task
          else {
            ctrl.display.settings.actions.push(name);
          }
          // All enabled, convert to boolean
          if (ctrl.display.settings.actions.length === ctrl.allTasks.length) {
            ctrl.display.settings.actions = true;
          }
        });
      };

      this.isEnabled = function(task) {
        if (typeof this.display.settings.actions === 'boolean') {
          return true;
        }
        return this.display.settings.actions.includes(task.name);
      };

    }
  });

})(angular, CRM.$);
