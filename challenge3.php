<?php

require 'vendor/autoload.php';

use OpenCloud\Rackspace;
use OpenCloud\DNS;

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

    $cloudDNS = $client->DNSService();

    echo "ID\tDomain\n--\t------\n";

    $domCount = 0;
    foreach ($cloudDNS->domainList() as $domain) {
        $domCount++;
        echo sprintf("%s\t%s\n", $domCount, $domain->Name());
    }

    // Get the number of servers to build
    $validInput = FALSE;
    while ($validInput == FALSE) {
        echo "Please input a domain ID from the list above above to add an A record: ";
        $handle = fopen ("php://stdin","r");
        $numServers = trim(fgets($handle));
        $filter_options = array(
            'options' => array(
                'min_range' => 1,
                'max_range' => $domCount
            )
        );
        if (filter_var($numServers, FILTER_VALIDATE_INT, $filter_options) == FALSE){
            echo "Invalid input, please try again.\n\n";
        } else {
            $validInput = TRUE;
            echo "\n";
        }
    }

} catch (Exception $e) {
    die($e->getMessage());

}
?>
