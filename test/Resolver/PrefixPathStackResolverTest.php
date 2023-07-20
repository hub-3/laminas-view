<?php

declare(strict_types=1);

namespace LaminasTest\View\Resolver;

use Laminas\View\Resolver\PrefixPathStackResolver;
use Laminas\View\Resolver\TemplateMapResolver;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

use function realpath;

#[CoversClass(PrefixPathStackResolver::class)]
class PrefixPathStackResolverTest extends TestCase
{
    private string $basePath;

    /**
     * {@inheritDoc}
     */
    protected function setUp(): void
    {
        $this->basePath = realpath(__DIR__ . '/../_templates/prefix-path-stack-resolver');
    }

    public function testResolveWithoutPathPrefixes(): void
    {
        $resolver = new PrefixPathStackResolver();

        $this->assertNull($resolver->resolve(__DIR__));
        $this->assertNull($resolver->resolve(__FILE__));
        $this->assertNull($resolver->resolve('path/to/foo'));
        $this->assertNull($resolver->resolve('path/to/bar'));
    }

    public function testResolve(): void
    {
        $resolver = new PrefixPathStackResolver([
            'base1' => $this->basePath,
            'base2' => $this->basePath . '/baz',
        ]);

        $this->assertEmpty($resolver->resolve('base1/foo'));
        $this->assertSame(realpath($this->basePath . '/bar.phtml'), $resolver->resolve('base1/bar'));
        $this->assertEmpty($resolver->resolve('base2/tab'));
        $this->assertSame(realpath($this->basePath . '/baz/taz.phtml'), $resolver->resolve('base2/taz'));
    }

    public function testResolveWithCongruentPrefix(): void
    {
        $resolver = new PrefixPathStackResolver([
            'foo'    => $this->basePath,
            'foobar' => $this->basePath . '/baz',
        ]);

        $this->assertSame(realpath($this->basePath . '/bar.phtml'), $resolver->resolve('foo/bar'));
        $this->assertSame(realpath($this->basePath . '/baz/taz.phtml'), $resolver->resolve('foobar/taz'));
    }

    public function testSetCustomPathStackResolver(): void
    {
        $resolver = new PrefixPathStackResolver([
            'foo' => new TemplateMapResolver([
                '/bar' => '1111',
                '/baz' => '2222',
            ]),
        ]);

        $this->assertSame('1111', $resolver->resolve('foo/bar'));
        $this->assertSame('2222', $resolver->resolve('foo/baz'));
        $this->assertNull($resolver->resolve('foo/tab'));
    }
}
