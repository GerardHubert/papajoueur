<?php

namespace App\Tests\Services;

use App\Services\FileUploadService;
use Exception;
use RuntimeException;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class FileUploadServiceTest extends WebTestCase
{

    /**
     * make the File Object an array
     * @return array
     */
    public function getArray(UploadedFile $file)
    {
        $data = [
            'name' => $file->getFilename(),
            'type' => $file->getMimeType(),
            'tmp_name' => './tests/images/temp/' . uniqid(),
            'error' => 0,
            'size' => $file->getSize()
        ];

        return $data;
    }

    public function testExceptionIfExtensionIsNotValid(): void
    {
        $client = static::createClient();

        $file = new UploadedFile('./tests/images/bad_extension.iso', 'bad_extension.iso', 'application/octet-stream');

        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Cette extension n'est pas autorisée.");
        FileUploadService::avatarUpload($this->getArray($file));
    }

    public function testExceptionIfDoubleExtension(): void
    {
        $client = static::createClient();

        $file = new UploadedFile('./tests/images/double_extension.php.jpeg', 'double_extension.php.jpeg', 'application/octet-stream');

        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Danger : risque de double extension !");
        FileUploadService::avatarUpload($this->getArray($file));
    }

    public function testExceptionIfFileIsTooHeavy(): void
    {
        $client = static::createClient();

        $file = new UploadedFile('./tests/images/too_heavy2.jpg', 'too_heavy2.jpg', 'image/jpeg');

        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Le fichier est trop volumineux (taille max 2Mo)");
        FileUploadService::avatarUpload($this->getArray($file));
    }

    public function testUploadSuccessful()
    {
        $file = new UploadedFile('./tests/images/ac_odyssey.jpg', 'ac_odyssey.jpg', 'image/jpeg');

        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Le fichier n'a pas pu être uploadé, merci de réessayer");
        FileUploadService::avatarUpload($this->getArray($file));
    }
}
