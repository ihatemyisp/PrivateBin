<?php

// This script was originally written by @idrassi (https://github.com/idrassi) for YOURLS
// security. Editted to include bitly (v4).
// Original discussion can be found here: https://github.com/PrivateBin/PrivateBin/issues/725
?>

<html>
<head>
<title>Secure PrivateBin URL Shortener</title>

<style type="text/css">
  SPAN.shortensuccess {font-weight: bolder; color : green}
  SPAN.shortenerror {font-weight: bolder; color : red}
</style>
</head>

<body>
<?php

	// These settings must be changed prior to using the shortener,

	// [Main Settings]
	// Which shortener are you using?
	// Currently supported: yourls, bitly 
	$shortner = "yourls";

	// Your PrivateBin pastes URL template.
	// Include the /? here
	$pbUrl = "https://privatebin.info/?";

	// [YOURLS Settings] 
	// The URL for your YOURLS installation API.
	// This is hasn't been tested in a folder vice subdomain use case; YMMV, but in theory, I think it should work
	$yourlsUrl = "https://url.privatebin.info/yourls-api.php";
	$yourlsBase = "https://url.privtebin.info/"; // too lazy to code in a regex for this
	$yourlsSignature = "XXXXXXX";

	// [Bitly Settings]
	$bitlyKey = "XXXXXXXXXXXXXXXXXXXX";

	// Don't edit below here
	// -------------------------------------------------

	$link = $_SERVER['REQUEST_URI'];
	$response = getGetData();

	$arr = explode('=',$response);
	$c = count ($arr);

	$opSuccess = FALSE;

	$shortenedUrl = "";
	$originalUrl = "";

	if(($shortner == "yourls")){
		if(($c == 2) && ($arr[0] == "link") && (strlen($arr[1]) < 256)) {
			$decodedUrl = urldecode($arr[1]);

			if (startsWith($decodedUrl, $pbUrl)) {
				$originalUrl = $decodedUrl;
	  			// Init the CURL session
				$ch = curl_init();
				curl_setopt($ch, CURLOPT_URL, $yourlsUrl);
				curl_setopt($ch, CURLOPT_HEADER, 0);            // No header in the result
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // Return, do not echo result
				curl_setopt($ch, CURLOPT_POST, 1);              // This is a POST request
				curl_setopt($ch, CURLOPT_POSTFIELDS, array(     // Data to POST
					'signature' => $yourlsSignature,
        			'format'   => 'simple',
        			'action'   => 'shorturl',
					'url' => $originalUrl
    			));

				// Fetch and return content
 				$data = curl_exec($ch);
 				curl_close($ch);

   				if (!($data === FALSE) && is_string($data) && startsWith ($data, $yourlsBase))
   				{
					$shortenedUrl = $data;
					$opSuccess = TRUE;
   				}
			}
		}
    }
    
    if(($shortner == "bitly")){
        if(($c == 2) && ($arr[0] == "link") && (strlen($arr[1]) < 256)) {
			$decodedUrl = urldecode($arr[1]);

			if (startsWith($decodedUrl, $pbUrl)) {
                $originalUrl = $decodedUrl;
                
                $json_data = array(
                    'long_url' => $originalUrl
                );
                $post_data = json_encode($json_data);

                // Init the CURL session
				$ch = curl_init();
				curl_setopt($ch, CURLOPT_URL, "https://api-ssl.bitly.com/v4/shorten");
                curl_setopt($ch, CURLOPT_HEADER, 0);            // No header in the result
                curl_setopt($ch, CURLOPT_HEADER_OUT, true);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // Return, do not echo result
                curl_setopt($ch, CURLOPT_POST, 1);              // This is a POST request
                curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                    'Content-Type: application/json',     // Auth with bitly via HTTP header
                    'Authorization: Bearer ' . $bitlyKey
                ));
				curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);

				// Fetch and return content
 				$data = curl_exec($ch);
                curl_close($ch);
                $return_data = json_decode($data, true);

   				if (!($return_data === FALSE) && is_array($return_data) && startsWith ($return_data['link'], "https://bit.ly/"))
   				{
					$shortenedUrl = $return_data['link'];
					$opSuccess = TRUE;
   				}
			}
		}
    }

if ($opSuccess)
{
	print("<br>Your shortened paste is <span class=\"shortensuccess\"><a href=\"$shortenedUrl\">$shortenedUrl</a></span>");
}
else
{
	print("<br><span class=\"shortenerror\">Error: An error occured while trying to shorten the given URL</span>");
}

function getGetData() {
	$data = http_build_query($_GET);
	return $data;
}

function startsWith($haystack, $needle)
{
	$length = strlen($needle);
	return (substr($haystack, 0, $length) === $needle);
}

?>

</body>
</html>
