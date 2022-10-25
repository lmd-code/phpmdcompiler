<?php

/**
 * PHP Markdown Compiler
 * (c) LMD, 2022
 * https://github.com/lmd-code/phpmarkdowncompiler
 *
 * @version 0.0.1
 * @license MIT
 */

declare(strict_types=1);

namespace lmdcode\phpmdcomp;

/**
 * Compiler Markdown
 */
class PhpMarkdownCompiler
{
    /**
     * Version number
     */
    public const VERSION = '0.0.1';

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
     * Currently opened markdown files
     * @var string[]
     */
    private $openFiles = [];

    /**
     * Compiled markdown contents
     *
     * @var string
     */
    private $content;

    /**
     * Table of contents
     * @var string
     */
    private $toc;

    /**
     * Last error message
     * @var string
     */
    private $errorMsg;

    /**
     * Compiler has been run
     * @var boolean
     */
    private $compiled = false;

    /**
     * Root path to compiler root directory
     *
     * @var string
     */
    private static $ROOT_PATH;

    /**
     * Regex to match Table of Contents Markdown syntax
     * @var string
     */
    private static $regex_toc_syntax = '/\n+```toc(\s*?)```\n+/is';

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
                $this->setInput($input);
            } catch (\Exception $e) {
                $errors .= "<br>\n- " . $e->getMessage();
            }

            try {
                $this->setOutput($output);
            } catch (\Exception $e) {
                $errors .= "<br>\n- " . $e->getMessage();
            }

            if ($errors !== '') {
                throw new \Exception("The following errors were found:" . $errors);
            }
        }
    }

    /**
     * Get compiled markdown content
     *
     * @return string
     */
    public function getContent(): string
    {
        return $this->content;
    }

    /**
     * Get Table of Contents
     *
     * @return string
     */
    public function getToc(): string
    {
        return $this->toc;
    }

    /**
     * Get source input file path
     *
     * @return string
     */
    public function getInputFilePath(): string
    {
        return $this->inputPath . '/' . $this->inputFile;
    }

    /**
     * Get compiled output file path
     *
     * @return string
     */
    public function getOutputFilePath(): string
    {
        return $this->outputPath . '/' . $this->outputFile;
    }

    /**
     * Set input source file
     *
     * @param string $input Absolute path to input source file
     *
     * @return void
     */
    public function setInput(string $input): void
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
    }

    /**
     * Set compiled output file
     *
     * @param string $output Absolute path to compiled output file
     *
     * @return void
     */
    public function setOutput(string $output): void
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
    }

    /**
     * Run Markdown Compiler
     *
     * @param bool $adjustHeadings Adjust headings down by one level after the main heading.
     *                             E.g. `#` => `##` (default: false)
     * @param bool $insToc         Insert Table of Contents (default: false)
     *
     * @return string
     */
    public function runCompiler(bool $adjustHeadings = false, bool $insToc = false): string
    {
        $this->content = self::parseHeadings($this->compile($this->inputFile), $adjustHeadings);

        $this->toc = self::generateToc($this->content);

        if ($insToc) {
            $this->content = $this->insertToc($this->content);
        } else {
            // remove any ToC Markdown syntax
            $this->content = preg_replace(self::$regex_toc_syntax, "\n\n", $this->content);
        }

        $this->compiled = true;

        return $this->content;
    }

    /**
     * Save compiled output file
     *
     * @return bool
     */
    public function saveFile(): bool
    {
        if (!$this->compiled) {
            throw new \Exception("Can not save output file before input has been compiled.");
        }

        $content = trim($this->content) . "\n"; // always new line at end of MD files

        if (file_put_contents($this->getOutputFilePath(), $content, LOCK_EX) === false) {
            throw new \Exception("Could not write to output file.");
        }

        return true;
    }

    /**
     * Get last error message
     *
     * @return string
     */
    public function getLastError(): string
    {
        return $this->errorMsg;
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
        $src = preg_replace("/\r\n/s", "\n", $src);

        // Normalise horizontal whitespace
        $src = preg_replace("/\t/s", "    ", $src);

        // If there are no includes return the origtinal source
        if (
            !preg_match_all(
                '/(?<inc>:\[(?<text>[^\]]+?)\]\((?<file>[^\) ]+?)\)\h*?)/s',
                $src,
                $foundIncs,
                PREG_SET_ORDER
            )
        ) {
            return $src;
        }

        // This file is open, do not include inside itself!
        array_push($this->openFiles, $srcFile);

        foreach ($foundIncs as $inc) {
            $content  = '';
            $file = trim($inc['file']);

            // Only recursively get contents if the file exists and isn't already open
            if (file_exists($this->inputPath . '/' . $file) && !in_array($file, $this->openFiles)) {
                $content = "\n\n" . $this->compile($file) . "\n\n";
            }

            // Add to imported content
            $src = preg_replace('/\n*?' . preg_quote($inc['inc'], '\n*?/') . '/s', $content, $src, 1);
        }

        // Remove item from array again (it can be included again later)
        if (($idx = array_search($srcFile, $this->openFiles)) !== false) {
            unset($this->openFiles[$idx]);
            $this->openFiles = array_values($this->openFiles); // reindex
        }

        return trim($src);
    }

    /**
     * Insert Table of Contents in to content
     *
     * If there is a `toc` (markdown syntax) token it will insert in that location,
     * otherwise it will be inserted after the first/main heading.
     *
     * @param string $content Markdown content
     *
     * @return string
     */
    private function insertToc(string $content): string
    {
        $toc = "\n\n" . $this->toc . "\n\n";

        if (preg_match(self::$regex_toc_syntax, $content)) {
            return preg_replace(self::$regex_toc_syntax, $toc, $content);
        }

        return preg_replace('/^# [^\n]+\n+/', $toc, $content);
    }

    /**
     * Parse Headings
     *
     * @param string $content        Markdown content
     * @param bool   $adjustHeadings Adjust heading levels (default: false)
     *
     * @return string
     */
    private static function parseHeadings(string $content, bool $adjustHeadings = false): string
    {
        $regex_match_heading_parts = '/^(?<level>#+)\s*?(?<text>[^$]+)$/';

        preg_match_all('/^(?<hs>#+ [^\n]+)\n?/im', $content, $findHeadings);

        foreach ($findHeadings['hs'] as $key => $heading) {
            preg_match($regex_match_heading_parts, trim($heading), $matches);

            $level = intval(strlen($matches['level'])); // current heading level
            $htext = trim($matches['text']); // heading text
            // @TODO Needs better solution - must accoumt for existing ID
            $id = '#h_' . $key; // heading id

            // Headings after the first item get shifted down by 1 level (1 => 2, 2 => 3, etc)
            if ($adjustHeadings) {
                $level = ($key > 0) ? $level + 1 : $level;
            }

            // Replace heading with approprate level and ID/anchor
            $newHeading = str_repeat('#', $level) . ' ' . $htext . ' {' . $id . '}';

            $regex_match_heading = preg_quote(trim($heading), '/');
            $content = preg_replace('/^' . $regex_match_heading . '$/m', $newHeading, $content, 1);
        }

        return trim($content);
    }

    /**
     * Generate and return Table of Contents
     *
     * @param string $content Markdown content
     *
     * @return string
     */
    private static function generateToc(string $content): string
    {
        $toc = '';
        $regex_match_heading_parts = '/^(?<level>#+)\s*?(?<text>[^{]+)\s*?\{(?<id>[^}]+)\}$/';

        $numMatches = preg_match_all('/^# /m', $content, $countMatches); // number of H1 headings
        $indentDiff = ($numMatches !== false && $numMatches > 1) ? 1 : 2; // h2 => 0

        preg_match_all('/^(?<hs>#+ [^\n]+)\n?/im', $content, $findHeadings);

        foreach ($findHeadings['hs'] as $key => $heading) {
            preg_match($regex_match_heading_parts, trim($heading), $matches);

            $level = intval(strlen($matches['level'])); // current heading level
            $htext = trim($matches['text']); // heading text
            $id = trim($matches['id']); // heading id

            // Add to ToC, ignore the first heading and indent appropriately
            if ($key > 0) {
                $toc .= str_repeat(' ', ($level - $indentDiff) * 4) . "- [" . $htext . "]($id)\n";
            }
        }

        return trim($toc);
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
