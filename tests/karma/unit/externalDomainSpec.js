'use strict';

describe('Web page', function(){

    var request = new XMLHttpRequest();
    var url = 'http://localhost:8000'; // Change to your port!

    beforeAll(function(){
        /**
         * Warning! Default installation of jasmine (karma+phantomjs)
         * doesn't support JS request to other domains than own.
         * The solutions is at page https://github.com/karma-runner/karma-phantomjs-launcher
         * + add additional configuration to karma.conf.js
         *   with parameter for PhantomJS webSecurityEnabled: false
         * + change run parameter in npm/test.sh
         */
        request.open("GET", url, false);
        request.send();
    });

    it('has response code 200', function(){
        expect(request.status).toBe(200);
    });

    it('contain tag body', function(){
        expect(request.responseText).toContain('<body');
    });

});
