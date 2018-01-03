<?php
declare(strict_types=1);

namespace Netglue\SitemapTest\Writer;

use PHPUnit\Framework\TestCase;
use Netglue\Sitemap\Sitemap;
use Netglue\Sitemap\SitemapIndex;
use Netglue\Sitemap\Writer\FileWriter;
use DateTime;
use Zend\Uri\Uri;
use Netglue\Sitemap\Exception\InvalidArgumentException;

class FileWriterTest extends TestCase
{
    private $dir;

    private $writer;

    public function setUp()
    {
        $this->dir = __DIR__ . '/tmp';
        mkdir($this->dir);
        $this->writer = new FileWriter($this->dir);
    }

    public function tearDown()
    {
        $dh = opendir($this->dir);
        while (($file = readdir($dh)) !== false) {
            if (preg_match('/\.xml$/', $file)) {
                unlink($this->dir . DIRECTORY_SEPARATOR . $file);
            }
        }
        closedir($dh);
        rmdir($this->dir);
    }

    public function testWriteIndex()
    {
        $index = new SitemapIndex('http://localhost');
        $index->addUri('/foo');

        $this->writer->writeIndex($index);

        $this->assertTrue(file_exists($this->dir . DIRECTORY_SEPARATOR . 'sitemap-index.xml'));
        $this->assertTrue(file_exists($this->dir . DIRECTORY_SEPARATOR . 'sitemap-0.xml'));
    }

    public function testIndexIsWrittenWithCustomFilename()
    {
        $index = new SitemapIndex('http://localhost');

        $this->writer->writeIndex($index, 'custom.xml');
        $this->assertTrue(file_exists($this->dir . DIRECTORY_SEPARATOR . 'custom.xml'));
        $this->assertFalse(file_exists($this->dir . DIRECTORY_SEPARATOR . 'sitemap-0.xml'));
    }

    public function testSitemapIsWrittenWithConstructorFilename()
    {
        $map = new Sitemap('name1.xml', 'http://localhost');
        $this->writer->writeSitemap($map);

        $this->assertTrue(file_exists($this->dir . DIRECTORY_SEPARATOR . 'name1.xml'));
    }

    public function testSitemapIsWrittenWithCustomFilename()
    {
        $map = new Sitemap('name1.xml', 'http://localhost');
        $this->writer->writeSitemap($map, 'custom.xml');

        $this->assertTrue(file_exists($this->dir . DIRECTORY_SEPARATOR . 'custom.xml'));
        $this->assertFalse(file_exists($this->dir . DIRECTORY_SEPARATOR . 'name1.xml'));
    }

    /**
     * @expectedException Netglue\Sitemap\Exception\InvalidArgumentException
     */
    public function testExceptionIsThrownForNonDirectory()
    {
        $file = $this->dir . DIRECTORY_SEPARATOR . 'file.xml';
        touch($file);
        $writer = new FileWriter($file);
    }

    public function testExceptionThrownForUnwritableDirectory()
    {
        chmod($this->dir, 0500);
        try {
            $writer = new FileWriter($this->dir);
            $this->fail('No exception was thrown');
        } catch (InvalidArgumentException $e) {
            $this->assertInstanceOf(InvalidArgumentException::class, $e);
        } finally {
            chmod($this->dir, 0700);
        }
    }

}
