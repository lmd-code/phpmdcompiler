<?php

/**
 * PHP Markdown Compiler
 * (c) LMD, 2022
 * https://github.com/lmd-code/phpmdcompiler
 *
 * @version 0.0.4
 * @license MIT
 */

declare(strict_types=1);

namespace lmdcode\phpmdcompiler;

/**
 * Compiler Markdown
 */
class PhpMarkdownCompiler
{
    /**
     * Version number
     */
    public const VERSION = '0.0.4';

    /**
     * Absolute path to source input folder
     * @var string
     */
    private $inputPath;

    /**
     * Absolute path to compiled output folder
     * @var string
     */
    private $outputPath;

    /**
     * Source input file name
     * @var string
     */
    private $inputFile;

    /**
     * Compiled output file name
     * @var string
     */
    private $outputFile;

    /**
     * Currently opened Markdown files
     * @var string[]
     */
    private $openFiles = [];

    /**
     * Adjust heading levels
     * @var boolean
     */
    private $adjustHeadings = false;

    /**
     * Insert Table of Contents
     * @var boolean
     */
    private $insertToc = false;

    /**
     * Compiled output Markdown
     *
     * @var string
     */
    private $mdOut;

    /**
     * Table of Contents Markdown
     * @var string
     */
    private $mdToc;

    /**
     * Path to compiler root/base/install directory
     *
     * @var string
     */
    private static $ROOT_PATH;

    /**
     * Regex to match file include syntax
     * @var string
     */
    private static $re_inc_syntax = '/^(?<inc>:\[(?<text>[^\]]+?)\]\((?<file>[^\) ]+?)\)\h*?)$/ms';

    /**
     * Regex to match Table of Contents Markdown token syntax
     * @var string
     */
    private static $re_toc_syntax = '/\n+\[\[_TOC_\]\]\n+/is';

    /**
     * Regex to match the parts of a heading (level, text and optional ID)
     * @var string
     */
    private static $re_heading = '/^(?<level>#+)\s*?(?<text>[^{]+?)\s*?(\{(?<id>[^}]+)\})?$/';

    /**
     * Constructor
     *
     * @param string $input  Absolute path to input source file
     * @param string $output Absolute path to compiled output file
     *
     * @return void
     */
    public function __construct(string $input = '', string $output = '')
    {
        self::$ROOT_PATH = self::normalisePath(dirname(__DIR__));

        if ($input !== '' || $output !== '') {
            $errors = '';
            try {
                $input = $this->setInputFile($input);
            } catch (\Exception $e) {
                $errors .= "<br>\n- " . $e->getMessage();
            }

            try {
                $output = $this->setOutputFile($output);
            } catch (\Exception $e) {
                $errors .= "<br>\n- " . $e->getMessage();
            }

            try {
                // Check that the source and output are not the same file
                if ($input === $output) {
                    throw new \Exception(
                        "The output file must not be identical to the input file!<br>\n"
                        . "INPUT FILE:  " . $input . "<br>\nOUTPUT FILE: " . $output
                    );
                }
            } catch (\Exception $e) {
                $errors .= "<br>\n- " . $e->getMessage();
            }

            if ($errors !== '') {
                throw new \Exception("The following errors were found:" . $errors);
            }
        }
    }

    /**
     * Normalise, validate and set input source file
     *
     * Returns the validated/normalised path
     *
     * @param string $input Absolute path to input source file
     *
     * @return string
     */
    public function setInputFile(string $input): string
    {
        $input = self::normalisePath($input);

        $pathInfo = pathinfo($input);

        // Check if the file has a .md extension
        if (empty($pathInfo['extension']) || $pathInfo['extension'] !== 'md') {
            throw new \Exception("Input file must have a '.md' file extension.");
        }

        // Check if the file exists
        if (!file_exists($input)) {
            throw new \Exception("Input file ($input) does not exist. Please check the path and file name.");
        }

        $this->inputFile = $pathInfo['basename'];
        $this->inputPath = $pathInfo['dirname'];

        return $input;
    }

    /**
     * Normalise, validate and set output source file
     *
     * Returns the validated/normalised path
     *
     * @param string $output Absolute path to compiled output file
     *
     * @return string
     */
    public function setOutputFile(string $output): string
    {
        $output = self::normalisePath($output);

        $pathInfo = pathinfo($output);

        // Check if the file has a .md extension
        if (empty($pathInfo['extension']) || $pathInfo['extension'] !== 'md') {
            throw new \Exception("Output file must have a '.md' file extension.");
        }

        // Check if the output directly exists
        if (!is_dir($pathInfo['dirname'])) {
            throw new \Exception(
                "Output directory ({$pathInfo['dirname']}) does not exist. Please fix or create it."
            );
        }

        $this->outputFile = $pathInfo['basename'];
        $this->outputPath = $pathInfo['dirname'];

        return $output;
    }

