<?php
// Database configuration settings
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'learners_db');

// Function to fetch data from the API
function fetchApiData($url)
{
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_TIMEOUT, 30);
    $response = curl_exec($curl);
    $err = curl_error($curl);
    curl_close($curl);
    if ($err) {
        error_log("cURL Error #:" . $err);
        return false;
    }
    return json_decode($response, true);
}

// Function to update the completion status in the database
function updateCompletionStatus()
{
    $apiUrl = "https://example.com/api/data"; // TODO: put the actual API URL
    $data = fetchApiData($apiUrl);

    if ($data && isset($data['completion']) && isset($data['name'])) {
        $completion = $data['completion'] ? 1 : 0;
        $name = $data['name'];

        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        if ($conn->connect_error) {
            error_log("Database connection failed: " . $conn->connect_error);
            return;
        }

        $stmt = $conn->prepare("UPDATE learners SET completion = ? WHERE name = ?");
        if ($stmt) {
            $stmt->bind_param("is", $completion, $name);
            $stmt->execute();
            if ($stmt->affected_rows > 0) {
                echo "Record updated successfully.\n";
            } else {
                echo "No record was updated. Check if the condition matches or if 'Henry' exists.\n";
            }
            $stmt->close();
        } else {
            error_log("Error preparing SQL statement: " . $conn->error);
        }

        $conn->close();
    } else {
        error_log("No data received from API or 'completion' key not found.");
    }
}

// Run the update function
updateCompletionStatus();
