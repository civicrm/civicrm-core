/**
 * Created by aditya on 6/12/14.
 */


(function(angular, $, _) {

    var partialUrl = function(relPath) {
        return CRM.resourceUrls['civicrm'] + '/partials/abtesting/' + relPath;
    };

    var crmMailingAB = angular.module('crmMailingAB', ['ngRoute', 'ui.utils']);


//-------------------------------------------------------------------------------------------------------
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
                    mailingList: function($route, crmApi) {
                        return crmApi('Mailing', 'get', {});
                    },
                    allgroups: function($route,crmApi){
                        return crmApi('Group','get',{});
                    }

                }


            });
        }
    ]);
//-----------------------------------------
    // Add a new record by name.
    // Ex: <crmAddName crm-options="['Alpha','Beta','Gamma']" crm-var="newItem" crm-on-add="callMyCreateFunction(newItem)" />

    crmMailingAB.controller('TabsDemoCtrl', function($scope, crmApi, mailingList) {

        $scope.adi=0;
        $scope.campaign_clicked= function(){
            if($scope.adi >= 0 ){
                $scope.adi  =0;
            }
        };
        $scope.compose_clicked=function(){
            if($scope.adi >=1){
                $scope.adi =1;
            }
        };
        $scope.rec_clicked=function(){
            if($scope.adi >=2){
                $scope.adi =2;
            }
        };
        $scope.preview_clicked=function(){
            if($scope.adi>=3){
                $scope.adi=3;
            }
        };
        $scope.templates =
            [   { name: 'Subject Lines', url: partialUrl('subject_lines.html')},
                { name: 'From Name', url: partialUrl('from_name.html')},
                {name:'Two different Emails',url: partialUrl('two_emails.html')} ];
        $scope.template = $scope.templates[0];

        $scope.slide_value = 0;

        $scope.$watch("automated", function() {

            console.log("Smsdd");

        });

        $scope.setifyes= function(val){
            if(val ==1) {
                $scope.ifyes = true;
            }
            else
            $scope.ifyes=false;
        };

        $scope.send_date ="10/4/2004";



    });

    crmMailingAB.directive('nexttab', function() {
        return {
            // Restrict it to be an attribute in this case
            restrict: 'A',
            // responsible for registering DOM listeners as well as updating the DOM
            link: function(scope, element, attrs) {

                $(element).parent().parent().parent().tabs(scope.$eval(attrs.nexttab));
                var myarr = new Array(1,2,3)
                $(element).parent().parent().parent().tabs({disabled:myarr});
                //$(element).parent().parent().parent().tabs({"enable":1});

                $(element).on("click",function() {
                    scope.adi=scope.adi +1;
                    var myArray1 = new Array(  );
                    for ( var i = scope.adi+1; i < 4; i++ ) {
                        myArray1.push(i);
                        console.log( "try " + i );
                    }
                    $(element).parent().parent().parent().tabs( "option", "disabled", myArray1 );
                    $(element).parent().parent().parent().tabs({active:scope.adi});
                    console.log("adiroxxx");
                });
            }
        };
    });

    crmMailingAB.directive('groupselect',function(){
       return {

           restrict : 'AE',

           link: function(scope,element, attrs){

              $(document).ready(function() { $(element).select2({width:"400px",placeholder: "Select the groups you wish to include"});

              });

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
                        console.log("yo");

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
                    console.log("you can do it");
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













})(angular, CRM.$, CRM._);