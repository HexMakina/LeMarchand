[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/HexMakina/LeMarchand/badges/quality-score.png?b=main)](https://scrutinizer-ci.com/g/HexMakina/LeMarchand/?branch=main)
<img src="https://img.shields.io/badge/PSR-11-brightgreen" alt="PSR-11 Compliant" />
<img src="https://img.shields.io/badge/PSR-12-brightgreen" alt="PSR-12 Compliant" />
<img src="https://img.shields.io/badge/PHP-7.0-brightgreen" alt="PHP 7.0 Required" />
[![Latest Stable Version](http://poser.pugx.org/hexmakina/le-marchand/v)](https://packagist.org/packages/hexmakina/le-marchand)
[![License](http://poser.pugx.org/hexmakina/le-marchand/license)](https://packagist.org/packages/hexmakina/le-marchand)

# LeMarchand
Basic PSR-11 container build for kadro

# Install
composer require hexmakina/le-marchand

# Usage

## Instantiante and load configuration array
```
$settings = [  
  'app' => [
     'name' => 'KORAL',
     'production_host' => 'engine.hexmakina.be',
     'session_start_options' => ['session_name' => 'koral-alias'],
     'time_window_start' => '-3 months',
     'time_window_stop' => '+1 month',
  ],
  'controller_namespaces' => [
    'App\\Controllers\\',
    'HexMakina\\koral\\Controllers\\',
    'HexMakina\\kadro\\Controllers\\'
  ]
];

$box=new LeMarchand($settings);
```

Settings will be retrievable with the 'settings' key, meaning:
```
$box->has('settings'); // returns true;
$box->has('settings.app.name'); // return true;
$box->get('settings.app.name'); // returns KORAL
```


## Register additionnal services
```
$box=new LeMarchand($settings);
$box->register('HexMakina\Crudites\DatabaseInterface', $database);

```

## Do we have a configuration ?

By PSR-11 law:
* A call to the has method with a non-existing id returns false
* A call to the get method with a non-existing id throws a Psr\Container\NotFoundExceptionInterface.
```
$box->has('NonExistingKey'); //return false
$box->get('NonExistingKey'); //throws a Psr\Container\NotFoundExceptionInterface
```

get takes one mandatory parameter: an entry identifier, which MUST be a string
```
$box->has(23); // return false;
$box->get(23); // throws a Psr\Container\ContainerExceptionInterface
```


## Lore & Hommage

The **LeMarchand** box that has become known in the Hellraiser film series as the **Lament Configuration** was introduced in The Hellbound Heart novella as "the Lemarchand Configuration".
