<?php

declare(strict_types=1);

namespace Netglue\Sitemap\Writer;

use Netglue\Sitemap\Exception;
use Netglue\Sitemap\Sitemap;
use Netglue\Sitemap\SitemapIndex;

use function basename;
use function file_put_contents;
use function is_dir;
use function is_writable;
use function rtrim;
use function sprintf;

use const DIRECTORY_SEPARATOR;

final class FileWriter
{
    private string $path;

    public function __construct(string $path)
    {
        $path = rtrim($path, DIRECTORY_SEPARATOR);
        $this->assertWritableDirectory($path);
        $this->path = $path;
    }

    private function assertWritableDirectory(string $path): void
    {
        if (! is_dir($path) || ! is_writable($path)) {
            throw new Exception\InvalidArgument(sprintf(
                'The given path `%s` is not a writable directory',
                $path,
            ));
        }
    }

    public function writeIndex(SitemapIndex $index, string $filename = 'sitemap-index.xml'): void
    {
        $path = sprintf(
            '%s%s%s',
            $this->path,
            DIRECTORY_SEPARATOR,
            basename($filename),
        );

        file_put_contents($path, (string) $index);
        foreach ($index->getSitemaps() as $sitemap) {
            $this->writeSitemap($sitemap);
        }
    }

    /** @param non-empty-string|null $filename */
    public function writeSitemap(Sitemap $sitemap, string|null $filename = null): void
    {
        $filename = basename($filename ?? $sitemap->getName());

        $path = sprintf(
            '%s%s%s',
            $this->path,
            DIRECTORY_SEPARATOR,
            $filename,
        );

        file_put_contents($path, (string) $sitemap);
    }
}
