<?php

namespace Jasny\View;

use Jasny\ViewInterface;

/**
 * A view plugin can be used to add functionality to a view.
 */
interface PluginInterface
{
    /**
     * Called when the plugin is added to the view.
     * 
     * @param ViewInterface $view
     */
    public function onAdd(ViewInterface $view);
    
    /**
     * Called when view renders a template.
     * 
     * @param ViewInterface $view
     * @param string        $template   Template filename
     * @param array         $context
     */
    public function onRender(ViewInterface $view, $template, array $context);
}
