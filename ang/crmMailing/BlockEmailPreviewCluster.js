(function(angular, $, _) {
  // example: <div crm-mailing-block-preview crm-mailing="myMailing" on-preview="openPreview(myMailing, preview.mode)" on-send="sendEmail(myMailing,preview.recipient)">
  // note: the directive defines a variable called "preview" with any inputs supplied by the user (e.g. the target recipient for an example mailing)

  angular.module('crmMailing').directive('crmMailingBlockEmailPreviewCluster', function (crmUiHelp, $http, crmApi) {

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
        
        var clientIconURL = {
          "gmail" : "http://www.socialtalent.co/wp-content/uploads/2015/07/Gmail-Logo.png",
          "yahoo" : "https://lh6.ggpht.com/yzhSae3SIKlwv9lBzpCWaexNKgpLHXvwnxyEE7_oW3SdMv604v-YtUcQnGCyAUpX1lcm=w300"
        }

      crmApi('Prevem', 'login', {})
        .then(function(r){  
          scope.accessToken = r.values.token;
          scope.prevemURL = r.values.url;
          scope.consumerId = r.values.consumerId;
          scope.initialCheck();
        })
        .catch(function(err){
          console.log('error', err);
        });

        scope.accessTokenUrlExtension = 'access_token=' + scope.accessToken;

        scope.initialCheck = function initialCheck() {
          var batchId = scope.consumerId + ':' + scope.mailing.id;
          var checkURL = scope.prevemURL + '/api/PreviewBatches?filter=%7B%22where%22%3A%7B%22batchId%22%20%3A%20%22'+batchId+'%22%7D%7D'+'&'+scope.accessTokenUrlExtension;
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
              CRM.alert(ts('Failure at the Preview Manager. Check if you have created a user at the Preview Manager and are logged in successfully there.'));
          });
        }

        scope.openPreviewImage = function openPreviewImage(mailing, clientName) {
          if (scope[clientName] === 0) {
            CRM.alert(ts('The screenshot for %1 is still being prepared. Try again in a minute.', {1: clientName}));
          }
          else if (scope[clientName] === 3) {
            CRM.alert(ts('Connection to the Selenium Server was refused. The screenshot for %1 couln\'t be fetched because of an error at the %1 renderer\'s end. Please make sure the Selenium server is running on the renderer\'s machine. Cancel this request and make a new request to try again.', {1: clientName}));
          }
          else if (scope[clientName] === 4) {
            CRM.alert(ts('Renderer couldn\'t locate an Element. The screenshot for %1 couln\'t be fetched because of an error at the %1 renderer\'s end. Cancel this request and make a new request to try again.', {1: clientName}));
          }
          else if (scope[clientName] === 5) {
            CRM.alert(ts('Unknown Error at the Renderer\'s end. The screenshot for %1 couln\'t be fetched because of an unknown error at the %1 renderer\'s end. Cancel this request and make a new request to try again.', {1: clientName}));
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
            scope[clientName] = 0;
            scope.iconClass[clientName] = 'fadedIcon';
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
          $http.get(statusURL+batchId+'&'+scope.accessTokenUrlExtension)
            .success(function(data, status, headers, config){
              /*called for result & error because 200 status*/    
              if (data.response){
                //handle success here
                console.log(data.response);
                for (var clientName in data.response) {
                  if (data.response[clientName] != 0 && data.response[clientName] != 2 && data.response[clientName] != null) {
                    scope[clientName] = data.response[clientName];
                    scope.iconClass[clientName] = "clearIcon";
                  }
                }
                if (data.response.finished === 1){
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
              console.log(status);
              clearInterval(scope.Interval);
              scope.requested = false;
              CRM.alert(ts('Failed to request a preview. Check if you have created a user at the Preview Manager and are logged in successfully there.'));
          });
        }

        scope.cancelBatch = function cancelBatch(mailing) {
          scope.requested= false;
          var deleteURL = scope.prevemURL + '/api/PreviewBatches/'
          var batchId = scope.consumerId + ':' + mailing.id;
          clearInterval(scope.Interval);
          $http.delete(deleteURL+batchId+'?'+scope.accessTokenUrlExtension)
            .success(function(data, status, headers, config){
              /*called for result & error because 200 status*/
              console.log(data)    
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
