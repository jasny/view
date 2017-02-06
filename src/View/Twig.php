<?php

namespace Jasny\View;

use Jasny\ViewInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Abstraction to use Twig with PSR-7
 */
class Twig implements ViewInterface
{
    /**
     * Twig environment
     * @var \Twig_Environment
     */
    protected $twig;

    /**
     * Class constructor
     * 
     * @param \Twig_Environment|array $options
     */
    public function __construct($options)
    {
        $twig = is_array($options) ? $this->createTwigEnvironment($options) : $options;
        
        if (!$twig instanceof \Twig_Environment) {
            throw new \InvalidArgumentException("Was expecting an array with options or a Twig_Environment, got a "
                . (is_object($twig) ? get_class($twig) . ' ' : '') . gettype($twig));
        }
        
        $this->twig = $twig;
    }
    
    /**
     * Create a new Twig environment
     * 
     * @param array $options
     * @return \Twig_Environment
     */
    protected function createTwigEnvironment(array $options)
    {
        if (!isset($options['path'])) {
            throw new \BadMethodCallException("'path' option is required");
        }

        $loader = new \Twig_Loader_Filesystem($options['path']);
        
        return new \Twig_Environment($loader, $options);
    }
    
    /**
     * Get Twig environment
     *
     * @return \Twig_Environment
     */
    public function getTwig()
    {
        return $this->twig;
    }

    
    /**
     * Assert valid variable, function and filter name
     * 
     * @param string $name
     * @throws \InvalidArgumentException
     */
    protected function assertViewVariableName($name)
    {
        if (!is_string($name)) {
            $type = (is_object($name) ? get_class($name) . ' ' : '') . gettype($name);
            throw new \InvalidArgumentException("Expected name to be a string, not a $type");
        }

        if (!preg_match('/^[a-z]\w*$/i', $name)) {
            throw new \InvalidArgumentException("Invalid name '$name'");
        }
    }

    /**
     * Expose a function to the view.
     *
     * @param string        $name      Function name in template
     * @param callable|null $function
     * @param string        $as        'function' or 'filter'
     * @return $this
     */
    public function expose($name, $function = null, $as = 'function')
    {
        $this->assertViewVariableName($name);
        
        if ($as === 'function') {
            $function = new \Twig_SimpleFunction($name, $function ?: $name);
            $this->getTwig()->addFunction($function);
        } elseif ($as === 'filter') {
            $filter = new \Twig_SimpleFilter($name, $function ?: $name);
            $this->getTwig()->addFilter($filter);
        } else {
            $not = is_string($as) ? "'$as'" : gettype($as);
            throw new \InvalidArgumentException("You can create either a 'function' or 'filter', not a $not");
        }

        return $this;
    }

    /**
     * Add the official Twig extension and Jansy Twig extensions.
     * 
     * @link https://github.com/twigphp/Twig-extensions
     * @link https://github.com/jasny/twig-extensions
     * 
     * @return $this
     */
    public function addDefaultExtensions()
    {
        $extensions = [
            'Twig_Extensions_Extension_Array',
            'Twig_Extensions_Extension_Date',
            'Twig_Extensions_Extension_I18n',
            'Twig_Extensions_Extension_Intl',
            'Twig_Extensions_Extension_Text',
            'Jasny\Twig\DateExtension',
            'Jasny\Twig\PcreExtension',
            'Jasny\Twig\TextExtension',
            'Jasny\Twig\ArrayExtension'
        ];
        
        foreach ($extensions as $class) {
            if (class_exists($class)) {
                $this->getTwig()->addExtension(new $class());
            }
        }
    }

    /**
     * Get the filename
     * 
     * @return string
     */
    protected function getFilename($name)
    {
        if (pathinfo($name, PATHINFO_EXTENSION) === '') {
            $name .= substr($name, -1) === '/' ? 'index.html.twig' : '.html.twig';
        }
        
        return $name;
    }
    
    /**
     * Render and output template
     *
     * @param ResponseInterface $response
     * @param string            $name      Template name
     * @param array             $context   Template context as associated array
     * @return ResponseInterface
     */
    public function output(ResponseInterface $response, $name, array $context = [])
    {
        $file = $this->getFilename($name);

        $twig = $this->getTwig();
        $tmpl = $twig->loadTemplate($file);

        $contents = $tmpl->render($context);
        
        $newResponse = $response->withHeader('Content-Type', 'text/html; charset=' . $twig->getCharset());
        $newResponse->getBody()->write($contents);
        
        return $newResponse;
    }
}
