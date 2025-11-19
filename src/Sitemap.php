<?php

declare(strict_types=1);

namespace Netglue\Sitemap;

use Countable;
use DateTimeInterface;
use League\Uri\Uri;
use Netglue\Sitemap\Exception\InvalidArgument;
use Override;
use XMLWriter;

use function array_values;
use function count;
use function in_array;
use function sprintf;

use const DATE_W3C;

/**
 * @psalm-type LocShape = array{
 *     loc: string,
 *     lastmod?: string,
 *     changefreq?: string,
 *     priority?: string,
 * }
 */
final class Sitemap implements Countable
{
    private const CHANGE_FREQ = [
        'always',
        'hourly',
        'daily',
        'weekly',
        'monthly',
        'yearly',
        'never',
    ];

    /** @var array<string, LocShape> */
    private array $locations = [];
    private readonly Uri $baseUrl;

    /** @param non-empty-string $name */
    public function __construct(private readonly string $name, string $baseUrl)
    {
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

    /** @return non-empty-string */
    public function getName(): string
    {
        return $this->name;
    }

    #[Override]
    public function count(): int
    {
        return count($this->locations);
    }

    public function addUri(
        string $uri,
        DateTimeInterface|null $lastMod = null,
        string|null $changeFreq = null,
        float|null $priority = null,
    ): void {
        $uri = Uri::new($uri);

        if ($uri->getHost() === null) {
            $uri = $this->baseUrl->withPath($uri->getPath())
                ->withQuery($uri->getQuery())
                ->withPort($uri->getPort());
        }

        $payload = ['loc' => $uri->toString()];
        if ($lastMod !== null) {
            $payload['lastmod'] = $lastMod->format(DATE_W3C);
        }

        if ($changeFreq !== null) {
            $payload['changefreq'] = $this->changeFreq($changeFreq);
        }

        if ($priority !== null) {
            $payload['priority'] = sprintf('%0.2f', $this->priority($priority));
        }

        $this->locations[$uri->toString()] = $payload;
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

        return $writer->outputMemory();
    }

    private function changeFreq(string $changeFreq): string
    {
        if (! in_array($changeFreq, self::CHANGE_FREQ, true)) {
            throw new InvalidArgument(sprintf('Invalid change frequency value "%s"', $changeFreq));
        }

        return $changeFreq;
    }

    private function priority(float $priority): float
    {
        if ($priority > 1 || $priority < 0) {
            throw new InvalidArgument(sprintf(
                'Priority must be a decimal between 0 and 1. Received "%0.2f"',
                $priority,
            ));
        }

        return $priority;
    }

    public function __toString(): string
    {
        return $this->toXmlString();
    }

    /** @return list<LocShape> */
    public function toArray(): array
    {
        return array_values($this->locations);
    }
}
