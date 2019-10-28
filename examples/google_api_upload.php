<?php
require '/var/www/html/demo/vendor/autoload.php';
include_once "templates/base.php";
set_time_limit(0);
ini_set('memory_limit', '100000M');

if (php_sapi_name() != 'cli') {
    throw new Exception('This application must be run on the command line.');
}

/**
 * Returns an authorized API client.
 * @return Google_Client the authorized client object
 */
function getClient()
{
    $client = new Google_Client();
    $client->setApplicationName('Google Drive API PHP Quickstart');
    // $client->setScopes(Google_Service_Drive::DRIVE_METADATA_READONLY);
	$client->setScopes(Google_Service_Drive::DRIVE);
    $client->setAuthConfig('credentials.json');
    $client->setAccessType('offline');
    $client->setPrompt('select_account consent');

    // Load previously authorized token from a file, if it exists.
    // The file token.json stores the user's access and refresh tokens, and is
    // created automatically when the authorization flow completes for the first
    // time.
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
        }
        // Save the token to a file.
        if (!file_exists(dirname($tokenPath))) {
            mkdir(dirname($tokenPath), 0700, true);
        }
        file_put_contents($tokenPath, json_encode($client->getAccessToken()));
    }
    return $client;
}


// Get the API client and construct the service object.
$client = getClient();
$service = new Google_Service_Drive($client);

// // Print the names and IDs for up to 10 files.
// $optParams = array(
  // 'pageSize' => 10,
  // 'fields' => 'nextPageToken, files(id, name)'
// );
// $results = $service->files->listFiles($optParams);

// if (count($results->getFiles()) == 0) {
    // print "No files found.\n";
// } else {
    // print "Files:\n";
    // foreach ($results->getFiles() as $file) {
        // printf("%s (%s)\n", $file->getName(), $file->getId());
    // }
// }

//Upload file

// $fileMetadata = new Google_Service_Drive_DriveFile(array(
    // 'name' => 'ORSPB-006.mp4'));
// $content = file_get_contents('/var/www/html/complete/ORSPB-006.mp4');
// $file = $service->files->create($fileMetadata, array(
    // 'data' => $content,
    // 'mimeType' => 'image/jpeg',
    // 'uploadType' => 'resumable',
    // 'fields' => 'id'));
// printf("File ID: %s\n", $file->id);

// upload file method 2

// if ($_SERVER['REQUEST_METHOD'] == 'POST' && $client->getAccessToken()) {
  /************************************************
   * We'll setup an empty 20MB file to upload.
   ************************************************/
 if (isset($argv)) {
	 // var_dump($argv[1]);
	 // die();
	  DEFINE("TESTFILE", '/var/www/html/complete/'.$argv[1]);
	  if (!file_exists(TESTFILE)) {
		$fh = fopen(TESTFILE, 'w');
		fseek($fh, 1024*1024*20);
		fwrite($fh, "!", 1);
		fclose($fh);
	  }

	  $file = new Google_Service_Drive_DriveFile();
	  $file->name = $argv[1];
	  $chunkSizeBytes = 1 * 1024 * 1024;

	  // Call the API with the media upload, defer so it doesn't immediately return.
	  $client->setDefer(true);
	  $request = $service->files->create($file);

	  // Create a media file upload to represent our upload process.
	  $media = new Google_Http_MediaFileUpload(
		  $client,
		  $request,
		  'video/mp4',
		  null,
		  true,
		  $chunkSizeBytes
	  );
	  // var_dump ($media) ;
	  $media->setFileSize(filesize(TESTFILE));

	  // Upload the various chunks. $status will be false until the process is
	  // complete.
	  $status = false;
	  $handle = fopen(TESTFILE, "rb");
	  while (!$status && !feof($handle)) {
		// read until you get $chunkSizeBytes from TESTFILE
		// fread will never return more than 8192 bytes if the stream is read buffered and it does not represent a plain file
		// An example of a read buffered file is when reading from a URL
		$chunk = readVideoChunk($handle, $chunkSizeBytes);
		$status = $media->nextChunk($chunk);
	  }

	  // The final value of $status will be the data from the API for the object
	  // that has been uploaded.
	  $result = false;
	  if ($status != false) {
		$result = $status;
	  }

	  fclose($handle);
	// }
}

function readVideoChunk ($handle, $chunkSize)
{
	$byteCount = 0;
	$giantChunk = "";
	while (!feof($handle)) {
		// fread will never return more than 8192 bytes if the stream is read buffered and it does not represent a plain file
		$chunk = fread($handle, 8192);
		$byteCount += strlen($chunk);
		$giantChunk .= $chunk;
		if ($byteCount >= $chunkSize)
		{
			return $giantChunk;
		}
	}
	return $giantChunk;
}
?>










