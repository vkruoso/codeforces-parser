#!/usr/bin/php
<?php
Phar::mapPhar('cf-parser.phar');

require 'phar://cf-parser.phar/vendor/autoload.php';
require 'phar://cf-parser.phar/src/CodeforcesParser/Parser.php';

// check number of arguments
if ($argc <= 2) {
    print "usage: cf-parser <contest> <directory> [<problems>]\n";
    exit(1);
}

// if there's more than the necessary arguments consider them problems
if ($argc>3) {
    for($i=3; $i < $argc; $i++)
        $problems[] = $argv[$i];
} else {
    $problems = null;
}

// create the parser instance and parse the problems
$parser = new CodeforcesParser\Parser($argv[1], $problems, $argv[2]);
$parser->parse();

__HALT_COMPILER();
