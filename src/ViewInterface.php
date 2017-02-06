<?php

namespace Jasny;

use Psr\Http\Message\ResponseInterface;

/**
 * Interface for abstracting over template engine.
 */
interface ViewInterface
{
    /**
     * Expose a function to the view.
     *
     * @param string        $name      Function name in template
     * @param callable|null $function
     * @return $this
     * @throws \BadMethodCallException if function can't be exposed
     */
    public function expose($name, $function = null);
    
    /**
     * Render and output template
     *
     * @param ResponseInterface $response
     * @param string            $name      Template name
     * @param array             $context   Template context as associated array
     * @return ResponseInterface
     */
    public function render(ResponseInterface $response, $name, array $context = []);
}
