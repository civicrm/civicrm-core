<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */
/**
 * Validation methods for credit card related data
 *
 * PHP Versions 4 and 5
 *
 * This source file is subject to the New BSD license, That is bundled
 * with this package in the file LICENSE, and is available through
 * the world-wide-web at
 * http://www.opensource.org/licenses/bsd-license.php
 * If you did not receive a copy of the new BSDlicense and are unable
 * to obtain it through the world-wide-web, please send a note to
 * pajoye@php.net so we can mail you a copy immediately.
 *
 * @category  Validate
 * @package   Validate_Finance_CreditCard
 * @author    Stefan Neufeind <pear.neufeind@speedpartner.de>
 * @author    Philippe Jausions <Philippe.Jausions@11abacus.com>
 * @copyright 1997-2005  Stefan Neufeind
 * @license   http://www.opensource.org/licenses/bsd-license.php  New BSD License
 * @version   CVS: $Id: CreditCard.php,v 1.13 2007/09/16 16:42:34 kguest Exp $
 * @link      http://pear.php.net/package/Validate_Finance_CreditCard
 */

/**
 * Credit card related information validation class
 *
 * This class provides methods to validate:
 *  - Credit card number
 *  - Card security code
 *  - Card type (i.e. Visa, Mastercard...)
 *
 * The methods only check the format of the data. For instance
 * the package does NOT check if a card is a legitimate card registered
 * with a card issuer, or if the card is reported stolen, etc...
 *
 * @category  Validate
 * @package   Validate_Finance_CreditCard
 * @author    Philippe Jausions <Philippe.Jausions@11abacus.com>
 * @author    Ondrej Jombik <nepto@pobox.sk>
 * @copyright 1997-2005  Stefan Neufeind
 * @license   http://www.opensource.org/licenses/bsd-license.php  New BSD License
 * @version   Release: @package_version@
 * @link      http://pear.php.net/package/Validate_Finance_CreditCard
 */
class Validate_Finance_CreditCard
{
    /**
     * Validates a number according to Luhn check algorithm
     *
     * This function checks given number according Luhn check
     * algorithm. It is published on several places. See links for details.
     *
     * @param string $number number to check
     *
     * @return bool    TRUE if number is valid, FALSE otherwise
     * @access public
     * @static
     * @deprecated
     * @link http://www.webopedia.com/TERM/L/Luhn_formula.html
     * @link http://www.merriampark.com/anatomycc.htm
     * @link http://hysteria.sk/prielom/prielom-12.html#3 (Slovak language)
     * @link http://www.speech.cs.cmu.edu/~sburke/pub/luhn_lib.html (Perl lib)
     */
    function Luhn($number)
    {
        return Validate_Finance_CreditCard::_luhn($number);
    }

    /**
     * Validates a number according to Luhn check algorithm
     *
     * This function checks given number according Luhn check
     * algorithm. It is published on several places. See links for details.
     *
     * @param string $number number to check
     *
     * @return bool    TRUE if number is valid, FALSE otherwise
     * @access private
     * @static
     * @link http://www.webopedia.com/TERM/L/Luhn_formula.html
     * @link http://www.merriampark.com/anatomycc.htm
     * @link http://hysteria.sk/prielom/prielom-12.html#3 (Slovak language)
     * @link http://www.speech.cs.cmu.edu/~sburke/pub/luhn_lib.html (Perl lib)
     */
    static function _luhn($number)
    {
        $len_number = strlen($number);
        $sum        = 0;
        for ($k = $len_number % 2; $k < $len_number; $k += 2) {
            if ((intval($number{$k}) * 2) > 9) {
                $sum += (intval($number{$k}) * 2) - 9;
            } else {
                $sum += intval($number{$k}) * 2;
            }
        }
        for ($k = ($len_number % 2) ^ 1; $k < $len_number; $k += 2) {
            $sum += intval($number{$k});
        }
        return ($sum % 10) ? false : true;
    }

    /**
     * Validates a credit card number
     *
     * If a type is passed, the card will be checked against it.
     * This method only checks the number locally. No banks or payment
     * gateways are involved.
     * This method doesn't guarantee that the card is legitimate. It merely
     * checks the card number passes a mathematical algorithm.
     *
     * @param string $creditCard number (spaces and dashes tolerated)
     * @param string $cardType   type/brand of card (case insensitive)
     *               "MasterCard", "Visa", "AMEX", "AmericanExpress",
     *               "American Express", "Diners", "DinersClub", "Diners Club",
     *               "CarteBlanche", "Carte Blanche", "Discover", "JCB",
     *               "EnRoute", "Eurocard", "Eurocard/MasterCard".
     *
     * @return bool   TRUE if number is valid, FALSE otherwise
     * @access public
     * @static
     */
    static function number($creditCard, $cardType = null)
    {
        $cc = str_replace(array('-', ' '), '', $creditCard);
        if (($len = strlen($cc)) < 13
            || strspn($cc, '0123456789') != $len) {

            return false;
        }

        // Only apply the Luhn algorithm for cards other than enRoute
        // So check if we have a enRoute card now
        if (strlen($cc) != 15
            || (substr($cc, 0, 4) != '2014'
                && substr($cc, 0, 4) != '2149')) {

            if (!Validate_Finance_CreditCard::_luhn($cc)) {
                return false;
            }
        }

        if (is_string($cardType)) {
            return Validate_Finance_CreditCard::type($cc, $cardType);
        }

        return true;
    }


