<?php

namespace Middlewares\tests;

use Middlewares\ContentType;
use Middlewares\Utils\Dispatcher;
use Middlewares\Utils\Factory;

class ContentTypeTest extends \PHPUnit_Framework_TestCase
{
    public function formatsProvider()
    {
        return [
            [
                '/',
                'application/xml;charset=UTF-8,application/xhtml+xml,text/html;q=0.9,text/plain;q=0.8',
                'text/html',
            ],
            [
                '/test.json',
                '',
                'application/json',
            ],
            [
                '/',
                '',
                'text/html',
            ],
            [
                '/',
                'application/xml,application/xhtml+xml,text/html;q=0.9,text/plain;q=0.8,image/png,*/*;q=0.5',
                'text/html',
            ],
            [
                '/',
                'text/html, image/gif, image/jpeg, *; q=0.2, */*; q=0.2',
                'text/html',
            ],
        ];
    }

    /**
     * @dataProvider formatsProvider
     */
    public function testFormats($uri, $accept, $mime)
    {
        $request = Factory::createServerRequest([], 'GET', $uri)->withHeader('Accept', $accept);

        $response = Dispatcher::run([
            new ContentType(),
            function ($request) {
                echo $request->getHeaderLine('Accept');
            },
        ], $request);

        $this->assertInstanceOf('Psr\\Http\\Message\\ResponseInterface', $response);
        $this->assertEquals($mime, (string) $response->getBody());
        $this->assertEquals($mime.'; charset=UTF-8', $response->getHeaderLine('Content-Type'));
        $this->assertEquals('nosniff', $response->getHeaderLine('X-Content-Type-Options'));
    }

    public function charsetProvider()
    {
        return [
            [
                ['UTF-8'],
                'application/xml,application/xhtml+xml,text/html;q=0.9,text/plain;q=0.8',
                'ISO-8859-1, UTF-8; q=0.9',
                'text/html; charset=UTF-8',
            ],[
                ['ISO-8859-1', 'UTF-8'],
                'application/xml,application/xhtml+xml,text/html;q=0.9,text/plain;q=0.8',
                'ISO-8859-1, UTF-8; q=0.9',
                'text/html; charset=ISO-8859-1',
            ],[
                ['ISO-8859-1', 'UTF-8'],
                'application/xml,application/xhtml+xml,text/html;q=0.9,text/plain;q=0.8',
                'UTF-8, ISO-8859-1; q=0.9',
                'text/html; charset=UTF-8',
            ],
        ];
    }

    /**
     * @dataProvider charsetProvider
     */
    public function testCharset($charsets, $accept, $acceptCharset, $result)
    {
        $request = Factory::createServerRequest([], 'GET', '/')
            ->withHeader('Accept-Charset', $acceptCharset)
            ->withHeader('Accept', $accept);

        $response = Dispatcher::run([
            (new ContentType())
                ->charsets($charsets),
        ], $request);

        $this->assertInstanceOf('Psr\\Http\\Message\\ResponseInterface', $response);
        $this->assertEquals($result, $response->getHeaderLine('Content-Type'));
    }
}
