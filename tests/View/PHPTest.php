<?php

namespace Jasny\View;

use Jasny\View\PHP as PHPView;
use PHPUnit_Framework_TestCase as TestCase;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

/**
 * @covers Jasny\View\PHP
 */
class PHPTest extends TestCase
{
    /**
     * @var vfsStreamDirectory
     */
    protected $root;
    
    /**
     * @var PHPView
     */
    protected $view;
    
    public function setUp()
    {
        $this->root = vfsStream::setup();
        
        $this->view = new PHPView([
            'path' => vfsStream::url('root')
        ]);
    }
    
    public function testConstruct()
    {
        $this->assertEquals(vfsStream::url('root'), $this->view->getPath());
        $this->assertEquals('php', $this->view->getExt());
    }
    
    public function testConstructWithExt()
    {
        $this->view = new PHPView([
            'path' => vfsStream::url('root'),
            'ext' => 'phtml'
        ]);
        
        $this->assertEquals(vfsStream::url('root'), $this->view->getPath());
        $this->assertEquals('phtml', $this->view->getExt());
    }
    
    /**
     * @expectedException \BadMethodCallException
     */
    public function testConstructWithMissingPathOption()
    {
        new PHPView([]);
    }
    
    
    public function functionProvider()
    {
        return [
            ['strlen', null, false],
            ['strlen', 'strlen', false],
            ['len', 'strlen', true],
            ['len', function() {}, true]
        ];
    }
    
    /**
     * @dataProvider functionProvider
     * 
     * @param string  $name
     * @param string  $function
     * @param boolean $expectException
     */
    public function testExpose($name, $function, $expectException)
    {
        if ($expectException) {
            $this->expectException(\BadMethodCallException::class);
        }
        
        $ret = $this->view->expose($name, $function);
        
        $this->assertSame($this->view, $ret);
    }
    
    
    public function filenameProvider()
    {
        return [
            ['foo', null, 'foo.php'],
            ['foo', null, 'foo.html.php', 'html.php'],
            ['foo.phtml', null, 'foo.phtml'],
            ['foo/zoo', 'foo', 'zoo.php'],
            ['foo/', 'foo', 'index.php']
        ];
    }
    
    /**
     * @dataProvider filenameProvider
     * 
     * @param string      $name
     * @param string|null $dir
     * @param string      $file
     * @param string      $defaultExt
     */
    public function testRender($name, $dir, $file, $defaultExt = null)
    {
        if (isset($defaultExt)) {
            $this->view = new PHPView(['path' => vfsStream::url('root'), 'ext' => $defaultExt]);
        }
        
        if (isset($dir)) {
            $vfsDir = vfsStream::newDirectory($dir);
            $this->root->addChild($vfsDir);
        } else {
            $vfsDir = $this->root;
        }
        
        vfsStream::create([
            $file => 'Hello! <?= $color ?> and <?= $answer ?>'
        ], $vfsDir);
        
        $context = ['color' => 'blue', 'answer' => 42];
        
        $stream = $this->createMock(StreamInterface::class);
        $stream->expects($this->once())->method('write')->with('Hello! blue and 42');

        $newResponse = $this->createMock(ResponseInterface::class);
        $newResponse->expects($this->once())->method('getBody')->willReturn($stream);
        
        $response = $this->createMock(ResponseInterface::class);
        $response->expects($this->once())->method('withHeader')->with('Content-Type', 'text/html')
            ->willReturn($newResponse);
        
        $this->view->render($response, $name, $context);
    }
    
    /**
     * @expectedException InvalidArgumentException
     */
    public function testRenderWithInvalidFile()
    {
        $response = $this->createMock(ResponseInterface::class);
        
        $this->view->render($response, '../secret.yml');
    }
    
    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage View file 'vfs://root/bar.php' doesn't exist
     */
    public function testRenderWithUnknownFile()
    {
        $response = $this->createMock(ResponseInterface::class);
        
        $this->view->render($response, 'bar');
    }
}
