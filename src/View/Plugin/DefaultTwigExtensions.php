<?php

namespace Jasny\View\Plugin;

use Jasny\ViewInterface;
use Jasny\View\PluginInterface;
use Jasny\View\Twig as TwigView;

/**
 * Add the official Twig extension and Jansy Twig extensions.
 * 
 * @link https://github.com/twigphp/Twig-extensions
 * @link https://github.com/jasny/twig-extensions
 */
class DefaultTwigExtensions implements PluginInterface
{
    /**
     * Extension class names
     * 
     * @var array
     */
    public $extensions = [
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

    /**
     * Called when the plugin is added to the view.
     * 
     * @param ViewInterface $view
     */
    public function onAdd(ViewInterface $view)
    {
        if (!$view instanceof TwigView) {
            throw new \InvalidArgumentException("This plugin only works with a Twig view");
        }
        
        foreach ($this->extensions as $class) {
            if (class_exists($class)) {
                $view->getTwig()->addExtension(new $class());
            }
        }
    }
    
    /**
     * Called when view renders a template.
     * 
     * @param ViewInterface $view
     * @param string        $template   Template filename
     * @param array         $context
     */
    public function onRender(ViewInterface $view, $template, array $context)
    {
    }
}
