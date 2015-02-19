/// crmFile: Manage file attachments
(function (angular, $, _) {

  angular.module('crmAttachment', ['angularFileUpload']);

  // crmAttachment manages the list of files which are attached to a given entity
  angular.module('crmAttachment').factory('CrmAttachments', function (crmApi, crmStatus, FileUploader, $q) {
    // @param target an Object(entity_table:'',entity_id:'') or function which generates an object
    function CrmAttachments(target) {
      var crmAttachments = this;
      this._target = target;
      this.files = [];
      this.trash = [];
      this.uploader = new FileUploader({
        url: CRM.url('civicrm/ajax/attachment'),
        onAfterAddingFile: function onAfterAddingFile(item) {
          item.crmData = {
            description: ''
          };
        },
        onSuccessItem: function onSuccessItem(item, response, status, headers) {
          crmAttachments.files.push(response.file.values[response.file.id]);
          crmAttachments.uploader.removeFromQueue(item);
        },
        onErrorItem: function onErrorItem(item, response, status, headers) {
          var msg = (response && response.file && response.file.error_message) ? response.file.error_message : ts('Unknown error');
          CRM.alert(item.file.name + ' - ' + msg, ts('Attachment failed'));
          crmAttachments.uploader.removeFromQueue(item);
        }
      });
    }

    angular.extend(CrmAttachments.prototype, {
      // @return Object(entity_table:'',entity_id:'')
      getTarget: function () {
        return (angular.isFunction(this._target) ? this._target() : this._target);
      },
      // @return Promise<Attachment>
      load: function load() {
        var target = this.getTarget();
        var Attachment = this;

        if (target.entity_id) {
          var params = {
            entity_table: target.entity_table,
            entity_id: target.entity_id
          };
          return crmApi('Attachment', 'get', params).then(function (apiResult) {
            Attachment.files = _.values(apiResult.values);
            return Attachment;
          });
        }
        else {
          var dfr = $q.defer();
          Attachment.files = [];
          dfr.resolve(Attachment);
          return dfr.promise;
        }
      },
      // @return Promise
      save: function save() {
        var crmAttachments = this;
        var target = this.getTarget();
        if (!target.entity_table || !target.entity_id) {
          throw "Cannot save attachments: unknown entity_table or entity_id";
        }

        var params = _.extend({}, target);
        params.values = crmAttachments.files;
        return crmApi('Attachment', 'replace', params)
          .then(function () {
            var dfr = $q.defer();

            var newItems = crmAttachments.uploader.getNotUploadedItems();
            if (newItems.length > 0) {
              _.each(newItems, function (item) {
                item.formData = [_.extend({crm_attachment_token: CRM.crmAttachment.token}, target, item.crmData)];
              });
              crmAttachments.uploader.onCompleteAll = function onCompleteAll() {
                delete crmAttachments.uploader.onCompleteAll;
                dfr.resolve(crmAttachments);
              };
              crmAttachments.uploader.uploadAll();
            }
            else {
              dfr.resolve(crmAttachments);
            }

            return dfr.promise;
          });
      },
      // Compute a digest over the list of files. The signature should change if the attachment list has changed
      // (become dirty).
      getAutosaveSignature: function getAutosaveSignature() {
        var sig = [];
        // Attachments have a special lifecycle, and attachments.queue is not properly serializable, so
        // it takes some special effort to figure out a suitable signature. Issues which can cause gratuitous saving:
        //  - Files move from this.uploader.queue to this.files after upload.
        //  - File names are munged after upload.
        //  - Deletes are performed immediately (outside the save process).
        angular.forEach(this.files, function(item) {
          sig.push({f: item.name.replace(/[^a-zA0-Z0-9\.]/, '_'), d: item.description});
        });
        angular.forEach(this.uploader.queue, function(item) {
          sig.push({f: item.file.name.replace(/[^a-zA0-Z0-9\.]/, '_'), d: item.crmData.description});
        });
        angular.forEach(this.trash, function(item) {
          sig.push({f: item.name.replace(/[^a-zA0-Z0-9\.]/, '_'), d: item.description});
        });
        return _.sortBy(sig, 'name');
      },
      // @param Object file APIv3 attachment record (e.g. id, entity_table, entity_id, description)
      deleteFile: function deleteFile(file) {
        var crmAttachments = this;

        var idx = _.indexOf(this.files, file);
        if (idx != -1) {
          this.files.splice(idx, 1);
        }

        this.trash.push(file);

        if (file.id) {
          var p = crmApi('Attachment', 'delete', {id: file.id}).then(
            function () { // success
            },
            function (response) { // error; restore the file
              var msg = angular.isObject(response) ? response.error_message : '';
              CRM.alert(msg, ts('Deletion failed'));
              crmAttachments.files.push(file);

              var trashIdx = _.indexOf(crmAttachments.trash, file);
              if (trashIdx != -1) {
                crmAttachments.trash.splice(trashIdx, 1);
              }
            }
          );
          return crmStatus({start: ts('Deleting...'), success: ts('Deleted')}, p);
        }
      }
    });

    return CrmAttachments;
  });

  // example:
  //   $scope.myAttachments = new CrmAttachments({entity_table: 'civicrm_mailing', entity_id: 123});
  //   <div crm-attachments="myAttachments"/>
  angular.module('crmAttachment').directive('crmAttachments', function ($parse, $timeout) {
    return {
      scope: {
        crmAttachments: '@'
      },
      template: '<div ng-if="ready" ng-include="inclUrl"></div>',
      link: function (scope, elm, attr) {
        var model = $parse(attr.crmAttachments);
        scope.att = model(scope.$parent);
        scope.ts = CRM.ts(null);
        scope.inclUrl = '~/crmAttachment/attachments.html';

        // delay rendering of child tree until after model has been populated
        scope.ready = true;
      }
    };
  });

})(angular, CRM.$, CRM._);
