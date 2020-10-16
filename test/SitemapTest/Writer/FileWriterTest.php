<?php

declare(strict_types=1);

namespace Netglue\SitemapTest\Writer;

use Netglue\Sitemap\Exception\InvalidArgument;
use Netglue\Sitemap\Sitemap;
use Netglue\Sitemap\SitemapIndex;
use Netglue\Sitemap\Writer\FileWriter;
use PHPUnit\Framework\TestCase;

use function chmod;
use function closedir;
use function mkdir;
use function opendir;
use function preg_match;
use function readdir;
use function rmdir;
use function touch;
use function unlink;

use const DIRECTORY_SEPARATOR;

class FileWriterTest extends TestCase
{
    /** @var string */
    private $dir;
    /** @var FileWriter */
    private $writer;

    public function setUp(): void
    {
        $this->dir = __DIR__ . '/tmp';
        mkdir($this->dir);
        $this->writer = new FileWriter($this->dir);
    }

    public function tearDown(): void
    {
        $dh = opendir($this->dir);
        while (($file = readdir($dh)) !== false) {
            if (! preg_match('/\.xml$/', $file)) {
                continue;
            }

            unlink($this->dir . DIRECTORY_SEPARATOR . $file);
        }

        closedir($dh);
        rmdir($this->dir);
    }

    public function testWriteIndex(): void
    {
        $index = new SitemapIndex('http://localhost');
        $index->addUri('/foo');

        $this->writer->writeIndex($index);

        self::assertFileExists($this->dir . DIRECTORY_SEPARATOR . 'sitemap-index.xml');
        self::assertFileExists($this->dir . DIRECTORY_SEPARATOR . 'sitemap-0.xml');
    }

    public function testIndexIsWrittenWithCustomFilename(): void
    {
        $index = new SitemapIndex('http://localhost');

        $this->writer->writeIndex($index, 'custom.xml');
        self::assertFileExists($this->dir . DIRECTORY_SEPARATOR . 'custom.xml');
        self::assertFileDoesNotExist($this->dir . DIRECTORY_SEPARATOR . 'sitemap-0.xml');
    }

    public function testSitemapIsWrittenWithConstructorFilename(): void
    {
        $map = new Sitemap('name1.xml', 'http://localhost');
        $this->writer->writeSitemap($map);

        self::assertFileExists($this->dir . DIRECTORY_SEPARATOR . 'name1.xml');
    }

    public function testSitemapIsWrittenWithCustomFilename(): void
    {
        $map = new Sitemap('name1.xml', 'http://localhost');
        $this->writer->writeSitemap($map, 'custom.xml');

        self::assertFileExists($this->dir . DIRECTORY_SEPARATOR . 'custom.xml');
        self::assertFileDoesNotExist($this->dir . DIRECTORY_SEPARATOR . 'name1.xml');
    }

    public function testExceptionIsThrownForNonDirectory(): void
    {
        $file = $this->dir . DIRECTORY_SEPARATOR . 'file.xml';
        touch($file);
        $this->expectException(InvalidArgument::class);
        new FileWriter($file);
    }

    public function testExceptionThrownForUnWritableDirectory(): void
    {
        chmod($this->dir, 0500);
        try {
            new FileWriter($this->dir);
            self::fail('No exception was thrown');
        } catch (InvalidArgument $e) {
            self::assertInstanceOf(InvalidArgument::class, $e);
        } finally {
            chmod($this->dir, 0700);
        }
    }
}
