<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Chomikuj\Exception\ChomikujException;
use Chomikuj\Mapper\FileMapper;
use Chomikuj\Entity\File;
use GuzzleHttp\Psr7\Response;

final class FileMapperTest extends TestCase
{
	public function testCanBeCreated(): void
	{
		$this->assertInstanceOf(
            FileMapper::class,
            new FileMapper
        );
	}

	public function testMapSearchResponseReturnsObjectsWithValidData(): void
	{
		$responseBody = <<<EOD
...
<div class="filerow alt fileItemContainer">
    <div class="fileinfo tab">
        <ul class="borderRadius tabGradientBg">
            <li><span>52 MB</span></li>
            <li><span class="date">3 maj 13 22:54</span></li>
        </ul>
    </div>

    <div class="fileActionsButtons clear visibleButtons  fileIdContainer" rel="2694749415" style="visibility: hidden;">
        <ul>
            <li><a class="showFileRating ratingBtn" href="javascript:;" title="Oceń plik"><span class="star5"></span></a></li>
            <li><a href="/somepath/somefile.pdf" class="downloadAction downloadContext" title="pobierz" ><img alt="pobierz" src="//x4.static-chomikuj.pl/res/xxxxxxx.png" title="pobierz" /></a></li>
        </ul>
    </div>
    <div onmouseover="$('.visibleArrow', this).css('visibility', 'visible')" 
    onmouseout="$('.visibleArrow', this).css('visibility', 'hidden');" class="filename pdf">
        <h3>
            <a class="expanderHeader downloadAction downloadContext" href="/somepath/somefile.pdf" title="Some file name">
                <span class="bold">Some file name</span>.pdf
            </a>
            <img alt="pobierz" class="downloadArrow visibleArrow" src="//x4.static-chomikuj.pl/res/xxxxxxx.png" style="visibility: hidden;" title="pobierz" />
        </h3>
    </div>
</div>
<div class="filerow alt fileItemContainer">
    <div class="fileinfo tab">
        <ul class="borderRadius tabGradientBg">
            <li><span>0,97 GB</span></li>
            <li><span class="date">14 sie 17 5:58</span></li>
        </ul>
    </div>

    <div class="fileActionsButtons clear visibleButtons  fileIdContainer" rel="2694749415" style="visibility: hidden;">
        <ul>
            <li><a class="showFileRating ratingBtn" href="javascript:;" title="Oceń plik"><span class="star5"></span></a></li>
            <li><a href="/anotherpath/anotherfile.pdf" class="downloadAction downloadContext" title="pobierz" ><img alt="pobierz" src="//x4.static-chomikuj.pl/res/xxxxxxx.png" title="pobierz" /></a></li>
        </ul>
    </div>
    <div onmouseover="$('.visibleArrow', this).css('visibility', 'visible')" 
    onmouseout="$('.visibleArrow', this).css('visibility', 'hidden');" class="filename pdf">
        <h3>
            <a class="expanderHeader downloadAction downloadContext" href="/anotherpath/anotherfile.pdf" title="Another file name">
                <span class="bold">Another file name</span>.pdf
            </a>
            <img alt="pobierz" class="downloadArrow visibleArrow" src="//x4.static-chomikuj.pl/res/xxxxxxx.png" style="visibility: hidden;" title="pobierz" />
        </h3>
    </div>
</div>
...
EOD;
	
		$response = new Response(200, [], $responseBody);

		$fileMapper = new fileMapper();
		$files = $fileMapper->mapSearchResponseToFiles($response);

		// First file
		$this->assertInstanceOf(
            File::class,
            $files[0]
		);
		$this->assertEquals('/somepath/somefile.pdf', $files[0]->getPath());
		$this->assertEquals('Some file name', $files[0]->getName());
		$this->assertEquals(52 * 1024, $files[0]->getSize());
		$this->assertEquals(
			\DateTime::createFromFormat('Y-m-d H:i', '2013-05-03 22:54', new \DateTimeZone('Europe/Warsaw')),
			$files[0]->getTimeUploaded()
		);

		// Second file
		$this->assertInstanceOf(
            File::class,
            $files[1]
		);
		$this->assertEquals('/anotherpath/anotherfile.pdf', $files[1]->getPath());
		$this->assertEquals('Another file name', $files[1]->getName());
		$this->assertEquals(floor(0.97 * 1024 * 1024), $files[1]->getSize());
		$this->assertEquals(
			\DateTime::createFromFormat('Y-m-d H:i', '2017-08-14 05:58', new \DateTimeZone('Europe/Warsaw')),
			$files[1]->getTimeUploaded()
		);
	}
}