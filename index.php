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

// Get name from URL or form submission
$name = isset($_GET['name']) ? $_GET['name'] : (isset($_POST['name']) ? $_POST['name'] : '');

function addLearner($name)
{
    global $conn;
    // Prepare the SQL statement
    $stmt = $conn->prepare("INSERT INTO learners (name) VALUES (?)");
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

// Function to fetch all courses
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
    $stmt = $conn->prepare("SELECT c.id FROM courses c JOIN completion cp ON c.id = cp.course_id WHERE cp.learner_id = ?");
    $stmt->bind_param("i", $learnerId);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $completedCourses[] = $row['id']; // Only fetch and store the course IDs
    }
    $stmt->close();
    return $completedCourses;
}

// Check if the form has been submitted
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["add_learner"])) {
    $name = $_POST["name"];
    addLearner($name);
    echo "<p>Learner added successfully!</p>";
}

// If no name provided, render form to enter name
if (empty($name)) {
?>
    <meta charset="UTF-8">
    <title>Enter Name</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/css/bootstrap-theme.min.css">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
    <div class="container" style="padding-top:50px">
        <h2>Name of Student</h2>
        <form class="form-inline" method="post">
            <div class="form-group">
                <label for="name" class="sr-only">Name</label>
                <input type="text" class="form-control" id="name" name="name" placeholder="Name" required>
            </div>
            <button type="submit" class="btn btn-primary">Check</button>
            <button type="submit" name="add_learner" class="btn btn-primary">Add</button>
        </form>
    </div>
    <?php
} else {
    // Prepare and execute the query
    $stmt = $conn->prepare("SELECT * FROM learners WHERE name = ?");
    $stmt->bind_param("s", $name);
    $stmt->execute();
    $result = $stmt->get_result();

    $allCourses = getAllCourses($conn);
    if (empty($allCourses)) {
        echo "No courses available.";
    } else {
        if ($result->num_rows > 0) {
            // If the name exists in the database, display the page
            $learnerRow = $result->fetch_assoc();
            $completedCourses = getCompletedCourses($conn, $learnerRow['id']);
    ?>
            <!DOCTYPE html>
            <html lang="en">

            <head>
                <meta charset="UTF-8">
                <title>All Courses</title>
                <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
                <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/css/bootstrap.min.css">
            </head>

            <body>
                <div class="container" style="padding-top:50px">
                    <h2>All Courses</h2>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Course Name</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($allCourses as $course) : ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($course['name']); ?></td>
                                    <td>
                                        <?php if (in_array($course['id'], $completedCourses)) : ?>
                                            <form class="form-inline" method="post" action="certificate.php">
                                                <input type="hidden" name="course_id" value="<?php echo $course['id']; ?>">
                                                <input type="hidden" name="learner_id" value="<?php echo $learnerRow['id']; ?>">
                                                <button type="submit" id="pdf" name="generate_pdf" class="btn btn-primary">
                                                    <i class="fa fa-file-pdf-o" aria-hidden="true"></i>
                                                </button>
                                                <a href="https://www.linkedin.com/profile/add?startTask=CERTIFICATION_NAME&name=<?php echo urlencode($course['name']); ?>&organizationId=10000&issueYear=2023&issueMonth=11&expirationYear=2026&expirationMonth=11&certUrl=<?php echo urlencode('https://www.yourdomain.com/certificate.php?course_id=' . $course['id'] . '&learner_id=' . $learnerRow['id']); ?>&certId=<?php echo $course['id']; ?>" target="_blank" class="btn btn-primary">
                                                    <i class="fa fa-linkedin" aria-hidden="true"></i>
                                                </a>
                                            </form>
                                        <?php else : ?>
                                            <span class="text-muted">Not completed</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <button onclick="history.back()" class="btn btn-secondary">Return</button>
                </div>
            </body>

            </html>
<?php
        } else {
            echo "<p>No records found for the specified name. Please check the name and try again.</p>";
        }
    }

    $stmt->close();
    $conn->close();
}
?>