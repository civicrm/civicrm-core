#!/bin/sh
# Script for Running all the Tests one after other

# Where are we called from?
P=`dirname $0`

# Current dir
ORIGPWD=`pwd`

# File for Storing Log Of Test.

logCPS=CPSTestResult
###########################################################
##
##  Create log for the tests
##
###########################################################

# Method to Create Log Folder if it does not Exists.
create_log()
{
    cd $ORIGPWD/../test/
    
    PATH4LOG=`pwd` 
    
    if [ ! -d "Result" ] ; then 
	mkdir Result
    fi
}


###########################################################
##
##  Following methods is used to run the test
##
###########################################################


# Method to Run Selenium Ruby Tests.
run_cpsTest()
{
    cd $ORIGPWD/../test/selenium-ruby/CPS
    # Running Selenium (ruby) Tests
    ruby test_cps_personal.rb 
}
###########################################################
##
##  Start of the script.
##
###########################################################

start()
{
create_log
clear
    echo
    echo " *********************** Load Testing for Quest/CPS *********************** "
    echo
run_cpsTest
echo;
}

###########################################################
##
##  Call to start of the script
##
###########################################################

start
