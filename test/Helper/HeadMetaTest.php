<?php

declare(strict_types=1);

namespace LaminasTest\View\Helper;

use Laminas\Escaper\Escaper;
use Laminas\View\Exception;
use Laminas\View\Exception\ExceptionInterface as ViewException;
use Laminas\View\Helper;
use Laminas\View\Helper\Doctype;
use Laminas\View\Helper\HeadMeta;
use Laminas\View\Renderer\PhpRenderer as View;
use PHPUnit\Framework\TestCase;

use function array_shift;
use function restore_error_handler;
use function set_error_handler;
use function sprintf;
use function str_replace;
use function substr_count;
use function ucwords;

use const E_USER_WARNING;
use const PHP_EOL;

class HeadMetaTest extends TestCase
{
    private HeadMeta $helper;
    private Escaper $escaper;
    private View $view;

    /**
     * Sets up the fixture, for example, open a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp(): void
    {
        Doctype::unsetDoctypeRegistry();
        $this->view = new View();
        $doctype    = $this->view->plugin(Doctype::class);
        $doctype->__invoke('XHTML1_STRICT');
        $this->helper = new HeadMeta();
        $this->helper->setView($this->view);
        $this->escaper = new Escaper();
    }

    public function testHeadMetaReturnsObjectInstance(): void
    {
        $placeholder = $this->helper->__invoke();
        $this->assertInstanceOf(Helper\HeadMeta::class, $placeholder);
    }

    public function testThatAppendThrowsExceptionWhenNonMetaValueIsProvided(): void
    {
        $this->expectException(Exception\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid value passed to append');
        $this->helper->append('foo');
    }

    public function testThatOffsetSetThrowsExceptionWhenNonMetaValueIsProvided(): void
    {
        $this->expectException(Exception\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid value passed to offsetSet');
        $this->helper->offsetSet(3, 'foo');
    }

    public function testThatPrependThrowsExceptionWhenNonMetaValueIsProvided(): void
    {
        $this->expectException(Exception\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid value passed to prepend');
        $this->helper->prepend('foo');
    }

    public function testThatSetThrowsExceptionWhenNonMetaValueIsProvided(): void
    {
        $this->expectException(Exception\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid value passed to set');
        $this->helper->set('foo');
    }

    private function inflectAction(string $type): string
    {
        $type = str_replace('-', ' ', $type);
        $type = ucwords($type);

        return str_replace(' ', '', $type);
    }

    protected function executeOverloadAppend(string $type): void
    {
        $action = 'append' . $this->inflectAction($type);
        $string = 'foo';
        for ($i = 0; $i < 3; ++$i) {
            $string .= ' foo';
            $this->helper->$action('keywords', $string);
            $values = $this->helper->getContainer()->getArrayCopy();
            $this->assertCount($i + 1, $values);

            $item = $values[$i];
            $this->assertObjectHasProperty('type', $item);
            $this->assertObjectHasProperty('modifiers', $item);
            $this->assertObjectHasProperty('content', $item);
            $this->assertObjectHasProperty($item->type, $item);
            $this->assertEquals('keywords', $item->{$item->type});
            $this->assertEquals($string, $item->content);
        }
    }

    protected function executeOverloadPrepend(string $type): void
    {
        $action = 'prepend' . $this->inflectAction($type);
        $string = 'foo';
        for ($i = 0; $i < 3; ++$i) {
            $string .= ' foo';
            $this->helper->$action('keywords', $string);
            $values = $this->helper->getContainer()->getArrayCopy();
            self::assertCount($i + 1, $values);
            $item = array_shift($values);
            self::assertIsObject($item);
            $this->assertObjectHasProperty('type', $item);
            $this->assertObjectHasProperty('modifiers', $item);
            $this->assertObjectHasProperty('content', $item);
            $this->assertObjectHasProperty($item->type, $item);
            $this->assertEquals('keywords', $item->{$item->type});
            $this->assertEquals($string, $item->content);
        }
    }

    protected function executeOverloadSet(string $type): void
    {
        $setAction    = 'set' . $this->inflectAction($type);
        $appendAction = 'append' . $this->inflectAction($type);
        $string       = 'foo';
        for ($i = 0; $i < 3; ++$i) {
            $this->helper->$appendAction('keywords', $string);
            $string .= ' foo';
        }
        $this->helper->$setAction('keywords', $string);

        $values = $this->helper->getContainer()->getArrayCopy();
        $this->assertCount(1, $values);
        $item = array_shift($values);
        self::assertIsObject($item);

        $this->assertObjectHasProperty('type', $item);
        $this->assertObjectHasProperty('modifiers', $item);
        $this->assertObjectHasProperty('content', $item);
        $this->assertObjectHasProperty($item->type, $item);
        $this->assertEquals('keywords', $item->{$item->type});
        $this->assertEquals($string, $item->content);
    }

    public function testOverloadingAppendNameAppendsMetaTagToStack(): void
    {
        $this->executeOverloadAppend('name');
    }

    public function testOverloadingPrependNamePrependsMetaTagToStack(): void
    {
        $this->executeOverloadPrepend('name');
    }

    public function testOverloadingSetNameOverwritesMetaTagStack(): void
    {
        $this->executeOverloadSet('name');
    }

    public function testOverloadingAppendHttpEquivAppendsMetaTagToStack(): void
    {
        $this->executeOverloadAppend('http-equiv');
    }

    public function testOverloadingPrependHttpEquivPrependsMetaTagToStack(): void
    {
        $this->executeOverloadPrepend('http-equiv');
    }

    public function testOverloadingSetHttpEquivOverwritesMetaTagStack(): void
    {
        $this->executeOverloadSet('http-equiv');
    }

    public function testOverloadingThrowsExceptionWithFewerThanTwoArgs(): void
    {
        $this->expectException(Exception\ExceptionInterface::class);
        /** @psalm-suppress TooFewArguments */
        $this->helper->setName('foo');
    }

    public function testOverloadingThrowsExceptionWithInvalidMethodType(): void
    {
        $this->expectException(Exception\ExceptionInterface::class);
        /** @psalm-suppress UndefinedMagicMethod */
        $this->helper->setFoo('foo');
    }

    public function testCanBuildMetaTagsWithAttributes(): void
    {
        $this->helper->setName('keywords', 'foo bar', ['lang' => 'us_en', 'scheme' => 'foo', 'bogus' => 'unused']);
        $value = $this->helper->getContainer()->getValue();
        self::assertIsObject($value);
        $this->assertObjectHasProperty('modifiers', $value);
        $modifiers = $value->modifiers;
        $this->assertArrayHasKey('lang', $modifiers);
        $this->assertEquals('us_en', $modifiers['lang']);
        $this->assertArrayHasKey('scheme', $modifiers);
        $this->assertEquals('foo', $modifiers['scheme']);
    }

    public function testToStringReturnsValidHtml(): void
    {
        $this->helper->setName('keywords', 'foo bar', ['lang' => 'us_en', 'scheme' => 'foo', 'bogus' => 'unused'])
                     ->prependName('title', 'boo bah')
                     ->appendHttpEquiv('screen', 'projection');
        $string = $this->helper->toString();

        $metas = substr_count($string, '<meta ');
        $this->assertEquals(3, $metas);
        $metas = substr_count($string, '/>');
        $this->assertEquals(3, $metas);
        $metas = substr_count($string, 'name="');
        $this->assertEquals(2, $metas);
        $metas = substr_count($string, 'http-equiv="');
        $this->assertEquals(1, $metas);

        $this->assertStringContainsString('http-equiv="screen" content="projection"', $string);
        $this->assertStringContainsString(
            'name="keywords" content="' . $this->escaper->escapeHtmlAttr('foo bar') . '"',
            $string,
        );
        $this->assertStringContainsString('lang="us_en"', $string);
        $this->assertStringContainsString('scheme="foo"', $string);
        $this->assertStringNotContainsString('bogus', $string);
        $this->assertStringNotContainsString('unused', $string);
        $this->assertStringContainsString(
            'name="title" content="' . $this->escaper->escapeHtmlAttr('boo bah') . '"',
            $string,
        );
    }

    public function testToStringWhenInvalidKeyProvidedShouldConvertThrownException(): void
    {
        $this->helper->__invoke('some-content', 'tag value', 'not allowed key');
        $error = null;
        set_error_handler(function (int $code, string $message) use (&$error): bool {
            self::assertSame(E_USER_WARNING, $code);
            $error = $message;

            return true;
        }, E_USER_WARNING);
        $string = $this->helper->toString();
        self::assertEquals('', $string);
        self::assertEquals(
            'Invalid type "not allowed key" provided for meta',
            $error,
        );
        restore_error_handler();
    }

    public function testHeadMetaHelperCreatesItemEntry(): void
    {
        $this->helper->__invoke('foo', 'keywords');
        $values = $this->helper->getContainer()->getArrayCopy();
        $this->assertCount(1, $values);
        $item = array_shift($values);
        $this->assertEquals('foo', $item->content);
        $this->assertEquals('name', $item->type);
        $this->assertEquals('keywords', $item->name);
    }

    public function testOverloadingOffsetInsertsAtOffset(): void
    {
        $this->helper->offsetSetName(100, 'keywords', 'foo');
        $values = $this->helper->getContainer()->getArrayCopy();
        $this->assertCount(1, $values);
        $this->assertArrayHasKey(100, $values);
        $item = $values[100];
        $this->assertEquals('foo', $item->content);
        $this->assertEquals('name', $item->type);
        $this->assertEquals('keywords', $item->name);
    }

    public function testIndentationIsHonored(): void
    {
        $this->helper->setIndent(4);
        $this->helper->appendName('keywords', 'foo bar');
        $this->helper->appendName('seo', 'baz bat');
        $string = $this->helper->toString();

        $scripts = substr_count($string, '    <meta name=');
        $this->assertEquals(2, $scripts);
    }

    public function testStringRepresentationReflectsDoctype(): void
    {
        $this->view->plugin(Doctype::class)->__invoke('HTML4_STRICT');
        $this->helper->__invoke('some content', 'foo');

        $test = $this->helper->toString();

        $this->assertStringNotContainsString('/>', $test);
        $this->assertStringContainsString($this->escaper->escapeHtmlAttr('some content'), $test);
        $this->assertStringContainsString('foo', $test);
    }

    public function testSetNameDoesntClobber(): void
    {
        $this->helper->setName('keywords', 'foo');
        $this->helper->appendHttpEquiv('pragma', 'bar');
        $this->helper->appendHttpEquiv('Cache-control', 'baz');
        $this->helper->setName('keywords', 'bat');

        $this->assertEquals(
            '<meta http-equiv="pragma" content="bar" />' . PHP_EOL . '<meta http-equiv="Cache-control" content="baz" />'
            . PHP_EOL . '<meta name="keywords" content="bat" />',
            $this->helper->toString()
        );
    }

    public function testSetNameDoesntClobberPart2(): void
    {
        $this->helper->setName('keywords', 'foo');
        $this->helper->setName('description', 'foo');
        $this->helper->appendHttpEquiv('pragma', 'baz');
        $this->helper->appendHttpEquiv('Cache-control', 'baz');
        $this->helper->setName('keywords', 'bar');

        $expected = sprintf(
            '<meta name="description" content="foo" />%1$s'
            . '<meta http-equiv="pragma" content="baz" />%1$s'
            . '<meta http-equiv="Cache-control" content="baz" />%1$s'
            . '<meta name="keywords" content="bar" />',
            PHP_EOL
        );

        $this->assertEquals($expected, $this->helper->toString());
    }

    public function testPlacesMetaTagsInProperOrder(): void
    {
        $this->helper->setName('keywords', 'foo');
        $this->helper->__invoke(
            'some content',
            'bar',
            'name',
            [],
            Helper\Placeholder\Container\AbstractContainer::PREPEND
        );

        $expected = sprintf(
            '<meta name="bar" content="%s" />%s'
            . '<meta name="keywords" content="foo" />',
            $this->escaper->escapeHtmlAttr('some content'),
            PHP_EOL
        );
        $this->assertEquals($expected, $this->helper->toString());
    }

    public function testContainerMaintainsCorrectOrderOfItems(): void
    {
        $this->helper->offsetSetName(1, 'keywords', 'foo');
        $this->helper->offsetSetName(10, 'description', 'foo');
        $this->helper->offsetSetHttpEquiv(20, 'pragma', 'baz');
        $this->helper->offsetSetHttpEquiv(5, 'Cache-control', 'baz');

        $test = $this->helper->toString();

        $expected = sprintf(
            '<meta name="keywords" content="foo" />%1$s'
            . '<meta http-equiv="Cache-control" content="baz" />%1$s'
            . '<meta name="description" content="foo" />%1$s'
            . '<meta http-equiv="pragma" content="baz" />',
            PHP_EOL
        );

        $this->assertEquals($expected, $test);
    }

    public function testCharsetValidateFail(): void
    {
        $view = new View();
        $view->plugin(Doctype::class)->__invoke('HTML4_STRICT');

        $this->expectException(Exception\ExceptionInterface::class);
        $this->helper->setCharset('utf-8');
    }

    public function testCharset(): void
    {
        $view = new View();
        $view->plugin(Doctype::class)->__invoke('HTML5');

        $this->helper->setCharset('utf-8');
        $this->assertEquals(
            '<meta charset="utf-8">',
            $this->helper->toString()
        );

        $view->plugin(Doctype::class)->__invoke('XHTML5');

        $this->assertEquals(
            '<meta charset="utf-8"/>',
            $this->helper->toString()
        );
    }

    public function testCharsetPosition(): void
    {
        $view = new View();
        $view->plugin(Doctype::class)->__invoke('HTML5');

        $this->helper
            ->setProperty('description', 'foobar')
            ->setCharset('utf-8');

        $this->assertEquals(
            '<meta charset="utf-8">' . PHP_EOL
            . '<meta property="description" content="foobar">',
            $this->helper->toString()
        );
    }

    public function testCharsetWithXhtmlDoctypeGotException(): void
    {
        $this->expectException(Exception\InvalidArgumentException::class);
        $this->expectExceptionMessage('XHTML* doctype has no attribute charset; please use appendHttpEquiv()');

        $view = new View();
        $view->plugin(Doctype::class)->__invoke('XHTML1_RDFA');

        $this->helper
             ->setCharset('utf-8');
    }

    public function testPropertyIsSupportedWithRdfaDoctype(): void
    {
        $this->view->doctype('XHTML1_RDFA');
        $this->helper->__invoke('foo', 'og:title', 'property');

        $expected = sprintf('<meta property="%s" content="foo" />', $this->escaper->escapeHtmlAttr('og:title'));
        $this->assertEquals($expected, $this->helper->toString());
    }

    public function testPropertyIsNotSupportedByDefaultDoctype(): void
    {
        try {
            $this->helper->__invoke('foo', 'og:title', 'property');
            $this->fail('meta property attribute should not be supported on default doctype');
        } catch (ViewException $e) {
            $this->assertStringContainsString('Invalid value passed', $e->getMessage());
        }
    }

    /**
     * @depends testPropertyIsSupportedWithRdfaDoctype
     */
    public function testOverloadingAppendPropertyAppendsMetaTagToStack(): void
    {
        $this->view->doctype('XHTML1_RDFA');
        $this->executeOverloadAppend('property');
    }

    /**
     * @depends testPropertyIsSupportedWithRdfaDoctype
     */
    public function testOverloadingPrependPropertyPrependsMetaTagToStack(): void
    {
        $this->view->doctype('XHTML1_RDFA');
        $this->executeOverloadPrepend('property');
    }

    /**
     * @depends testPropertyIsSupportedWithRdfaDoctype
     */
    public function testOverloadingSetPropertyOverwritesMetaTagStack(): void
    {
        $this->view->doctype('XHTML1_RDFA');
        $this->executeOverloadSet('property');
    }

    public function testItempropIsSupportedWithHtml5Doctype(): void
    {
        $this->view->doctype('HTML5');
        $this->helper->__invoke('HeadMeta with Microdata', 'description', 'itemprop');

        $expected = sprintf(
            '<meta itemprop="description" content="%s">',
            $this->escaper->escapeHtmlAttr('HeadMeta with Microdata'),
        );
        $this->assertEquals($expected, $this->helper->toString());
    }

    public function testItempropIsNotSupportedByDefaultDoctype(): void
    {
        try {
            $this->helper->__invoke('HeadMeta with Microdata', 'description', 'itemprop');
            $this->fail('meta itemprop attribute should not be supported on default doctype');
        } catch (ViewException $e) {
            $this->assertStringContainsString('Invalid value passed', $e->getMessage());
        }
    }

    /**
     * @depends testItempropIsSupportedWithHtml5Doctype
     */
    public function testOverloadingAppendItempropAppendsMetaTagToStack(): void
    {
        $this->view->doctype('HTML5');
        $this->executeOverloadAppend('itemprop');
    }

    /**
     * @depends testItempropIsSupportedWithHtml5Doctype
     */
    public function testOverloadingPrependItempropPrependsMetaTagToStack(): void
    {
        $this->view->doctype('HTML5');
        $this->executeOverloadPrepend('itemprop');
    }

    /**
     * @depends testItempropIsSupportedWithHtml5Doctype
     */
    public function testOverloadingSetItempropOverwritesMetaTagStack(): void
    {
        $this->view->doctype('HTML5');
        $this->executeOverloadSet('itemprop');
    }

    public function testConditional(): void
    {
        $html = $this->helper->appendHttpEquiv('foo', 'bar', ['conditional' => 'lt IE 7'])->toString();

        $this->assertMatchesRegularExpression("|^<!--\[if lt IE 7\]>|", $html);
        $this->assertMatchesRegularExpression("|<!\[endif\]-->$|", $html);
    }

    public function testConditionalNoIE(): void
    {
        $html = $this->helper->appendHttpEquiv('foo', 'bar', ['conditional' => '!IE'])->toString();

        $this->assertStringContainsString('<!--[if !IE]><!--><', $html);
        $this->assertStringContainsString('<!--<![endif]-->', $html);
    }

    public function testConditionalNoIEWidthSpace(): void
    {
        $html = $this->helper->appendHttpEquiv('foo', 'bar', ['conditional' => '! IE'])->toString();

        $this->assertStringContainsString('<!--[if ! IE]><!--><', $html);
        $this->assertStringContainsString('<!--<![endif]-->', $html);
    }

    public function testTurnOffAutoEscapeDoesNotEncode(): void
    {
        $this->helper->setAutoEscape(false)->appendHttpEquiv('foo', 'bar=baz');
        $this->assertEquals('<meta http-equiv="foo" content="bar=baz" />', $this->helper->toString());
    }
}
