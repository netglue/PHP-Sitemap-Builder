<?php
declare(strict_types=1);

namespace Netglue\SitemapTest;

use PHPUnit\Framework\TestCase;
use Netglue\Sitemap\Sitemap;
use DateTime;
use Zend\Uri\Uri;

class SitemapTest extends TestCase
{

    private $map;

    public function setUp()
    {
        $this->map = new Sitemap('sitemap.xml', 'http://localhost');
    }

    public function testInitialInstance()
    {
        $this->assertSame('sitemap.xml', $this->map->getName());
        $this->assertCount(0, $this->map);
    }

    /**
     * @expectedException Netglue\Sitemap\Exception\InvalidArgumentException
     */
    public function testExceptionThrownForInvalidChangeFreq()
    {
        $this->map->addUri(new Uri('/test'), null, 'nope');
    }

    /**
     * @expectedException Netglue\Sitemap\Exception\InvalidArgumentException
     */
    public function testExceptionThrownForInvalidPriority()
    {
        $this->map->addUri(new Uri('/test'), null, 'never', 1.5);
    }


    public function testValidUriIncreasesCount()
    {
        $this->map->addUri(new Uri('/test'), null, 'never', 0.9);
        $this->assertCount(1, $this->map);
    }

    public function testXmlIsRenderedWithZeroUrls()
    {
        $expect = '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"/>';
        $this->assertXmlStringEqualsXmlString($expect, $this->map->toXmlString());
    }

    public function testExpectedXmlOutput()
    {
        $lastMod = DateTime::createFromFormat('Y-m-d H:i:s', '2018-01-01 12:00:00');
        $this->map->addUri(new Uri('/test'), $lastMod, 'never', 0.9);

        $file = __DIR__ . '/data/basic-sitemap.xml';
        $this->assertXmlStringEqualsXmlFile($file, $this->map->toXmlString());
    }

    public function testSuccessiveCallsToOutputReturnsTheSameValue()
    {
        $lastMod = DateTime::createFromFormat('Y-m-d H:i:s', '2018-01-01 12:00:00');
        $this->map->addUri(new Uri('/test'), $lastMod, 'never', 0.9);

        $file = __DIR__ . '/data/basic-sitemap.xml';
        $this->assertXmlStringEqualsXmlFile($file, $this->map->toXmlString());
        $this->assertXmlStringEqualsXmlFile($file, $this->map->toXmlString());
    }

    public function testAddingUrisAfterRenderWillCauseReRender()
    {
        $lastMod = DateTime::createFromFormat('Y-m-d H:i:s', '2018-01-01 12:00:00');
        $this->map->addUri(new Uri('/test'), $lastMod, 'never', 0.9);

        $file = __DIR__ . '/data/basic-sitemap.xml';
        $this->assertXmlStringEqualsXmlFile($file, $this->map->toXmlString());

        $lastMod = DateTime::createFromFormat('Y-m-d H:i:s', '2018-01-02 12:00:00');
        $this->map->addUri(new Uri('/test2'), $lastMod, 'always', 0.1);

        $file = __DIR__ . '/data/sitemap-with-2-uris.xml';
        $this->assertXmlStringEqualsXmlFile($file, $this->map->toXmlString());
    }

    public function testToArrayReturnsArray()
    {
        $value = $this->map->toArray();
        $this->assertInternalType('array', $value);
        $this->assertCount(0, $value);

        $lastMod = DateTime::createFromFormat('Y-m-d H:i:s', '2018-01-01 12:00:00');
        $this->map->addUri(new Uri('/test'), $lastMod, 'never', 0.9);

        $value = $this->map->toArray();
        $this->assertInternalType('array', $value);
        $this->assertCount(1, $value);
    }

    public function testToStringReturnsXmlValue()
    {
        $string = (string) $this->map;

        $this->assertSame($this->map->toXmlString(), $string);
    }

}