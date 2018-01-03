<?php
declare(strict_types=1);

namespace Netglue\Sitemap;

use XMLWriter;
use DateTimeInterface;
use DateTime;
use Zend\Uri\Uri;
use Zend\Uri\UriInterface;
use Zend\Uri\Exception\ExceptionInterface as UriException;

class SitemapIndex
{

    use BaseUrlTrait;

    const SCHEMA = 'http://www.sitemaps.org/schemas/sitemap/0.9';

    /**
     * The maximum number of URLs in a sitemap
     * @var int
     */
    private $maxLoc = 50000;

    /** @var array */
    private $sitemaps = [];

    /** @var Sitemap|null */
    private $currentSitemap;

    /** @var DateTimeInterface|null */
    private $lastMod;

    public function __construct(string $baseUrl)
    {
        $this->setBaseUrlWithString($baseUrl);
    }

    public function getBaseUrl() : string
    {
        return (string) $this->baseUrl;
    }

    public function setMaxEntriesPerSitemap(int $max) : void
    {
        if ($max < 1 || $max > 50000) {
            throw new Exception\InvalidArgumentException('The max number of url entries per sitemap must be between 1 and 50k');
        }
        $this->maxLoc = $max;
    }

    public function getSitemaps() : array
    {
        return $this->sitemaps;
    }

    private function getSitemap() : Sitemap
    {
        // Unset current sitemap if it's getting big…
        if ($this->currentSitemap && $this->currentSitemap->count() >= $this->maxLoc) {
            $this->currentSitemap = null;
        }
        $index = count($this->sitemaps);
        if (!$this->currentSitemap) {
            $filename = $this->generateSitemapName($index);
            $this->currentSitemap = new Sitemap($filename, (string) $this->baseUrl);
            $this->sitemaps[$index] = $this->currentSitemap;
        }

        return $this->currentSitemap;
    }

    private function generateSitemapName(int $index) : string
    {
        return sprintf('sitemap-%d.xml', $index);
    }

    public function addUri($uri, ?DateTimeInterface $lastMod = null, ?string $changeFreq = null, ?float $priority = null) : void
    {
        try {
            $uri = Uri::merge($this->baseUrl, $uri);
        } catch (UriException $e) {
            throw new Exception\InvalidArgumentException('URIs must be strings or Zend\Uri instances', 0, $e);
        }
        $sitemap = $this->getSitemap();
        $sitemap->addUri($uri, $lastMod, $changeFreq, $priority);
        $this->lastMod($lastMod);
    }

    private function lastMod(?DateTimeInterface $lastMod = null) : ?DateTimeInterface
    {
        if (!$this->lastMod && $lastMod) {
            $this->lastMod = clone $lastMod;
        }
        if ($lastMod && $lastMod > $this->lastMod) {
            $this->lastMod = clone $lastMod;
        }

        return $this->lastMod;
    }

    public function toXmlString() : string
    {
        // LastMod is set to the same date for all sitemaps, but do we care?
        $lastMod = $this->lastMod();
        if (!$lastMod) {
            $lastMod = new DateTime;
        }
        $writer = new XMLWriter;
        $writer->openMemory();
        $writer->setIndent(true);
        $writer->startDocument('1.0','UTF-8');
        $writer->startElement('sitemapindex');
        $writer->writeAttribute('xmlns', self::SCHEMA);
        foreach ($this->sitemaps as $sitemap) {
			$writer->startElement('sitemap');
			$sitemapUrl = Uri::merge($this->baseUrl, $sitemap->getName());
			$writer->writeElement('loc', (string) $sitemapUrl);
			$writer->writeElement('lastmod', $lastMod->format('Y-m-d'));
			$writer->endElement();
		}
		$writer->endElement();
		$writer->endDocument();
		return $writer->outputMemory(true);
    }

    public function __toString() : string
    {
        return $this->toXmlString();
    }



}
