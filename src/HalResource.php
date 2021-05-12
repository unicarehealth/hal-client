<?php declare(strict_types=1);

namespace Jsor\HalClient;

use Psr\Http\Message\ResponseInterface;

/**
 * @phpstan-import-type RequestOptionsType from HalClientInterface
 * @phpstan-import-type RawHalLink from HalLink
 * @phpstan-type LinksCache array<string, RawHalLink>
 * @phpstan-type PropertiesCache array<string|int, mixed>
 * @phpstan-type RawResource array<mixed>
 * @phpstan-type ResourcesCache array<string|int, RawResource>
 */
final class HalResource
{
    private HalClientInterface $client;

    /** @var PropertiesCache */
    private array $properties;

    /** @var LinksCache */
    private array $links;

    /** @var ResourcesCache */
    private array $resources;

    /**
     * @param PropertiesCache $properties
     * @param LinksCache $links
     * @param ResourcesCache $resources
     */
    public function __construct(
        HalClientInterface $client,
        array $properties = [],
        array $links = [],
        array $resources = []
    ) {
        $this->client     = $client;
        $this->properties = $properties;
        $this->links      = $links;
        $this->resources  = $resources;
    }

    /**
     * @param array<int|string, mixed> $array
     */
    public static function fromArray(HalClientInterface $client, array $array) : self
    {
        $links     = [];
        $resources = [];

        if (isset($array['_links'])) {
            $links = $array['_links'];
        }

        if (isset($array['_embedded'])) {
            $resources = $array['_embedded'];
        }

        unset($array['_links'], $array['_embedded']);

        $properties = $array;

        return new self(
            $client,
            $properties,
            $links,
            $resources
        );
    }

    /** @return PropertiesCache */
    public function getProperties() : array
    {
        return $this->properties;
    }

    public function hasProperty(string|int $nameOrIndex) : bool
    {
        return isset($this->properties[$nameOrIndex]);
    }

    public function getProperty(string|int $nameOrIndex) : mixed
    {
        if (isset($this->properties[$nameOrIndex])) {
            return $this->properties[$nameOrIndex];
        }

        return null;
    }

    public function hasLinks() : bool
    {
        return count($this->links) > 0;
    }

    /** @return array<string, HalLink[]> */
    public function getLinks() : array
    {
        $all = [];

        foreach ($this->links as $rel => $_) {
            $all[$rel] = $this->getLink($rel);
        }

        return $all;
    }

    public function hasLink(string $rel) : bool
    {
        return false !== $this->resolveLinkRel($rel);
    }

    /**
     * @return HalLink[]
     */
    public function getLink(string $rel) : array
    {
        return array_map(fn($link) => HalLink::fromArray($this->client, $link), $this->getLinkData($rel));
    }

    public function getFirstLink(string $rel) : ?HalLink
    {
        $link = $this->getLinkData($rel);

        if (!isset($link[0])) {
            return null;
        }

        return HalLink::fromArray($this->client, $link[0]);
    }

    /**
     * @return RawHalLink[]
     */
    private function getLinkData(string $rel) : array
    {
        $resolvedRel = $this->resolveLinkRel($rel);

        if (false === $resolvedRel) {
            throw new Exception\InvalidArgumentException(
                sprintf(
                    'Unknown link %s.',
                    json_encode($rel)
                )
            );
        }

        return $this->normalizeData($this->links[$resolvedRel], fn($link) => ['href' => $link]);
    }

    private function resolveLinkRel(string $rel) : false|string
    {
        if (isset($this->links[$rel])) {
            return $rel;
        }

        if (!isset($this->links['curies'])) {
            return false;
        }

        foreach ($this->getLink('curies') as $curie) {
            if (!$curie->getName()) {
                continue;
            }

            $linkRel = $curie->getName() . ':' . $rel;

            if (isset($this->links[$linkRel])) {
                return $linkRel;
            }
        }

        return false;
    }

    public function hasResources() : bool
    {
        return count($this->resources) > 0;
    }

    /**
     * @return array<int|string, HalResource[]>
     */
    public function getResources() : array
    {
        $all = [];

        foreach ($this->resources as $rel => $_) {
            $all[$rel] = $this->getResource($rel);
        }

        return $all;
    }

    public function hasResource(string $name) : bool
    {
        return isset($this->resources[$name]);
    }

    /**
     * @return HalResource[]
     */
    public function getResource(int|string $rel) : array
    {
        return array_map(fn($data) => static::fromArray($this->client, $data), $this->getResourceData($rel));
    }

    public function getFirstResource(int|string $rel) : ?self
    {
        $resources = $this->getResourceData($rel);

        if (!isset($resources[0])) {
            return null;
        }

        return static::fromArray($this->client, $resources[0]);
    }

    /** @return RawResource */
    private function getResourceData(int|string $rel) : array
    {
        if (isset($this->resources[$rel])) {
            return $this->normalizeData($this->resources[$rel], fn($resource) => [$resource]);
        }

        throw new Exception\InvalidArgumentException(
            sprintf(
                'Unknown resource %s.',
                json_encode($rel)
            )
        );
    }

    /** @return array<mixed> */
    private function normalizeData(mixed $data, callable $arrayNormalizer) : array
    {
        if (!$data) {
            return [];
        }

        if (!isset($data[0]) || !is_array($data)) {
            $data = [$data];
        }

        $mapper = function ($entry) use ($arrayNormalizer) {
            return (null !== $entry && !is_array($entry)) ? $arrayNormalizer($entry) : $entry;
        };
        $data = array_map($mapper, $data);

        $nonNullEntries = array_filter($data, fn($entry) => null !== $entry);
        return $nonNullEntries;
    }

    /**
     * @param RequestOptionsType $options
     */
    public function get(array $options = []) : HalResource|ResponseInterface
    {
        return $this->request('GET', $options);
    }

    /**
     * @param RequestOptionsType $options
     */
    public function post(array $options = []) : HalResource|ResponseInterface
    {
        return $this->request('POST', $options);
    }

    /**
     * @param RequestOptionsType $options
     */
    public function put(array $options = []) : HalResource|ResponseInterface
    {
        return $this->request('PUT', $options);
    }

    /**
     * @param RequestOptionsType $options
     */
    public function delete(array $options = []) : HalResource|ResponseInterface
    {
        return $this->request('DELETE', $options);
    }

    /**
     * @param RequestOptionsType $options
     */
    public function request(string $method, array $options = []) : HalResource|ResponseInterface
    {
        return $this->getFirstLink('self')?->request($method, [], $options) ?? throw new Exception\BadResponseException('Response _links does not contain key \'self\'.');
    }
}
