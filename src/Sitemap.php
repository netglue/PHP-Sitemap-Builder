<?php
declare(strict_types=1);

namespace Netglue\Sitemap;

use DateTimeInterface;
use Zend\Uri\Uri;
use Zend\Uri\UriInterface;
use Zend\Uri\Exception\ExceptionInterface as UriException;
use XMLWriter;
use Countable;
class Sitemap implements Countable
{
    use BaseUrlTrait;

    private static $changeFreq = [
        'always',
        'hourly',
        'daily',
        'weekly',
        'monthly',
        'yearly',
        'never',
    ];

    /**
     * Sitemap URLs
     */
    private $locations = [];

    /**
     * Sitemap Name
     * @var string
     */
    private $name;

    /**
     * @var XMLWriter|null
     */
    private $writer;

    /**
     * @var string|null
     */
    private $xmlString;

    public function __construct(string $name, string $baseUrl)
    {
        $this->name = $name;
        $this->setBaseUrlWithString($baseUrl);
    }

    public function getName() : string
    {
        return $this->name;
    }

    public function count() : int
    {
        return count($this->locations);
    }

    public function addUri($uri, ?DateTimeInterface $lastMod = null, ?string $changeFreq = null, ?float $priority = null) : void
    {
        $url = (string) Uri::merge($this->baseUrl, $uri);
        $payload = [
            'loc' => $url
        ];
        if ($lastMod) {
            $payload['lastmod'] = $lastMod->format(DATE_W3C);
        }
        if ($changeFreq) {
            $payload['changefreq'] = $this->changeFreq($changeFreq);
        }
        if ($priority) {
            $payload['priority'] = sprintf('%0.2f', $this->priority($priority));
        }
        $this->locations[$url] = $payload;
        $this->invalidateXml();
    }

    private function invalidateXml() : void
    {
        $this->xmlString = null;
        $this->writer = null;
    }

    public function toXmlString() : string
    {
        if (!$this->xmlString) {
            $writer = $this->getWriter();
            array_walk($this->locations, [$this, 'writeUrl']);
            $writer->endElement();
            $writer->endDocument();
            $this->xmlString = $writer->outputMemory();
        }
        return $this->xmlString;
    }

    private function getWriter() : XMLWriter
    {
        if (!$this->writer) {
            $this->writer = new XMLWriter;
            $this->writer->openMemory();
            $this->writer->setIndent(true);
            $this->writer->startDocument('1.0','UTF-8');
            $this->writer->startElement('urlset');
            $this->writer->writeAttribute('xmlns', SitemapIndex::SCHEMA);
        }
        return $this->writer;
    }

    private function writeUrl(array &$url) : void
    {
        $writer = $this->getWriter();
        $writer->startElement('url');
		foreach ($url as $tag => $value) {
		    $writer->writeElement($tag, $value);
		}
		$writer->endElement();
    }

    private function changeFreq(string $changeFreq) : string
    {
        if (!in_array($changeFreq, static::$changeFreq, true)) {
            throw new Exception\InvalidArgumentException(sprintf('Invalid change frequency value "%s"', $changeFreq));
        }
        return $changeFreq;
    }

    private function priority(float $priority) : float
    {
        if ($priority > 1 || $priority < 0) {
            throw new Exception\InvalidArgumentException(sprintf('Priority must be a decimal between 0 and 1. Received "%0.2f"', $priority));
        }
        return $priority;
    }

    public function __toString() : string
    {
        return $this->toXmlString();
    }

    public function toArray() : array
    {
        return array_values($this->locations);
    }

}
