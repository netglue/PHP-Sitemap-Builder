<?php

declare(strict_types=1);

namespace Netglue\Sitemap;

use Countable;
use DateTimeInterface;
use Laminas\Uri\Exception\ExceptionInterface as UriException;
use Laminas\Uri\Uri;
use Laminas\Uri\UriInterface;
use Netglue\Sitemap\Exception\InvalidArgument;
use XMLWriter;

use function array_values;
use function count;
use function in_array;
use function sprintf;

use const DATE_W3C;

class Sitemap implements Countable
{
    /** @var string[] */
    private static $changeFreq = [
        'always',
        'hourly',
        'daily',
        'weekly',
        'monthly',
        'yearly',
        'never',
    ];

    /** @var array<string, string[]|float[]> */
    private $locations = [];

    /** @var string */
    private $name;

    /** @var UriInterface */
    private $baseUrl;

    public function __construct(string $name, string $baseUrl)
    {
        $this->name = $name;
        $this->setBaseUrl($baseUrl);
    }

    private function setBaseUrl(string $url): void
    {
        $uri = new Uri($url);
        if (! $uri->isAbsolute()) {
            throw new InvalidArgument(
                'Base URL must include scheme and host, i.e. https://example.com'
            );
        }

        $this->baseUrl = $uri;
        if (! empty($this->baseUrl->getPath())) {
            return;
        }

        $this->baseUrl->setPath('/');
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function count(): int
    {
        return count($this->locations);
    }

    /** @param Uri|string $uri */
    public function addUri(
        $uri,
        ?DateTimeInterface $lastMod = null,
        ?string $changeFreq = null,
        ?float $priority = null
    ): void {
        try {
            $url = (string) Uri::merge($this->baseUrl, $uri);
        } catch (UriException $e) {
            throw new InvalidArgument('URIs must be strings or Laminas\Uri instances', 0, $e);
        }

        $payload = ['loc' => $url];
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
    }

    public function toXmlString(): string
    {
        $writer = new XMLWriter();
        $writer->openMemory();
        $writer->setIndent(true);
        $writer->startDocument('1.0', 'UTF-8');
        $writer->startElement('urlset');
        $writer->writeAttribute('xmlns', SitemapIndex::SCHEMA);
        foreach ($this->locations as $location) {
            $writer->startElement('url');
            foreach ($location as $tag => $value) {
                $writer->writeElement($tag, $value);
            }

            $writer->endElement();
        }

        $writer->endElement();
        $writer->endDocument();

        return $writer->outputMemory(true);
    }

    private function changeFreq(string $changeFreq): string
    {
        if (! in_array($changeFreq, static::$changeFreq, true)) {
            throw new InvalidArgument(sprintf('Invalid change frequency value "%s"', $changeFreq));
        }

        return $changeFreq;
    }

    private function priority(float $priority): float
    {
        if ($priority > 1 || $priority < 0) {
            throw new InvalidArgument(sprintf(
                'Priority must be a decimal between 0 and 1. Received "%0.2f"',
                $priority
            ));
        }

        return $priority;
    }

    public function __toString(): string
    {
        return $this->toXmlString();
    }

    /** @return mixed[][] */
    public function toArray(): array
    {
        return array_values($this->locations);
    }
}
