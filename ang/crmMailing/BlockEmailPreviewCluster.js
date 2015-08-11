(function(angular, $, _) {
  // example: <div crm-mailing-block-preview crm-mailing="myMailing" on-preview="openPreview(myMailing, preview.mode)" on-send="sendEmail(myMailing,preview.recipient)">
  // note: the directive defines a variable called "preview" with any inputs supplied by the user (e.g. the target recipient for an example mailing)

  angular.module('crmMailing').directive('crmMailingBlockEmailPreviewCluster', function (crmUiHelp, $http) {
    return {
      templateUrl: '~/crmMailing/BlockEmailPreviewCluster.html',
      link: function(scope, elm, attr) {
        scope.$watch(attr.crmMailing, function(newValue) {
          scope.mailing = newValue;
        });
        scope.crmMailingConst = CRM.crmMailing;
        scope.ts = CRM.ts(null);
        scope.hs = crmUiHelp({file: 'CRM/Mailing/MailingUI'});
        scope.testContact = {email: CRM.crmMailing.defaultTestEmail};
        scope.testGroup = {gid: null};
        scope.yahooURL = 'javascript:';
        scope.renderingDone = false;

        scope.requestPreview = function requestPreview(renderers) {
          scope.$eval(attr.onRequest);
          scope.requested= true;
          if (renderers.gmail) {
            scope.gmailShow = true;
          }
          else {
            scope.gmailShow = false;
          }
          if (renderers.yahoo) {
            scope.yahooShow = true;
          }
          else {
            scope.yahooShow = false;
          }
          var statusURL = 'http://0.0.0.0:3000/api/PreviewBatches/status?batchId='
          var batchId = 1234;
          var Interval;
          Interval = setInterval( function(){
            checkStatus(statusURL);
          }, 5000);
          function checkStatus(statusURL) {
            $http.get(statusURL+batchId)
                .success(function(data, status, headers, config){
                /*called for result & error because 200 status*/
                //console.log(data)    
                if (data.response){
                    //handle success here
                    //console.log(data.response.finished)
                    if (data.response.finished == 1){
                      clearInterval(Interval);
                      //console.log("done")
                      scope.yahooURL = data.response.yahoo;
                      console.log(scope.yahooURL);
                      scope.renderingDone = true;
                    }
                } else if (data.error) {
                    //handle error here
                    console.log(status)
                }
              })
            .error(function(data, status, headers, config){
                /*handle non 200 statuses*/
                console.log(data);
            });
          }
        };
        scope.cancelBatch = function cancelBatch() {
          scope.requested= false;
          console.log("hello");
          console.log(scope.yahooURL);
        };

        scope.previewTestGroup = function(e) {
          var $dialog = $(this);
          $dialog.html('<div class="crm-loading-element"></div>').parent().find('button[data-op=yes]').prop('disabled', true);
          $dialog.dialog('option', 'title', ts('Send to %1', {1: _.pluck(_.where(scope.crmMailingConst.groupNames, {id: scope.testGroup.gid}), 'title')[0]}));
          CRM.api3('contact', 'get', {
            group: scope.testGroup.gid,
            options: {limit: 0},
            return: 'display_name,email'
          }).done(function(data) {
            var count = 0,
            // Fixme: should this be in a template?
              markup = '<ol>';
            _.each(data.values, function(row) {
              // Fixme: contact api doesn't seem capable of filtering out contacts with no email, so we're doing it client-side
              if (row.email) {
                count++;
                markup += '<li>' + row.display_name + ' - ' + row.email + '</li>';
              }
            });
            markup += '</ol>';
            markup = '<h4>' + ts('A test message will be sent to %1 people:', {1: count}) + '</h4>' + markup;
            if (!count) {
              markup = '<div class="messages status"><div class="icon ui-icon-alert"></div> ' +
              (data.count ? ts('None of the contacts in this group have an email address.') : ts('Group is empty.')) +
              '</div>';
            }
            $dialog
              .html(markup)
              .trigger('crmLoad')
              .parent().find('button[data-op=yes]').prop('disabled', !count);
          });
        };
      }
    };
  });

})(angular, CRM.$, CRM._);
