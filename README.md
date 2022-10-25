# PHP Markdown Compiler

Compile multiple Markdown (`*.md`) files into a single document using a special *file include* Markdown syntax.

Optionally include a Table of Contents generated from the headers in the compiled document.

**NB:** This is not a *parser*, it will not convert Markdown content into another markup syntax.

## Markdown Syntax

### File Includes

The Markdown syntax for including a file is the same as creating a link, but with a colon (`:`) prefixed.

However, unlike links, each include must appear on a new line and be the only item on that line.

```markdown
# Document Title

Some text.. 

:[Heading A](filename-a.md)

:[Heading B](filename-b.md)

More text
```

### Table of Contents

There are two ways to include a table of contents.

The first, and easiest, is to just let the compiler insert it after the main heading.

If you would prefer to have some control over its placement, the Markdown syntax is an empty `code` block with "`toc`" set as its "language".

````markdown
# Document Title

Some intro text…

```toc
```

The rest of the document…
````

## Running the Compiler from the Command Line

You need to have at least PHP 7.4 installed on your system.

1. Download and unzip the repository to a location of your choice.
2. In the command line tool of you choice, navigate to the folder you just downloaded/unzipped.
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

Enter '`Y`' to adjust headings in included files *down* (in importance) by one level. For example, level 1 (`#`) becomes level 2 (`##`) and so on.

> Valid Markdown documents should only have a single level 1 (`#`) heading. If included files all contain a level 1 heading (to be valid individually), then the combined Markdown document would become invalid. Adjusting the heading levels of included files fixes this issue. **Important:** this is only effective if all Markdown files are valid and follow a logical heading structure.

#### Insert Table of Contents? \[Y/N\]

Enter '`Y`' to generate and insert a Table of Contents (see [Table of Contents](#table-of-contents) Markdown syntax for more information).

#### Show output in console? \[Y/N\]

Enter '`Y`' to to review the generated output before committing it to file.

Enter '`N`' to skip directly to saving/creating the output file.

#### Save/create output file? \[Y/N\]

Enter '`Y`' to save (or create) the output file. **Note:** only shown if you entered '`Y`' to viewing output in the console.

### Run the Demo

To run the demo, follow the steps above and then at each prompt enter:

**Note 1:** `$root` is a special shortcut to refer to the PHP Markdown Compiler's root/install directory. It is treated as a string literal and not parsed as a variable.

**Note 2:** `Y/N` values are not case sensitive.

```console
PATH TO INPUT FILE...           > $root/demo/md/index.md
PATH TO OUTPUT FILE...          > $root/demo/md/compiled.md
ADJUST HEADINGS? [Y/N]          > Y
INSERT TABLE OF CONTENTS? [Y/N] > Y
SHOW OUTPUT IN CONSOLE? [Y/N]   > Y
SAVE/CREATE OUTPUT FILE? [Y/N]  > Y
```

## Running the Compiler from a Server-side Script

If you would prefer to run the compiler from a web-accesible server-side script, then please view the example code in `demo/example.phps` and review the class documentation below.
