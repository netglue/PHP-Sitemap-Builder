<?php
declare(strict_types=1);

namespace Netglue\SitemapTest;

use PHPUnit\Framework\TestCase;
use Netglue\Sitemap\Sitemap;
use DateTime;
use Zend\Uri\Uri;

class SitemapTest extends TestCase
{

    public function testInitialInstance()
    {
        $map = new Sitemap('foo');
        $this->assertSame('foo', $map->getName());
        $this->assertCount(0, $map);
    }

    /**
     * @expectedException Netglue\Sitemap\Exception\InvalidArgumentException
     */
    public function testExceptionThrownForInvalidChangeFreq()
    {
        $map = new Sitemap('foo');
        $map->addUri(new Uri('/test'), null, 'nope');
    }

    /**
     * @expectedException Netglue\Sitemap\Exception\InvalidArgumentException
     */
    public function testExceptionThrownForInvalidPriority()
    {
        $map = new Sitemap('foo');
        $map->addUri(new Uri('/test'), null, 'never', 1.5);
    }


    public function testValidUriIncreasesCount()
    {
        $map = new Sitemap('foo');
        $map->addUri(new Uri('/test'), null, 'never', 0.9);
        $this->assertCount(1, $map);
    }

    public function testXmlIsRenderedWithZeroUrls()
    {
        $map = new Sitemap('foo');
        $expect = '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"/>';
        $this->assertXmlStringEqualsXmlString($expect, $map->toXmlString());
    }

    public function testExpectedXmlOutput()
    {
        $map = new Sitemap('foo');
        $lastMod = DateTime::createFromFormat('Y-m-d H:i:s', '2018-01-01 12:00:00');
        $map->addUri(new Uri('/test'), $lastMod, 'never', 0.9);

        $file = __DIR__ . '/data/basic-sitemap.xml';
        $this->assertXmlStringEqualsXmlFile($file, $map->toXmlString());
    }

    public function testSuccessiveCallsToOutputReturnsTheSameValue()
    {
        $map = new Sitemap('foo');
        $lastMod = DateTime::createFromFormat('Y-m-d H:i:s', '2018-01-01 12:00:00');
        $map->addUri(new Uri('/test'), $lastMod, 'never', 0.9);

        $file = __DIR__ . '/data/basic-sitemap.xml';
        $this->assertXmlStringEqualsXmlFile($file, $map->toXmlString());
        $this->assertXmlStringEqualsXmlFile($file, $map->toXmlString());
    }

    public function testAddingUrisAfterRenderWillCauseReRender()
    {
        $map = new Sitemap('foo');
        $lastMod = DateTime::createFromFormat('Y-m-d H:i:s', '2018-01-01 12:00:00');
        $map->addUri(new Uri('/test'), $lastMod, 'never', 0.9);

        $file = __DIR__ . '/data/basic-sitemap.xml';
        $this->assertXmlStringEqualsXmlFile($file, $map->toXmlString());

        $lastMod = DateTime::createFromFormat('Y-m-d H:i:s', '2018-01-02 12:00:00');
        $map->addUri(new Uri('/test2'), $lastMod, 'always', 0.1);

        $file = __DIR__ . '/data/sitemap-with-2-uris.xml';
        $this->assertXmlStringEqualsXmlFile($file, $map->toXmlString());
    }

    public function testToArrayReturnsArray()
    {
        $map = new Sitemap('foo');
        $value = $map->toArray();
        $this->assertInternalType('array', $value);
        $this->assertCount(0, $value);

        $lastMod = DateTime::createFromFormat('Y-m-d H:i:s', '2018-01-01 12:00:00');
        $map->addUri(new Uri('/test'), $lastMod, 'never', 0.9);

        $value = $map->toArray();
        $this->assertInternalType('array', $value);
        $this->assertCount(1, $value);
    }

    public function testToStringReturnsXmlValue()
    {
        $map = new Sitemap('foo');
        $string = (string) $map;

        $this->assertSame($map->toXmlString(), $string);
    }

}
