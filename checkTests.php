<?php
    //Import the errorhandler
    include_once('errorHandler.php');

    //Split the array up into the different headers
    $testDocument = explode("###", file_get_contents($_FILES['testDocument']['tmp_name']));

    //Remove the blank element from the beginning of the array
    array_shift($testDocument);

    //Do we have the right number of headers?
    if((sizeof($testDocument) / 2) != 5){
        error(0);        
    }

    //Split the testDocument array into sections
    $temp = $testDocument;

    $testDocument = array(
            "Sprint-Name" => trim($temp[1]),
            "Dev-Number" => trim($temp[3]),
            "Functionality" => str_replace("\\r\\n", "<br />", trim($temp[5])),
            "Precondition" => str_replace("\\r\\n", "<br />", trim($temp[7])),
            "Cases" => array_map('trim', explode("\n", trim($temp[9]))),
        );

    //We need to chunk up that array based on all the items which are blank.
    $BlankLines = array_keys($testDocument['Cases'], '');
    $Previous = 0;
    $temp = $testDocument["Cases"];

    //This marker is here to seperate the tests at the end of the test document so they are terminated correctly. Without it, the last test case will not terminate and all the steps will go into the
    //last expected result of the previous test.
    array_push($BlankLines, sizeof($temp));

    //The array of test cases starts off as a blank array
    $testDocument["Cases"] = array();

    //Split up the test document based off the number of blank separator lines.
    foreach($BlankLines as $lineNumber){
        array_push($testDocument["Cases"], array_filter(array_slice($temp, $Previous, $BlankLines[0] - $Previous)));
        $Previous = $BlankLines[0];
        array_shift($BlankLines);
    }

    //Split test case into name and steps.
    $testCases = $testDocument["Cases"];
    $testDocument["Cases"] = array();
    foreach($testCases as $Case){
        //We need to re number the arrays so they start at 0
        $Case = array_values($Case);
     
        $name = explode(" -> ", $Case[0]);
        $steps = array();

        //Split up all the steps
        foreach(array_slice($Case, 1, sizeof($Case)) as $step){
            $step = explode(" -> ", substr($step, strpos($step, '. ') + 1));

            $step = array(
                    "description" => $step[0],
                    "testData" => "",
                    "expectedResult" => $step[1],
                );
            array_push($steps, $step);
        };

        $Case = array(
                "Name" => $name,
                "Steps" => $steps,
            );

        array_push($testDocument["Cases"], $Case);
    }

    //The upload JSON starts as a blank array
    $uploadJSON = array();

    //Build up the array for each test case and push it onto the uploadJSON array
    foreach($testDocument["Cases"] as $TC){
        $JSONBody = array(
    	    "projectKey" => "DEV",
                "folder" => '/' . strtoupper($testDocument["Sprint-Name"]) . '/' . strtoupper($testDocument["Dev-Number"]),
                "name" => strtoupper($testDocument["Dev-Number"]) . ' - ' . $TC['Name'][1],
                "objective" => $testDocument["Functionality"],
                "precondition" => $testDocument["Precondition"],
                "issueLinks" => array(strtoupper($testDocument["Dev-Number"])),
                "testScript" => array(
                        "type" => "STEP_BY_STEP",
                        "steps" => $TC["Steps"]
             	),
    	);

        //Push the data onto the uploadJSON array
        array_push($uploadJSON, $JSONBody);
    }

    //Sanitise the step replacing newlines with the BR tag
    $uploadJSON = str_replace('\r\n', '<br />', json_encode($uploadJSON));
?>
<html>
    <head>
    </head>
    <body>
        <h1>PLEASE READ</h1>
        <h5>You are attempting to upload <?php echo sizeof($testDocument["Cases"]) ?> tests into the folder /<?php echo strtoupper($testDocument["Sprint-Name"]) . '/' . strtoupper($testDocument["Dev-Number"]) ?> on JIRA.</h5>
        <br />
        <table border>
            <tr>
                <td style="min-width: 100px;">
                    <h4 style="margin: 0;">Test Case</h4>
                </td>
                <td style="width:25px;"></td>
                <td style="min-width: 100px;">
                    <h4 style="margin: 0;">Steps</h4>
                </td>
            </tr>
                <?php
                    foreach($testDocument["Cases"] as $case){ 
                ?>
                    <tr>
                        <td>
                            <?php echo $case["Name"][1] ?>
                        </td>
                        <td></td>
                        <td>
                            <?php echo sizeof($case["Steps"]) ?>
                        </td>
                    </tr>
                <?php 
                    }
                ?>
        </table>
        <br />
        <br />
        <button style="color: green; height: 50; width: 300; font-size: 20px;" onclick="confirmedUpload();" id="confirmUpload">These are correct.</button>
        <button style="color: red; height: 50; width: 300; font-size: 20px;" onclick="alert('Please check the formatting of your text file and try again.'); window.location.replace('index.php')">These are not correct.</button>
        <?php
            if($_POST['verbose'] == "on"){
                echo '<pre>';
                    print_r($_POST);
                    print_r($_FILES);
                    print_r(json_decode($uploadJSON));
                echo '</pre>';
            }
        ?>
        <script type="text/javascript">
            function confirmedUpload(){
                //Make the button so it can't be clicked again
                document.getElementById('confirmUpload').setAttribute("disabled", true);

                //This is the data we are posting
                var postData = JSON.stringify(<?php echo $uploadJSON; ?>);

                //Create an XMLHttpRequestObject
                var xhr = new XMLHttpRequest();
                xhr.open("POST", "/uploadTests.php", true);

                xhr.setRequestHeader( "Content-Type", "application/json" );
                xhr.send(postData);

                //Wait for the page to respond.
                xhr.onreadystatechange = function()
                {
                    if(xhr.readyState == 4 && xhr.status == 200)
                    {
                        //Decode the response from the uploader script
                        var Response = JSON.parse(this.responseText);
			var errors = "";
                        var keys = "";

                        console.log(this.responseText);

                        //Build a friendly list of keys
                        for(var i = 0; i<Response.length; i++){
                            if(Response[i]["key"]){
                                keys = keys + Response[i]["key"] + "\n";
                            } else {
                                for(var e = 0; e<Response[i]["errorMessages"].length; e++){
	                                errors = errors + Response[i]["errorMessages"][e] + "\n\n";
				}
                            }
                        }

                        //If we have no errors, make sure there's a message to say that
                        if(errors.length == 0){
                            errors = "There are no errors."
                        }

                        //Alert the user of the keys
                        alert("Done!\n\nHere are your test case IDs:\n\n" + keys + "\n\n\nErrors (if any):\n\n" + errors);

                        //Reload the index.
                        window.location.replace('index.php');
                    }
                }
            }
        </script>
    </body>
</html>
