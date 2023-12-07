# PHP Markdown Compiler

Compile multiple Markdown (`*.md`) files into a single document using a special *file include* Markdown syntax.

Optionally include a Table of Contents generated from the headers in the compiled document.

**NB:** This is not a *parser*, it will not convert Markdown content into another markup syntax.

- [Markdown Syntax](#markdown-syntax)
    - [File Includes](#file-includes)
    - [Table of Contents](#table-of-contents)
- [Running the Compiler from the Command Line](#running-the-compiler-from-the-command-line)
    - [Compiler Options](#compiler-options)
    - [Run the Demo](#run-the-demo)
- [Running the Compiler from a Server-side Script](#running-the-compiler-from-a-server-side-script)
- [Class Reference](#class-reference)
    - [Methods](#methods)
    - [Basic Example](#basic-example)

## Markdown Syntax

### File Includes

The Markdown syntax for including a file is the same as creating a link, but with a colon (`:`) prefixed.

However, unlike links, each include must appear on a new line, be the only item on that line, and have a blank line before and after it.

The link title can contain any text you like, for example a useful hint to the included file's contents (e.g. it's main title, or a version of it).

```markdown
# Document Title

Some text.. 

:[Heading A](filename-a.md)

:[Heading B](filename-b.md)

More text
```

### Table of Contents

There are two methods to include a table of contents.

The easiest method is to just let the compiler insert it after the main heading.

However, if you would prefer to have some control over its placement, then use the Markdown syntax token `[[_TOC_]]` (case-insensitive) method instead. The token must appear on a new line, be the only item on that line, and have a blank line before and after it.

````markdown
# Document Title

Some intro text…

The Table of Contents Markdown syntax is borrowed from Gitlab Flavoured Markdown.

[[_TOC_]]

The rest of the document…
````

## Running the Compiler from the Command Line

You need to have at least PHP 7.4 installed on your system.

1. Download and unzip the `phpmdcompiler` repository to a location of your choice.
2. In the command line tool of your choice, navigate to the folder you just downloaded/unzipped.
3. At the command prompt, enter `php bin/mdcompile` and follow the instructions on screen.

### Compiler Options

#### Path to input file&hellip;

Enter the absolute path to the location of the source input file, including the '`.md`' file extension.

Example: `/home/user/projects/foo/src/index.md`

#### Path to output file&hellip;

Enter the absolute path to the location of the compiled output file, including the '`.md`' file extension.

Example: `/home/user/projects/foo/out/compiled.md`

The output directory must exist, but the output file itself will be created if necessary.

#### Adjust headings? \[Y/N\]

Enter '`Y`' (case-insensitive) to adjust headings in included files *down* (in importance) by one level. For example, level 1 (`#`) becomes level 2 (`##`) and so on.

> Valid Markdown documents should only have a single level 1 (`#`) heading.
>
> If each included file contains a level 1 heading (to be valid individually), then the combined Markdown document would become invalid.
>
> Adjusting the heading levels of included files fixes this issue.
>
> **Important:** this is only effective if all Markdown files are valid and follow a logical heading structure.

#### Insert Table of Contents? \[Y/N\]

Enter '`Y`' to generate and insert a Table of Contents (see [Table of Contents](#table-of-contents) Markdown syntax for more information).

#### Show output in console? \[Y/N\]

Enter '`Y`' to to review the generated output before committing it to file.

Enter '`N`' to skip directly to saving/creating the output file.

#### Save/create output file? \[Y/N\]

Enter '`Y`' to save (or create) the output file. **Note:** only shown if you entered '`Y`' to viewing output in the console.

### Run the Demo

To run the demo, follow the steps above and then at each prompt enter the following:

```console
PATH TO INPUT FILE...           > $root/demo/md/index.md
PATH TO OUTPUT FILE...          > $root/demo/md/compiled.md
ADJUST HEADINGS? [Y/N]          > Y
INSERT TABLE OF CONTENTS? [Y/N] > Y
SHOW OUTPUT IN CONSOLE? [Y/N]   > Y
SAVE/CREATE OUTPUT FILE? [Y/N]  > Y
```

**Note:** `$root` is a special shortcut to refer to the PHP Markdown Compiler's root/install directory. It is treated as a string literal and not parsed as a variable.

## Running the Compiler from a Server-side Script

If you would prefer to run the compiler from a web-accessible server-side script, then please view the example code in `demo/example.phps` and review the class reference below.

## Class Reference

### Methods

#### `new PhpMarkdownCompiler($input = '', $output = '')`

Constructor method

Setting the input/output files here is optional. For example, the CLI app uses the [`setInputFile()`](#setinputfileinput)/[`setOutputFile()`](#setoutputfileoutput) methods instead.

|Parameter|Type|Description|
|---|---|---|
|`$input`|String|Absolute path to input source file. *Optional*|
|`$output`|String|Absolute path to compiled output file. *Optional*|

**Throws:** *Exception*

#### `runCompiler($adjustHeadings = false, $insertToc = false)`

Run the Markdown compiler and return compiled output.

Optionally adjust headings and/or insert a Table of Contents.

|Parameter|Type|Default|Description|
|---|---|---|---|
|`$adjustHeadings`|Boolean|`false`|Adjust headings down by one level after the main heading.  E.g. `#` => `###`|
|`$insertToc`|Boolean|`false`|Insert Table of Contents|

**Returns:** *String* - compiled output Markdown.

#### `saveFile()`

Save compiled output to file.

**Throws:** *Exception*

**Returns:** *Boolean*

#### `getTableOfContents()`

Get the Table of Contents.

This is available regardless of whether the Table of Contents was inserted into the compiled document.

**Returns** *String* - Table of Contents Markdown.

#### `setInputFile($input)`

Validate/normalise and set the source input file location.

Used in the CLI app.

|Parameter|Type|Description|
|---|---|---|
|`$input`|String|Absolute path to input source file|

**Throws:** *Exception*

**Returns:** *String* - sanitised input path.

#### `setOutputFile($output)`

Validate/normalise and set the compiled output file location.

Used in the CLI app.

|Parameter|Type|Description|
|---|---|---|
|`$output`|String|Absolute path to compiled output file|

**Throws:** *Exception*

**Returns:** *String* - sanitised output path.

### Basic Example

```php
try {
    // Initialise the class
    $mdcompiler = new \lmdcode\phpmdcompiler\PhpMarkdownCompiler(
        '/path/to/input.md',
        '/path/to/output.md',
    );

    // Get compiled contents (adjust headings and insert Table of Contents)
    $contents = $mdcompiler->runCompiler(true, true);

    // Save the result to file
    $mdcompiler->saveFile();
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage();
    exit;
}
```

---

This README was compiled using PHP Markdown Compiler.
