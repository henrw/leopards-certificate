<?php

include_once("connection.php");
$conn = (new dbObj())->getConnstring();

function generateRandomKey($length = 20)
{
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';

    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[random_int(0, $charactersLength - 1)];
    }

    return $randomString;
}

function addCompletionRecord($learner_id, $course_id)
{
    global $conn;

    // Generate a random key for the certificate_id
    $randomString = generateRandomKey(); // Assuming generateRandomKey() is defined elsewhere

    // Prepare the SQL statement
    $stmt = $conn->prepare("INSERT INTO completion (learner_id, course_id, certificate_id) VALUES (?, ?, ?)");
    if ($stmt === false) {
        die('MySQL prepare error: ' . $conn->error);  // Consider more detailed error handling
    }

    // Bind the parameters to the SQL query
    $stmt->bind_param("iis", $learner_id, $course_id, $randomString);  // 'iis' for two integers and one string
    if ($stmt->execute() === false) {
        die('Execute error: ' . $stmt->error);  // Consider more detailed error handling
    }

    // Close the statement
    $stmt->close();
}

function removeCompletionRecord($learner_id, $course_id) {
    global $conn;

    // Prepare the SQL statement to delete a completion record
    $stmt = $conn->prepare("DELETE FROM completion WHERE learner_id = ? AND course_id = ?");
    if ($stmt === false) {
        // Consider logging this error or handling it more gracefully
        die('MySQL prepare error: ' . $conn->error);
    }

    // Bind the parameters to the SQL query
    $stmt->bind_param("ii", $learner_id, $course_id);  // 'ii' indicates that both parameters are integers

    // Execute the query
    if ($stmt->execute() === false) {
        // Consider logging this error or handling it more gracefully
        die('Execute error: ' . $stmt->error);
    }

    // // Check if any rows were affected
    // if ($stmt->affected_rows === 0) {
    //     echo "No records found to delete.";
    // }

    // Close the statement
    $stmt->close();
}

function addLearner($name, $email)
{
    global $conn;
    // Prepare the SQL statement
    $stmt = $conn->prepare("INSERT INTO learners (name, email) VALUES (?, ?)");
    if ($stmt === false) {
        // Handle error (e.g., log, throw an exception)
        die('MySQL prepare error: ' . $conn->error);
    }

    // Bind the parameters to the SQL query
    $stmt->bind_param("ss", $name, $email);
    if ($stmt->execute() === false) {
        // Handle error in execution
        die('Execute error: ' . $stmt->error);
    }

    // Close the statement
    $stmt->close();
}

function addCourse($name)
{
    global $conn;
    // Prepare the SQL statement
    $stmt = $conn->prepare("INSERT INTO courses (name) VALUES (?)");
    if ($stmt === false) {
        // Handle error (e.g., log, throw an exception)
        die('MySQL prepare error: ' . $conn->error);
    }

    // Bind the parameters to the SQL query
    $stmt->bind_param("s", $name);
    if ($stmt->execute() === false) {
        // Handle error in execution
        die('Execute error: ' . $stmt->error);
    }

    // Close the statement
    $stmt->close();
}

function deleteLearner($id)
{
    global $conn;

    // Start transaction
    $conn->begin_transaction();

    try {
        // Prepare the SQL statement to delete related completions
        $stmt = $conn->prepare("DELETE FROM completion WHERE learner_id = ?");
        if ($stmt === false) {
            throw new Exception('MySQL prepare error: ' . $conn->error);
        }

        // Bind the parameter and execute
        $stmt->bind_param("i", $id);
        if ($stmt->execute() === false) {
            throw new Exception('Execute error (completion deletion): ' . $stmt->error);
        }
        $stmt->close();

        // Prepare the SQL statement for deleting a learner based on id
        $stmt = $conn->prepare("DELETE FROM learners WHERE id = ?");
        if ($stmt === false) {
            throw new Exception('MySQL prepare error: ' . $conn->error);
        }

        // Bind the parameter and execute
        $stmt->bind_param("i", $id);
        if ($stmt->execute() === false) {
            throw new Exception('Execute error (learner deletion): ' . $stmt->error);
        }
        $stmt->close();

        // Commit transaction
        $conn->commit();
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        die($e->getMessage());
    }
}

