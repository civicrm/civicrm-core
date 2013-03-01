#!/usr/bin/env bash -v

./setup.sh

cd ../test/maxq

# This script is used to run maxq generated test scripts.
# Before running these scripts please see Common.py
# In Common.py comment and uncomment the constants as per the need
# Below commands use someof the modes of maxq. The modes used are : 
# The -r mode is for running maxq generated test scripts.
# The -q mode is for quite mode while testing.
# Instead of -q (quite mode), -d(debug mode) can be used.
# For all other options, fire $ maxq --help   


############################
# Test for Adding Contacts # 
############################

maxq -q -r testAddContactIndividual.py 
maxq -q -r testAddContactHousehold.py 
maxq -q -r testAddContactOrganization.py

#############################
# Test for Editing Contacts # 
#############################

maxq -q -r testEditContactIndividual.py 
maxq -q -r testEditContactHousehold.py 
maxq -q -r testEditContactOrganization.py

#############################
# Test for Viewing Contacts # 
#############################

maxq -q -r testViewContactIndividual.py 
maxq -q -r testViewContactHousehold.py 
maxq -q -r testViewContactOrganization.py

#############################################
# Test for Relationship By Relationship Tab # 
#############################################

maxq -q -r testAddRelByRelTab.py 
maxq -q -r testEditRelByRelTab.py 
maxq -q -r testViewRelByRelTab.py 
maxq -q -r testDisableEnableRelByRelTab.py
maxq -q -r testDeleteRelByRelTab.py 

########################################
# Test for Relationship By Contact Tab # 
#########################################

maxq -q -r testAddRelByContactTab.py
maxq -q -r testEditRelByContactTab.py 

###############################
# Test for Group By Group Tab # 
###############################

maxq -q -r testGroupAllByGroupTab.py 
maxq -q -r testGroupAllByContactTab.py

#############################
# Test for Tags By Tags Tab # 
#############################

maxq -q -r testTagsAllByTagsTab.py

##############################
# Test for Notes By Note Tab # 
##############################

maxq -q -r testAddNoteByNoteTab.py 
maxq -q -r testEditNoteByNoteTab.py 
maxq -q -r testViewNoteByNoteTab.py 
maxq -q -r testDeleteNoteByNoteTab.py

#################################
# Test for Notes By Contact Tab # 
#################################

maxq -q -r testAddNoteByContactTab.py 
maxq -q -r testEditNoteByContactTab.py
maxq -q -r testDeleteNoteByNoteTab.py

#######################
# Test for Admin Tags # 
#######################

maxq -q -r testAdminAddTags.py 
maxq -q -r testAdminEditTags.py 
maxq -q -r testAdminDeleteTag.py

################################
# Test for Admin Location Type # 
################################

maxq -q -r testAdminAddLocationType.py 
maxq -q -r testAdminEditLocationType.py 
maxq -q -r testAdminEnableDisableLocationType.py
maxq -q -r testAdminDeleteLocationType.py

##################################
# Test for Admin Mobile Provider # 
##################################

maxq -q -r testAdminAddMobileProvider.py 
maxq -q -r testAdminEditMobileProvider.py 
maxq -q -r testAdminEnableDisableMobileProvider.py
maxq -q -r testAdminDeleteMobileProvider.py

##############################
# Test for Admin IM Provider # 
##############################

maxq -q -r testAdminAddIMProvider.py 
maxq -q -r testAdminEditIMProvider.py
maxq -q -r testAdminEnableDisableIMProvider.py
maxq -q -r testAdminDeleteIMProvider.py

#####################################
# Test for Admin Relationship Types # 
#####################################

maxq -q -r testAdminAddRel.py 
maxq -q -r testAdminEditRel.py 
maxq -q -r testAdminViewRel.py 
maxq -q -r testAdminEnableDisableRel.py
maxq -q -r testAdminDeleteRel.py

####################################
# Test for Admin Custom Data Group # 
####################################

maxq -q -r testAdminAddCustomDataGroup.py 
maxq -q -r testAdminEditCustomDataGroup.py 
maxq -q -r testAdminEnableDisableCustomDataGroup.py
maxq -q -r testAdminPreviewCustomDataGroup.py

####################################
# Test for Admin Custom Data Field # 
####################################

maxq -q -r testAdminAddCustomDataField.py 
maxq -q -r testAdminEditCustomDataField.py 
maxq -q -r testAdminEnableDisableCustomDataField.py
maxq -q -r testAdminPreviewCustomDataField.py

########################
# Test for Custom Data # 
########################

maxq -r -q testEditCustomDataInline.py

#maxq -q -r adminDeleteCustomDataField.py
#maxq -q -r adminDeleteCustomDataGroup.py

##############################
# Test for Admin CiviDonate  # 
##############################

###################
# Contribute Mode #
###################

maxq -r testAdminAddCiviDonateContributeMode.py
maxq -r testAdminEditCiviDonateContributeMode.py
maxq -r testAdminDisableEnableCiviDonateContributeMode.py
maxq -r testAdminDeleteCiviDonateContributeMode.py

###################
# Contribute Type #
###################

maxq -r testAdminAddCiviDonateContributeType.py
maxq -r testAdminEditCiviDonateContributeType.py
maxq -r testAdminDisableEnableCiviDonateContributeType.py
maxq -r testAdminDeleteCiviDonateContributeType.py

######################
# Payment Instrument #
######################

maxq -r testAdminAddCiviDonatePaymentInstrument.py
maxq -r testAdminEditCiviDonatePaymentInstrument.py
maxq -r testAdminDisableEnableCiviDonatePaymentInstrument.py
maxq -r testAdminDeleteCiviDonatePaymentInstrument.py

##########################
# Test for Basic Search  # 
##########################

maxq -q -r testSearchByLNameIndividual.py 
maxq -q -r testSearchByHNameHousehold.py 
maxq -q -r testSearchByONameOraganization.py 
maxq -q -r testSearchByNoCriteria.py 
maxq -q -r testSearchByGroup.py 
maxq -q -r testSearchByContactTagGroupName.py 

#############################
# Test for Advanced Search  # 
#############################

maxq -q -r testAdvSearchByAllCriteria.py 
maxq -q -r testAdvSearchByContactName.py 
maxq -q -r testAdvSearchByContactGroupCategory.py 
