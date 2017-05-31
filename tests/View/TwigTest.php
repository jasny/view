<?php

namespace Jasny\View;

use Jasny\View\Twig as TwigView;
use Jasny\View\PluginInterface;
use PHPUnit_Framework_TestCase as TestCase;
use org\bovigo\vfs\vfsStream;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

/**
 * @covers Jasny\View\Twig
 */
class TwigTest extends TestCase
{
    public function testConstructWithOptions()
    {
        $root = vfsStream::setup();
        $view = new TwigView([
            'path' => vfsStream::url('root'),
            'strict_variables' => true
        ]);
        
        $this->assertInstanceOf(\Twig_Environment::class, $view->getTwig());
        $this->assertInstanceOf(\Twig_Loader_Filesystem::class, $view->getTwig()->getLoader());
        $this->assertEquals([vfsStream::url('root')], $view->getTwig()->getLoader()->getPaths());
        $this->assertEquals(true, $view->getTwig()->isStrictVariables());
    }
    
    public function testConstructWithDI()
    {
        $twig = $this->createMock(\Twig_Environment::class);
        $view = new TwigView($twig);
        
        $this->assertSame($twig, $view->getTwig());
    }
    
    /**
     * @expectedException \InvalidArgumentException
     */
    public function testConstructWithInvalidArgument()
    {
        new TwigView('foo');
    }
    
