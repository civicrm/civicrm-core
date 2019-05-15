<?php

/**
 * Class CRM_Core_CodeGen_Util_ArraySyntaxConverter
 *
 * @link      https://github.com/thomasbachem/php-short-array-syntax-converter
 *
 * @license   http://www.gnu.org/licenses/lgpl.html
 * @author    Thomas Bachem <mail@thomasbachem.com>
 */
class CRM_Core_CodeGen_Util_ArraySyntaxConverter {

  /**
   * @param string $code
   * @return string
   */
  public static function convert($code) {
    $tokens = token_get_all($code);

    // - - - - - PARSE CODE - - - - -
    $replacements = [];
    $offset = 0;
    for ($i = 0; $i < count($tokens); ++$i) {
      // Keep track of the current byte offset in the source code
      $offset += strlen(is_array($tokens[$i]) ? $tokens[$i][1] : $tokens[$i]);
      // T_ARRAY could either mean the "array(...)" syntax we're looking for
      // or a type hinting statement ("function(array $foo) { ... }")
      if (is_array($tokens[$i]) && $tokens[$i][0] === T_ARRAY) {
        // Look for a subsequent opening bracket ("(") to be sure we're actually
        // looking at an "array(...)" statement
        $isArraySyntax = FALSE;
        $subOffset = $offset;
        for ($j = $i + 1; $j < count($tokens); ++$j) {
          $subOffset += strlen(is_array($tokens[$j]) ? $tokens[$j][1] : $tokens[$j]);
          if (is_string($tokens[$j]) && $tokens[$j] == '(') {
            $isArraySyntax = TRUE;
            break;
          }
          elseif (!is_array($tokens[$j]) || $tokens[$j][0] !== T_WHITESPACE) {
            $isArraySyntax = FALSE;
            break;
          }
        }
        if ($isArraySyntax) {
          // Replace "array" and the opening bracket (including preceeding whitespace) with "["
          $replacements[] = [
            'start' => $offset - strlen($tokens[$i][1]),
            'end' => $subOffset,
            'string' => '[',
          ];
          // Look for matching closing bracket (")")
          $subOffset = $offset;
          $openBracketsCount = 0;
          for ($j = $i + 1; $j < count($tokens); ++$j) {
            $subOffset += strlen(is_array($tokens[$j]) ? $tokens[$j][1] : $tokens[$j]);
            if (is_string($tokens[$j]) && $tokens[$j] == '(') {
              ++$openBracketsCount;
            }
            elseif (is_string($tokens[$j]) && $tokens[$j] == ')') {
              --$openBracketsCount;
              if ($openBracketsCount == 0) {
                // Replace ")" with "]"
                $replacements[] = [
                  'start' => $subOffset - 1,
                  'end' => $subOffset,
                  'string' => ']',
                ];
                break;
              }
            }
          }
        }
      }
    }

    // - - - - - UPDATE CODE - - - - -
    // Apply the replacements to the source code
    $offsetChange = 0;
    foreach ($replacements as $replacement) {
      $code = substr_replace(
        $code,
        $replacement['string'],
        $replacement['start'] + $offsetChange,
        $replacement['end'] - $replacement['start']
      );
      $offsetChange += strlen($replacement['string']) - ($replacement['end'] - $replacement['start']);
    }

    return $code;
  }

}
