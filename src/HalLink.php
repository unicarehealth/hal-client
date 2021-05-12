<?php declare(strict_types=1);

namespace Jsor\HalClient;

use GuzzleHttp\UriTemplate\UriTemplate;
use Psr\Http\Message\ResponseInterface;

/**
 * @phpstan-type GuzzleUriTemplateVariables array<string, mixed>
 * @phpstan-type RawHalLink array{href?:string, templated?:bool, type?:?string, deprecation?:?string, name?:?string, profile?:?string, title?:?string, hreflang?:?string}
 * @phpstan-import-type RequestOptionsType from HalClientInterface
 */
final class HalLink
{
    private HalClientInterface $client;
    private string $href;
    private bool $templated;
    private ?string $type;
    private ?string $deprecation;
    private ?string $name;
    private ?string $profile;
    private ?string $title;
    private ?string $hreflang;

    public function __construct(
        HalClientInterface $client,
        string $href = '',
        bool $templated = false,
        ?string $type = null,
        ?string $deprecation = null,
        ?string $name = null,
        ?string $profile = null,
        ?string $title = null,
        ?string $hreflang = null
    ) {
        $this->client      = $client;
        $this->href        = $href;
        $this->templated   = $templated;
        $this->type        = $type;
        $this->deprecation = $deprecation;
        $this->name        = $name;
        $this->profile     = $profile;
        $this->title       = $title;
        $this->hreflang    = $hreflang;
    }

    /**
     * @param RawHalLink $array
     */
    public static function fromArray(HalClientInterface $client, array $array) : self
    {
        $array = array_replace([
            'href'        => '',
            'templated'   => false,
            'type'        => null,
            'deprecation' => null,
            'name'        => null,
            'profile'     => null,
            'title'       => null,
            'hreflang'    => null,
        ], $array);

        return new self(
            $client,
            is_string($array['href']) ? $array['href'] : '',
            is_bool($array['templated']) ? $array['templated'] : false,
            is_string($array['type']) ? $array['type'] : null,
            is_string($array['deprecation']) ? $array['deprecation'] : null,
            is_string($array['name']) ? $array['name'] : null,
            is_string($array['profile']) ? $array['profile'] : null,
            is_string($array['title']) ? $array['title'] : null,
            is_string($array['hreflang']) ? $array['hreflang'] : null
        );
    }

    /**
     * @param GuzzleUriTemplateVariables $variables Variables to use in the template expansion
     */
    public function getUri(array $variables = []) : string
    {
        $uri = (string)$this->href;

        if (true === $this->templated) {
            $uri = self::expandUriTemplate($uri, $variables);
        }

        return $uri;
    }

    public function getHref() : string
    {
        return $this->href;
    }

    public function getTemplated() : bool
    {
        return $this->templated;
    }

    public function getType() : ?string
    {
        return $this->type;
    }

    public function getDeprecation() : ?string
    {
        return $this->deprecation;
    }

    public function getName() : ?string
    {
        return $this->name;
    }

    public function getProfile() : ?string
    {
        return $this->profile;
    }

    public function getTitle() : ?string
    {
        return $this->title;
    }

    public function getHreflang() : ?string
    {
        return $this->hreflang;
    }

    /**
     * @param GuzzleUriTemplateVariables $variables
     * @param RequestOptionsType $options
     */
    public function get(array $variables = [], array $options = []) : HalResource|ResponseInterface
    {
        return $this->request('GET', $variables, $options);
    }

    /**
     * @param GuzzleUriTemplateVariables $variables
     * @param RequestOptionsType $options
     */
    public function post(array $variables = [], array $options = []) : HalResource|ResponseInterface
    {
        return $this->request('POST', $variables, $options);
    }

    /**
     * @param GuzzleUriTemplateVariables $variables
     * @param RequestOptionsType $options
     */
    public function put(array $variables = [], array $options = []) : HalResource|ResponseInterface
    {
        return $this->request('PUT', $variables, $options);
    }

    /**
     * @param GuzzleUriTemplateVariables $variables
     * @param RequestOptionsType $options
     */
    public function delete(array $variables = [], array $options = []) : HalResource|ResponseInterface
    {
        return $this->request('DELETE', $variables, $options);
    }

    /**
     * @param GuzzleUriTemplateVariables $variables
     * @param RequestOptionsType $options
     */
    public function request(string $method, array $variables = [], array $options = []) : HalResource|ResponseInterface
    {
        return $this->client->request(
            $method,
            $this->getUri($variables),
            $options
        );
    }

    /**
     * @param GuzzleUriTemplateVariables $variables Variables to use in the template expansion
     */
    private static function expandUriTemplate(string $template, array $variables) : string
    {
        static $guzzleUriTemplate;

        if (!$guzzleUriTemplate) {
            $guzzleUriTemplate = new UriTemplate();
        }

        return $guzzleUriTemplate->expand($template, $variables);
    }
}
