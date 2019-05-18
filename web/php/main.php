<?php
/**
// This script recieves a $POST from the main page when a user enters a valid QRL address. This is th emeat and potatos of the program.
// The users IP address is grabbed, hashed and checked against the database alongside the QRL address submitted.
//
**/

// Logging to the default webserver log file as ERROR for all functions. FIXME to normal logging locations
error_log("*FAUCET* post recieved", 0); //Declare a $POST is recieved
// require IP validation and identify code
require 'getInfo.php'; //this is to validate the IP address is not spoofed.
// set the address variable, strip and sanitize
$address = strip_tags(trim($_POST['address']));
$address = preg_replace("/[^A-Za-z0-9 ]/", '', $address);
//set the date/time
date_default_timezone_set("UTC"); 
$date = date('m/d/Y', time());
$time = date('h:i:s a', time());
$now = date('m/d/Y h:i:s a', time());
//faucet settings for the database
$faucetAmmount = 0.000001; // How much are we expecting to pay, this is not used later. Set the payout amount in payout.py
$payoutInterval = 24; //payout interval in hours, how long before user is valid again if seen prior
//query variables from database
$lastPaidTime = "";
$timesPaid = "";
// get info from user Post Data
$ip = get_ip_address(); //from getInfo.php
$ipHash = hash("sha256", $ip);
    error_log("ipHash is: ". $ipHash, 0); //logging the user IP hash to logfile
$errors = array();      // array to hold validation errors
$data = array();      // array to pass back data
//mysql variables setup at install
$SQLservername = "localhost";
$SQLuser = "qrl";
$SQLpassword = "DATABASE_PASSWORD";
$database = "faucet";
//coinhive info
$coinhiveSecret = "SECERET_KEY_FROM_COINHIVE"; // This is found in the coinhive setup. https://coinhive.com/settings/sites
$hashes = 256; //this must match the web page or coinhive complains.

// mysql connector constructor
$SQLconn = new mysqli($SQLservername, $SQLuser, $SQLpassword, $database);

/*/ query the database for the address or ip address
// if seen in less than the $payoutInterval returns empty

/*/
$querySQL = "select TX_ID, QRL_ADDR, IP_ADDR, PAYOUT, DATETIME from PAYOUT where (QRL_ADDR = '". $address ."' and DATETIME > DATE_SUB(NOW(), INTERVAL " . $payoutInterval . " HOUR)) OR (IP_ADDR = '" . $ipHash . "' and DATETIME > DATE_SUB(NOW(), INTERVAL " . $payoutInterval . " HOUR))";
$PayoutSqlQuery = "select TX_ID, QRL_ADDR, IP_ADDR, PAYOUT, DATETIME from PAYOUT where (QRL_ADDR = '". $address ."' ) OR (IP_ADDR = '" .$ipHash ."')";
$PayOutSQL = "INSERT INTO PAYOUT VALUES (NULL, '".$address."', '".$ipHash."', ".$faucetAmmount.", now() )";

//$CheckPayoutQuery = "SELECT (select count(*) from PAYOUT where QRL_ADDR = '".$address."' OR IP_ADDR = '".$ipHash."')";


        //check the post data for empty
        if (empty($_POST['address'])) {
            error_log("EMPTY address submitted" , 0);
            // the POST is empty, send errors
            $errors['message'] = "Address is required.";         
            $data['success'] = false;
            $data['errors']  = $errors;
            //send the data back to the users
            header('Content-Type: application/json');
            echo json_encode($data);
            exit(1);   
        }
        // Checck if the address does NOT match the regex
        if(!preg_match("/^(Q|q)[0-9a-zA-Z]{78}$/",$address)) {
            error_log("Thats not a valid address, REGEX Does Not Match" . $address, 0);
            // Thats not a valid address
            $data['message'] = "Valid Address is required.";
            $data['success'] = false;
            $data['errors']  = $errors;
            header('Content-Type: application/json');
            echo json_encode($data);
            exit(1);
        }
        // check for mysql connection error
        if ($SQLconn->connect_error) {
            error_log("MYSql Connection issue: " . $SQLconn->connect_error, 0);
            $errors['message'] = "SQL SERVER Connection failed. Error: ".$SQLconn->connect_error." ";
            $data['success'] = false;
            $data['errors']  = $errors;
            header('Content-Type: application/json');
            echo json_encode($data);        
            exit(1);
        }
        //get the results of database query for address exsiting in the last $payoutInterval
        $result = mysqli_query ($SQLconn,$querySQL);
        if ($result->num_rows > 0) {
        //seen before, kick out and throw error
            while ($row = mysqli_fetch_array ($result)) {
                error_log("Address Seen in the last " . $payoutInterval , 0);
                $data['DATETIME'] = "We saw you last: " . $row["DATETIME"];
                $errors['message'] = "You have been seen reciently";
                $errors['data'] = "QRL Address: " . $row['QRL_ADDR'] ." Date and Time: ". $row['DATETIME'];
                $data['success'] = false;
                $data['errors']  = $errors;
                header('Content-Type: application/json');
                echo json_encode($data);     
                $SQLconn->close();
                exit(1);
            }
        }
        else{
            //Address not seen in last $pyoutInterval, add to database and pay
            $query = mysqli_query ($SQLconn,$PayoutSqlQuery);
            error_log("Adding payment Details to Database", 0);
            if ($query->num_rows > 0) {
                error_log("Address seen, but not in the last 24 hours", 0);
                $data['firstSeen'] = false;
                //Enter the address and IP into the Database now
                if ($SQLconn->query($PayOutSQL) === TRUE) {
                    $data['PaymentSuccess'] = true;       
                    $data['success'] = true;
                } else {
                    //if failure report error
                    error_log("Error: " . $PayOutSQL . $SQLconn->error, 0);   
                    $data['PaymentSuccess'] = false;  
                    $data['success'] = false;
                }
            }
            else {
                // We have not seen this address before.
                error_log("Address Not Seen Before: " . $address , 0);
                $data['firstSeen'] = true;         
                //Add to database and payout
                if ($SQLconn->query($PayOutSQL) === TRUE) {
                    $data['PaymentSuccess'] = true;  
                    $data['success'] = true;
                } 
                else {
                    //if failure report error
                    error_log("Error: " . $PayOutSQL . $SQLconn->error, 0);   
                    $data['PaymentSuccess'] = false;  
                    $data['success'] = false;                   
                }
            }
        }   
        $SQLconn->close();
        //Check the error array for data, if found, print error and end script, returning values to user
        if ( ! empty($errors)) {
            //loging stuff to console
            error_log("ERROR ARRAY IS NOT EMPTY " . $errors['data'] , 0);
            error_log("ERROR ARRAY IS NOT EMPTY " . $errors, 0);
            // if there are items in our errors array, return those errors
            $data['success'] = false;
            $data['errors']  = $errors;
        } 
    
    //send the data back to the users
    header('Content-Type: application/json');
    // return all our data to an AJAX call
    echo json_encode($data);
    exit(0)
?>