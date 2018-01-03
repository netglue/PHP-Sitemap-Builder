<?php
/**
 * @see       https://github.com/netglue/PHP-Sitemap-Builder for the canonical source repository
 * @copyright Copyright (c) 2018 Netglue Ltd. (https://netglue.uk)
 * @license   https://github.com/netglue/PHP-Sitemap-Builder/blob/master/LICENSE.md MIT License
 */

declare(strict_types=1);

namespace Netglue\Sitemap\Writer;

use Netglue\Sitemap\Sitemap;
use Netglue\Sitemap\SitemapIndex;
use Netglue\Sitemap\Exception;

class FileWriter
{
    /** @var string */
    private $path;

    public function __construct(string $path)
    {
        $path = rtrim($path, DIRECTORY_SEPARATOR);
        $this->assertWritableDirectory($path);
        $this->path = $path;
    }

    private function assertWritableDirectory(string $path)
    {
        if (! is_dir($path) || ! is_writable($path)) {
            throw new Exception\InvalidArgumentException(sprintf(
                'The given path `%s` is not a writable directory',
                $path
            ));
        }
    }

    public function writeIndex(SitemapIndex $index, string $filename = 'sitemap-index.xml')
    {
        $path = sprintf(
            '%s%s%s',
            $this->path,
            DIRECTORY_SEPARATOR,
            basename($filename)
        );

        file_put_contents($path, (string) $index);
        foreach ($index->getSitemaps() as $sitemap) {
            $this->writeSitemap($sitemap);
        }
    }

    public function writeSitemap(Sitemap $sitemap, ?string $filename = null)
    {
        $filename = $filename
                  ? $filename
                  : $sitemap->getName();

        $path = sprintf(
            '%s%s%s',
            $this->path,
            DIRECTORY_SEPARATOR,
            basename($filename)
        );

        file_put_contents($path, (string) $sitemap);
    }
}
