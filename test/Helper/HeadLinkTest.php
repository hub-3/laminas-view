<?php

declare(strict_types=1);

namespace LaminasTest\View\Helper;

use Laminas\View\Exception;
use Laminas\View\Helper;
use Laminas\View\Helper\Doctype;
use Laminas\View\Helper\EscapeHtmlAttr;
use Laminas\View\Helper\HeadLink;
use Laminas\View\Renderer\PhpRenderer as View;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

use function array_fill;
use function count;
use function sprintf;
use function substr_count;
use function var_export;

use const PHP_EOL;

class HeadLinkTest extends TestCase
{
    private HeadLink $helper;
    private EscapeHtmlAttr $attributeEscaper;
    private View $view;

    /**
     * Sets up the fixture, for example, open a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp(): void
    {
        Helper\Doctype::unsetDoctypeRegistry();
        $this->view   = new View();
        $this->helper = new HeadLink();
        $this->helper->setView($this->view);
        $this->attributeEscaper = new EscapeHtmlAttr();
    }

    public function testHeadLinkReturnsObjectInstance(): void
    {
        $placeholder = $this->helper->__invoke();
        $this->assertInstanceOf(HeadLink::class, $placeholder);
    }

    public function testPrependThrowsExceptionWithoutArrayArgument(): void
    {
        $this->expectException(Exception\ExceptionInterface::class);
        /** @psalm-suppress InvalidArgument */
        $this->helper->prepend('foo');
    }

    public function testAppendThrowsExceptionWithoutArrayArgument(): void
    {
        $this->expectException(Exception\ExceptionInterface::class);
        /** @psalm-suppress InvalidArgument */
        $this->helper->append('foo');
    }

    public function testSetThrowsExceptionWithoutArrayArgument(): void
    {
        $this->expectException(Exception\ExceptionInterface::class);
        /** @psalm-suppress InvalidArgument */
        $this->helper->set('foo');
    }

    public function testOffsetSetThrowsExceptionWithoutArrayArgument(): void
    {
        $this->expectException(Exception\ExceptionInterface::class);
        /** @psalm-suppress InvalidArgument */
        $this->helper->offsetSet(1, 'foo');
    }

    public function testCreatingLinkStackViaHeadLinkCreatesAppropriateOutput(): void
    {
        $links = [
            'link1' => ['rel' => 'stylesheet', 'type' => 'text/css', 'href' => 'foo'],
            'link2' => ['rel' => 'stylesheet', 'type' => 'text/css', 'href' => 'bar'],
            'link3' => ['rel' => 'stylesheet', 'type' => 'text/css', 'href' => 'baz'],
        ];
        $this->helper->headLink($links['link1'])
                     ->headLink($links['link2'], 'PREPEND')
                     ->headLink($links['link3']);

        $string = $this->helper->toString();
        $lines  = substr_count($string, PHP_EOL);
        $this->assertEquals(2, $lines);
        $lines = substr_count($string, '<link ');
        $this->assertEquals(3, $lines, $string);

        $attributeEscaper = $this->attributeEscaper;

        foreach ($links as $link) {
            $substr = ' href="' . $attributeEscaper($link['href']) . '"';
            $this->assertStringContainsString($substr, $string);
            $substr = ' rel="' . $attributeEscaper($link['rel']) . '"';
            $this->assertStringContainsString($substr, $string);
            $substr = ' type="' . $attributeEscaper($link['type']) . '"';
            $this->assertStringContainsString($substr, $string);
        }

        $order = [];
        foreach ($this->helper as $key => $value) {
            if (isset($value->href)) {
                $order[$key] = $value->href;
            }
        }
        $expected = ['bar', 'foo', 'baz'];
        $this->assertSame($expected, $order);
    }

    public function testCreatingLinkStackViaStyleSheetMethodsCreatesAppropriateOutput(): void
    {
        $links = [
            'link1' => ['rel' => 'stylesheet', 'type' => 'text/css', 'href' => 'foo'],
            'link2' => ['rel' => 'stylesheet', 'type' => 'text/css', 'href' => 'bar'],
            'link3' => ['rel' => 'stylesheet', 'type' => 'text/css', 'href' => 'baz'],
        ];
        $this->helper->appendStylesheet($links['link1']['href'])
                     ->prependStylesheet($links['link2']['href'])
                     ->appendStylesheet($links['link3']['href']);

        $string = $this->helper->toString();
        $lines  = substr_count($string, PHP_EOL);
        $this->assertEquals(2, $lines);
        $lines = substr_count($string, '<link ');
        $this->assertEquals(3, $lines, $string);

        $attributeEscaper = $this->attributeEscaper;

        foreach ($links as $link) {
            $substr = ' href="' . $attributeEscaper($link['href']) . '"';
            $this->assertStringContainsString($substr, $string);
            $substr = ' rel="' . $attributeEscaper($link['rel']) . '"';
            $this->assertStringContainsString($substr, $string);
            $substr = ' type="' . $attributeEscaper($link['type']) . '"';
            $this->assertStringContainsString($substr, $string);
        }

        $order = [];
        foreach ($this->helper as $key => $value) {
            if (isset($value->href)) {
                $order[$key] = $value->href;
            }
        }
        $expected = ['bar', 'foo', 'baz'];
        $this->assertSame($expected, $order);
    }

    public function testCreatingLinkStackViaAlternateMethodsCreatesAppropriateOutput(): void
    {
        $links = [
            'link1' => ['title' => 'stylesheet', 'type' => 'text/css', 'href' => 'foo'],
            'link2' => ['title' => 'stylesheet', 'type' => 'text/css', 'href' => 'bar'],
            'link3' => ['title' => 'stylesheet', 'type' => 'text/css', 'href' => 'baz'],
        ];
        $where = 'append';
        foreach ($links as $link) {
            $method = $where . 'Alternate';
            $this->helper->$method($link['href'], $link['type'], $link['title']);
            $where = 'append' === $where ? 'prepend' : 'append';
        }

        $string = $this->helper->toString();
        $lines  = substr_count($string, PHP_EOL);
        $this->assertEquals(2, $lines);
        $lines = substr_count($string, '<link ');
        $this->assertEquals(3, $lines, $string);
        $lines = substr_count($string, ' rel="alternate"');
        $this->assertEquals(3, $lines, $string);

        $attributeEscaper = $this->attributeEscaper;

        foreach ($links as $link) {
            $substr = ' href="' . $attributeEscaper($link['href']) . '"';
            $this->assertStringContainsString($substr, $string);
            $substr = ' title="' . $attributeEscaper($link['title']) . '"';
            $this->assertStringContainsString($substr, $string);
            $substr = ' type="' . $attributeEscaper($link['type']) . '"';
            $this->assertStringContainsString($substr, $string);
        }

        $order = [];
        foreach ($this->helper as $key => $value) {
            if (isset($value->href)) {
                $order[$key] = $value->href;
            }
        }
        $expected = ['bar', 'foo', 'baz'];
        $this->assertSame($expected, $order);
    }

    public function testOverloadingThrowsExceptionWithNoArguments(): void
    {
        $this->expectException(Exception\ExceptionInterface::class);
        /** @psalm-suppress TooFewArguments */
        $this->helper->appendStylesheet();
    }

    public function testOverloadingShouldAllowSingleArrayArgument(): void
    {
        $this->helper->setStylesheet(['href' => '/styles.css']);
        $link = $this->helper->getValue();
        $this->assertEquals('/styles.css', $link->href);
    }

    public function testOverloadingUsingSingleArrayArgumentWithInvalidValuesThrowsException(): void
    {
        $this->expectException(Exception\ExceptionInterface::class);
        $this->helper->setStylesheet(['bogus' => 'unused']);
    }

    public function testOverloadingOffsetSetWorks(): void
    {
        $this->helper->offsetSetStylesheet(100, '/styles.css');
        $items = $this->helper->getArrayCopy();
        $this->assertTrue(isset($items[100]));
        $link = $items[100];
        $this->assertEquals('/styles.css', $link->href);
    }

    public function testOverloadingThrowsExceptionWithInvalidMethod(): void
    {
        $this->expectException(Exception\ExceptionInterface::class);
        $this->helper->bogusMethod();
    }

    public function testStylesheetAttributesGetSet(): void
    {
        $this->helper->setStylesheet('/styles.css', 'projection', 'ie6');
        $item = $this->helper->getValue();
        $this->assertObjectHasProperty('media', $item);
        $this->assertObjectHasProperty('conditionalStylesheet', $item);

        $this->assertEquals('projection', $item->media);
        $this->assertEquals('ie6', $item->conditionalStylesheet);
    }

    public function testConditionalStylesheetNotCreatedByDefault(): void
    {
        $this->helper->setStylesheet('/styles.css');
        $item = $this->helper->getValue();
        $this->assertObjectHasProperty('conditionalStylesheet', $item);
        $this->assertFalse($item->conditionalStylesheet);

        $attributeEscaper = $this->attributeEscaper;

        $string = $this->helper->toString();
        $this->assertStringContainsString($attributeEscaper('/styles.css'), $string);
        $this->assertStringNotContainsString('<!--[if', $string);
        $this->assertStringNotContainsString(']>', $string);
        $this->assertStringNotContainsString('<![endif]-->', $string);
    }

    public function testConditionalStylesheetCreationOccursWhenRequested(): void
    {
        $this->helper->setStylesheet('/styles.css', 'screen', 'ie6');
        $item = $this->helper->getValue();
        $this->assertObjectHasProperty('conditionalStylesheet', $item);
        $this->assertEquals('ie6', $item->conditionalStylesheet);

        $attributeEscaper = $this->attributeEscaper;

        $string = $this->helper->toString();
        $this->assertStringContainsString($attributeEscaper('/styles.css'), $string);
        $this->assertStringContainsString('<!--[if ie6]>', $string);
        $this->assertStringContainsString('<![endif]-->', $string);
    }

    public function testConditionalStylesheetCreationNoIE(): void
    {
        $this->helper->setStylesheet('/styles.css', 'screen', '!IE');
        $item = $this->helper->getValue();
        $this->assertObjectHasProperty('conditionalStylesheet', $item);
        $this->assertEquals('!IE', $item->conditionalStylesheet);

        $attributeEscaper = $this->attributeEscaper;

        $string = $this->helper->toString();
        $this->assertStringContainsString($attributeEscaper('/styles.css'), $string);
        $this->assertStringContainsString('<!--[if !IE]><!--><', $string);
        $this->assertStringContainsString('<!--<![endif]-->', $string);
    }

    public function testConditionalStylesheetCreationNoIEWidthSpaces(): void
    {
        $this->helper->setStylesheet('/styles.css', 'screen', '! IE');
        $item = $this->helper->getValue();
        $this->assertObjectHasProperty('conditionalStylesheet', $item);
        $this->assertEquals('! IE', $item->conditionalStylesheet);

        $attributeEscaper = $this->attributeEscaper;

        $string = $this->helper->toString();
        $this->assertStringContainsString($attributeEscaper('/styles.css'), $string);
        $this->assertStringContainsString('<!--[if ! IE]><!--><', $string);
        $this->assertStringContainsString('<!--<![endif]-->', $string);
    }

    /** @return array<string, array{0: int}> */
    public static function argumentCountProvider(): array
    {
        return [
            'One' => [1],
            'Two' => [2],
        ];
    }

    #[DataProvider('argumentCountProvider')]
    public function testSettingAlternateWithTooFewArgsRaisesException(int $argumentCount): void
    {
        $arguments = array_fill(0, $argumentCount, 'foo');
        $this->expectException(Exception\InvalidArgumentException::class);
        $this->expectExceptionMessage(sprintf(
            'Alternate tags require 3 arguments; %d provided',
            $argumentCount
        ));
        $this->helper->setAlternate(...$arguments);
    }

    public function testIndentationIsHonored(): void
    {
        $this->helper->setIndent(4);
        $this->helper->appendStylesheet('/css/screen.css');
        $this->helper->appendStylesheet('/css/rules.css');
        $string = $this->helper->toString();

        $scripts = substr_count($string, '    <link ');
        $this->assertEquals(2, $scripts);
    }

    public function testLinkRendersAsPlainHtmlIfDoctypeNotXhtml(): void
    {
        $this->view->plugin(Doctype::class)->__invoke('HTML4_STRICT');
        $this->helper->__invoke(['rel' => 'icon', 'src' => '/foo/bar'])
                     ->__invoke(['rel' => 'foo', 'href' => '/bar/baz']);
        $test = $this->helper->toString();
        $this->assertStringNotContainsString(' />', $test);
    }

    public function testDoesNotAllowDuplicateStylesheets(): void
    {
        $this->helper->appendStylesheet('foo');
        $this->helper->appendStylesheet('foo');
        $this->assertEquals(1, count($this->helper), var_export($this->helper->getContainer()->getArrayCopy(), true));
    }

    public function testBooleanStylesheet(): void
    {
        $this->helper->appendStylesheet(['href' => '/bar/baz', 'conditionalStylesheet' => false]);
        $test = $this->helper->toString();
        $this->assertStringNotContainsString('[if false]', $test);
    }

    public function testBooleanTrueConditionalStylesheet(): void
    {
        $this->helper->appendStylesheet(['href' => '/bar/baz', 'conditionalStylesheet' => true]);
        $test = $this->helper->toString();
        $this->assertStringNotContainsString('[if 1]', $test);
        $this->assertStringNotContainsString('[if true]', $test);
    }

    public function testTurnOffAutoEscapeDoesNotEncodeAmpersand(): void
    {
        $this->helper->setAutoEscape(false)->appendStylesheet('/css/rules.css?id=123&foo=bar');
        $this->assertStringContainsString('id=123&foo=bar', $this->helper->toString());
    }

    public function testSetAlternateWithExtras(): void
    {
        $this->helper->setAlternate('/mydocument.pdf', 'application/pdf', 'foo', ['media' => ['print', 'screen']]);
        $test = $this->helper->toString();
        $this->assertStringContainsString('media="print,screen"', $test);
    }

    public function testAppendStylesheetWithExtras(): void
    {
        $this->helper->appendStylesheet([
            'href'                  => '/bar/baz',
            'conditionalStylesheet' => false,
            'extras'                => ['id' => 'my_link_tag'],
        ]);
        $test = $this->helper->toString();
        $this->assertStringContainsString('id="my_link_tag"', $test);
    }

    public function testSetStylesheetWithMediaAsArray(): void
    {
        $this->helper->appendStylesheet('/bar/baz', ['screen', 'print']);
        $test = $this->helper->toString();
        $this->assertStringContainsString(' media="screen,print"', $test);
    }

    public function testSetPrevRelationship(): void
    {
        $this->helper->appendPrev('/foo/bar');
        $test = $this->helper->toString();

        $attributeEscaper = $this->attributeEscaper;

        $this->assertStringContainsString('href="' . $attributeEscaper('/foo/bar') . '"', $test);
        $this->assertStringContainsString('rel="prev"', $test);
    }

    public function testSetNextRelationship(): void
    {
        $this->helper->appendNext('/foo/bar');
        $test = $this->helper->toString();

        $attributeEscaper = $this->attributeEscaper;

        $this->assertStringContainsString('href="' . $attributeEscaper('/foo/bar') . '"', $test);
        $this->assertStringContainsString('rel="next"', $test);
    }

    public function testContainerMaintainsCorrectOrderOfItems(): void
    {
        $this->helper->__invoke()->offsetSetStylesheet(1, '/test1.css');
        $this->helper->__invoke()->offsetSetStylesheet(10, '/test2.css');
        $this->helper->__invoke()->offsetSetStylesheet(20, '/test3.css');
        $this->helper->__invoke()->offsetSetStylesheet(5, '/test4.css');

        $attributeEscaper = $this->attributeEscaper;

        $test = $this->helper->toString();

        $expected = sprintf(
            '<link href="%3$s" media="screen" rel="stylesheet" type="%2$s">%1$s'
            . '<link href="%4$s" media="screen" rel="stylesheet" type="%2$s">%1$s'
            . '<link href="%5$s" media="screen" rel="stylesheet" type="%2$s">%1$s'
            . '<link href="%6$s" media="screen" rel="stylesheet" type="%2$s">',
            PHP_EOL,
            $attributeEscaper('text/css'),
            $attributeEscaper('/test1.css'),
            $attributeEscaper('/test4.css'),
            $attributeEscaper('/test2.css'),
            $attributeEscaper('/test3.css')
        );

        $this->assertEquals($expected, $test);
    }

    public function testIdAttributeIsSupported(): void
    {
        $this->helper->appendStylesheet(['href' => '/bar/baz', 'id' => 'foo']);
        $this->assertStringContainsString('id="foo"', $this->helper->toString());
    }

    public function testSizesAttributeIsSupported(): void
    {
        $this->helper->appendStylesheet(['rel' => 'icon', 'href' => '/bar/baz', 'sizes' => '123x456']);
        $this->assertStringContainsString('sizes="123x456"', $this->helper->toString());
    }

    public function testItempropAttributeIsSupported(): void
    {
        /** @psalm-suppress TooFewArguments */
        $this->helper->prependAlternate(['itemprop' => 'url', 'href' => '/bar/baz', 'rel' => 'canonical']);
        $this->assertStringContainsString('itemprop="url"', $this->helper->toString());
    }

    public function testAsAttributeIsSupported(): void
    {
        $this->helper->headLink(['as' => 'style', 'href' => '/foo/bar.css', 'rel' => 'preload']);
        $this->assertStringContainsString('as="style"', $this->helper->toString());
    }
}
