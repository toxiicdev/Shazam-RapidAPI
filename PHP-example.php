<?php

/* Rapid API Shazam Example by toxiicdev */
/* Dependencies: CURL, ShellExec, FFmpeg */

function identifySong($filePath)
{
    $apiKey = "INSERT-HERE-YOUR-KEY"; // <==== Insert here your RapidAPI Key
    $datPath = __DIR__."/converted_".md5($filePath).".dat";
    shell_exec("ffmpeg -i \"$filePath\" -ac 1 -f s16le -acodec pcm_s16le -ar 44100 -y \"$datPath\" >/dev/null 2>&1");
    // Max 500kb as limited by Rapid API documentation
    $maxSize = 500 * 1024;

    $file = fopen($datPath, 'rb');
    $fileContent = fread($file, $maxSize);
    fclose($file);
    unlink($datPath);
    
    $base64FileContent = base64_encode($fileContent);
    $curl = curl_init();

    curl_setopt_array($curl, array(
        CURLOPT_URL => "https://shazam.p.rapidapi.com/songs/v2/detect",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "POST",
        CURLOPT_POSTFIELDS => $base64FileContent,
        CURLOPT_HTTPHEADER => array(
            "Content-Type: application/octet-stream",
            "X-RapidAPI-Key: $apiKey",
            "X-RapidAPI-Host: shazam.p.rapidapi.com"
        ),
    ));
    
    $response = curl_exec($curl);
    $err = curl_error($curl);
    curl_close($curl);

    if ($err) return null;
    
    $responseData = json_decode($response, true);

    if (isset($responseData['track']['title']) && isset($responseData['track']['subtitle']))
        return $responseData['track']['subtitle'] . " - " . $responseData['track']['title'];
    else if(isset($responseData['track']['title']))
        return $responseData['track']['title'];
    return null;
}

$filePath = __DIR__."/test.mp3";
$songName = identifySong($filePath);

if($songName != null)
    echo "Song name: $songName\n";
else
  echo "Song cannot be identified";

?>