function deleteCourse($id)
{
    global $conn;

    // Start transaction
    $conn->begin_transaction();

    try {
        // Prepare the SQL statement to delete related completions
        $stmt = $conn->prepare("DELETE FROM completion WHERE course_id = ?");
        if ($stmt === false) {
            throw new Exception('MySQL prepare error: ' . $conn->error);
        }

        // Bind the parameter and execute
        $stmt->bind_param("i", $id);
        if ($stmt->execute() === false) {
            throw new Exception('Execute error (completion deletion): ' . $stmt->error);
        }
        $stmt->close();

        // Prepare the SQL statement for deleting a learner based on id
        $stmt = $conn->prepare("DELETE FROM courses WHERE id = ?");
        if ($stmt === false) {
            throw new Exception('MySQL prepare error: ' . $conn->error);
        }

        // Bind the parameter and execute
        $stmt->bind_param("i", $id);
        if ($stmt->execute() === false) {
            throw new Exception('Execute error (course deletion): ' . $stmt->error);
        }
        $stmt->close();

        // Commit transaction
        $conn->commit();
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        die($e->getMessage());
    }
}

function getAllLearners($conn)
{
    $learners = [];
    $query = "SELECT id, name, email FROM learners";
    $result = $conn->query($query);
    while ($row = $result->fetch_assoc()) {
        $learners[] = $row;
    }
    return $learners;
}

function getAllCourses($conn)
{
    $courses = [];
    $query = "SELECT id, name FROM courses";
    $result = $conn->query($query);
    while ($row = $result->fetch_assoc()) {
        $courses[] = $row;
    }
    return $courses;
}

function getCompletedCourses($conn, $learnerId)
{
    $completedCourses = [];
    $stmt = $conn->prepare("SELECT c.id AS course_id, cp.certificate_id FROM courses c JOIN completion cp ON c.id = cp.course_id WHERE cp.learner_id = ?");
    $stmt->bind_param("i", $learnerId);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $completedCourses[] = $row; // Only fetch and store the course IDs
    }
    $stmt->close();
    return $completedCourses;
}

function getCompletedLearners($conn, $courseId)
{
    $completedLearners = [];
    $stmt = $conn->prepare("SELECT l.id AS learner_id, cp.certificate_id FROM learners l JOIN completion cp ON l.id = cp.learner_id WHERE cp.course_id = ?");
    $stmt->bind_param("i", $courseId);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $completedLearners[] = $row; // Only fetch and store the learner IDs
    }
    $stmt->close();
    return $completedLearners;
}

function getEmailHistory($mysqli, $learnerId = 0, $courseId = 0) {
    
    $query = "SELECT learner_id, course_id, email_text, email_date FROM email_history WHERE 1=1";
    $types = '';
    $params = [];

    if ($learnerId != 0) {
        $query .= " AND learner_id = ?";
        $types .= 'i';
        $params[] = $learnerId;
    }

    if ($courseId != 0) {
        $query .= " AND course_id = ?";
        $types .= 'i';
        $params[] = $courseId;
    }


    $stmt = $mysqli->prepare($query);
    if ($stmt === false) {
        // Error handling if the prepare fails
        echo "Failed to prepare statement. Error: " . $mysqli->error;
    }

    if (!empty($types) && !empty($params)) {
        $stmt->bind_param($types, ...$params);
    }

    if (!$stmt->execute()) {
        error_log('Execute error: ' . $stmt->error); // Log error to error log
        $stmt->close();
        return false; // Return or handle error gracefully
    }

    $result = $stmt->get_result();
    $emailHistory = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    return $emailHistory;
}