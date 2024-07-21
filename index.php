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
$courseName = '';

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

function getCompletedLearners($conn, $courseId)
{
    $completedLearners = [];
    $stmt = $conn->prepare("SELECT l.id FROM learners l JOIN completion cp ON l.id = cp.learner_id WHERE cp.course_id = ?");
    $stmt->bind_param("i", $courseId);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $completedLearners[] = $row['id']; // Only fetch and store the learner IDs
    }
    $stmt->close();
    return $completedLearners;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    if (isset($_POST["add_learner"])) {
        $name = $_POST['name'];
        $email = $_POST['email'];
        addLearner($name, $email);
    } else if (isset($_POST["delete_learner"])) {
        $id = $_POST['learner_id'];
        deleteLearner($id);
    } else if (isset($_POST["add_course"])) {
        $name = $_POST['name'];
        addCourse($name);
    } else if (isset($_POST["delete_course"])) {
        $id = $_POST['course_id'];
        deleteCourse($id);
    } else if (isset($_POST["set_learner"])) {
        $name = $_POST["name"];
        $courseName = "";
    } else if (isset($_POST["set_course"])) {
        $courseName = $_POST["name"];
        $name = "";
    }
}

$allLearners = getAllLearners($conn);
$allCourses = getAllCourses($conn);

