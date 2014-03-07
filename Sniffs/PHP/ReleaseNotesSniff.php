<?php
/**
 * PHPCompatibility_Sniffs_PHP_ReleaseNotesSniff.
 *
 * PHP version 5.5
 *
 * @category  PHP
 * @package   PHPCompatibility
 * @author    Wim Godden <wim.godden@cu.be>
 * @copyright 2012 Cu.be Solutions bvba
 */

/**
 * PHPCompatibility_Sniffs_PHP_ReleaseNotesSniff.
 *
 * @category  PHP
 * @package   PHPCompatibility
 * @author    Wim Godden <wim.godden@cu.be>
 * @version   1.1.0
 * @copyright 2012 Cu.be Solutions bvba
 */
class PHPCompatibility_Sniffs_PHP_ReleaseNotesSniff extends Generic_Sniffs_PHP_ForbiddenFunctionsSniff
{

    /**
     * A list of functions that have issues noted in the Release Notes
     *
     * The array lists : version number with 0 (deprecated) or 1 (forbidden) and an alternative function.
     * If no alternative exists, it is NULL. IE, the function should just not be used.
     *
     * @var array(string => array(string => int|string|null))
     */
    protected $forbiddenFunctions = array(
                                        'ob_start' => array(
                                            '5.4' => false,
                                            '5.5' => false,
                                            'alternative' => 'the updated convention'
                                        ),
                                        'array_combine' => array(
                                            '5.4' => false,
                                            '5.5' => false,
                                            'alternative' => 'the updated convention'
                                        ),
                                    );

    /**
     * If true, an error will be thrown; otherwise a warning.
     *
     * @var bool
     */
    public $error = false;

    /**
     * Processes this test, when one of its tokens is encountered.
     *
     * @param PHP_CodeSniffer_File $phpcsFile The file being scanned.
     * @param int                  $stackPtr  The position of the current token in
     *                                        the stack passed in $tokens.
     *
     * @return void
     */
    public function process(PHP_CodeSniffer_File $phpcsFile, $stackPtr)
    {
        $tokens = $phpcsFile->getTokens();

        $ignore = array(
                T_DOUBLE_COLON,
                T_OBJECT_OPERATOR,
                T_FUNCTION,
                T_CONST,
                T_USE,
                T_NS_SEPARATOR,
        );

        $prevToken = $phpcsFile->findPrevious(T_WHITESPACE, ($stackPtr - 1), null, true);
        if (in_array($tokens[$prevToken]['code'], $ignore) === true) {
            // Not a call to a PHP function.
            return;
        }

        $function = strtolower($tokens[$stackPtr]['content']);
        $pattern  = null;

        if ($this->patternMatch === true) {
            $count   = 0;
            $pattern = preg_replace(
                    $this->forbiddenFunctionNames,
                    $this->forbiddenFunctionNames,
                    $function,
                    1,
                    $count
            );

            if ($count === 0) {
                return;
            }

            // Remove the pattern delimiters and modifier.
            $pattern = substr($pattern, 1, -2);
        } else {
            if (in_array($function, $this->forbiddenFunctionNames) === false) {
                return;
            }
        }

        $this->addError($phpcsFile, $stackPtr, $function, $pattern);

    }//end process()

    /**
     * Generates the error or wanrning for this sniff.
     *
     * @param PHP_CodeSniffer_File $phpcsFile The file being scanned.
     * @param int                  $stackPtr  The position of the forbidden function
     *                                        in the token array.
     * @param string               $function  The name of the forbidden function.
     * @param string               $pattern   The pattern used for the match.
     *
     * @return void
     */
    protected function addError($phpcsFile, $stackPtr, $function, $pattern=null)
    {
        if ($pattern === null) {
            $pattern = $function;
        }

        $error = '';

        $this->error = false;
        foreach ($this->forbiddenFunctions[$pattern] as $version => $forbidden) {
            if (
                is_null(PHP_CodeSniffer::getConfigData('testVersion'))
                ||
                (
                    !is_null(PHP_CodeSniffer::getConfigData('testVersion'))
                    &&
                    version_compare(PHP_CodeSniffer::getConfigData('testVersion'), $version) >= 0
                )
            ) {
                if ($version != 'alternative') {
                    if ($forbidden === true) {
                        $this->error = true;
                        $error .= 'forbidden';
                    } else {
                        $error .= 'different';
                    }
                    $error .=  ' in PHP version ' . $version . ' and ';
                }
            }
        }
        if (strlen($error) > 0) {
            $error = 'The use of function ' . $function . ' is ' . $error;
            $error = substr($error, 0, strlen($error) - 5);

            if ($this->forbiddenFunctions[$pattern]['alternative'] !== null) {
                $error .= '; use ' . $this->forbiddenFunctions[$pattern]['alternative'] . ' instead';
            }

            if ($this->error === true) {
                $phpcsFile->addError($error, $stackPtr);
            } else {
                $phpcsFile->addWarning($error, $stackPtr);
            }
        }

    }//end addError()

}//end class
