<?php

namespace Jasny\View;

use Jasny\ViewInterface;
use Jasny\View\Twig as TwigView;
use Jasny\View\Plugin\TwigAssetic;
use PHPUnit_Framework_TestCase as TestCase;
use PHPUnit_Framework_MockObject_MockObject as MockObject;
use Assetic\AssetWriter;
use Assetic\Extension\Twig\AsseticExtension;
use Assetic\Extension\Twig\TwigFormulaLoader;
use Assetic\Extension\Twig\TwigResource;
use Assetic\Factory\AssetFactory;
use Assetic\Factory\LazyAssetManager;

/**
 * @covers Jasny\View\Plugin\TwigAssetic
 */
class TwigAsseticTest extends TestCase
{
    /**
     * @var \Twig_Environment|MockObject
     */
    protected $twig;
    
    /**
     * @var TwigView|MockObject
     */
    protected $view;
    
    /**
     * @var TwigAssetic|MockObject
     */
    protected $plugin;
    
    /**
     * @var AssetFactory|MockObject
     */
    protected $factory;
    
    /**
     * @var AssetWriter|MockObject
     */
    protected $writer;
    
    
    public function setUp()
    {
        $this->twig = $this->createMock(\Twig_Environment::class);
        $this->view = $this->createConfiguredMock(TwigView::class, ['getTwig' => $this->twig]);
        
        $this->factory = $this->createMock(AssetFactory::class);
        $this->writer = $this->createMock(AssetWriter::class);
        
        $this->plugin = $this->getMockBuilder(TwigAssetic::class)
            ->setConstructorArgs([$this->factory, $this->writer])
            ->setMethods(['createExtension', 'createFormulaLoader', 'createAssetManager', 'createResource'])
            ->getMock();
    }
    
    public function testOnAdd()
    {
        $extension = $this->createMock(AsseticExtension::class);

        $this->plugin->expects($this->once())->method('createExtension')->willReturn($extension);
        $this->twig->expects($this->once())->method('addExtension')->with($this->identicalTo($extension));
        
        $this->plugin->onAdd($this->view);
    }
    
    /**
     * @expectedException \InvalidArgumentException
     */
    public function testOnAddInvalidArgument()
    {
        $this->plugin->onAdd($this->createMock(ViewInterface::class));
    }
    
    
    public function testOnRender()
    {
        $loader = $this->createMock(TwigFormulaLoader::class);
        $manager = $this->createMock(LazyAssetManager::class);
        $resource = $this->createMock(TwigResource::class);
        
        $this->plugin->expects($this->once())->method('createFormulaLoader')
            ->with($this->identicalTo($this->twig))
            ->willReturn($loader);
        
        $this->plugin->expects($this->once())->method('createAssetManager')
            ->with($this->identicalTo($loader))
            ->willReturn($manager);
        
        $this->plugin->expects($this->once())->method('createResource')
            ->with($this->identicalTo($this->twig), $this->identicalTo('foo.html.twig'))
            ->willReturn($resource);
        
        $manager->expects($this->once())->method('setLoader')->with('twig', $this->identicalTo($loader));
        $manager->expects($this->once())->method('addResource')->with($this->identicalTo($resource), 'twig');
        
        $this->writer->expects($this->once())->method('writeManagerAssets')->with($this->identicalTo($manager));
        
        $this->plugin->onRender($this->view, 'foo.html.twig', []);
    }
    
    /**
     * @expectedException \InvalidArgumentException
     */
    public function testOnRenderInvalidArgument()
    {
        $this->plugin->onAdd($this->createMock(ViewInterface::class));
    }
}