    /**
     * Validates the credit card number against a type
     *
     * This method only checks for the type marker. It doesn't
     * validate the card number. Some card "brands" share the same
     * numbering system, so checking the card type against any of the
     * sister brand will return the same result.
     *
     * For instance, if a $card is a MasterCard, type($card, 'EuroCard')
     * will also return true.
     *
     * @param string $creditCard number (spaces and dashes tolerated)
     * @param string $cardType   type/brand of card (case insensitive)
     *               "MasterCard", "Visa", "AMEX", "AmericanExpress",
     *               "American Express", "Diners", "DinersClub", "Diners Club",
     *               "CarteBlanche", "Carte Blanche", "Discover", "JCB",
     *               "EnRoute", "Eurocard", "Eurocard/MasterCard".
     *
     * @return bool   TRUE is type matches, FALSE otherwise
     * @access public
     * @static
     * @link http://www.beachnet.com/~hstiles/cardtype.html
     */
    static function type($creditCard, $cardType)
    {
        switch (strtoupper($cardType)) {
        case 'MASTERCARD':
        case 'EUROCARD':
        case 'EUROCARD/MASTERCARD':
            $regex = '5[1-5][0-9]{14}';
            break;
        case 'VISA':
        case 'VISA / VISA DEBIT':
            $regex = '4([0-9]{12}|[0-9]{15})';
            break;
        case 'AMEX':
        case 'AMERICANEXPRESS':
        case 'AMERICAN EXPRESS':
            $regex = '3[47][0-9]{13}';
            break;
        case 'DINERS':
        case 'DINERSCLUB':
        case 'DINERS CLUB':
        case 'CARTEBLANCHE':
        case 'CARTE BLANCHE':
            $regex = '3(0[0-5][0-9]{11}|[68][0-9]{12})';
            break;
        case 'DISCOVER':
            $regex = '6011[0-9]{12}';
            break;
        case 'JCB':
            $regex = '(3[0-9]{15}|(2131|1800)[0-9]{11})';
            break;
        case 'ENROUTE':
            $regex = '2(014|149)[0-9]{11}';
            break;
        case 'LASER':
            $regex = '(6304|6706|6771|6709)([0-9]{12,15})';
            break;
        case 'SOLO':
            $regex = '(6334[5-9]{1}[0-9]{1}|6767[0-9]{2}|3528[0-9]{2})([0-9]{10}|[0-9]{12}|[0-9]{13})';
            break;
        case 'SWITCH':
        case 'MAESTRO':
        case 'SWITCH/MAESTRO':
        case 'MAESTRO/SWITCH':
            $regex = '(49030[2-9]{1}|49033[5-9]{1}|49110[1-2]{1}|49117[4-9]{1}|49118[0-2]{1}|4936[0-9]{2}|564182|6333[1-4]{1}[0-9]{1}|6331[1-4]{1}[0-9]{1}|6759[0-9]{2})([0-9]{10}|[0-9]{12}|[0-9]{13})';
            break;
        default:
            return false;
        }
        $regex = '/^' . $regex . '$/';

        $cc = str_replace(array('-', ' '), '', $creditCard);
        return (bool)preg_match($regex, $cc);
    }


    /**
     * Validates a card verification value format
     *
     * This method only checks for the format. It doesn't
     * validate that the value is the one on the card.
     *
     * CVV is also known as
     *  - CVV2 Card Validation Value 2 (Visa)
     *  - CVC  Card Validation Code (MasterCard)
     *  - CID  Card Identification (American Express and Discover)
     *  - CIN  Card Identification Number
     *  - CSC  Card Security Code
     *
     * Important information regarding CVV:
     *    If you happen to have to store credit card information, you must
     *    NOT retain the CVV after transaction is complete. Usually this
     *    means you cannot store it in a database, not even in an encrypted
     *    form. See http://www.pcisecuritystandards.org/
     *
     * This method returns FALSE for card types that don't support CVV.
     *
     * @param string $cvv      value to verify
     * @param string $cardType type/brand of card (case insensitive)
     *               "MasterCard", "Visa", "AMEX", "AmericanExpress",
     *               "American Express", "Discover", "Eurocard/MasterCard",
     *               "Eurocard"
     *
     * @return bool   TRUE if format is correct, FALSE otherwise
     * @access public
     * @static
     * @link http://www.pcisecuritystandards.org/
     */
    static function cvv($cvv, $cardType)
    {
        switch (strtoupper($cardType)) {
        case 'MASTERCARD':
        case 'EUROCARD':
        case 'EUROCARD/MASTERCARD':
        case 'VISA':
        case 'VISA / VISA DEBIT':
        case 'DISCOVER':
        case 'LASER':
        case 'SWITCH':
        case 'MAESTRO':
        case 'SWITCH/MAESTRO':
        case 'MAESTRO/SWITCH':
        case 'SOLO':
            $digits = 3;
            break;
        case 'AMEX':
        case 'AMERICANEXPRESS':
        case 'AMERICAN EXPRESS':
            $digits = 4;
            break;
        default:
            return false;
        }

        if (strlen($cvv) == $digits
            && strspn($cvv, '0123456789') == $digits) {
            return true;
        }

        return false;
    }
}

?>