    /**
     * Get Table of Contents Markdown
     *
     * @return string
     */
    public function getTableOfContents(): string
    {
        return $this->mdToc;
    }

    /**
     * Save compiled output file
     *
     * @return bool
     */
    public function saveFile(): bool
    {
        if ($this->mdOut === null) {
            throw new \Exception("Can not save output file before input has been compiled.");
        }

        $content = trim($this->mdOut) . "\n"; // always new line at end of MD files

        $outputFilePath = $this->outputPath . '/' . $this->outputFile;
        if (file_put_contents($outputFilePath, $content, LOCK_EX) === false) {
            throw new \Exception("Could not write to output file.");
        }

        return true;
    }

    /**
     * Run Markdown Compiler
     *
     * @param bool $adjustHeadings Adjust headings down by one level after the main heading.
     *                             E.g. `#` => `##` (default: false)
     * @param bool $insertToc      Insert Table of Contents (default: false)
     *
     * @return string
     */
    public function runCompiler(bool $adjustHeadings = false, bool $insertToc = false): string
    {
        $this->adjustHeadings = $adjustHeadings;
        $this->insertToc = $insertToc;

        // Get/prepare compiled output
        $compiledOutput = $this->compile($this->inputFile);

        // Generate and store Table of Contents (even if not inserted)
        $this->mdToc = self::generateTableOfContents($compiledOutput);

        if ($insertToc) {
            // Return with Table of Contents inserted
            $compiledOutput = $this->insertTableOfContents($compiledOutput);
        }

        // Remove any remaining 'toc' Markdown tokens
        $compiledOutput = preg_replace(self::$re_toc_syntax, "\n\n", $compiledOutput);

        $this->mdOut = $compiledOutput; // make available to saveFile()

        // Return without Table of Contents inserted
        return $compiledOutput;
    }

    /**
     * Compile Markdown Files
     *
     * Recursively compiles files.
     *
     * @param string $srcFile Filename of current file
     *
     * @return string
     */
    private function compile(string $srcFile): string
    {
        // Get file contents
        $src = file_get_contents($this->inputPath . '/' . $srcFile);

        // Normalise vertical whitespace
        $src = preg_replace("/\R/su", "\n", $src);

        // Normalise tabs
        $src = preg_replace("/\t/s", "    ", $src);

        // Parse headings (levels are never adjusted on the top-level document)
        $src = $this->parseHeadings(
            $src,
            (count($this->openFiles) > 0) ? $this->adjustHeadings : false
        );

        // Find includes
        $haveIncs = preg_match_all(self::$re_inc_syntax, $src, $foundIncs, PREG_SET_ORDER);
        if (!$haveIncs || $haveIncs === 0) {
            return trim($src); // if there are no includes return the original source
        }

        // This file is open, do not include inside itself!
        array_push($this->openFiles, $srcFile);

        foreach ($foundIncs as $inc) {
            $file = trim($inc['file']);
            $regex_inc = '\n*?' . preg_quote($inc['inc'], '/') . '\n*?';

            // Only recursively get contents if the file exists and isn't already open
            if (file_exists($this->inputPath . '/' . $file) && !in_array($file, $this->openFiles)) {
                $content = "\n\n" . $this->compile($file) . "\n@INC\n"; // @INC = spacing token
                $src = preg_replace('/' . $regex_inc . '/s', $content, $src, 1);
            } else {
                // Remove non-existant includes
                $src = preg_replace('/' . $regex_inc . '/s', "", $src, 1);
            }
        }

        // Remove item from array again (it can be included again later)
        if (($idx = array_search($srcFile, $this->openFiles)) !== false) {
            unset($this->openFiles[$idx]);
            $this->openFiles = array_values($this->openFiles); // reindex
        }

        // Replace @INC spacing tokens
        $src = preg_replace('/\n+@INC\n*/', "\n\n", $src);

        return trim($src);
    }

