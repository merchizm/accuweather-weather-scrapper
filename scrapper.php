<?php
function curl($url) : array
{
    $ch = curl_init();
    // curl options
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);// If Location: headers are sent by the server, follow the location.
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        ''=>''
    ]);
    curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/15.2 Safari/605.1.15");
    $r["http_response"] = curl_exec($ch);
    $r["http_response_code"] = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $r["url"] = $url;
    curl_close($ch);
    return $r;
}

if(empty($_GET['city']))
    die(json_encode([
        "status"=>"error",
        "message"=>"If you do not fill the city parameter, you will not get results :("
    ]));

$cityDetails = curl("https://www.accuweather.com/web-api/autocomplete?query={$_GET['city']}&language={$_SERVER['HTTP_ACCEPT_LANGUAGE']}");

if($cityDetails["http_response_code"] === 200){
    $cityCode = json_decode($cityDetails['http_response'])[0]->{'key'};
    $cityName = json_decode($cityDetails['http_response'])[0]->{'localizedName'};

    $weather = curl("https://www.accuweather.com/".str_replace("-", "/", explode(',', $_SERVER['HTTP_ACCEPT_LANGUAGE'])[0])."/$cityName/$cityCode/current-weather/$cityCode");

    if($weather['http_response_code'] !== 200)
        die(json_encode([
            "status"=>"error",
            "message"=>"Unfortunately, this script outdated.",
            "url"=>$weather['url'],
            "response_code"=>$weather['http_response_code']
        ]));

    $domDoc = new DOMDocument();
    libxml_use_internal_errors(true); // for HTML5 errors
    $domDoc->loadHTML($weather['http_response']);
    libxml_clear_errors(); // ;)
    $domX = new DOMXPath($domDoc);

    $result['status'] = $domX->query("/html/body/div/div[5]/div[1]/div[1]/div[2]/div[3]")->item(0)->nodeValue;
    $result['wind'] = $domX->query('/html/body/div/div[5]/div[1]/div[1]/div[2]/div[4]/div[1]/div[1]/div[2]')->item(0)->nodeValue;
    $result['wind_gusts'] = $domX->query('/html/body/div/div[5]/div[1]/div[1]/div[2]/div[4]/div[1]/div[2]/div[2]')->item(0)->nodeValue;
    $result['humidity'] = $domX->query('/html/body/div/div[5]/div[1]/div[1]/div[2]/div[4]/div[1]/div[3]/div[2]')->item(0)->nodeValue;
    $result['indoor_humidity'] = $domX->query('/html/body/div/div[5]/div[1]/div[1]/div[2]/div[4]/div[1]/div[4]/div[2]')->item(0)->nodeValue;
    $result['temp'] = $domX->query('/html/body/div/div[5]/div[1]/div[1]/div[2]/div[2]/div[1]/div/div[1]')->item(0)->nodeValue;
    $result['pressure'] = $domX->query('/html/body/div/div[5]/div[1]/div[1]/div[2]/div[4]/div[2]/div[1]/div[2]')->item(0)->nodeValue;
    $result['cloud_cover'] = $domX->query('/html/body/div/div[5]/div[1]/div[1]/div[2]/div[4]/div[2]/div[2]/div[2]')->item(0)->nodeValue;
    $result['visibility'] = $domX->query('/html/body/div/div[5]/div[1]/div[1]/div[2]/div[4]/div[2]/div[3]/div[2]')->item(0)->nodeValue;
    $result['cloud_ceiling'] = $domX->query('/html/body/div/div[5]/div[1]/div[1]/div[2]/div[4]/div[2]/div[4]/div[2]')->item(0)->nodeValue;

    die(json_encode([
        "city" => $cityName,
        ...$result
    ]));

}else{
    die(json_encode([
        "status"=>"error",
        "message"=>"Unfortunately, this script outdated."
    ]));
}