<?php

namespace Challenges;

class Auth {
    var $Client;

    public function authenticate($credsFile) {
        $credsFile = $_SERVER['HOME'] . "/.rackspace_cloud_credentials";

        try {
            // Try opening $credsFile and fetching the credentials from it
            if (($cloudCreds = @parse_ini_file($credsFile)) == false) {
                throw new Exception("Missing or unreadable INI file: " . $credsFile . "\n");
            }
            // Auth using credentials from $credsFile
            $client = new Rackspace(Rackspace::US_IDENTITY_ENDPOINT, array(
                'username' => $cloudCreds['username'],
                'apiKey'   => $cloudCreds['api_key']
            ));

            $this->Client = $client;
        } catch (Exception $e) {
                die($e->getMessage());
        }
    }
}

?>