    /**
     * Parse Headings
     *
     * Conditionally adjust levels/insert IDs
     *
     * @param string $content        Markdown content
     * @param bool   $adjustHeadings Adjust heading levels (default: false)
     *
     * @return string
     */
    private function parseHeadings(string $content, bool $adjustHeadings = false): string
    {
        preg_match_all('/^(?<hs>#+ [^\n]+)\n?/im', $content, $findHeadings);

        foreach ($findHeadings['hs'] as $heading) {
            $haveHeading = preg_match(self::$re_heading, trim($heading), $matches);
            if ($haveHeading !== 1) {
                continue; // skip if heading format doesn't match
            }

            $level = intval(strlen($matches['level'])); // current heading level
            $htext = trim($matches['text']); // heading text
            $id = !empty($matches['id']) ? trim($matches['id']) : '';

            // heading id
            if ($id !== '') {
                $id = ' {' . trim($matches['id']) . '}'; // existing ID (always keep)
            } else {
                if ($this->insertToc) {
                    // Only generate an ID if inserting a Table of Contents
                    $id = ' {#' . self::generateHeadingId($htext) . '}';
                }
            }

            // Headings after the first item get shifted down by 1 level (1 => 2, 2 => 3, etc)
            if ($adjustHeadings) {
                $level = $level + 1;
            }

            // Replace heading with approprate level and ID/anchor (which can be empty)
            $newHeading = str_repeat('#', $level) . ' ' . $htext . $id;

            $regex_oldHeading = preg_quote(trim($heading), '/');
            $content = preg_replace('/^' . $regex_oldHeading . '$/m', $newHeading, $content, 1);
        }

        return trim($content);
    }

    /**
     * Insert Table of Contents in to compiled document
     *
     * Will either insert where there is a `toc` Markdown token, or if there is no token,
     * it will insert after the main heading.
     *
     * Where token exists, it will only insert into the first occurance it finds.
     *
     * @param string $content Markdown content
     *
     * @return string
     */
    private function insertTableOfContents(string $content): string
    {
        $toc = "\n\n" . $this->mdToc . "\n\n";

        if (preg_match(self::$re_toc_syntax, $content) === 1) {
            // Insert at first 'toc' Markdown token
            return preg_replace(self::$re_toc_syntax, $toc, $content, 1);
        }

        // Insert after main heading
        return preg_replace('/^(# [^\n]+)\n+/', '\\1' . $toc, $content, 1);
    }

    /**
     * Generate and return Table of Contents
     *
     * @param string $content Markdown content
     *
     * @return string
     */
    private static function generateTableOfContents(string $content): string
    {
        $numHeadings = preg_match_all('/^(?<hs>#+ [^\n]+)\n?/im', $content, $findHeadings);
        if (!$numHeadings || $numHeadings < 1) {
            return ''; // no headings found
        }

        $toc = '';
        $numMatches = preg_match_all('/^# /m', $content, $countMatches); // number of '#' headings
        $indentDiff = ($numMatches !== false && $numMatches > 1) ? 1 : 2; // '##' => 0

        foreach ($findHeadings['hs'] as $key => $heading) {
            if ($key === 0) {
                continue; // skip the first heading
            }

            $haveHeading = preg_match(self::$re_heading, trim($heading), $match);
            if ($haveHeading !== 1) {
                continue; // skip if heading format doesn't match
            }

            $level = intval(strlen($match['level'])); // heading level
            $htext = trim($match['text']); // heading text
            $id = (!empty($match['id'])) ? trim($match['id']) : ''; // heading id

            // Add to ToC and indent appropriately (only link if it has an ID/anchor)
            $toc .= str_repeat(' ', ($level - $indentDiff) * 4) . "- "
            . ($id !== '' ? "[$htext]($id)" : $htext) . "\n";
        }

        return trim($toc);
    }

    /**
     * Generate a heading ID from its text
     *
     * Valid IDs consist of lowercase alphanumeric ascii characters plus dashes.
     *
     * Any non-valid character is removed, and spaces are converted to dashes.
     *
     * @param string $heading  Heading text
     *
     * @return string
     */
    private static function generateHeadingId($heading): string
    {
        return str_replace(' ', '-', preg_replace('/[^-a-z0-9 ]/', '', strtolower($heading)));
    }

    /**
     * Normalise a file path
     *
     * @param string $path File path to normalise
     *
     * @return string
     */
    private static function normalisePath(string $path)
    {
        // Convert slashes to forward slashes
        $path = rtrim(str_replace(DIRECTORY_SEPARATOR, '/', trim($path)), '/');

        // Start from root directory if shortcut found
        if (mb_stripos($path, '$ROOT/') === 0) {
            $path = str_ireplace('$ROOT/', self::$ROOT_PATH . '/', ltrim($path, '/'));
        }

        return $path;
    }
}
