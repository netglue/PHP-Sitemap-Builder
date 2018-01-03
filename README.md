# Another Sitemap Builder Utility in PHP

[![Coverage Status](https://coveralls.io/repos/github/netglue/PHP-Sitemap-Builder/badge.svg?branch=master)](https://coveralls.io/github/netglue/PHP-Sitemap-Builder?branch=master)
[![Build Status](https://travis-ci.org/netglue/PHP-Sitemap-Builder.svg?branch=master)](https://travis-ci.org/netglue/PHP-Sitemap-Builder)

## Why?

It's pretty easy to make an XML sitemap and there's lots of utilities out there, but these tend to concentrate on persisting the xml to disk. If you're running your app on a handful of servers, disks are a pain in the ass.

I wanted something that only generated the XML as a string so that I could easily throw that at a cache, or serialise to files on AWS or Google cloud or whatever.

## Install

Install with composer in the normal way…

    composer require netglue/sitemap-builder

This lib requires the xml writer extension, and a minimum of PHP 7.1

## Tests

Coverage is currently 100%, just CD to the project directory and…
    
    $ composer install
    $ vendor/bin/phpunit

## Usage

### Single, standalone sitemap

If you have no need of a sitemap index file, i.e. you're never going to have more than 50k URLs, then something like this should suffice:
    
    use Netglue\Sitemap\Sitemap;
    
    $sitemap = new Sitemap('some-file.xml', 'http://base-host-and-scheme.com');
    $sitemap->addUri('/somewhere');
    
    $xml = (string) $sitemap; // or $sitemap->toXmlString()
    
    // Now do whatever with your xml…

The signature for `Sitemap::addUri()` is:
    
    Sitemap::addUri(
        \Zend\Uri\UriInterface|string $uri,
        ?\DateTimeInterface $lastMod = null,
        ?string $changeFreq = null,
        ?float $priority = null
    ) : void;

### Multiple sitemaps with an index

Adding urls to an index will automatically generate new sitemaps when the max location count is reached for each sitemap. Each sitemap will be named `sitemap-{index}.xml` where `{index}` starts at zero
    
    use Netglue\Sitemap\SitemapIndex;
    use Netglue\Sitemap\Sitemap;
    
    $index = new SitemapIndex('http://baseurl.host');
    // Optionally limit sitemap size (Default is 50k)
    $index->setMaxEntriesPerSitemap(10);
    // Add a shed load of relative, or absolute URIs
    $index->addUri('/someplace');
    // ... 
    
    // Retrieve the sitemaps and do something with them:
    foreach ($index->getSitemaps() as $sitemap) {
        /** @var Sitemap $sitemap */
        $xml = (string) $sitemap;
        $filename = $sitemap->getName(); // i.e 'sitemap-0.xml'
    }
    
    // Do somthing with the Index XML
    $indexXml = (string) $index;

## Writing to Disk

I figured that if anyone uses this lib, there's a good chance that they may wish to write sitemaps to disk, so it also includes a simple writer class

    use Netglue\Sitemap\Writer\FileWriter;
    use Netglue\Sitemap\SitemapIndex;
    
    $writer = new FileWriter('/path/to/disk/location');
    $writer->writeIndex($index);

The above will write a Sitemap Index and all the sitemaps found to the given directory. You can provide a filename too instead of the default `sitemap-index.xml`.
If you are working with a single sitemap, then this will do the job:

    use Netglue\Sitemap\Writer\FileWriter;
    use Netglue\Sitemap\Sitemap;
    
    $writer = new FileWriter('/path/to/disk/location');
    $writer->writeSitemap($sitemap, 'my-sitemap.xml');

The filename is optional and defaults to `$sitemap->getName()`

## Exceptions

Consistent Exceptions are thrown that all implement `Netglue\Sitemap\Exception\ExceptionInterface`. For example, changefreq, when provided must be valid according to the schema. Priority must be a float between 0 and 1 etc.

## License

This lib is MIT Licensed. Do what you like with it, but don't blame anyone if there's a problem

## Feedback and Contributions…

… are welcomed. Please add tests for any fixes or feature pull requests.

## About

[Netglue is a web design firm based in Devon, UK](https://netglue.uk).

We hope this is useful to you and we’d appreciate feedback either way :)

    
    
    
