<?php

namespace Jasny\View\Plugin;

use Jasny\ViewInterface;
use Jasny\View\Twig as TwigView;
use Jasny\View\PluginInterface;
use Assetic\AssetWriter;
use Assetic\Extension\Twig\AsseticExtension;
use Assetic\Extension\Twig\TwigFormulaLoader;
use Assetic\Extension\Twig\TwigResource;
use Assetic\Factory\AssetFactory;
use Assetic\Factory\LazyAssetManager;

/**
 * Assetic support for Twig
 */
class TwigAssetic implements PluginInterface
{
    /**
     * @var AssetFactory 
     */
    protected $factory;

    /**
     * @var AssetWriter
     */
    protected $writer;
    
    /**
     * Class constructor
     * 
     * @param AssetFactory $factory
     * @param AssetWriter  $writer
     */
    public function __construct(AssetFactory $factory, AssetWriter $writer)
    {
        $this->factory = $factory;
        $this->writer = $writer;
    }

    /**
     * Check that the view is a twig view
     * 
     * @param ViewInterface $view
     * @throws \InvalidArgumentException
     */
    protected function assertView(ViewInterface $view)
    {
        if (!$view instanceof TwigView) {
            throw new \InvalidArgumentException("This plugin only works with a Twig view");
        }
    }
    

    /**
     * Create an assetic extension for Twig.
     * @codeCoverageIgnore
     * 
     * @return AsseticExtension
     */
    protected function createExtension()
    {
        return new AsseticExtension($this->factory);
    }

    /**
     * Create an assetic formula loader.
     * @codeCoverageIgnore
     * 
     * @param \Twig_Environment $twig
     * @return TwigFormulaLoader
     */
    protected function createFormulaLoader($twig)
    {
        return new TwigFormulaLoader($twig);
    }
    
    /**
     * Create an assetic asset manager.
     * @codeCoverageIgnore
     * 
     * @param TwigFormulaLoader $loader
     * @return LazyAssetManager
     */
    protected function createAssetManager(TwigFormulaLoader $loader)
    {
        return new LazyAssetManager($this->factory);
    }
    
    /**
     * Create an assetic twig resource.
     * @codeCoverageIgnore
     * 
     * @param \Twig_Environment $twig
     * @param string            $template
     * @return TwigResource
     */
    protected function createResource($twig, $template)
    {
        return new TwigResource($twig->getLoader(), $template);
    }
    

    /**
     * Called when the plugin is added to the view.
     * 
     * @param ViewInterface $view
     */
    public function onAdd(ViewInterface $view)
    {
        $this->assertView($view);

        $view->getTwig()->addExtension($this->createExtension());
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
        $this->assertView($view);
        
        $twig = $view->getTwig();
        
        $loader = $this->createFormulaLoader($twig);
        
        $assetManager = $this->createAssetManager($loader);
        $assetManager->setLoader('twig', $loader);   

        $tmpl = $twig->loadTemplate($template);
        
        do {
            $name = (string)$tmpl;
            $resource = $this->createResource($twig, $name);
            $assetManager->addResource($resource, 'twig');
        } while ($tmpl = $tmpl->getParent($context));
        
        $this->writer->writeManagerAssets($assetManager);
    }
}
