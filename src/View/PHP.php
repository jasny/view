<?php

namespace Jasny\View;

use Jasny\ViewInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Abstraction use PHP templates as views with PSR-7
 */
class PHP implements ViewInterface
{
    /**
     * @var string
     */
    protected $path;

    /**
     * @var string
     */
    protected $ext = 'php';
    
    /**
     * Class constructor
     * 
     * @param array $options
     */
    public function __construct(array $options)
    {
        if (!isset($options['path'])) {
            throw new \BadMethodCallException("'path' option is required");
        }
            
        $this->path = $options['path'];
        
        if (isset($options['ext'])) {
            $this->ext = $options['ext'];
        }
    }
    
    /**
     * Get directory path.
     * 
     * @return string
     */
    public function getPath()
    {
       return $this->path; 
    }
    
    /**
     * Get the default file extension
     * 
     * @return string
     */
    public function getExt()
    {
        return $this->ext;
    }
    
    
    /**
     * Expose a function to the view.
     *
     * @param string        $name      Function name in template
     * @param callable|null $function
     * @return $this
     * @throws \BadMethodCallException if function can't be exposed
     */
    public function expose($name, $function = null)
    {
        if (function_exists($name) && (!isset($function) || $name === $function)) {
            return $this;
        }
        
        throw new \BadMethodCallException("Exposing functions under an alias isn't supported with PHP views");
    }
    
    
    /**
     * Get the path to a view file
     * 
     * @param string $name
     * @return string
     */
    protected function getFilePath($name)
    {
        if (pathinfo($name, PATHINFO_EXTENSION) === '') {
            $name .= (substr($name, -1) === '/' ? 'index' : '') . '.' . $this->getExt();
        }
        
        return rtrim($this->path, '/') . '/' . ltrim($name, '/');
    }
    
    /**
     * Assert the filename and that the file exists
     * 
     * @param string $name
     * @throws \InvalidArgumentException
     */
    protected function assertFile($name)
    {
        if (strpos($name, '..') !== false) {
            throw new \InvalidArgumentException("File name '$name' isn't valid");
        }
        
        $file = $this->getFilePath($name);
        
        if (!file_exists($file)) {
            throw new \RuntimeException("View file '$file' doesn't exist");
        }
    }
    
    /**
     * Run the PHP script
     * 
     * @param string $name
     * @param array  $context
     */
    protected function runScript($name, array $context)
    {
        // Prevent $name and $context being available in the view script
        $___ = $this->getFilePath($name); 
        $_context_ = $context;
        unset($name, $context);
        
        /** @see http://php.net/extract */
        extract($_context_);
        unset($_context_);
        
        include $___;
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
        $this->assertFile($name);

        if (!$response->hasHeader('Content-Type')) {
            $response = $response->withHeader('Content-Type', 'text/html');
        }
        
        $body = $response->getBody();
        
        ob_start(function ($buffer) use ($body) {
            $body->write($buffer);
        });
        
        try {
            $this->runScript($name, $context);
        } finally {
            ob_end_clean();
        }
        
        return $response;
    }
}
