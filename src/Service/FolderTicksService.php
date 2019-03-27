<?php

namespace Chomikuj\Service;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use Chomikuj\Exception\ChomikujException;
use Chomikuj\Service\FolderTicksServiceInterface;

class FolderTicksService implements FolderTicksServiceInterface
{
    const BASE_URL = 'https://chomikuj.pl';
    
    const ERR_USERNAME_NOT_FOUND = 'Username not found.';
    const ERR_STATUS_NOT_200 = "Status code is not 200 (%d returned).";
    const ERR_WEIRD_RESPONSE = 'Response looks valid, but could not be read (reason unknown).';

    private $client;
    private $ticks;

    public function __construct(?ClientInterface $client = null)
    {
        if ($client === null) {
            $client = new Client([
                'allow_redirects' => false,
                'http_errors' => false,
            ]);
        }

        $this->client = $client;
    }

    public function getTicks(string $username, bool $forceUpdate = false): string
    {
        if (!isset($this->ticks[$username]) || $forceUpdate) {
            $this->updateTicks($username);
        }

        return $this->ticks[$username];
    }

    private function updateTicks($username): void
    {
        $response = $this->client->request(
            'GET',
            self::BASE_URL . '/' . $username
        );

        $this->checkStatusCode($response->getStatusCode());

        $ticks = $this->extractTicksFromHtml($response->getBody()->getContents());
        $this->ticks[$username] = $ticks;
    }

    private function checkStatusCode(int $statusCode): void
    {
        switch ($statusCode) {
            case 200:
                return;
            case 404:
                throw new ChomikujException(self::ERR_USERNAME_NOT_FOUND);
            default:
                throw new ChomikujException(sprintf(self::ERR_STATUS_NOT_200, $statusCode));
        }
    }

    private function extractTicksFromHtml($html)
    {
        if (!$html) {
            throw new ChomikujException(self::ERR_WEIRD_RESPONSE);
        }

        $doc = new \DOMDocument();

        \libxml_use_internal_errors(true);
        $doc->loadHTML($html);
        \libxml_clear_errors();

        $xpath = new \DOMXpath($doc);
        
        $list = $xpath->query("//input[@name='TreeTicks']/@value");

        $attr = $list->item(0);
        if ($attr === null) {
            throw new ChomikujException(self::ERR_WEIRD_RESPONSE);
        }

        return $attr->value;
    }
}