// If no name provided, render form to enter name
if (empty($name) && empty($courseName)) {
?>
    <!DOCTYPE html>
    <html lang="en">

    <head>
        <meta charset="UTF-8">
        <title>Learners and Courses</title>
        <script src="https://cdn.jsdelivr.net/npm/vue@2.6.14/dist/vue.js"></script>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    </head>

    <body>
        <div class="container" id="app">
            <div class="row">
                <div class="col-md-6 p-4">
                    <div class="d-flex justify-content-between align-items-center">
                        <h2>List of Learners</h2>
                        <button :class="['btn', 'btn-sm', editBtnLearnerClass]" @click="toggleEditLearner">
                            <span v-if="!editLearner">Edit</span>
                            <span v-else>Save</span>
                        </button>
                    </div>
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Learner Name</th>
                                <th>Email</th>
                                <!-- <th></th> -->
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($allLearners as $learner) : ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($learner['name']); ?></td>
                                    <td><?php echo htmlspecialchars($learner['email']); ?></td>
                                    <td>
                                        <form class="form-inline" method="post" v-if="!editLearner">
                                            <input type="hidden" name="name" value="<?php echo htmlspecialchars($learner['name']); ?>">
                                            <button type="submit" class="btn btn-dark btn-sm" name="set_learner"><i class="bi bi-arrow-up-right"></i></button>
                                        </form>
                                        <form class="form-inline" method="post" v-else>
                                            <input type="hidden" name="learner_id" value="<?php echo htmlspecialchars($learner['id']); ?>">
                                            <button type="submit" class="btn btn-danger btn-sm" name="delete_learner"><i class="bi bi-trash-fill"></i></button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <tr class="table-secondary" v-if="editLearner">
                                <td><input v-model="newLearnerName" placeholder="New Learner Name"></td>
                                <td><input v-model="newLearnerEmail" placeholder="New Learner Email"></td>
                                <td>
                                </td>
                            </tr>
                            <!-- <form class="form-inline" method="post">
                    <div class="form-group">
                        <label for="name" class="sr-only">Name</label>
                        <input type="text" class="form-control" id="name" name="name" placeholder="Name" required>
                    </div>
                    <button type="submit" name="add_learner" class="btn btn-dark">Add</button>
                    </form> -->
                        </tbody>
                    </table>
                </div>
                <div class="col-md-6 p-4">
                    <div class="d-flex justify-content-between align-items-center">
                        <h2>List of Courses</h2>
                        <button :class="['btn', 'btn-sm', editBtnCourseClass]" @click="toggleEditCourse">
                            <span v-if="!editCourse">Edit</span>
                            <span v-else>Save</span>
                        </button>
                    </div>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Course Name</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($allCourses as $course) : ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($course['name']); ?></td>
                                    <td>
                                        <form class="form-inline" method="post" v-if="!editCourse">
                                            <input type="hidden" name="name" value="<?php echo htmlspecialchars($course['name']); ?>">
                                            <button type="submit" class="btn btn-dark btn-sm" name="set_course"><i class="bi bi-arrow-up-right"></i></button>
                                        </form>
                                        <form class="form-inline" method="post" v-else>
                                            <input type="hidden" name="course_id" value="<?php echo htmlspecialchars($course['id']); ?>">
                                            <button type="submit" class="btn btn-danger btn-sm" name="delete_course"><i class="bi bi-trash-fill"></i></button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <tr class="table-secondary" v-if="editCourse">
                                <td><input v-model="newCourseName" placeholder="New Course Name"></td>
                                <td>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </body>
    <script>
        new Vue({
            el: '#app',
            data: {
                editLearner: false,
                newLearnerName: "",
                newLearnerEmail: "",
                editCourse: false,
                newCourseName: "",
            },
            methods: {
                toggleEditLearner() {
                    if (this.editLearner) {
                        if (this.newLearnerName.trim() !== '' && this.newLearnerEmail.trim() !== '') {
                            const formData = new FormData();
                            formData.append('name', this.newLearnerName);
                            formData.append('email', this.newLearnerEmail);
                            formData.append('add_learner', true); // This should match the check in PHP

                            // Make the POST request
                            fetch('index.php', {
                                    method: 'POST',
                                    body: formData
                                })
                                .then(response => response.text())
                                .then(data => {
                                    window.location.reload();
                                })
                                .catch(error => console.error('Error:', error));
                        }
                    }
                    this.editLearner = !this.editLearner;
                },
                deleteLearner(learnerId) {
                    const formData = new FormData();
                    formData.append('learner_id', learnerId);
                    formData.append('delete_learner', true); // This should match the check in PHP

                    // Make the POST request
                    fetch('index.php', {
                            method: 'POST',
                            body: formData
                        })
                        .then(response => response.text())
                        .then(data => {
                            window.location.reload();
                        })
                        .catch(error => console.error('Error:', error));
                },
                toggleEditCourse() {
                    if (this.editCourse) {
                        if (this.newCourseName.trim() !== '') {
                            const formData = new FormData();
                            formData.append('name', this.newCourseName);
                            formData.append('add_course', true); // This should match the check in PHP

                            // Make the POST request
                            fetch('index.php', {
                                    method: 'POST',
                                    body: formData
                                })
                                .then(response => response.text())
                                .then(data => {
                                    window.location.reload();
                                })
                                .catch(error => console.error('Error:', error));
                        }
                    }
                    this.editCourse = !this.editCourse;
                },
                deleteCourse(courseId) {
                    const formData = new FormData();
                    formData.append('course_id', courseId);
                    formData.append('delete_course', true); // This should match the check in PHP

                    // Make the POST request
                    fetch('index.php', {
                            method: 'POST',
                            body: formData
                        })
                        .then(response => response.text())
                        .then(data => {
                            window.location.reload();
                        })
                        .catch(error => console.error('Error:', error));
                },
            },
            computed: {
                editBtnLearnerClass() {
                    return this.editLearner ? 'btn-success' : 'btn-dark';
                },
                editBtnCourseClass() {
                    return this.editCourse ? 'btn-success' : 'btn-dark';
                }
            }
        });
    </script>

    </html>
    <?php
} else if (!empty($name)) {
    // Prepare and execute the query
    $stmt = $conn->prepare("SELECT * FROM learners WHERE name = ?");
    $stmt->bind_param("s", $name);
    $stmt->execute();
    $result = $stmt->get_result();

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
                <title><?php echo htmlspecialchars($name); ?>'s Course Completion Status</title>
                <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
                <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">

            </head>

            <body>
                <div class="container" style="padding-top:50px">
                    <h2><?php echo htmlspecialchars($name); ?>'s Course Completion Status</h2>
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
                                                <button type="submit" id="pdf" name="generate_pdf" class="btn btn-dark btn">
                                                    <i class="bi bi-file-earmark-pdf-fill"></i>
                                                </button>
                                                <a href="https://www.linkedin.com/profile/add?startTask=CERTIFICATION_NAME&name=<?php echo urlencode($course['name']); ?>&organizationId=10000&issueYear=2023&issueMonth=11&expirationYear=2026&expirationMonth=11&certUrl=<?php echo urlencode('https://www.yourdomain.com/certificate.php?course_id=' . $course['id'] . '&learner_id=' . $learnerRow['id']); ?>&certId=<?php echo $course['id']; ?>" target="_blank" class="btn btn-dark btn">
                                                    <i class="bi bi-linkedin"></i>
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
                    <button onclick="history.back()" class="btn btn-dark">Return</button>
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
} else {
    // echo $result;
    // Prepare and execute the query
    $stmt = $conn->prepare("SELECT * FROM courses WHERE name = ?");
    $stmt->bind_param("s", $courseName);
    $stmt->execute();
    $result = $stmt->get_result();
    if (empty($allLearners)) {
        echo "No Learners in records.";
    } else {
        if ($result->num_rows > 0) {
            // If the name exists in the database, display the page
            $courseRow = $result->fetch_assoc();
            $completedLearners = getCompletedLearners($conn, $courseRow['id']);
        ?>
            <!DOCTYPE html>
            <html lang="en">

            <head>
                <meta charset="UTF-8">
                <title><?php echo htmlspecialchars($name); ?> Completion Status</title>
                <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
                <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/css/bootstrap.min.css">
            </head>

            <body>
                <div class="container" style="padding-top:50px">
                    <h2><?php echo htmlspecialchars($name); ?> Course Completion Status</h2>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Learner Name</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($allLearners as $learner) : ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($learner['name']); ?></td>
                                    <td>
                                        <?php if (in_array($learner['id'], $completedLearners)) : ?>
                                            <form class="form-inline" method="post" action="certificate.php">
                                                <input type="hidden" name="course_id" value="<?php echo $courseRow['id']; ?>">
                                                <input type="hidden" name="learner_id" value="<?php echo $learner['id']; ?>">
                                                <button type="submit" id="pdf" name="generate_pdf" class="btn btn-dark">
                                                    <i class="bi bi-file-earmark-pdf-fill"></i>
                                                </button>
                                                <a href="https://www.linkedin.com/profile/add?startTask=CERTIFICATION_NAME&name=<?php echo urlencode($course['name']); ?>&organizationId=10000&issueYear=2023&issueMonth=11&expirationYear=2026&expirationMonth=11&certUrl=<?php echo urlencode('https://www.yourdomain.com/certificate.php?course_id=' . $course['id'] . '&learner_id=' . $learnerRow['id']); ?>&certId=<?php echo $course['id']; ?>" target="_blank" class="btn btn-dark">
                                                    <i class="bi bi-linkedin"></i>
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
                    <button onclick="history.back()" class="btn btn-dark">Return</button>
                </div>
            </body>

            </html>
<?php
        } else {
            echo "<p>Test. Please check the name and try again.</p>";
        }
    }

    $stmt->close();
    $conn->close();
}
?>