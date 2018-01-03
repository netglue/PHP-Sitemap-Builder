<?php
declare(strict_types=1);

namespace Netglue\Sitemap;
use Zend\Uri\Uri;
use Zend\Uri\UriInterface;

trait BaseUrlTrait
{

    /**
     * @var UriInterface|null
     */
    private $baseUrl;

    public function setBaseUrlWithString(string $url) : void
    {
        $this->setBaseUrl(new Uri($url));
    }

    public function setBaseUrl(UriInterface $url) : void
    {
        if (!$url->isAbsolute()){
            throw new Exception\InvalidArgumentException('Base URL must include scheme and host, i.e. https://example.com');
        }
        $this->baseUrl = clone $url;
        if (empty($this->baseUrl->getPath())) {
            $this->baseUrl->setPath('/');
        }
    }


}
