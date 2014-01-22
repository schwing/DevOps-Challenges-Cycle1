<?php

require 'vendor/autoload.php';

use OpenCloud\Rackspace;

$credsFile = $_SERVER['HOME'] . "/.rackspace_cloud_credentials";

try {
    // Try to open $credsFile and read the credentials from it
    if (($cloudCreds = @parse_ini_file($credsFile)) == false) {
        throw new Exception("Missing or unreadable INI file: " . $credsFile . "\n");
    }

    // Auth using credentials from $credsFile
    $client = new Rackspace(Rackspace::US_IDENTITY_ENDPOINT, array(
        'username' => $cloudCreds['username'],
        'apiKey'   => $cloudCreds['api_key']
    ));

    $cloudFiles = $client->objectStoreService('cloudFiles', 'DFW');

    // Get the new container name
    // TODO: constraints on input from (https://github.com/rackspace/php-opencloud/blob/master/docs/userguide/ObjectStore/Storage/Container.md):
    //  "Container names must be valid strings between 0 and 256 characters. Forward slashes are not currently permitted."
    $validInput = FALSE;
    while ($validInput == FALSE) {
        echo "Please input a name for the new container: ";
        $handle = fopen ("php://stdin","r");
        $containerName = trim(fgets($handle));
        if ($containerName == NULL) {
            echo "Invalid input, please try again.\n\n";
        } else {
            $validInput = TRUE;
            echo "\n";
        }
    }

    // Try to create the container
    $container = $cloudFiles->createContainer($containerName);

    // Throw an exception if we weren't able to create the container.
    if ($container == FALSE) {
        // Try to get the container. If this fails for any reason, it will throw an exception.
        // Otherwise, the container exists because we were able to get it, so we throw our own custom exception and exit.
        $cloudFiles->getContainer($containerName);
        throw new Exception(sprintf("A container named \"%s\" already exists.\nPlease run this script again with a different container name.\n", $containerName));
    }

    // Get the filesystem path of the directory to upload to this new container
    $validInput = FALSE;
    while ($validInput == FALSE) {
        echo "Input the filesystem path of the directory to upload to the new container: ";
        $handle = fopen ("php://stdin","r");
        $directoryPath = trim(fgets($handle));
        if ($directoryPath == NULL) {
            echo "Invalid input. Directory not specified. Please try again.\n\n";
        }
        elseif (!is_dir($directoryPath) || !is_readable($directoryPath)) {
            echo "Invalid input. Directory does not exist or is unreadable. Please try again.\n\n";
        } else {
            $validInput = TRUE;
            echo "\n";
        }
    }

    // Upload the directory contents to the container
    //   This doesn't return anything, and I don't see any hooks to check on upload status, current file,
    //   or anything else that would be useful when uploading files :-(
    $container->uploadDirectory($directoryPath);

    echo "Upload complete.\n";

    // Enable CDN for the container
    $container->enableCdn();

    // Get the CDN URI
    $cdnUri = $container->getCdn()->getCdnUri();
    printf("CDN enabled. URI: %s\n", $cdnUri);

} catch (Exception $e) {
    die($e->getMessage());

}
?>
