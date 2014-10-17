php-icu-resourcebundle
======================

This is an ICU like Resource Bundle in PHP that work directly with .txt files (no need to use genrb)

This implementation was developped for **development environment only**, it should never run on production. It was developped to ease the process of creating ResourceBundle source without having to recompile the source file each time with genrb.

This project is neither part of the real [ICU Project](http://site.icu-project.org/) nor [PHP Intl](http://www.php.net/intl).


### __Usage :__


#### test.php
``` php
<?php

use Adoy\ICU\ResourceBundle\ResourceBundle;
use Adoy\ICU\ResourceBundle\Parser;
use Adoy\ICU\ResourceBundle\Lexer

$dir = "/path/to/folder/container/txt/files/"
$rb = new ResourceBundle('fr_CA', $dir, true, new Parser(new Lexer()));

echo $rb['Hello'], ' ', $rb['Language'];
```

### root.txt
```
root:table {
    Hello { "Hello" }
}
```

### fr.txt
```
fr:table {
    Language:string { "fr" }
}
```

#### Output :
```
Hello fr
```

