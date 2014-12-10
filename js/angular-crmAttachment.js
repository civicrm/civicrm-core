/// crmFile: Manage file attachments
(function (angular, $, _) {
  var partialUrl = function (relPath) {
    return CRM.resourceUrls['civicrm'] + '/partials/crmAttachment/' + relPath;
  };

  angular.module('crmAttachment', ['angularFileUpload']);

  // crmAttachment manages the list of files which are attached to a given entity
  angular.module('crmAttachment').factory('CrmAttachments', function (crmApi, crmStatus, FileUploader, $q) {
    var _target;

    // @param target an Object(entity_table:'',entity_id:'') or function which generates an object
    function CrmAttachments(target) {
      _target = target;
      var crmAttachments = this;
      this.files = [];
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
        return (angular.isFunction(_target) ? _target() : _target);
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
                item.formData = [_.extend({}, target, item.crmData)];
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
      // @param Object file APIv3 attachment record (e.g. id, entity_table, entity_id, description)
      deleteFile: function deleteFile(file) {
        var crmAttachments = this;

        var idx = _.indexOf(this.files, file);
        if (idx != -1) {
          this.files.splice(idx, 1);
        }

        if (file.id) {
          var p = crmApi('Attachment', 'delete', {id: file.id}).then(
            function () { // success
            },
            function (response) { // error; restore the file
              var msg = angular.isObject(response) ? response.error_message : '';
              CRM.alert(msg, ts('Deletion failed'));
              crmAttachments.files.push(file);
            }
          );
          return crmStatus({start: ts('Deleting...'), success: ts('Deleted')}, p);
        }
      }
    });

    return CrmAttachments;
  });

  // example:
  //   $scope.attachments = new CrmAttachments({entity_table: 'civicrm_mailing', entity_id: 123});
  //   <div ng-controller="CrmAttachmentsCtrl" ng-init="init(attachments)">
  angular.module('crmAttachment').controller('CrmAttachmentsCtrl', function ($scope, CrmAttachments) {
    $scope.init = function init(att) {
      $scope.att = att;
      att.load();
    };
  });

})(angular, CRM.$, CRM._);
