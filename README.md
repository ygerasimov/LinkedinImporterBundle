LinkedinImporterBundle
======================

Linkedin profile data importer for symfony

Installation
------------

### Add the package to your dependencies

``` json
{
    "require": {
        "ccc/linkedin-importer-bundle": "dev-master"
        ...
    }
}
```

### Register the bundle in your kernel

``` php
public function registerBundles()
{
    $bundles = array(
        // ...
        new CCC\LinkedinImporterBundle\LinkedinImporterBundle(),
        // ...
    );
```

### Update your packages

``` bash
$ php composer.phar update
```

Basic Usage
-----
See /LinkedinImporterBundle/DefaultController.php for examples

### Requesting User Permissions

``` php
$importer = $this->get('ccc_linkedin_importer.importer');
$importer->setRedirect($url);
$importer->requestPermission();
```

### Getting an access token

``` php
$access_token = $importer->setCode($code_retrived_from_permission_request)->requestAccessToken();
```

### Pulling user data

Private profile data
``` php
$profile_data = $importer->requestUserData('private', $access_token);
```

Public profile data
``` php
$profile_url = 'http://linkedin.com/someones-profile';
$profile_data = $importer->setPublicProfileUrl($profile_url)->requestUserData('public', $access_token);
```
