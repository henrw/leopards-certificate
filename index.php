<?php
// Database configuration
$host = "localhost";
$username = "root";
$password = "";
$database = "learners_db";
$conn = new mysqli($host, $username, $password, $database);

// Check database connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get name from URL
$name = isset($_GET['name']) ? $_GET['name'] : 'default';

// Prepare and execute the query
$stmt = $conn->prepare("SELECT * FROM learners WHERE name = ?");
$stmt->bind_param("s", $name);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    // If the name exists in the database, display the page
    $row = $result->fetch_assoc();
    // $courses = file("/var/external_includes/course-names.txt", FILE_IGNORE_NEW_LINES);
?>
    <meta charset="UTF-8">
    <title>Fake Certificate</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/css/bootstrap-theme.min.css">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
    <div class="container" style="padding-top:50px">
        <h2>Congratulations, <?php echo htmlspecialchars($name); ?>! You just completed the course!</h2>
        <form class="form-inline" method="post" action="certificate.php">
            <input type="hidden" name="name" value="<?php echo htmlspecialchars($name); ?>">
            <button type="submit" id="pdf" name="generate_pdf" class="btn btn-primary">
                <i class="fa fa-file-pdf-o" aria-hidden="true"></i> Get Certificate
            </button>
        </form>
        <a href="https://www.linkedin.com/profile/add?startTask=CERTIFICATION_NAME&name=CMU+Africa+Leopards+Certificate&organizationId=10000&issueYear=2023&issueMonth=11&expirationYear=2026&expirationMonth=11&certUrl=https%3A%2F%2Fwww.fakeurl.com&certId=123456" target="_blank" class="btn btn-primary">
            Add to LinkedIn Profile
        </a>
        <!-- <a href="https://www.linkedin.com/sharing/share-offsite/?url=https%3A%2F%2Fwww.example.com%2Fcourse-detail" target="_blank">Share this course on LinkedIn!</a> -->
    </div>
<?php
} else {
    // If the name does not exist, show a 404 error
    header("HTTP/1.0 404 Not Found");
    echo "<h1>404 Not Found</h1>";
    echo "The page that you have requested could not be found.";
}

$stmt->close();
$conn->close();
?>