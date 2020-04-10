<?php


class GoogleDrive
{
    protected $client;
    protected $service;

    public function __construct()
    {
        require __DIR__ . '/vendor/autoload.php';
        $this->client = $this->getClient();
        $this->service = new Google_Service_Drive($this->client);
    }

    function getClient()
    {
        $client = new Google_Client();
        $client->setApplicationName('Google Sheets API');
        $client->setScopes(Google_Service_Drive::DRIVE);
        $client->setAuthConfig('credentials.json');
        $client->setAccessType('offline');
        $client->setPrompt('select_account consent');
        $tokenPath = 'token.json';
        if (file_exists($tokenPath)) {
            $accessToken = json_decode(file_get_contents($tokenPath), true);
            $client->setAccessToken($accessToken);
        }

        // If there is no previous token or it's expired.
        if ($client->isAccessTokenExpired()) {
            // Refresh the token if possible, else fetch a new one.
            if ($client->getRefreshToken()) {
                $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
            } else {
                // Request authorization from the user.
                $authUrl = $client->createAuthUrl();
                printf("Open the following link in your browser:\n%s\n", $authUrl);
                print 'Enter verification code: ';
                $authCode = trim(fgets(STDIN));

                // Exchange authorization code for an access token.
                $accessToken = $client->fetchAccessTokenWithAuthCode($authCode);
                $client->setAccessToken($accessToken);

                // Check to see if there was an error.
                if (array_key_exists('error', $accessToken)) {
                    throw new Exception(join(', ', $accessToken));
                }
                // Save the token to a file.
                if (!file_exists(dirname($tokenPath))) {
                    mkdir(dirname($tokenPath), 0700, true);
                }
                file_put_contents($tokenPath, json_encode($client->getAccessToken()));
            }

        }
        return $client;
    }

    function convertDocTOPdf($path)
    {
        $fileId = $this->upload($path);
        //$fileId = '1xw07QDsz1xtfb7WFMW15eO6JeJJRTn2W2OyEtcNdLgk';
        $content = $this->service->files->export($fileId, 'application/pdf', array('alt' => 'media'));
        $this->export($path, $content);
        $response = $this->delete($fileId);
    }

    function upload($path)
    {
        $fileMetadata = new Google_Service_Drive_DriveFile(array(
            'name' => 'testdoc.docx',
            'mimeType' => 'application/vnd.google-apps.document'));
        $content = file_get_contents($path);
        $file = $this->service->files->create($fileMetadata, array(
            'data' => $content,
            'mimeType' => 'application/vnd.google-apps.document',
            'uploadType' => 'multipart',
            'fields' => 'id'));
        printf("File ID: %s\n", $file->id);
        return $file->id;
    }

    function export($path, $content)
    {
        $newPath = preg_replace('/[a-z]+$/u', 'pdf', $path);
        $outHandle = fopen($newPath, "w+");
        while (!$content->getBody()->eof()) {
            fwrite($outHandle, $content->getBody()->read(1024));
        }
        fclose($outHandle);
    }

    function delete($fileId)
    {
        $response = $this->service->files->delete($fileId);
        return response;
    }


}