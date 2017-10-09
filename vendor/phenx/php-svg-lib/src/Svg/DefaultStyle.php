<?php
/**
 * @package php-svg-lib
 * @link    http://github.com/PhenX/php-svg-lib
 * @author  Fabien M�nager <fabien.menager@gmail.com>
 * @license http://www.gnu.org/copyleft/lesser.html GNU Lesser General Public License
 */

namespace Svg;

class DefaultStyle extends Style
{
    public $color = '';
    public $opacity = 1.0;
    public $display = 'inline';

    public $fill = 'black';
    public $fillOpacity = 1.0;
    public $fillRule = 'nonzero';

    public $stroke = 'none';
    public $strokeOpacity = 1.0;
    public $strokeLinecap = 'butt';
    public $strokeLinejoin = 'miter';
    public $strokeMiterlimit = 4;
    public $strokeWidth = 1.0;
    public $strokeDasharray = 0;
    public $strokeDashoffset = 0;
}