    /**
     * @expectedException \BadMethodCallException
     */
    public function testConstructWithMissingPathOption()
    {
        new TwigView([]);
    }

    
    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Expected name to be a string, not a array
     */
    public function testExposeInvalidArgument()
    {
        $twig = $this->createMock(\Twig_Environment::class);
        $twig->expects($this->never())->method('addFunction');
        $twig->expects($this->never())->method('addFilter');
        
        $view = new TwigView($twig);
        
        $view->expose(['class', 'method']);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Invalid name '/abc/def'
     */
    public function testExposeInvalidName()
    {
        $twig = $this->createMock(\Twig_Environment::class);
        $twig->expects($this->never())->method('addFunction');
        $twig->expects($this->never())->method('addFilter');
        
        $view = new TwigView($twig);
        
        $view->expose('/abc/def');
    }

    public function exposeProvider()
    {
        return [
            ['strlen'],
            ['string_length', 'strlen'],
            ['foo', function () {}]
        ];
    }
    
    /**
     * @dataProvider exposeProvider
     * 
     * @param string $name
     * @param string $function
     */
    public function testExposeFunction($name, $function = null)
    {
        $twig = $this->createMock(\Twig_Environment::class);
        $twig->expects($this->never())->method('addFilter');
        
        $twig->expects($this->once())->method('addFunction')
            ->with($this->callback(function ($fn) use ($name, $function) {
                $this->assertInstanceOf(\Twig_SimpleFunction::class, $fn);
                $this->assertSame($name, $fn->getName());
                $this->assertSame($function ?: $name, $fn->getCallable());

                return true;
            }));
        
        $view = new TwigView($twig);
        
        $view->expose($name, $function);
    }
    
    /**
     * @dataProvider exposeProvider
     * 
     * @param string $name
     * @param string $function
     */
    public function testExposeFilter($name, $function = null)
    {
        $twig = $this->createMock(\Twig_Environment::class);
        $twig->expects($this->never())->method('addFunction');
        
        $twig->expects($this->once())->method('addFilter')
            ->with($this->callback(function ($fn) use ($name, $function) {
                $this->assertInstanceOf(\Twig_SimpleFilter::class, $fn);
                $this->assertSame($name, $fn->getName());
                $this->assertSame($function ?: $name, $fn->getCallable());

                return true;
            }));
        
        $view = new TwigView($twig);
        
        $view->expose($name, $function, 'filter');
    }
    
    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage You can create either a 'function' or 'filter', not a 'foo'
     */
    public function testExposeWithInvalidAs()
    {
        $twig = $this->createMock(\Twig_Environment::class);
        $twig->expects($this->never())->method('addFunction');
        $twig->expects($this->never())->method('addFilter');
        
        $view = new TwigView($twig);
        
        $view->expose('strlen', null, 'foo');
    }
    
    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage You can create either a 'function' or 'filter', not a array
     */
    public function testExposeWithInvalidTypeAs()
    {
        $twig = $this->createMock(\Twig_Environment::class);
        $twig->expects($this->never())->method('addFunction');
        $twig->expects($this->never())->method('addFilter');
        
        $view = new TwigView($twig);
        
        $view->expose('strlen', null, []);
    }

    public function testAddPlugin()
    {
        $view = new TwigView($this->createMock(\Twig_Environment::class));
        
        $plugin = $this->createMock(PluginInterface::class);
        $plugin->expects($this->once())->method('onAdd')->with($this->identicalTo($view));
        $plugin->expects($this->never())->method('onRender');
        
        $view->addPlugin($plugin);
        
        $this->assertSame([$plugin], $view->getPlugins());
    }

    public function testInvokePluginsOnRender()
    {
        $twig = $this->createMock(\Twig_Environment::class);
        $view = new TwigView($twig);
        
        $plugins = [];
        for ($i = 0; $i < 3; $i++) {
            $plugins[$i] = $this->createMock(PluginInterface::class);
            $plugins[$i]->expects($this->once())->method('onAdd')->with($this->identicalTo($view));
            $plugins[$i]->expects($this->once())->method('onRender')
                ->with($this->identicalTo($view), 'foo.html.twig', ['color' => 'blue']);
            
            $view->addPlugin($plugins[$i]);
        }
        
        $this->assertSame($plugins, $view->getPlugins());
        
        $response = $this->createMock(ResponseInterface::class);
        $response->expects($this->any())->method('withHeader')->willReturnSelf();
        $response->expects($this->any())->method('getBody')->willReturn($this->createMock(StreamInterface::class));
        
        $template = $this->createMock(\Twig_TemplateInterface::class);
        $template->expects($this->once())->method('render')->with(['color' => 'blue']);
        
        $twig->expects($this->once())->method('load')->willReturn($template);
        
        $view->render($response, 'foo.html.twig', ['color' => 'blue']);
    }
    
    
    public function testAddDefaultExtensions()
    {
        $twig = $this->createMock(\Twig_Environment::class);
        
        $twig->expects($this->exactly(9))->method('addExtension')
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
        
        $view = new TwigView($twig);
        $view->addDefaultExtensions();
        
        $this->assertContainsOnlyInstancesOf(Plugin\DefaultTwigExtensions::class, $view->getPlugins());
    }
    
    public function filenameProvider()
    {
        return [
            ['foo', 'foo.html.twig'],
            ['foo.html.twig', 'foo.html.twig'],
            ['foo.html', 'foo.html'],
            ['foo/bar/zoo', 'foo/bar/zoo.html.twig'],
            ['foo/bar/', 'foo/bar/index.html.twig']
        ];
    }
    
    /**
     * @dataProvider filenameProvider
     * 
     * @param string $name
     * @param string $expect
     */
    public function testRender($name, $expect)
    {
        $context = ['color' => 'blue', 'answer' => 42];
        
        $stream = $this->createMock(StreamInterface::class);
        $stream->expects($this->once())->method('write')->with('Hello world');

        $newResponse = $this->createMock(ResponseInterface::class);
        $newResponse->expects($this->once())->method('getBody')->willReturn($stream);
        
        $response = $this->createMock(ResponseInterface::class);
        $response->expects($this->once())->method('withHeader')->with('Content-Type', 'text/html; charset=Foo')
            ->willReturn($newResponse);
        
        $template = $this->createMock(\Twig_TemplateInterface::class);
        $template->expects($this->once())->method('render')->with($context)->willReturn('Hello world');
        
        $twig = $this->createMock(\Twig_Environment::class);
        $twig->expects($this->once())->method('getCharset')->willReturn('Foo');
        $twig->expects($this->once())->method('load')->with($expect)->willReturn($template);
        
        $view = new TwigView($twig);
        
        $view->render($response, $name, $context);
    }
}
