<?php

/**
 * PHP Markdown Compiler
 * (c) LMD, 2022
 * https://github.com/lmd-code/phpmdcompiler
 *
 * Basic server-side script example.
 *
 * You can run this example from your local copy of PhpMarkdownCompiler
 *
 * 1. Rename this file to 'example.php' (it's just a file extension change).
 *
 * 2. In your browser, go to:
 *    http://host.name/path/to/phpmdcompiler/demo/example.php
 *
 *    For example, in a dev environment:
 *    http://project.locahost/phpmdcompiler/demo/example.php
 */

declare(strict_types=1);

require_once '../src/PhpMarkdownCompiler.php';

$saved = (!empty($_GET['saved']) && boolval($_GET['saved']) === true);

$compile = (!empty($_POST['compile']) && boolval($_POST['compile']) === true);
$insToc = (!empty($_POST['toc']) && boolval($_POST['toc']) === true);
$adjustHeadings = (!empty($_POST['headings']) && boolval($_POST['headings']) === true);

$save = false;
if (!empty($_POST['save']) &&  boolval($_POST['save']) === true) {
    $compile = true; // always compile on save
    $save = true;
}

if ($compile) {
    /***
     * In this example we are setting the the input and output files directly in the
     * the constructor method and any errors will combined into a single exception.
     * $root = shortcut string, not a variable
     */
    try {
        // Initialise the class
        $mdcompiler = new \lmdcode\phpmdcompiler\PhpMarkdownCompiler(
            '$root/demo/md/index.md',
            '$root/demo/md/demo-compiled.md',
        );

        $contents = $mdcompiler->runCompiler($adjustHeadings, $insToc);

        if ($save) {
            $mdcompiler->saveFile();
            header("Location: example.php?saved=1");
            exit;
        }
    } catch (\Exception $e) {
        echo "Error: " . $e->getMessage();
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PHP Markdown Compiler Demo</title>
    <link href="demo-style.min.css" rel="stylesheet">
</head>
<body>
<div id="container">
<h1>PHP Markdown Compiler Demo</h1>

<?php if ($saved) : ?>
<p class="saved">The compiled output file was sucessfully saved.</p>
<?php endif; ?>

<p>Basic server-side script example.</p>

<form method="POST" action="example.php">
<fieldset>
    <legend>Compile Options</legend>
    <p>
        <label>
            <input
                type="checkbox"
                name="headings"
                value="1"
                <?php echo ($adjustHeadings) ? ' checked' : '';?>
            >
            Adjust headings
        </label>
    </p>

    <p>
        <label>
            <input
                type="checkbox"
                name="toc"
                value="1"
                <?php echo ($insToc) ? ' checked' : '';?>
            >
            Insert Table of Contents
        </label>
    </p>

    <p><button type="submit" name="compile" value="1">Compile</button></p>
</fieldset>

<h2>Compiled Output Preview</h2>

<p>Note: this will not parse the Markdown, it will display the raw syntax.</p>

<?php if ($compile) : ?>
<pre><code title="markdown"><?php echo htmlspecialchars($contents); ?></code></pre>

<p><button type="submit" name="save" value="1">Save/Create File</button></p>
<?php endif; ?>
</form>
</div>
</body>
</html>