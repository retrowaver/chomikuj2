<?php

namespace Chomikuj\Mapper;

use Chomikuj\Api;
use Chomikuj\Entity\File;
use Chomikuj\Exception\ChomikujException;
use Psr\Http\Message\ResponseInterface;

class FileMapper implements FileMapperInterface
{
	public function mapSearchResponseToFiles(ResponseInterface $response): array
	{
        $html = $response->getBody()->getContents();
        if (!$html) {
            // This shouldn't happen
            return [];
        }

        $html = mb_convert_encoding($html, 'HTML-ENTITIES', "UTF-8");

        $doc = new \DOMDocument();
        $doc->loadHTML($html);
        $xpath = new \DOMXpath($doc);
        
        $rows = $xpath->query("//div[@id='listView']/div[contains(@class, 'filerow')]");
        $files = [];

        foreach ($rows as $row) {
            $files[] = $this->extractFileFromSearchRow($xpath, $row);
        }
        
        return $files;
    }

    private function extractFileFromSearchRow(\DOMXpath $xpath, \DOMNode $contextNode): File
    {
        $name = $this->extractFileNameFromSearchRow($xpath, $contextNode);
        $path = $this->extractFilePathFromSearchRow($xpath, $contextNode);
        $size = $this->extractFileSizeFromSearchRow($xpath, $contextNode);
        $timeUploaded = $this->extractFileTimeUploadedFromSearchRow($xpath, $contextNode);

        return new File(
            $name,
            $path,
            $this->convertTextualSizeToKilobytes($size),
            $this->convertTextualTimeUploadedToDateTime($timeUploaded)
        );
    }

    private function extractFileSizeFromSearchRow(\DOMXpath $xpath, \DOMNode $contextNode): string
    {
        $fileInfoNodes = $xpath->query(".//*[contains(@class, 'fileinfo')]/ul/li/span", $contextNode);

        foreach ($fileInfoNodes as $node) {
            preg_match('/^([0-9]+\ [A-Z]{2}|[0-9]+,[0-9]+\ [A-Z]{2})$/', $node->nodeValue, $matches);
            
            if (!empty($matches)) {
                return $matches[0];
            }
        }

        throw new ChomikujException(Api::ERR_WEIRD_RESPONSE);
    }

    private function extractFileTimeUploadedFromSearchRow(\DOMXpath $xpath, \DOMNode $contextNode): string
    {
        $nodes = $xpath->query(".//*[contains(@class, 'fileinfo')]/ul/li/span[contains(@class, 'date')]", $contextNode);
        $dateNode = $nodes->item(0);

        if ($dateNode === null) {
            throw new ChomikujException(Api::ERR_WEIRD_RESPONSE);
        }

        return $dateNode->nodeValue;
    }

    private function extractFilePathFromSearchRow(\DOMXpath $xpath, \DOMNode $contextNode): string
    {
        $nodes = $xpath->query(".//*[contains(@class, 'filename')]/h3/a/@href", $contextNode);
        $pathAttr = $nodes->item(0);

        if ($pathAttr === null) {
            throw new ChomikujException(Api::ERR_WEIRD_RESPONSE);
        }

        return $pathAttr->value;
    }

    private function extractFileNameFromSearchRow(\DOMXpath $xpath, \DOMNode $contextNode): string
    {
        $nodes = $xpath->query(".//*[contains(@class, 'filename')]/h3/a/@title", $contextNode);
        $nameAttr = $nodes->item(0);

        if ($nameAttr === null) {
            throw new ChomikujException(Api::ERR_WEIRD_RESPONSE);
        }

        return $nameAttr->value;
    }

    private function convertTextualSizeToKilobytes(string $textualSize): int
    {
        $textualSize = explode(' ', $textualSize);

        $value = floatval(
            str_replace(',', '.', $textualSize[0])
        );

        $unit = $textualSize[1];

        switch ($unit) {
            case 'KB':
                return $value;
            case 'MB':
                return floor($value * 1024);
            case 'GB':
                return floor($value * 1024 * 1024);
        }
    }

    private function convertTextualTimeUploadedToDateTime(string $texttualTimeUploaded): \DateTime
    {
        $trans = [
            'sty' => 'Jan',
            'lut' => 'Feb',
            'mar' => 'Mar',
            'kwi' => 'Apr',
            'maj' => 'May',
            'cze' => 'Jun',
            'lip' => 'Jul',
            'sie' => 'Aug',
            'wrz' => 'Sep',
            'paź' => 'Oct',
            'lis' => 'Nov',
            'gru' => 'Dec'
        ];

        $texttualTimeUploaded = strtr($texttualTimeUploaded, $trans);

        $date = \DateTime::createFromFormat(
            'j M y G:i',
            $texttualTimeUploaded,
            new \DateTimeZone('Europe/Warsaw') // important, because Chomikuj always displays time using Europe/Warsaw timezone
        );

        if ($date === false) {
            throw new ChomikujException(Api::ERR_WEIRD_RESPONSE);
        }

        return $date;
    }
}
