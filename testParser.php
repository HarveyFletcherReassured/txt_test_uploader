<?php

	//This is the overall output of the tests, it is a global
	$Tests_Sent_Output = array();

	//Get the input test document and split it up.
	$testDocument = explode('###',file_get_contents('tests.txt'));
	
	//Remove the blank label from the start of the document
	array_shift($testDocument);
	
	//Split into 3 different sections
	$testDocument = array_chunk($testDocument, 2);

	//The test steps are contained in a subarray, store those as a single array.
	$tests = explode(PHP_EOL, trim($testDocument[4][1]));
	
	//If we're in debug mode, show the input file
	if(isset($_GET['debug'])){	
		echo '<pre>' . print_r($testDocument, 1) . '</pre>';
	}
	
	//Initialise index as 0 so that we start in the right place on the array.
	$index = 0;
	
	//For all the tests, we want to split the steps into an array of description and expected result based on the separator.
	foreach($tests as $item){
		//Split into array
		$item = explode("->", trim($item));
	
		//Update the test step so it is the new array
		$tests[$index] = $item;
		
		//Increment to the next step
		$index++;
	}
	
	if($tests[sizeof($tests) - 1] != ""){
		array_push($tests, "");
	}
	
	//We are going to need somewhere to store the processed test cases.
	$output_tests = array();
	
	//We need a temporary array to store the test case currently under processing.
	$test = array();
	
	//For all the test cases, ensure they have a name and ID as well as steps.
	foreach($tests as $item){
		
		//Only process if there is at least 1 step (not blank)
		if(sizeof($item) > 1){
			//If this is not a header, process the step, if it is the header, add it at the start of the test case.
			if(strpos($item[0], "TC") === false){
				//Build the step so it has the right columns and the columns contain the right data
				$item['step'] = explode(".", $item[0])[0];			
				$item['description'] = trim(substr($item[0], strpos($item[0], ".") + 1));
				$item['expected'] = trim($item[1]);
				
				//These are the initial values which are no longer needed, remove them.
				unset($item[0]);
				unset($item[1]);
			} else {
				//Process the test case headers
				$item['id'] = trim($item[0]);
				$item['name'] = trim($item[1]);
				unset($item[0]);
				unset($item[1]);
			}
			
			//Add the header or the step onto the temporary test
 			array_push($test, $item);
		} else {
			//We are done, apply the current test to test output.
			array_push($output_tests, $test);
			
			//Re-initialise the temporary test so that we can process the next one.
			$test = array();
		}
	}
	
	//Tests is a blank array so that we can add stuff to it in the next processing step.
	$tests = array();
	
	//Re-set index to 0 so that we start processing from the beginning of the tests array.
	$index = 0;	
	
	//Process the test steps by giving the columns names which Adaptavist uses.
	foreach($output_tests as $test_case){
		$test_case['test_details'] = $test_case[0];
		unset($test_case[0]);
		
		$test_case['test_steps'] = array();
		
		foreach($test_case as $step){
			$step['testdata'] = "";
			unset($test_case[$index]);
			array_push($test_case['test_steps'], $step);
			$index++;
		}
	
		
		array_pop($test_case['test_steps']);
		array_pop($test_case['test_steps']);
		
		$index=0;
		
		//Add the test case on to the array of tests.
		array_push($tests, $test_case);
	}
	
	//Post each test case through to JIRA and Adaptavist
	foreach($tests as $testcase){
		if(strlen($testDocument[1][1]) > 4){
			$FolderName = "/" . trim(strtoupper($testDocument[0][1])) . "/" . trim(strtoupper($testDocument[1][1]));
			$IssueLinks = array(str_replace(' ', '-', trim(strtoupper($testDocument[1][1]))));
		} else {
			$FolderName = "/" . trim(strtoupper($testDocument[0][1]));
			//$FolderName = "/" . trim($testDocument[0][1]);
			$IssueLinks = array();
		}
		
		$JSONBody = array(
			"projectKey" => "DEV",
			"folder" => $FolderName,
			"name" => $testcase['test_details']['name'],
			"objective" => trim($testDocument[2][1]),
			"precondition" => trim($testDocument[3][1]),
			"issueLinks" => $IssueLinks,
			"testScript" => array(
			        "type" => "STEP_BY_STEP",
					"steps" => array(),
			    ),
		);
		
		foreach(range(0, sizeof($testcase['test_steps']) - 1) as $index){
			$step = array();
			$step['description'] = $testcase['test_steps'][$index]['description'];
			$step['testData'] = "";
			$step['expectedResult'] = $testcase['test_steps'][$index]['expected'];
			
			array_push($JSONBody['testScript']['steps'], $step);
		}
				
		//If debug is on, we want to pretty print an array of test cases.
		if(isset($_GET['debug'])){
			echo '<pre>' . print_r($JSONBody, 1) . '</pre>';
		}
		
		//Send the test into the system
		if(!isset($_GET['test'])){
			insert_JIRA($JSONBody);
		} else {
			$Tests_Sent_Output['key'] = "null";
		}
			
		//If there are errors, display them.
		if(array_key_exists('errorMessages', $Tests_Sent_Output)){
			echo 'WARNING:<br />Upload failed due to 1 or more errors.<br /><br />';
			
			//The individual errors:
			foreach($Tests_Sent_Output['errorMessages'] as $errorMessage){
				echo $errorMessage . '<br />';				
			}
			
			//A friendly message.
			echo '<br />The test case has <b><u>NOT</u></b> been uploaded to JIRA.<br />Please check your test document and try again.';
		} else {
			echo 'SUCCESS!<br />The test has been uploaded to JIRA<br /><br />The test case key is:<br />    ' . $Tests_Sent_Output['key'] . '<br /><br />';
		}
	}
	
	function insert_JIRA($cURLdata){
		$curl = curl_init();

		curl_setopt_array($curl, array(
		  CURLOPT_PORT => "8080",
		  CURLOPT_URL => "http://servicedesk.re-assured.net:8080/rest/atm/1.0/testcase/",
		  CURLOPT_RETURNTRANSFER => true,
		  CURLOPT_ENCODING => "",
		  CURLOPT_MAXREDIRS => 10,
		  CURLOPT_TIMEOUT => 30,
		  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		  CURLOPT_CUSTOMREQUEST => "POST",
		  CURLOPT_POSTFIELDS => json_encode($cURLdata),
		  CURLOPT_HTTPHEADER => array(
			"Authorization: Basic aGFydmV5LmZsZXRjaGVyQHJlYXNzdXJlZC5jby51azoqSEFyR0JAQDE5Nzk=",
			"Cache-Control: no-cache",
			"Content-Type: application/json",
			"Postman-Token: 09ee4077-a3e2-405a-92b6-f58a5c4a68ea"
		  ),
		));

		$response = curl_exec($curl);
		$err = curl_error($curl);

		curl_close($curl);

		if ($err) {
			$GLOBALS['Tests_Sent_Output'] = json_decode($err, true);
		} else {
			$GLOBALS['Tests_Sent_Output'] = json_decode($response, true);
		}
	}