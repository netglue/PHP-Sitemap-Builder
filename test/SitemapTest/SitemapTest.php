<?php

declare(strict_types=1);

namespace Netglue\SitemapTest;

use DateTime;
use Netglue\Sitemap\Exception\InvalidArgument;
use Netglue\Sitemap\Sitemap;
use Override;
use PHPUnit\Framework\TestCase;

final class SitemapTest extends TestCase
{
    private Sitemap $map;

    #[Override]
    public function setUp(): void
    {
        $this->map = new Sitemap('sitemap.xml', 'http://localhost');
    }

    public function testTheBaseUriMustBeAbsolute(): void
    {
        $this->expectException(InvalidArgument::class);
        new Sitemap('sitemap.xml', '/foo');
    }

    public function testInitialInstance(): void
    {
        self::assertSame('sitemap.xml', $this->map->getName());
        self::assertCount(0, $this->map);
    }

    public function testExceptionThrownForInvalidChangeFreq(): void
    {
        $this->expectException(InvalidArgument::class);
        $this->map->addUri('/test', null, 'nope');
    }

    public function testExceptionThrownForInvalidPriority(): void
    {
        $this->expectException(InvalidArgument::class);
        $this->map->addUri('/test', null, 'never', 1.5);
    }

    public function testValidUriIncreasesCount(): void
    {
        $this->map->addUri('/test', null, 'never', 0.9);
        self::assertCount(1, $this->map);
    }

    public function testDuplicateUrisDoesNotIncreaseCount(): void
    {
        $this->map->addUri('/test');
        self::assertCount(1, $this->map);
        $this->map->addUri('/test');
        self::assertCount(1, $this->map);
        $this->map->addUri('http://localhost/test');
        self::assertCount(1, $this->map);
    }

    public function testXmlIsRenderedWithZeroUrls(): void
    {
        $expect = '<urlset xmlns="https://www.sitemaps.org/schemas/sitemap/0.9"/>';
        self::assertXmlStringEqualsXmlString($expect, $this->map->toXmlString());
    }

    public function testExpectedXmlOutput(): void
    {
        $lastMod = DateTime::createFromFormat('Y-m-d H:i:s', '2018-01-01 12:00:00');
        $this->map->addUri('/test', $lastMod, 'never', 0.9);

        $file = __DIR__ . '/data/basic-sitemap.xml';
        self::assertXmlStringEqualsXmlFile($file, $this->map->toXmlString());
    }

    public function testSuccessiveCallsToOutputReturnsTheSameValue(): void
    {
        $lastMod = DateTime::createFromFormat('Y-m-d H:i:s', '2018-01-01 12:00:00');
        $this->map->addUri('/test', $lastMod, 'never', 0.9);

        $file = __DIR__ . '/data/basic-sitemap.xml';
        self::assertXmlStringEqualsXmlFile($file, $this->map->toXmlString());
        self::assertXmlStringEqualsXmlFile($file, $this->map->toXmlString());
    }

    public function testAddingUrisAfterRenderWillCauseReRender(): void
    {
        $lastMod = DateTime::createFromFormat('Y-m-d H:i:s', '2018-01-01 12:00:00');
        $this->map->addUri('/test', $lastMod, 'never', 0.9);

        $file = __DIR__ . '/data/basic-sitemap.xml';
        self::assertXmlStringEqualsXmlFile($file, $this->map->toXmlString());

        $lastMod = DateTime::createFromFormat('Y-m-d H:i:s', '2018-01-02 12:00:00');
        $this->map->addUri('/test2', $lastMod, 'always', 0.1);

        $file = __DIR__ . '/data/sitemap-with-2-uris.xml';
        self::assertXmlStringEqualsXmlFile($file, $this->map->toXmlString());
    }

    public function testToArrayReturnsArray(): void
    {
        $value = $this->map->toArray();
        self::assertCount(0, $value);

        $lastMod = DateTime::createFromFormat('Y-m-d H:i:s', '2018-01-01 12:00:00');
        $this->map->addUri('/test', $lastMod, 'never', 0.9);

        $value = $this->map->toArray();
        self::assertCount(1, $value);
    }

    public function testToStringReturnsXmlValue(): void
    {
        $string = (string) $this->map;

        self::assertSame($this->map->toXmlString(), $string);
    }

    public function testBaseUrlIsPrependedWhenAppropriate(): void
    {
        $this->map->addUri('/test');
        $this->map->addUri('http://www.example.com/test');

        $urls = $this->map->toArray();
        self::assertSame('http://localhost/test', $urls[0]['loc']);
        self::assertSame('http://www.example.com/test', $urls[1]['loc']);
    }

    public function testAddUriAcceptsStringUri(): void
    {
        $this->map->addUri('/test/strings');
        $urls = $this->map->toArray();
        self::assertSame('http://localhost/test/strings', $urls[0]['loc']);
    }
}
