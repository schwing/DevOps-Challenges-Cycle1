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

} catch (Exception $e) {
    die($e->getMessage());

}
?>
