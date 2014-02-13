<?php 

/* 
 * Code provided by Ticketmaster/IATS in their php API 
 * Used by IATS Payment processor code
 *
 */


class creditCard 
{ 
    function cleanNum ($cc_no) 
    { 
        // Remove non-numeric characters from $cc_no 
        return ereg_replace ('[^0-9]+', '', $cc_no); 
    } 

    function ccType ($cc_no) 
    { 
         $cc_no = creditCard::cleanNum ($cc_no); 

        // Get card type based on prefix and length of card number 
        if (ereg ('^4(.{12}|.{15})$', $cc_no)) 
            return 'VISA'; 
        if (ereg ('^5[1-5].{14}$', $cc_no)) 
            return 'MC'; 
        if (ereg ('^3[47].{13}$', $cc_no)) 
            return 'AMX'; 
        if (ereg ('^3(0[0-5].{11}|[68].{12})$', $cc_no)) 
            return 'DC';	//'Diners Club/Carte Blanche'; 
        if (ereg ('^6011.{12}$', $cc_no)) 
            return 'DSC';	//'Discover Card'; 
        if (ereg ('^(3.{15}|(2131|1800).{11})$', $cc_no)) 
            return 'DC';	//'JCB'; 
        if (ereg ('^2(014|149).{11})$', $cc_no)) 
            return 'ENROUT';	'enRoute'; 

        return 'UNKNOWN'; 
    } 

    function isValid ($cc_no) 
    {   $sum = 0;
        $digits = 0;
        // Reverse and clean the number 
        $cc_no = strrev (creditCard::cleanNum ($cc_no)); 
         
        // VALIDATION ALGORITHM 
        // Loop through the number one digit at a time 
        // Double the value of every second digit (starting from the right) 
        // Concatenate the new values with the unaffected digits 
        for ($ndx = 0; $ndx < strlen ($cc_no); ++$ndx) 
            $digits .= ($ndx % 2) ? $cc_no[$ndx] * 2 : $cc_no[$ndx]; 
         
        // Add all of the single digits together 
        for ($ndx = 0; $ndx < strlen ($digits); ++$ndx) 
            $sum += $digits[$ndx]; 

        // Valid card numbers will be transformed into a multiple of 10 
        return ($sum % 10) ? FALSE : TRUE; 
    } 

   
} 
