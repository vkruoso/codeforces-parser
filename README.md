Codeforces Parser
=================

[![Build Status](https://travis-ci.org/vkruoso/codeforces-parser.png?branch=master)](https://travis-ci.org/vkruoso/codeforces-parser)

A tool that allows you to automatically setup a contest environment.

Usage / How it works
--------------------

    cf-parser <contest-id> <language> <contest-folder> [<problem-list>]
    
The tool will create inside the `contest-folder` folder all the files needed for your contest. The tool works like this:

  - If the problem list has not been provided, it will try to discover the problems from the contest.
  - Downloads the problems pages in parallel.
  - Parse the problems pages and create the input/output files.
  - Copy template files.
    
Directory structure
-------------------

You will want to have a folder only for Codeforces contests with one folder for each contest. It's important to have a `template` folder in the current directory.

Now we assume you have a directory tree like this:

    Codeforces/
      - template/
      - round150div2/
      - round156div1/
      - ...
      
Your template folder should have all files you want copied to your contest folder. There's only one exception: in order to create code templates to each problem, you should create a file called `model.{language}`, where `language` is the extension of your favorite coding language.

File organization and scripts
-----------------------------

The files inside the contest folder will be organized like this:

    - <problem>.in<case>
    - <problem>.out<case>
    - <problem>.<language>
    - Makefile
    - test
    - clean
    
When you want to test your code against the inputs/outputs available in the folder, just run `./test <problem>`. It will compile and execute your problem against all test cases. If it don't pass some test case, it will show you the diff between your output and the correct output. It whould look like this:

    $ ./test A
    Test 1: PASSED.
    Test 2: FAILED.
    1                         > 2
    Test 3: PASSED.
    
When you finish the contest, run `./clean` to remove all input/output and executable files, leaving you with only your code.

**IMPORTANT:** If you are not using C++ language, make sure you read and **update** the `clean` and `test` scripts so you can run them based on the language you use.

Installation
------------

Create your `template` folder and copy the `test` and `clean` scripts (from this repository) to it. Create your `Makefile` and `model.{language}` files and you'll be ready.

The tool has been packed into a PHP archive file. This archive includes all the dependencies, so nothing else is needed. Download the PHAR file and simply run it. A sample usage whould be:

    $ mkdir round161div2
    $ wget https://raw.github.com/vkruoso/codeforces-parser/master/build/cf-parser.phar
    $ php cf-parser.phar 263 cpp round161div2
    
Or you can install it in your PATH doing:

    $ wget https://raw.github.com/vkruoso/codeforces-parser/master/build/cf-parser.phar
    $ sudo mv cf-parser.phar /usr/local/bin/cf-parser
    $ sudo chmod +x /usr/local/bin/cf-parser
    
And now you can just use it by typing:

    $ cf-parser 263 cpp round161div2
    Retrieving problems: A, B, C, D, E.
    
The tool will report the problems it will retrieve. If you specify the problems in the command line it will only retrieve those problems, like:

    $ cf-parser 263 cpp round161div2 C E
    Retrieving problems: C, E.

**NOTE:** If you change the code for a problem, and run the tool again, it will NOT overwrite your code.

Alerts
------

This tool is not 100% done. It will fail hard in case of errors. If the program outputs something different than the expected, is not creating the input/output files correctly or is taking too long to finish, just stop it and run it again.

It's not a problem to run the program more than one time. For example, if the input/output of a problem has been changed durring the contest, just run the tool again and it will be updated. But keep in mind that any code will not be overwriten.
