/**
 * Created by aditya on 6/12/14.
 */


(function(angular, $, _) {

    var partialUrl = function(relPath) {
        return CRM.resourceUrls['civicrm'] + '/partials/abtesting/' + relPath;
    };

    var crmMailingAB = angular.module('crmMailingAB', ['ngRoute', 'ui.utils']);

    crmMailingAB.run(function($rootScope, $templateCache) {
        $rootScope.$on('$viewContentLoaded', function() {
            $templateCache.removeAll();
        });
    });

    crmMailingAB.config(['$routeProvider',
        function($routeProvider) {
            $routeProvider.when('/mailing', {
                template: '<h1>sdfs</h1>',
                controller: 'mailingListCtrl',
                resolve: {
                    mailingList: function($route, crmApi) {
                        return crmApi('Mailing', 'get', {});
                    }
                }
            });

            $routeProvider.when('/mailing/abtesting', {
                templateUrl: partialUrl('helloworld.html'),
                controller: 'TabsDemoCtrl',
                resolve: {
                    metaData: function($route, crmApi) {
                        return crmApi('Group', 'get', {});
                    },
                    mailingList: function($route, crmApi) {
                        return crmApi('Mailing', 'get', {});
                    }
                }
            });
        }
    ]);
//-----------------------------------------
    // Add a new record by name.
    // Ex: <crmAddName crm-options="['Alpha','Beta','Gamma']" crm-var="newItem" crm-on-add="callMyCreateFunction(newItem)" />

    crmMailingAB.controller('TabsDemoCtrl', function($scope, crmApi, metaData, mailingList) {

        $scope.groups = metaData.values;
        $scope.mailings = mailingList.values;
        $scope.tab_val=0;
        $scope.campaign_clicked= function(){
            if($scope.tab_val >= 0 ){
                $scope.tab_val  =0;
            }
        };
        $scope.compose_clicked=function(){
            if($scope.tab_val >=1){
                $scope.tab_val =1;
            }
        };
        $scope.rec_clicked=function(){
            if($scope.tab_val >=2){
                $scope.tab_val =2;
            }
        };
        $scope.preview_clicked=function(){
            if($scope.tab_val>=3){
                $scope.tab_val=3;
            }
        };
        $scope.templates =
            [   { name: 'Subject Lines', url: partialUrl('subject_lines.html')},
                { name: 'From Name', url: partialUrl('from_name.html')},
                {name:'Two different Emails',url: partialUrl('two_emails.html')} ];
        $scope.template = $scope.templates[0];

        $scope.slide_value = 0;

        $scope.setifyes= function(val){
            if(val ==1) {
                $scope.ifyes = true;
            }
            else
            $scope.ifyes=false;
        };

        $scope.send_date ="10/4/2004";

        $scope.mailA={};

        $scope.mailB={};
        $scope.save=function(dat){

            var result= crmApi('Mailing', 'create',dat, true);
            console.log("Ac "+result);
        };

        $scope.init=function(par){

            $scope.whatnext=par.toString()
        }


    });

    crmMailingAB.directive('nexttab', function() {
        return {
            // Restrict it to be an attribute in this case
            restrict: 'A',
            priority: 500,
            // responsible for registering DOM listeners as well as updating the DOM
            link: function(scope, element, attrs) {

                $(element).parent().parent().parent().tabs(scope.$eval(attrs.nexttab));
                var myarr = new Array(1,2,3)
                $(element).parent().parent().parent().tabs({disabled:myarr});

                $(element).on("click",function() {
                    scope.tab_val=scope.tab_val +1;
                    var myArray1 = new Array(  );
                    for ( var i = scope.tab_val+1; i < 4; i++ ) {
                        myArray1.push(i);
                    }
                    $(element).parent().parent().parent().tabs( "option", "disabled", myArray1 );
                    $(element).parent().parent().parent().tabs({active:scope.tab_val});
                    console.log("Adir");
                });
            }
        };
    });

    crmMailingAB.directive('groupselect',function(){
       return {
           restrict : 'AE',
           link: function(scope,element, attrs){
               $(element).select2({width:"400px",placeholder: "Select the groups you wish to include"});
               $(element).select2("data",groups)
           }
       };

    });

    crmMailingAB.directive('sliderbar',function(){
       return{
           restrict: 'AE',
           link: function(scope,element, attrs){
               $(element).slider({min:1});
               $(element).slider({
                   slide: function( event, ui ) {
                       scope.slide_value = ui.value;
                       scope.$apply();
                   }
               });
           }
       };
    });

    crmMailingAB.directive('tpmax',function(){
        return {
            restrict: 'E',
            link: function(scope,element,attr){
                scope.$watch('automated', function(val) {
                    if(val=="Yes") {
                        $(element).dialog({
                            title: 'Automated A/B Testing',
                            width: 800,
                            height: 600,
                            closed: false,
                            cache: false,
                            modal: true
                        });
                    }
                });

                $(element).find("#closebutton").on("click",function(){
                    $(element).dialog("close");
                });
            }
        };
    });

    crmMailingAB.directive('numbar',function(){
        return{
            restrict: 'AE',
            link: function(scope,element, attrs){
                $(element).spinner({max:attrs.numbar,min:0});
            }
        };
    });

    crmMailingAB.directive('datepick',function(){
        return {
            scope :{
                foo : '=send_date'
            },
          restrict: 'AE',
          link: function(scope,element,attrs){
                $(element).datepicker({
                    onSelect: function(date) {
                        $(".ui-datepicker a").removeAttr("href");
                        scope.foo =date;
                        console.log(date);
                    }
                });
            }
        };
    });

    crmMailingAB.directive('submitform',function(){
        return {
          restrict:'A',
            priority: 1000,
          link: function(scope,element,attrs){
              $(element).on("click",function() {

                  console.log("clicked");
                  scope.save({
                      "sequential": 1,
                       "name": "Aditya Nambiar",
                      "subject": scope.mailA.subj,
                      "created_id": "2",
                      "from_email": scope.mailA.fromEmail,
                      "body_text": scope.mailA.body

                  });
                  console.log("Truth "+ scope.whatnext)

                  if(scope.whatnext=="3"){
                      console.log("sdf");
                      scope.mailB.subj=scope.mailA.subj;
                      scope.mailB.body=scope.mailA.body;

                  }
                  else if(scope.whatnext=="2"){
                      scope.mailB.fromEmail=scope.mailA.fromEmail;
                      scope.mailB.body=scope.mailA.body;

                  }
                  

                  scope.save({
                      "sequential": 1,
                      "name": "Aditya Nambiar",
                      "subject": scope.mailB.subj,
                      "created_id": "2",
                      "from_email": scope.mailB.fromEmail,
                      "body_text": scope.mailB.body

                  });

              });
          }
        };

    });


})(angular, CRM.$, CRM._);