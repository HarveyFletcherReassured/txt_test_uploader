<html>
    <head>
        <title>
            Test Document Uploader
        </title>
        <style type="text/css">
            <!--
                body, html{
                    margin: 0;
                }
                div.formborder{
                    border-radius: 45px;
                    border-width: 5px;
                    border-color: grey;
                    border-style: dashed;

                    width: 500px;
                    min-height: 300px;

                    margin-left: auto;
                    margin-right: auto;
                    margin-top: 40px;

                    text-align: center;
                }
                input.submitButton{
                    width: 100%;
                    height: 40px;
                    font-size: 30px;
                }
            -->
        </style>
    </head>
<!--    <body onload="alert('* * * * *\nS T O P\n* * * * *\n\nThe test uploader is broken and does not work if you only have one test case in your textfile.\n\nPlease do not use the test uploader.'); window.location.replace('https://www.reassured.co.uk/')">  -->
    <body>
        <div class="formborder">
            <h2>Test Document Uploader</h2>
            <br />
            <div style="width: 70%; margin-right: auto; margin-left: auto;">
                <form action="checkTests.php" method="post" enctype="multipart/form-data">
                    <input type="file" name="testDocument" id="testDocument" style="width: 100%;" required onchange="isTextFile(this);">
                    <br />
                    <br />
                    <br />
                    <input type="submit" name="submit" id="submit" class="submitButton" disabled>
                    <br />
                    <br />
                    <input type="checkbox" name="verbose">Verbose Output
                </form>
            </div>
        </div>
        <script type="text/javascript">
            function isTextFile(inputObject){
                var fileExt = inputObject.value.slice(-4);

                if(fileExt != ".txt"){
                    document.getElementById('submit').setAttribute('disabled',true);
                    alert("That doesn't seem to match the criteria.\nPlease only upload files in \'.txt\' file formats.");
                } else {
                    document.getElementById('submit').removeAttribute('disabled');
                }
            }
        </script>
    </body>
</head>
