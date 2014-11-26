#!/usr/bin/php
<?php
Phar::mapPhar('cf-parser.phar');

require 'phar://cf-parser.phar/vendor/autoload.php';
require 'phar://cf-parser.phar/src/CodeforcesParser/Parser.php';

// check number of arguments
if ($argc <= 3) {
    print "usage: cf-parser <contest> <language> <directory> [<problems>]\n";
    exit(1);
}

// if there's more than the necessary arguments consider them problems
if ($argc>4) {
    for($i=4; $i < $argc; $i++)
        $problems[] = $argv[$i];
} else {
    $problems = null;
}

// create the parser instance and parse the problems
$parser = new CodeforcesParser\Parser($argv[1], $argv[2], $argv[3], $problems);
$parser->parse();

__HALT_COMPILER();
