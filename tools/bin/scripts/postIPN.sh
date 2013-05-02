#!/bin/sh

curl http://crm_32/sites/crm_32/modules/civicrm/extern/ipn.php?reset=1\&module=event\&contactID=102\&participantID=2222\&contributionID=4927\&eventID=57 -d mc_gross=289.00 -d txn_id=5M6789701L0500744 -d invoice=464c1b17c130a3eaffc159629013203e -d payment_status=Completed -d mc_fee=29.00
