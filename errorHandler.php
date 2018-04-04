<?php

    function error($code){
        $errors = array(
                "You have not provided the correct number of headers.",
                "That is not the correct file type. Please upload .txt file",
            );

        echo $errors[$code];
        die();
    }
