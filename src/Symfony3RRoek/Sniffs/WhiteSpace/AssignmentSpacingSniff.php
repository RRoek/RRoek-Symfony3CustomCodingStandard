<?php

namespace Symfony3RRoek\Sniffs\WhiteSpace;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Util\Tokens;

/**
 * AssignmentSpacingSniff.
 *
 * Throws warnings if an assignment operator isn't surrounded with whitespace.
 *
 * PHP version 7
 *
 * @category PHP
 * @package  Symfony3-custom-coding-standard
 * @author   Roy Roek <contact@lemonways.fr>
 * @license  http://spdx.org/licenses/MIT MIT License
 * @link     https://github.com/RRoek/RRoek-Symfony3CustomCodingStandard
 */
class AssignmentSpacingSniff implements Sniff
{
    /**
     * A list of tokenizers this sniff supports.
     *
     * @var array
     */
    public $supportedTokenizers = [
        'PHP',
    ];

    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @return array
     */
    public function register()
    {
        return Tokens::$assignmentTokens;
    }

    /**
     * Processes this test, when one of its tokens is encountered.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int  $stackPtr  The position of the current token in the stack passed in $tokens.
     *
     * @return void
     */
    public function process(File $phpcsFile, $stackPtr)
    {
        $tokens = $phpcsFile->getTokens();

        if (($tokens[$stackPtr - 1]['code'] !== T_WHITESPACE
                || $tokens[$stackPtr + 1]['code'] !== T_WHITESPACE)
            && $tokens[$stackPtr - 1]['content'] !== 'strict_types'
        ) {
            $phpcsFile->addError(
                'Add a single space around assignment operators',
                $stackPtr,
                'Invalid'
            );
        }
    }
}
