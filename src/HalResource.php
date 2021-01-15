<?php

namespace Jsor\HalClient;

use Psr\Http\Message\ResponseInterface;

final class HalResource
{
    private HalClientInterface $client;
    private array $properties;
    private array $links;
    private array $resources;

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

    public function getProperties() : array
    {
        return $this->properties;
    }

    public function hasProperty(string $name) : bool
    {
        return isset($this->properties[$name]);
    }

    public function getProperty(string $name) : mixed
    {
        if (isset($this->properties[$name])) {
            return $this->properties[$name];
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
        return array_map(function ($link) {
            return HalLink::fromArray($this->client, $link);
        }, $this->getLinkData($rel));
    }

    public function getFirstLink(string $rel) : ?HalLink
    {
        $link = $this->getLinkData($rel);

        if (!isset($link[0])) {
            return null;
        }

        return HalLink::fromArray($this->client, $link[0]);
    }

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

        return $this->normalizeData($this->links[$resolvedRel], function ($link) {
            return ['href' => $link];
        });
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
     * @return array<string, HalResource[]>
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
    public function getResource(string $rel) : array
    {
        return array_map(function ($data) {
            return static::fromArray($this->client, $data);
        }, $this->getResourceData($rel));
    }

    public function getFirstResource(string $rel) : ?self
    {
        $resources = $this->getResourceData($rel);

        if (!isset($resources[0])) {
            return null;
        }

        return static::fromArray($this->client, $resources[0]);
    }

    private function getResourceData(string $rel) : array
    {
        if (isset($this->resources[$rel])) {
            return $this->normalizeData($this->resources[$rel], function ($resource) {
                return [$resource];
            });
        }

        throw new Exception\InvalidArgumentException(
            sprintf(
                'Unknown resource %s.',
                json_encode($rel)
            )
        );
    }

    private function normalizeData(mixed $data, callable $arrayNormalizer) : array
    {
        if (!$data) {
            return [];
        }

        if (!isset($data[0]) || !is_array($data)) {
            $data = [$data];
        }

        $data = array_map(function ($entry) use ($arrayNormalizer) {
            if (null !== $entry && !is_array($entry)) {
                $entry = $arrayNormalizer($entry);
            }

            return $entry;
        }, $data);

        return array_filter($data, function ($entry) {
            return null !== $entry;
        });
    }

    public function get(array $options = []) : HalResource|ResponseInterface
    {
        return $this->request('GET', $options);
    }

    public function post(array $options = []) : HalResource|ResponseInterface
    {
        return $this->request('POST', $options);
    }

    public function put(array $options = []) : HalResource|ResponseInterface
    {
        return $this->request('PUT', $options);
    }

    public function delete(array $options = []) : HalResource|ResponseInterface
    {
        return $this->request('DELETE', $options);
    }

    public function request(string $method, array $options = []) : HalResource|ResponseInterface
    {
        return $this->getFirstLink('self')->request($method, [], $options);
    }
}
