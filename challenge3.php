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
        $domains[$domCount] = $domain;
        echo sprintf("%s\t%s\n", $domCount, $domain->Name());
    }

    // Get the domain ID (this is not the actual domain ID, but is from the simple numbered list above)
    $validInput = FALSE;
    while ($validInput == FALSE) {
        echo "Please input a domain ID from the list above above to add an A record: ";
        $handle = fopen ("php://stdin","r");
        $domId = trim(fgets($handle));
        $filter_options = array(
            'options' => array(
                'min_range' => 1,
                'max_range' => $domCount
            )
        );
        if (filter_var($domId, FILTER_VALIDATE_INT, $filter_options) == FALSE) {
            echo "Invalid input, please try again.\n\n";
        } else {
            $validInput = TRUE;
            echo "\n";
        }
    }

    // Get the FQDN
    $validInput = FALSE;
    while ($validInput == FALSE) {
        echo "Please input the FQDN: ";
        $handle = fopen ("php://stdin","r");
        $fqdn = trim(fgets($handle));

        if ($fqdn == NULL) {
            echo "No fqdn provided. Please try again.\n\n";
        } else {
            $validInput = TRUE;
            echo "\n";
        }
    }

    // Get the TTL
    $validInput = FALSE;
    while ($validInput == FALSE) {
        echo "Please input the TTL: ";
        $handle = fopen ("php://stdin","r");
        $ttl = trim(fgets($handle));
        if (filter_var($ttl, FILTER_VALIDATE_INT) == FALSE) {
            echo "Invalid input, please try again.\n\n";
        } else {
            $validInput = TRUE;
            echo "\n";
        }
    }

    // Get the IP address
    $validInput = FALSE;
    while ($validInput == FALSE) {
        echo "Please input the IPv4 address: ";
        $handle = fopen ("php://stdin","r");
        $ipAddr = trim(fgets($handle));
        if ($ipAddr == NULL) {
            echo "No fqdn provided. Please try again.\n\n";
        } else {
            $validInput = TRUE;
            echo "\n";
        }
    }

    // Add the A record
    $record = $domains[$domId]->record();
    $response = $record->create(array(
        'type' => 'A',
        'name' => $fqdn,
        'ttl' => $ttl,
        'data' => $ipAddr
    ));

    // Wait for the update to complete
    $callback = function($response) {
        printf("%s %s\n", $response->Status(), $response->Name());
        if ($response->Status() == 'ERROR') {
            throw new Exception(sprintf("\tError code [%d] message [%s]\n\tDetails: %s\n", $response->error->code, $response->error->message, $response->error->details));
        }
        else if ($response->Status() == 'COMPLETED') {
            // Only adding 1 record, so only one should be returned
            $records = $response->response->records[0];
            echo sprintf("Record added successfully: %s\nTTL: %s\nIP Address: %s\n", $records->name, $records->ttl, $records->data);
        }
    };

    $response->waitFor("COMPLETED", 300, $callback);

} catch (Exception $e) {
    die($e->getMessage());

}
?>
