<?php

namespace Symfony3RRoek\Sniffs\Classes;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;

/**
 * Throws errors if multiple classes are defined in a single file.
 *
 * PHP version 7
 *
 * Symfony coding standard specifies: "Define one class per file;"
 *
 * @category PHP
 * @package  Symfony3-custom-coding-standard
 * @author   Roy Roek <contact@lemonways.fr>
 * @license  http://spdx.org/licenses/MIT MIT License
 * @link     https://github.com/RRoek/RRoek-Symfony3CustomCodingStandard
 */
class MultipleClassesOneFileSniff implements Sniff
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
     * The number of times the T_CLASS token is encountered in the file.
     *
     * @var int
     */
    protected $classCount = 0;
    /**
     * The current file this class is operating on.
     *
     * @var string
     */
    protected $currentFile;

    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @return array
     */
    public function register()
    {
        return [T_CLASS];
    }

    /**
     * Processes this test, when one of its tokens is encountered.
     *
     * @param File $phpcsFile All the tokens found in the document.
     * @param int  $stackPtr  The position of the current token in the stack passed in $tokens.
     *
     * @return void
     */
    public function process(File $phpcsFile, $stackPtr)
    {
        if ($this->currentFile !== $phpcsFile->getFilename()) {
            $this->classCount  = 0;
            $this->currentFile = $phpcsFile->getFilename();
        }

        $this->classCount++;

        if ($this->classCount > 1) {
            $phpcsFile->addError('Multiple classes defined in a single file', $stackPtr, 'multipleClassesOneFile');
        }

        return;
    }
}
