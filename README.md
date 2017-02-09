Jasny View
===

[![Build Status](https://travis-ci.org/jasny/view.svg?branch=master)](https://travis-ci.org/jasny/view)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/jasny/view/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/jasny/view/?branch=master)
[![Code Coverage](https://scrutinizer-ci.com/g/jasny/view/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/jasny/view/?branch=master)
[![SensioLabsInsight](https://insight.sensiolabs.com/projects/755b902c-99d8-4535-8f1e-56394460a5a9/mini.png)](https://insight.sensiolabs.com/projects/755b902c-99d8-4535-8f1e-56394460a5a9)
[![Packagist Stable Version](https://img.shields.io/packagist/v/jasny/view.svg)](https://packagist.org/packages/jasny/view)
[![Packagist License](https://img.shields.io/packagist/l/jasny/view.svg)](https://packagist.org/packages/jasny/view)

An abstraction for using [PSR-7](http://www.php-fig.org/psr/psr-7/) with template engines.

Jasny View isn't bound to any framework and can be used anywhere you want to use an existing template engine like Twig
with PSR-7.

Installation
---

Install using composer

    composer require jasny\view


Usage
---

All view layers of Jasny View implement the `Jasny\ViewInterface`, which defines the following methods:

### Expose

Expose a function to the view. This can be a build-in PHP function, a user defined function or even an anonymous
function.

    expose(string $name, callable $function = null);

You may omit the second argument. In that case, the name will be used as function name.

```php
$view->expose('strlen');
$view->expose('replace', 'str_replace');
$view->expose('add', function($a, $b) { return $a + $b; });
```

### Render

Render and output a template. Outputting is done by writing the result to the response body and setting the response
`Content-Type` header.

    render(ResponseInterface $response, string $name, array $context = []);

The `name` is the name of the template and usually corresponds with a filename. The `context` are the values that are
made available to the view (as variable or constant).


```php
$view->render($response, 'index', ['color' => 'blue', 'answer' => 42]);
```

## Twig

`Jasny\View\Twig` is a wrapper around [`Twig_Environment`](http://twig.sensiolabs.org/doc/2.x/api.html). When creating
the view object, you can either specify the options to create an environment or pass a Twig environment.

```php
$view = new Jasny\View\Twig(['path' => 'views', 'cache' => '/tmp/views']);
```

The `path` option is required. It's passed to the `Twig_Loader_Filesystem` and serves as the base directory where the
view files are located.

Other [options](http://twig.sensiolabs.org/doc/2.x/api.html#environment-options) are passed the constructor when
creating a `Twig_Environment`. The following options are available:

 * debug: When set to true, it automatically set "auto_reload" to true as well (default to false).
 * charset: The charset used by the templates (default to UTF-8).
 * basetemplateclass: The base template class to use for generated templates (default to Twig_Template).
 * cache: An absolute path where to store the compiled templates, a TwigCacheInterface implementation, or false to
   disable compilation cache (default).
 * autoreload: Whether to reload the template if the original source changed. If you don't provide the autoreload
   option, it will be determined automatically based on the debug value.
 * strict_variables: Whether to ignore invalid variables in templates (default to false).
 * autoescape: Whether to enable auto-escaping (default to html):
    * false: disable auto-escaping
    * html, js: set the autoescaping to one of the supported strategies
    * name: set the autoescaping strategy based on the template name extension
    * PHP callback: a PHP callback that returns an escaping strategy based on the template "name"
 * optimizations: A flag that indicates which optimizations to apply (default to -1 which means that all optimizations
   are enabled; set it to 0 to disable).

Passing a `Twig_Environment` is recommended if your applicated focusses on Dependency Injection.

### getTwig

The `getTwig()` method returns the `Twig_Environment` object it wraps. It can be used to extends the twig environment.

```php
$view = new Jasny\View\Twig(['path' => 'views']);
$view->getTwig()->addExtension(new MyTwigExtension());
$view->getTwig()->addGlobal('foo', 'bar');
```

### addDefaultExtensions

Calling `$view->addDefaultExtensions()` will add all [Official Twig extensions](https://github.com/twigphp/Twig-extensions)
and [Jasny Twig extensions](https://github.com/jasny/twig-extensions) if available.

### expose

For Twig, `expose` optionally takes a third argument. You can specify if the function should be added as
[Twig function](http://twig.sensiolabs.org/doc/2.x/advanced.html#functions) or [Twig filter](http://twig.sensiolabs.org/doc/2.x/advanced.html#filters).

    expose($name, $function = null, $as = 'function')

### render

Render will automatically add `.html.twig` to the name, if the name doesn't contain an extension. It calls the 
[`render` method](http://twig.sensiolabs.org/doc/2.x/api.html#rendering-templates) of the Twig environment and write the
redered content to the response body.


## PHP

The PHP layer doesn't use template rendering engine, but simply includes a PHP file.

The constructor takes an array of options, which must contain a `path` property. This is the path to the directory where
the view files are located.

Optionally the `ext` option may be passed. This determines the default extension for the view name for `render()`.

```php
$view = new Jasny\View\Twig(['path' => 'views', 'ext' => 'phtml']);
```

### getPath

Get the directory path.

### getExt

Get the default extension.

### expose

It's typically not needed to call `expose`. Global PHP functions (build-in or user defined) are already available.
Adding a function as alias (so `$name` is not the same as `$template`, is not availabe.

### render

The `render()` method will include the specified template file using `include`. In the same context, the `$context` is
extracted, so everything is available as variable in the view.

The output is streamed to the response body, using an output buffer callback.

```php
$view->render($response, 'index', ['color' => 'blue', 'answer' => 42]);
```

If the specified file name is a directory the index file from that directory is automatically used. If the file doesn't
exist, a `RuntimeExpection` is thrown.

## Related libraries

* [Jasny HTTP Message](https://github.com/jasny/http-message) - A PSR-7 implementation
* [Jasny Controller](https://github.com/jasny/controller) - A general purpose controller for PSR-7
* [Jasny MVC](https://github.com/jasny/mvc) - Meta package for Jasny Router, Controller and View
* [Twig](http://twig.sensiolabs.org/) - The flexible, fast, and secure template engine for PHP

