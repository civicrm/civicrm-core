(function(angular, $, _) {
  "use strict";
  // Example usage: <af-form ctrl="afform">
  angular.module('af').component('afForm', {
    bindings: {
      ctrl: '@'
    },
    require: {
      ngForm: 'form'
    },
    controller: function($scope, $element, $timeout, crmApi4, crmStatus, $window, $location, $parse, FileUploader) {
      const
        ctrl = this,
        ts = CRM.ts('org.civicrm.afform'),
        saveDraftButtons = [],
        schema = {},
        data = {extra: {}};

      let
        args,
        submissionResponse,
        // Default autosave function does nothing
        autoSave = () => {},
        draftStatus = 'pristine',
        cancelDraftWatcher,
        uploadingDraftFiles = false;

      this.$onInit = () => {
        // This component has no template. It makes its controller available within it by adding it to the parent scope.
        $scope.$parent[this.ctrl] = this;

        $timeout(function() {
          ctrl.loadData()
            .then(setupDraftWatcher);

          ctrl.showSubmitButton = displaySubmitButton(args);
        });
      };

      this.registerEntity = function registerEntity(entity) {
        schema[entity.modelName] = entity;
        data[entity.modelName] = [];
      };
      this.getEntity = function getEntity(name) {
        return schema[name];
      };
      // Returns field values for a given entity
      this.getData = function getData(name) {
        return data[name];
      };
      this.getSchema = function getSchema(name) {
        return schema[name];
      };
      // Returns the 'meta' record ('name', 'description', etc) of the active form.
      // @see afform_civicrm_buildAsset() for whitelist of form metadata
      this.getFormMeta = function getFormMeta() {
        return $scope.$parent.meta;
      };
      this.resetForm = function() {
        this.ngForm.$setPristine();
        $scope.$parent.$broadcast('afFormReset');
        this.loadData();
      };
      // With no arguments this will prefill the entire form based on url args
      // and also check if the form is open for submissions.
      // With selectedEntity, selectedIndex & selectedId provided this will prefill a single entity
      this.loadData = function(selectedEntity, selectedIndex, selectedId, selectedField, joinEntity, joinIndex) {
        let toLoad = true;
        const params = {name: ctrl.getFormMeta().name, args: {}};
        // Load single entity
        if (selectedEntity) {
          toLoad = !!selectedId;
          params.args[selectedEntity] = {};
          params.args[selectedEntity][selectedIndex] = {};
          if (joinEntity) {
            params.fillMode = 'join';
            params.args[selectedEntity][selectedIndex].joins = {};
            params.args[selectedEntity][selectedIndex].joins[joinEntity] = {};
            params.args[selectedEntity][selectedIndex].joins[joinEntity][joinIndex] = {};
            params.args[selectedEntity][selectedIndex].joins[joinEntity][joinIndex][selectedField] = selectedId;
          } else {
            params.fillMode = 'entity';
            params.args[selectedEntity][selectedIndex][selectedField] = selectedId;
          }
        }
        // Prefill entire form
        else {
          params.fillMode = 'form';
          args = Object.assign({}, $scope.$parent.routeParams || {}, $scope.$parent.options || {});
          Object.keys(schema).forEach((entityName) => {
            if (args[entityName] && typeof args[entityName] === 'string') {
              args[entityName] = args[entityName].split(',');
            }
          });
          params.args = args;
          ctrl.showSubmitButton = displaySubmitButton(args);
        }
        if (toLoad) {
          if (params.fillMode === 'form') {
            $element.block();
          }
          return crmApi4('Afform', 'prefill', params)
            .then((result) => {
              result.forEach((item) => {
                // Use _.each() because item.values could be cast as an object if array keys are not sequential
                _.each(item.values, (values, index) => {
                  data[item.name][index] = data[item.name][index] || {};
                  data[item.name][index].joins = data[item.name][index].joins || {};
                  angular.merge(data[item.name][index], values, {fields: _.cloneDeep(schema[item.name].data || {})});
                });
              });
              $element.unblock();
            }, (error) => {
              disableForm(error.error_message, ts('Sorry'), 'error');
              $element.unblock();
            });
        }
        // Clear existing join selection
        else if (joinEntity) {
          data[selectedEntity][selectedIndex].joins[joinEntity][joinIndex] = {};
        }
        // Clear existing entity selection
        else if (selectedEntity) {
          // Delete object keys without breaking object references
          Object.keys(data[selectedEntity][selectedIndex].fields).forEach((key) => delete data[selectedEntity][selectedIndex].fields[key]);
          // Fill pre-set values
          angular.merge(data[selectedEntity][selectedIndex].fields, _.cloneDeep(schema[selectedEntity].data || {}));
          data[selectedEntity][selectedIndex].joins = {};
        }
      };

      function displaySubmitButton(args) {
        if (args.sid && args.sid.length > 0) {
          return false;
        }
        return true;
      }

      // Used when submitting file fields
      const token = new URLSearchParams(window.location.search).get('_aff');
      const headers = {'X-Requested-With': 'XMLHttpRequest'};
      if (token) {
        headers['X-Civi-Auth-Afform'] = token;
      }
      this.fileUploader = new FileUploader({
        url: CRM.url('civicrm/ajax/api4/Afform/submitFile'),
        headers: headers,
        onAfterAddingFile: (item) => {
          setDraftStatus('unsaved');
        },
        onSuccessItem: onFileUploadSuccess,
        onCompleteAll: onFileUploadsComplete,
        onBeforeUploadItem: (item) => {
          // Show unobtrusive status indicator.
          item._status = CRM.status({
            start: ts('Uploading %1', {1: item.file.name}),
            error: ts('Upload failed'),
            success: ts('Upload complete'),
          });
        },
        onCompleteItem: (item, response, status, headers) => {
          if (status === 200) {
            item._status.resolve();
          } else {
            item._status.reject();
          }
        }
      });

      function onFileUploadSuccess(item, response, status, headers) {
        if (response.values && response.values[0] && response.values[0].id) {
          const dataProvider = item.crmDataProvider;
          dataProvider.getFieldData()[item.crmFieldName] = response.values[0];
        }
      }

      function onFileUploadsComplete() {
        if (uploadingDraftFiles) {
          uploadingDraftFiles = false;
          setDraftStatus('saved');
          //
          if (draftStatus === 'unsaved') {
            autoSave();
          }
        } else {
          postProcess();
        }
      }

      // Set up background tasks for saving draft
      function setupDraftWatcher() {
        const buttons = getDraftButtons();
        const autoSaveEnabled = ctrl.getFormMeta().autosave_draft;

        if ((!autoSaveEnabled && !buttons.length) || !ctrl.showSubmitButton || (!CRM.config.cid && !token)) {
          // No watchers needed
          return;
        }

        // Store initial state of any save-draft buttons on the form
        $.each(buttons, function(index, button) {
          saveDraftButtons[index] = {
            text: $(button).text(),
            icon: $(button).attr('crm-icon'),
          };
        });

        // If autosave enabled, save every ten seconds if changes have been made
        if (autoSaveEnabled) {
          autoSave = _.debounce(ctrl.submitDraft, 10000);
        }

        cancelDraftWatcher = $scope.$watch(() => data, function (newVal, oldVal) {
            if (oldVal) {
              if (draftStatus === 'pristine') {
                setDraftStatus('saved');
              } else {
                setDraftStatus('unsaved');
                autoSave(newVal);
              }
            }
          },
          true
        );
      }

      // Handle the logic for conditional fields
      this.checkConditions = function(conditions, op) {
        op = op || 'AND';
        // OR and AND have the opposite behavior so the logic is inverted
        // NOT works identically to OR but gets flipped at the end
        let ret = op === 'AND',
          flip = !ret;
        _.each(conditions, function(clause) {
          // Recurse into nested group
          if (Array.isArray(clause[1])) {
            if (ctrl.checkConditions(clause[1], clause[0]) === flip) {
              ret = flip;
            }
          } else {
            // Angular can't handle expressions with quotes inside brackets, so they are omitted
            // Here we add them back to make valid js
            if (typeof clause[0] === 'string' && clause[0].charAt(0) !== '"') {
              clause[0] = clause[0].replace(/\[([^'"])/g, "['$1").replace(/([^'"])]/g, "$1']");
            }
            let parser1 = $parse(clause[0]);
            let parser2 = $parse(clause[2]);
            let result = compareConditions(parser1(data), clause[1], parser2(data));
            if (result === flip) {
              ret = flip;
            }
          }
        });
        return op === 'NOT' ? !ret : ret;
      };

      function compareConditions(val1, op, val2) {
        const yes = (op !== '!=' && !op.includes('NOT '));

        switch (op) {
          case '=':
          case '!=':
          // Legacy operator, changed to '=', but may still exist on older forms.
          case '==':
            // Case-insensitive string comparisons
            if (typeof val1 === 'string') {
              val1 = val1.toLowerCase();
            }
            if (typeof val2 === 'string') {
              val2 = val2.toLowerCase();
            }
            return angular.equals(val1, val2) === yes;

          case '>':
            return val1 > val2;

          case '<':
            return val1 < val2;

          case '>=':
            return val1 >= val2;

          case '<=':
            return val1 <= val2;

          case 'IS EMPTY':
            return !val1;

          case 'IS NOT EMPTY':
            return !!val1;

          case 'CONTAINS':
          case 'NOT CONTAINS':
            if (Array.isArray(val1)) {
              return val1.includes(val2) === yes;
            } else if (typeof val1 === 'string' && typeof val2 === 'string') {
              return val1.toLowerCase().includes(val2.toLowerCase()) === yes;
            }
            return angular.equals(val1, val2) === yes;

          case 'IN':
          case 'NOT IN':
            if (Array.isArray(val2)) {
              return val2.includes(val1) === yes;
            }
            return angular.equals(val1, val2) === yes;

          case 'LIKE':
          case 'NOT LIKE':
            if (typeof val1 === 'string' && typeof val2 === 'string') {
              return likeCompare(val1, val2) === yes;
            }
            return angular.equals(val1, val2) === yes;
        }
      }

      function likeCompare(str, pattern) {
        // Escape regex special characters in the pattern, except for % and _
        const regexPattern = pattern
          .replace(/([.+?^=!:${}()|\[\]\/\\])/g, "\\$1")
          .replace(/%/g, '.*') // Convert % to .*
          .replace(/_/g, '.'); // Convert _ to .
        const regex = new RegExp(`^${regexPattern}$`, 'i');
        return regex.test(str);
      }

      // Called after form is submitted and files are uploaded
      function postProcess() {
        const metaData = ctrl.getFormMeta(),
          dialog = $element.closest('.ui-dialog-content');

        // reset form to clean
        ctrl.ngForm.$setPristine();

        $element.trigger('crmFormSuccess', {
          afform: metaData,
          data: data,
          submissionResponse: submissionResponse,
        });

        if (submissionResponse[0].redirect) {
          let url = submissionResponse[0].redirect;
          if (url.indexOf('civicrm/') === 0) {
            url = CRM.url(url);
          } else if (url.indexOf('/') === 0) {
            let port = $location.port();
            port = port ? `:${port}` : '';
            url = `${$location.protocol()}://${$location.host()}${port}${url}`;
          }
          $window.location.href = url;
          return;
        }

        if (submissionResponse[0].message) {
          $element.hide();
          const $confirmation = $('<div class="afform-confirmation" />');
          $confirmation.html(submissionResponse[0].message);
          $confirmation.insertAfter($element);
        }
        else if (dialog.length) {
          dialog.dialog('close');
        }
        else {
          $element.unblock();
        }
      }

      function validateFileFields() {
        let valid = true;
        $("af-form[ng-form=" + ctrl.getFormMeta().name + "] input[type='file']").each((index, fld) => {
          if ($(fld).attr('required') && $(fld).get(0).files.length == 0) {
            valid = false;
          }
        });
        return valid;
      }

      function disableForm(errorMsg) {
        $('af-form[ng-form="' + ctrl.getFormMeta().name + '"]')
          .addClass('disabled')
          .find('button[ng-click="afform.submit()"]').prop('disabled', true);
        CRM.alert(errorMsg, ts('Sorry'), 'error');
      }

      const handleError = (error) => {
        // see: CRM/Api4/Page/AJAX.php
        if (error && error.error_code !== '1') {
          CRM.alert(error.error_message, ts('Please resolve these issues'), 'warning');
        }
        else {
          const message = error?.error_message ? error.error_message : ts('Unknown error');
          CRM.alert(message, ts('There is a problem'), 'error');
        }
      };

      this.submit = function () {
        // validate required fields on the form
        if (!ctrl.ngForm.$valid || !validateFileFields()) {
          // at this point we want the user to know to check the invalid fields
          //
          // the complication is the browser will natively trigger notifications
          // for invalid fields, and focus the first one of these
          //
          // on the backend CRM.alert complements this by adding a global popup
          // that does not block the user flow
          //
          // however on the frontend CRM.alert will trigger a popup (either our
          // homebrew or sweetalert) which *interrupts* the browser putting focus
          // on the invalid fields. in this case we are better off doing nothing
          // and just letting the browser alerts do their work
          //
          // NOTE: if you set sweetalert to Override Everywhere it will turn the
          // below alert into a popup on the backend too, which isn't great
          //
          // TODO: in the long run we should provide a way for callers of
          // CRM.alert to specify between interrupting vs non-interrupting alerts
          if (document.getElementById('crm-notification-container')) {
            CRM.alert(ts('Please fill all required fields.'), ts('Form Error'));
          }
          return;
        }
        $element.block();
        if (cancelDraftWatcher) {
          cancelDraftWatcher();
        }

        const submitApi = crmApi4('Afform', 'submit', {
          name: ctrl.getFormMeta().name,
          args: args,
          values: data,
        }).then(function(response) {
          submissionResponse = response;
          if (ctrl.fileUploader.getNotUploadedItems().length) {
            _.each(ctrl.fileUploader.getNotUploadedItems(), function(file) {
              file.formData.push({
                params: JSON.stringify(_.extend({
                  token: response[0].token,
                  name: ctrl.getFormMeta().name
                }, file.crmApiParams()))
              });
            });
            ctrl.fileUploader.uploadAll();
          }
          else {
            postProcess();
          }
        })
        .catch(function(error) {
          $element.unblock();

          handleError(error);

          $element.trigger('crmFormError', {
            afform: ctrl.getFormMeta(),
            data: data,
            submissionResponse: submissionResponse,
            error: error
          });
        });
        // Show unobtrusive status indicator.
        crmStatus({
          // Defaults for `start` and `success` are 'Saving...' and 'Saved' .
          error: ts('Not Saved'),
        }, submitApi);
      };

      this.submitDraft = function() {
        if (uploadingDraftFiles) {
          return;
        }
        setDraftStatus('saving');
        const submitDraft = crmApi4('Afform', 'submitDraft', {
          name: ctrl.getFormMeta().name,
          args: args,
          values: data,
        }).then(function(response) {
          if (ctrl.fileUploader.getNotUploadedItems().length) {
            uploadingDraftFiles = true;
            ctrl.fileUploader.getNotUploadedItems().forEach((file) => {
              file.formData.push({
                params: JSON.stringify(_.extend({
                  name: ctrl.getFormMeta().name
                }, file.crmApiParams()))
              });
            });
            ctrl.fileUploader.uploadAll();
          } else {
            setDraftStatus('saved');
          }
        })
        .catch(function(error) {
          setDraftStatus('unsaved');
          handleError(error);
        });
        // Show unobtrusive status indicator.
        crmStatus({
          start: ts('Saving Draft'),
          success: ts('Draft Saved'),
          error: ts('Not Saved'),
        }, submitDraft);
      };

      function getDraftButtons() {
        return $element.find('button[ng-click="afform.submitDraft()"]');
      }

      function setDraftStatus(newStatus) {
        if (draftStatus === newStatus) {
          return;
        }
        if (draftStatus === 'unsaved' && newStatus === 'saved') {
          // If form was altered during a save operation, keep the 'unsaved' status
          return;
        }
        // Setting to 'unsaved' - restore buttons to initial state
        if (newStatus === 'unsaved' && !uploadingDraftFiles) {
          restoreDraftButtons();
        }
        // Change icon, text & disable button for 'saving' or 'saved' status
        else if (!uploadingDraftFiles) {
          const newText = newStatus === 'saving' ? ts('Saving Draft') : ts('Draft Saved');
          const newIcon = newStatus === 'saving' ? 'fa-spinner fa-spin' : 'fa-check';
          disableDraftButtons(newText, newIcon);
        }
        draftStatus = newStatus;
      }

      function disableDraftButtons(text, icon) {
        const buttons = getDraftButtons();
        $.each(buttons, function(index, button) {
          $(button).text(text).attr('disabled', true);
          $(button).prepend('<i class="crm-i ' + icon + '" role="img" aria-hidden="true"></i> ');
        });
      }

      function restoreDraftButtons() {
        const buttons = getDraftButtons();
        $.each(buttons, function(index, button) {
          const initialState = saveDraftButtons[index] || saveDraftButtons[0];
          $(button).text(initialState.text).attr('disabled', false);
          if (initialState.icon) {
            $(button).prepend('<i class="crm-i ' + saveDraftButtons[index].icon + '" role="img" aria-hidden="true"></i> ');
          }
        });
      }

      // Used by "extra" afFields that have no entity.
      this.getFieldData = () => {
        return data.extra;
      };

      // these tokens matching/replacing functions should match
      // the serverside implementation in AbstractProcessor::replaceTokens
      this.identifyTokens = (message) => message?.match(/\[[a-zA-Z0-9_]+\.[0-9]+\.[^\]]+\]/g);

      this.getTokenValues = (tokens) => {
        const values = {};

        tokens.forEach((token) => {
          const parts = token.slice(1, -1).split('.');
          values[token] = data[parts[0]][parts[1]].fields[parts.slice(2).join('.')];
          values[token] = (values[token] === undefined) ? '' : values[token];
        });

        return values;
      };

      this.replaceTokens = (message) => {
        const tokens = this.identifyTokens(message);
        const tokenValues = this.getTokenValues(tokens);
        tokens.forEach((token) => message = message.replace(token, tokenValues[token]));
        return message;
      };

    }
  });
})(angular, CRM.$, CRM._);
