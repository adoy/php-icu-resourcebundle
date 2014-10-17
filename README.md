php-icu-resourcebundle
======================

A PHP Implementation of the ICU Resource Bundle that work directly with .txt files (no need to use genrb)

This implementation was developped for development environment only. It was developped to ease the process of creating ResourceBundle source without having to recompile the source file each time.

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

