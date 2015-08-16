(function(angular, $, _) {
  // example: <div crm-mailing-block-preview crm-mailing="myMailing" on-preview="openPreview(myMailing, preview.mode)" on-send="sendEmail(myMailing,preview.recipient)">
  // note: the directive defines a variable called "preview" with any inputs supplied by the user (e.g. the target recipient for an example mailing)

  angular.module('crmMailing').directive('crmMailingBlockEmailPreviewCluster', function (crmUiHelp, $http, crmApi) {

    crmApi('Prevem', 'login').then(function(returnValues){ console.log('returnValues'); });

    return {
      templateUrl: '~/crmMailing/BlockEmailPreviewCluster.html',
      link: function(scope, elm, attr) {
        scope.$watch(attr.crmMailing, function(newValue) {
          scope.mailing = newValue;
        });
        scope.crmMailingConst = CRM.crmMailing;
        scope.ts = CRM.ts(null);
        scope.hs = crmUiHelp({file: 'CRM/Mailing/MailingUI'});
        scope.gmail = 0;
        scope.yahoo = 0;
        scope.iconClass = {
          "gmail" : "fadedIcon",
          "yahoo" : "fadedIcon"
        }
        scope.prevemURL = CRM.crmMailing.prevemUrl;
        scope.consumerId = CRM.crmMailing.prevemConsumer;
        
        var clientIconURL = {
          "gmail" : "http://www.socialtalent.co/wp-content/uploads/2015/07/Gmail-Logo.png",
          "yahoo" : "https://lh6.ggpht.com/yzhSae3SIKlwv9lBzpCWaexNKgpLHXvwnxyEE7_oW3SdMv604v-YtUcQnGCyAUpX1lcm=w300"
        }

        scope.initialCheck = function initialCheck() {
          var batchId = scope.consumerId + ':' + scope.mailing.id;
          var checkURL = scope.prevemURL + '/api/PreviewBatches?filter=%7B%22where%22%3A%7B%22batchId%22%20%3A%20%22'+batchId+'%22%7D%7D'
          var statusURL = scope.prevemURL + '/api/PreviewBatches/status?batchId='
          $http.get(checkURL)
            .success(function(data, status, headers, config){
              /*called for result & error because 200 status*/    
              if (data[0] != undefined){
                //handle success here
                scope.requested = true;
                data[0].renderers.forEach(function(entry) {
                  scope[entry + 'Show'] = true;
                })
                scope.Interval = setInterval( function(){
                  scope.checkStatus(scope.mailing, statusURL);
                }, 5000);
              } else if (data.error) {
                //handle error here
                console.log(data.error)
              }
            })
          .error(function(data, status, headers, config){
              /*handle non 200 statuses*/
              console.log(data);
          });
        }

        scope.initialCheck();

        scope.openPreviewImage = function openPreviewImage(mailing, clientName) {
          if (scope[clientName] === 0) {
            CRM.alert(ts('The screenshot for %1 is still being prepared. Try again in a minute.', {1: clientName}));
          }
          else {
            window.open(scope[clientName]);
          }
        }

        scope.getClientIcon = function getClientIcon(clientName) {
          return clientIconURL[clientName];
        }

        scope.requestPreview = function requestPreview(mailing, renderers) {
          scope.$eval(attr.onRequest);
          scope.requested= true;
          for (var clientName in renderers) {
            if (renderers[clientName]) {
              scope[clientName + 'Show'] = true;
            }
            else {
              scope[clientName + 'Show'] =false
            }
          }

          var statusURL = scope.prevemURL + '/api/PreviewBatches/status?batchId='
          var batchId = scope.consumerId + ':' + mailing.id;
          scope.Interval = setInterval( function(){
            scope.checkStatus(mailing, statusURL);
          }, 5000);
        };

        scope.checkStatus = function checkStatus(mailing, statusURL) {
          var batchId = scope.consumerId + ':' + mailing.id;
          $http.get(statusURL+batchId)
            .success(function(data, status, headers, config){
              /*called for result & error because 200 status*/    
              if (data.response){
                //handle success here
                for (var clientName in data.response) {
                  if (data.response[clientName] != 0 && data.response[clientName] != 2 && data.response[clientName] != null) {
                    scope[clientName] = data.response[clientName];
                    scope.iconClass[clientName] = "clearIcon";
                  }
                }
                if (data.response.finished == 1){
                  clearInterval(scope.Interval);
                }
              } else if (data.error) {
                //handle error here
                console.log(status);
                console.log(data.error);
              }
            })
          .error(function(data, status, headers, config){
              /*handle non 200 statuses*/
              console.log(data);
          });
        }

        scope.cancelBatch = function cancelBatch(mailing) {
          scope.requested= false;
          var deleteURL = scope.prevemURL + '/api/PreviewBatches/'
          var batchId = scope.consumerId + ':' + mailing.id;
          $http.delete(deleteURL+batchId)
            .success(function(data, status, headers, config){
              /*called for result & error because 200 status*/
              console.log(status)    
              if (data.result){
                //handle success here
                console.log(data.result)
              } else if (data.error) {
                //handle error here
                console.log(status)
              }
            })
            .error(function(data, status, headers, config){
                /*handle non 200 statuses*/
                console.log(data);
            })
        };

      }
    };
  });

})(angular, CRM.$, CRM._);
