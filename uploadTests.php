<?php

    //Insert the settings file which contains passwords and such
    include_once('api_settings.php');

    //Get the JSON input
    $input = json_decode(file_get_contents('php://input'));

    //This is where we store the output
    $output = array();

    //One by one, insert those test cases, appending the results to the output
    foreach($input as $test){
        array_push($output, insert_JIRA($test));
    }

    //Return the output
    echo json_encode($output);

    //This is the function for inserting into JIRA
    function insert_JIRA($cURLdata){
	$curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_PORT => "8080",
            CURLOPT_URL => $GLOBALS['jiraHost'] . "testcase/",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => json_encode($cURLdata, JSON_UNESCAPED_SLASHES),
            CURLOPT_HTTPHEADER => array(
		"Authorization: Basic " . $GLOBALS['authKey'],
		"Cache-Control: no-cache",
		"Content-Type: application/json",
		"Postman-Token: 09ee4077-a3e2-405a-92b6-f58a5c4a68ea"
            ),
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
            return json_decode($err, true);
        } else {
            return json_decode($response, true);
        }
    }

?>
