<?php

declare(strict_types=1);

namespace Netglue\Sitemap;

use DateTimeImmutable;
use DateTimeInterface;
use League\Uri\Uri;
use Netglue\Sitemap\Exception\InvalidArgument;
use XMLWriter;

use function count;
use function sprintf;

final class SitemapIndex
{
    public const SCHEMA = 'https://www.sitemaps.org/schemas/sitemap/0.9';
    private const MAX_PER_SITEMAP = 50000;

    /** @var Sitemap[] */
    private array $sitemaps = [];
    private Sitemap|null $currentSitemap = null;
    private DateTimeInterface|null $lastMod = null;
    private readonly Uri $baseUrl;

    public function __construct(string $baseUrl, private readonly int $maxEntriesPerSitemap = self::MAX_PER_SITEMAP)
    {
        if ($maxEntriesPerSitemap < 1 || $maxEntriesPerSitemap > 50000) {
            throw new InvalidArgument(
                'The max number of url entries per sitemap must be between 1 and 50k',
            );
        }

        $uri = Uri::new($baseUrl);
        if (! $uri->isAbsolute()) {
            throw new InvalidArgument(
                'Base URL must include scheme and host, i.e. https://example.com',
            );
        }

        if ($uri->getPath() === '') {
            $uri = $uri->withPath('/');
        }

        $this->baseUrl = $uri;
    }

    public function getBaseUrl(): string
    {
        return (string) $this->baseUrl;
    }

    /** @return Sitemap[] */
    public function getSitemaps(): array
    {
        return $this->sitemaps;
    }

    private function getSitemap(): Sitemap
    {
        // Unset current sitemap if it's getting big…
        if ($this->currentSitemap && $this->currentSitemap->count() >= $this->maxEntriesPerSitemap) {
            $this->currentSitemap = null;
        }

        $index = count($this->sitemaps);
        if (! $this->currentSitemap) {
            $filename = $this->generateSitemapName($index);
            $this->currentSitemap = new Sitemap($filename, $this->baseUrl->toString());
            $this->sitemaps[$index] = $this->currentSitemap;
        }

        return $this->currentSitemap;
    }

    /** @return non-empty-string */
    private function generateSitemapName(int $index): string
    {
        return sprintf('sitemap-%d.xml', $index);
    }

    public function addUri(
        string $uri,
        DateTimeInterface|null $lastMod = null,
        string|null $changeFreq = null,
        float|null $priority = null,
    ): void {
        $sitemap = $this->getSitemap();
        $sitemap->addUri($uri, $lastMod, $changeFreq, $priority);
        $this->lastMod($lastMod);
    }

    private function lastMod(DateTimeInterface|null $lastMod = null): DateTimeInterface|null
    {
        if (! $this->lastMod && $lastMod) {
            $this->lastMod = clone $lastMod;
        }

        if ($lastMod && $lastMod > $this->lastMod) {
            $this->lastMod = clone $lastMod;
        }

        return $this->lastMod;
    }

    public function toXmlString(): string
    {
        // LastMod is set to the same date for all sitemaps, but do we care?
        $lastMod = $this->lastMod();
        if (! $lastMod) {
            $lastMod = new DateTimeImmutable();
        }

        $writer = new XMLWriter();
        $writer->openMemory();
        $writer->setIndent(true);
        $writer->startDocument('1.0', 'UTF-8');
        $writer->startElement('sitemapindex');
        $writer->writeAttribute('xmlns', self::SCHEMA);
        foreach ($this->sitemaps as $sitemap) {
            $writer->startElement('sitemap');
            $sitemapUrl = $this->baseUrl->withPath('/' . $sitemap->getName());
            $writer->writeElement('loc', (string) $sitemapUrl);
            $writer->writeElement('lastmod', $lastMod->format('Y-m-d'));
            $writer->endElement();
        }

        $writer->endElement();
        $writer->endDocument();

        return $writer->outputMemory();
    }

    public function __toString(): string
    {
        return $this->toXmlString();
    }
}
