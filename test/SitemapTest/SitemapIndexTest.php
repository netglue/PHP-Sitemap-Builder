<?php

declare(strict_types=1);

namespace Netglue\SitemapTest;

use DateTime;
use Netglue\Sitemap\Exception\InvalidArgument;
use Netglue\Sitemap\SitemapIndex;
use PHPUnit\Framework\TestCase;

use function current;

class SitemapIndexTest extends TestCase
{
    public function testInvalidBaseUrlTriggersException(): void
    {
        $this->expectException(InvalidArgument::class);
        new SitemapIndex(':::');
    }

    public function testNonAbsoluteBaseUrlTriggersException(): void
    {
        $this->expectException(InvalidArgument::class);
        new SitemapIndex('/foo');
    }

    public function testMissingPathIsAppendedToBaseUrl(): void
    {
        $index = new SitemapIndex('http://localhost');
        self::assertSame('http://localhost/', $index->getBaseUrl());
    }

    public function testZeroSitemapsWillStillRenderIndex(): void
    {
        $index = new SitemapIndex('http://localhost');
        $xml = $index->toXmlString();
        self::assertXmlStringEqualsXmlString(
            '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"/>',
            $xml
        );
    }

    public function testExceptionThrownForInvalidMaxEntries(): void
    {
        $this->expectException(InvalidArgument::class);
        new SitemapIndex('http://localhost', -10);
    }

    public function testAddingUrlWillCreateSitemapInstance(): void
    {
        $index = new SitemapIndex('http://localhost');
        $index->addUri('/test');
        self::assertCount(1, $index->getSitemaps());
    }

    public function testSitemapsIncrementedOnMaxCountMet(): void
    {
        $index = new SitemapIndex('http://localhost', 1);
        self::assertCount(0, $index->getSitemaps());
        $index->addUri('/test');
        self::assertCount(1, $index->getSitemaps());
        $index->addUri('/test2');
        self::assertCount(2, $index->getSitemaps());
    }

    public function testSitemapIndexOutputsExpectedXml(): void
    {
        $lastMod = DateTime::createFromFormat('Y-m-d', '2018-01-01');
        $index = new SitemapIndex('http://localhost');
        $index->addUri('/test', $lastMod);
        $xml = $index->toXmlString();
        $file = __DIR__ . '/data/basic-expected-index.xml';
        self::assertXmlStringEqualsXmlFile($file, $xml);
    }

    public function testLastModIsSetToMostRecentUri(): void
    {
        $index = new SitemapIndex('http://localhost');

        $lastMod = DateTime::createFromFormat('Y-m-d', '2018-01-01');
        $index->addUri('/test', $lastMod);

        $lastMod = DateTime::createFromFormat('Y-m-d', '2018-01-02');
        $index->addUri('/test', $lastMod);

        $file = __DIR__ . '/data/lastmod-set-to-most-recent-uri.xml';

        self::assertXmlStringEqualsXmlFile($file, $index->toXmlString());
    }

    public function testToStringReturnsXmlValue(): void
    {
        $index = new SitemapIndex('http://localhost');
        $string = (string) $index;

        self::assertSame($index->toXmlString(), $string);
    }

    public function testAbsoluteUrlIsPrependedToRelativeUrls(): void
    {
        $index = new SitemapIndex('http://localhost');
        $index->addUri('/test');

        $sitemaps = $index->getSitemaps();
        self::assertCount(1, $sitemaps);
        $map = current($sitemaps);

        $locations = $map->toArray();
        self::assertCount(1, $locations);

        $url = current($locations);
        self::assertSame('http://localhost/test', $url['loc']);
    }

    public function testAddUriThrowsExceptionForInvalidType(): void
    {
        $index = new SitemapIndex('http://localhost');
        $this->expectException(InvalidArgument::class);
        $index->addUri([]);
    }
}
