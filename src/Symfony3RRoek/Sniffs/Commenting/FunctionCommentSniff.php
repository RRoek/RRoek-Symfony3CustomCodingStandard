<?php

namespace Symfony3RRoek\Sniffs\Commenting;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Standards\PEAR\Sniffs\Commenting\FunctionCommentSniff as PearFunctionCommentSniff;

/**
 * Symfony3RRoek standard customization to PEARs FunctionCommentSniff.
 *
 * Verifies that :
 * <ul>
 *   <li>
 *     There is a &#64;return tag if a return statement exists inside the method
 *   </li>
 * </ul>
 *
 * PHP version 7
 *
 * @category PHP
 * @package  Symfony3-custom-coding-standard
 * @author   Roy Roek <contact@lemonways.fr>
 * @license  http://spdx.org/licenses/MIT MIT License
 * @link     http://pear.php.net/package/PHP_CodeSniffer
 */
class FunctionCommentSniff extends PearFunctionCommentSniff
{
    /**
     * Process the return comment of this function comment.
     *
     * @param File $phpcsFile    The file being scanned.
     * @param int  $stackPtr     The position of the current token in the stack passed in $tokens.
     * @param int  $commentStart The position in the stack where the comment started.
     *
     * @return void
     */
    protected function processReturn(File $phpcsFile, $stackPtr, $commentStart)
    {

        if ($this->isInheritDoc($phpcsFile, $stackPtr)) {
            return;
        }

        $tokens = $phpcsFile->getTokens();

        // Only check for a return comment if a non-void return statement exists
        if (isset($tokens[$stackPtr]['scope_opener'])) {
            // Start inside the function
            $start = $phpcsFile->findNext(
                T_OPEN_CURLY_BRACKET,
                $stackPtr,
                $tokens[$stackPtr]['scope_closer']
            );
            for ($i = $start; $i < $tokens[$stackPtr]['scope_closer']; ++$i) {
                // Skip closures
                if ($tokens[$i]['code'] === T_CLOSURE) {
                    $i = $tokens[$i]['scope_closer'];
                    continue;
                }

                // Found a return not in a closure statement
                // Run the check on the first which is not only 'return;'
                if ($tokens[$i]['code'] === T_RETURN
                    && $this->isMatchingReturn($tokens, $i)
                ) {
                    parent::processReturn($phpcsFile, $stackPtr, $commentStart);
                    break;
                }
            }
        }
    }

    /**
     * @param File $phpcsFile
     * @param int  $stackPtr
     * @param int  $commentStart
     */
    protected function processWhitespace(File $phpcsFile, int $stackPtr, int $commentStart)
    {
        $tokens = $phpcsFile->getTokens();
        $before = $phpcsFile->findPrevious(T_WHITESPACE, ($commentStart - 1), null, true);

        $startLine = $tokens[$commentStart]['line'];
        $prevLine  = $tokens[$before]['line'];

        $found = $startLine - $prevLine - 1;

        // Skip for class opening
        if ($found !== 1 && !($found === 0 && $tokens[$before]['type'] === 'T_OPEN_CURLY_BRACKET')) {
            if ($found < 0) {
                $found = 0;
            }

            $error = 'Expected 1 blank line before docblock; %s found';
            $data  = [$found];
            $fix   = $phpcsFile->addFixableError($error, $commentStart, 'SpacingBeforeDocblock', $data);

            if ($fix === true) {
                if ($found > 1) {
                    $phpcsFile->fixer->beginChangeset();
                    for ($i = ($before + 1); $i < ($commentStart - 1); $i++) {
                        $phpcsFile->fixer->replaceToken($i, '');
                    }

                    $phpcsFile->fixer->replaceToken($i, $phpcsFile->eolChar);
                    $phpcsFile->fixer->endChangeset();
                } else {
                    // Try and maintain indentation.
                    if ($tokens[($commentStart - 1)]['code'] === T_WHITESPACE) {
                        $phpcsFile->fixer->addNewlineBefore($commentStart - 1);
                    } else {
                        $phpcsFile->fixer->addNewlineBefore($commentStart);
                    }
                }
            }
        }
    }

    /**
     * Is the comment an inheritdoc?
     *
     * @param File $phpcsFile The file being scanned.
     * @param int  $stackPtr  The position of the current token in the stack passed in $tokens.
     *
     * @return boolean True if the comment is an inheritdoc
     */
    protected function isInheritDoc(File $phpcsFile, $stackPtr)
    {
        $tokens = $phpcsFile->getTokens();

        $start = $phpcsFile->findPrevious(T_DOC_COMMENT_OPEN_TAG, $stackPtr - 1);
        $end   = $phpcsFile->findNext(T_DOC_COMMENT_CLOSE_TAG, $start);

        $content = $phpcsFile->getTokensAsString($start, ($end - $start));

        return preg_match('#{@inheritdoc}#i', $content) === 1;
    }

    /**
     * Process the function parameter comments.
     *
     * @param File $phpcsFile    The file being scanned.
     * @param int  $stackPtr     The position of the current token in the stack passed in $tokens.
     * @param int  $commentStart The position in the stack where the comment started.
     *
     * @return void
     */
    protected function processParams(
        File $phpcsFile,
        $stackPtr,
        $commentStart
    ) {
        $tokens = $phpcsFile->getTokens();

        if ($this->isInheritDoc($phpcsFile, $stackPtr)) {
            return;
        }

        $this->processWhitespace($phpcsFile, $stackPtr, $commentStart);

        parent::processParams($phpcsFile, $stackPtr, $commentStart);
    }

    /**
     * Is the return statement matching?
     *
     * @param array $tokens    Array of tokens
     * @param int   $returnPos Stack position of the T_RETURN token to process
     *
     * @return boolean True if the return does not return anything
     */
    protected function isMatchingReturn($tokens, $returnPos)
    {
        do {
            $returnPos++;
        } while ($tokens[$returnPos]['code'] === T_WHITESPACE);

        return $tokens[$returnPos]['code'] !== T_SEMICOLON;
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
        if (false === $commentEnd = $phpcsFile
                ->findPrevious(
                    [
                        T_COMMENT,
                        T_DOC_COMMENT,
                        T_CLASS,
                        T_FUNCTION,
                        T_OPEN_TAG,
                    ],
                    ($stackPtr - 1)
                )
        ) {
            return;
        }

        $tokens = $phpcsFile->getTokens();
        $code   = $tokens[$commentEnd]['code'];

        $name = $phpcsFile->getDeclarationName($stackPtr);

        $commentRequired = strpos($name, 'test') !== 0 && $name !== 'setUp';

        if (($code === T_COMMENT && !$commentRequired)
            || ($code !== T_DOC_COMMENT && !$commentRequired)
        ) {
            return;
        }

        parent::process($phpcsFile, $stackPtr);
    }
}
