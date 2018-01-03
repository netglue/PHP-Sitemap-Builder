<?php
declare(strict_types=1);

namespace Netglue\SitemapTest;

use PHPUnit\Framework\TestCase;
use Netglue\Sitemap\SitemapIndex;
use DateTime;

class SitemapIndexTest extends TestCase
{

    /**
     * @expectedException Netglue\Sitemap\Exception\InvalidArgumentException
     */
    public function testInvalidBaseUrlTriggersException()
    {
        $index = new SitemapIndex(':::');
    }

    /**
     * @expectedException Netglue\Sitemap\Exception\InvalidArgumentException
     */
    public function testNonAbsoluteBaseUrlTriggersException()
    {
        $index = new SitemapIndex('/foo');
    }

    public function testMissingPathIsAppendedToBaseUrl()
    {
        $index = new SitemapIndex('http://localhost');
        $this->assertSame('http://localhost/', $index->getBaseUrl());
    }

    public function testZeroSitemapsWillStillRenderIndex()
    {
        $index = new SitemapIndex('http://localhost');
        $xml = $index->toXmlString();
        $this->assertXmlStringEqualsXmlString('<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"/>', $xml);
    }

    /**
     * @expectedException Netglue\Sitemap\Exception\InvalidArgumentException
     */
    public function testExceptionThrownForInvalidMaxEntries()
    {
        $index = new SitemapIndex('http://localhost');
        $index->setMaxEntriesPerSitemap(-10);
    }

    public function testAddingUrlWillCreateSitemapInstance()
    {
        $index = new SitemapIndex('http://localhost');
        $index->addUri('/test');
        $this->assertCount(1, $index->getSitemaps());
    }

    public function testSitemapsIncrementedOnMaxCountMet()
    {
        $index = new SitemapIndex('http://localhost');
        $this->assertCount(0, $index->getSitemaps());
        $index->setMaxEntriesPerSitemap(1);
        $index->addUri('/test');
        $this->assertCount(1, $index->getSitemaps());
        $index->addUri('/test2');
        $this->assertCount(2, $index->getSitemaps());
    }

    public function testSitemapIndexOutputsExpectedXml()
    {
        $lastMod = DateTime::createFromFormat('Y-m-d', '2018-01-01');
        $index = new SitemapIndex('http://localhost');
        $index->addUri('/test', $lastMod);
        $xml = $index->toXmlString();
        $file = __DIR__ . '/data/basic-expected-index.xml';
        $this->assertXmlStringEqualsXmlFile($file, $xml);
    }

    public function testLastModIsSetToMostRecentUri()
    {
        $index = new SitemapIndex('http://localhost');

        $lastMod = DateTime::createFromFormat('Y-m-d', '2018-01-01');
        $index->addUri('/test', $lastMod);

        $lastMod = DateTime::createFromFormat('Y-m-d', '2018-01-02');
        $index->addUri('/test', $lastMod);

        $file = __DIR__ . '/data/lastmod-set-to-most-recent-uri.xml';

        $this->assertXmlStringEqualsXmlFile($file, $index->toXmlString());
    }

    public function testToStringReturnsXmlValue()
    {
        $index = new SitemapIndex('http://localhost');
        $string = (string) $index;

        $this->assertSame($index->toXmlString(), $string);
    }

    public function testAbsoluteUrlIsPrependedToRelativeUrls()
    {
        $index = new SitemapIndex('http://localhost');
        $index->addUri('/test');

        $sitemaps = $index->getSitemaps();
        $this->assertCount(1, $sitemaps);
        $map = current($sitemaps);

        $locations = $map->toArray();
        $this->assertCount(1, $locations);

        $url = current($locations);
        $this->assertSame('http://localhost/test', $url['loc']);
    }

    /**
     * @expectedException Netglue\Sitemap\Exception\InvalidArgumentException
     */
    public function testAddUriThrowsExceptionForInvalidType()
    {
        $index = new SitemapIndex('http://localhost');
        $index->addUri([]);
    }

}
