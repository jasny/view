<?php

namespace Jasny\View;

use Jasny\ViewInterface;
use Jasny\View\Twig as TwigView;
use Jasny\View\Plugin\DefaultTwigExtensions;
use PHPUnit_Framework_TestCase as TestCase;
use PHPUnit_Framework_MockObject_MockObject as MockObject;

/**
 * @covers Jasny\View\Plugin\DefaultTwigExtensions
 */
class DefaultTwigExtensionsTest extends TestCase
{
    /**
     * @var \Twig_Environment|MockObject
     */
    protected $twig;
    
    /**
     * @var TwigView|MockObject
     */
    protected $view;
    
    public function setUp()
    {
        $this->twig = $this->createMock(\Twig_Environment::class);
        $this->view = $this->createConfiguredMock(TwigView::class, ['getTwig' => $this->twig]);
    }
    
    public function testOnAdd()
    {
        $this->twig->expects($this->exactly(9))->method('addExtension')
            ->withConsecutive(
                [$this->isInstanceOf(\Twig_Extensions_Extension_Array::class)],
                [$this->isInstanceOf(\Twig_Extensions_Extension_Date::class)],
                [$this->isInstanceOf(\Twig_Extensions_Extension_I18n::class)],
                [$this->isInstanceOf(\Twig_Extensions_Extension_Intl::class)],
                [$this->isInstanceOf(\Twig_Extensions_Extension_Text::class)],
                [$this->isInstanceOf(\Jasny\Twig\DateExtension::class)],
                [$this->isInstanceOf(\Jasny\Twig\PcreExtension::class)],
                [$this->isInstanceOf(\Jasny\Twig\TextExtension::class)],
                [$this->isInstanceOf(\Jasny\Twig\ArrayExtension::class)]
            );

        $plugin = new DefaultTwigExtensions();
        $plugin->onAdd($this->view);
    }
    
    /**
     * @expectedException \InvalidArgumentException
     */
    public function testOnAddInvalidArgument()
    {
        $plugin = new DefaultTwigExtensions();
        $plugin->onAdd($this->createMock(ViewInterface::class));
    }
    
    public function testOnRender()
    {
        $plugin = new DefaultTwigExtensions();
        $plugin->onRender($this->view, 'foo', []); // Does nothing
    }
}
