# chomikuj2

Unofficial Chomikuj.pl PHP API.

## Installing
```
composer require retrowaver/chomikuj2
```

## Usage

### Initialization

```php
use Chomikuj\Api;

//...

$chomikuj = new Api();
$chomikuj->login('username', 'password');
```

### Creating folders
```php
// Create some folders in root folder (0)
$chomikuj->createFolder('some folder', 0);
$chomikuj->createFolder('some NSFW folder', 0, true);
$chomikuj->createFolder('some password-protected folder', 0, false, 'some_password');
```

### Removing folders
```php
// Remove folder with id 12345
$chomikuj->removeFolder(12345);
```
```php
// Remove all folders
$rootFolder = $chomikuj->getFoldersByUsername();
foreach ($rootFolder->getFolders() as $folder) {
	$chomikuj->removeFolder($folder->getId());
}
```

### Uploading files
```php
// Upload a file to root folder
$chomikuj->uploadFile(0, 'path/to/file.zip');
```
```php
// Upload a file to a folder with id 12
$chomikuj->uploadFile(12, 'path/to/another/file.zip');
```

### Logging out

```php
$chomikuj->logout();
```

## Built with

* [guzzlehttp/guzzle ](https://packagist.org/packages/guzzlehttp/guzzle)

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.