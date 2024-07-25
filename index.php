<?php
// Database configuration

include_once("connection.php");
require("utils.php");
require("secrets.php");

ini_set('display_errors', 1);
error_reporting(E_ALL);
$conn = (new dbObj())->getConnstring();

// Check database connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$learnerId = 0;
$courseId = 0;

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
        $learnerId = intval($_POST["learner_id"]);
        $learnerName = $_POST["learner_name"];
        $courseId = 0;
    } else if (isset($_POST["set_course"])) {
        $courseId = intval($_POST["course_id"]);
        $courseName = $_POST["course_name"];
        $learnerId = 0;
    } else if (isset($_POST["add_completion"])) {
        $learner_id = $_POST["learner_id"];
        $course_id = $_POST["course_id"];
        addCompletionRecord($learner_id, $course_id);
        if ($_POST["page"] == "learner") {
            $learnerId = intval($_POST["learner_id"]);
            $learnerName = $_POST["learner_name"];
        } else {
            $courseId = intval($_POST["course_id"]);
            $courseName = $_POST["course_name"];
        }
    } else if (isset($_POST["remove_completion"])) {
        $learner_id = $_POST["learner_id"];
        $course_id = $_POST["course_id"];
        removeCompletionRecord($learner_id, $course_id);
        if ($_POST["page"] == "learner") {
            $learnerId = intval($_POST["learner_id"]);
            $learnerName = $_POST["learner_name"];
        } else {
            $courseId = intval($_POST["course_id"]);
            $courseName = $_POST["course_name"];
        }
    } else if (isset($_POST["send_email"])) {
        $params = [
            'learnerId' => $_POST['learner_id'],
            'courseId' => $_POST['course_id'],
            'key' => $_POST['key']
        ];
        $to = htmlspecialchars($_POST['email']);
        $course_name = $_POST['course_name'];
        $subject = "Congratulations on completing " . $course_name;
        $certificateUrl = "http://localhost/certificate/?" . http_build_query($params);
        $linkedInUrl = "https://www.linkedin.com/profile/add?startTask=CERTIFICATION_NAME&name=<?php echo urlencode($course_name); ?>&organizationId=10000&issueYear=2023&issueMonth=11&expirationYear=2026&expirationMonth=11&certUrl='https://www.yourdomain.com/'&certId=12345";
        $message = <<<EOD
        <!DOCTYPE html>
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; }
                .header { color: #ffffff; background-color: #4CAF50; padding: 10px; text-align: center; }
                .content { margin: 20px; padding: 10px; background-color: #f8f8f8; }
                .footer { text-align: center; padding: 10px; background-color: #eeeeee; }
            </style>
        </head>
        <body>
            <div class="header">
                <h1>Congratulations on Completing the Course!</h1>
            </div>
            <div class="content">
                <p>Hi $learnerName,</p>
                <p>Great job on completing the course: $courseName</p>
                <p>Please view your certificate at <a href='$certificateUrl'>View Certificate</a></p>
                <p>You can also share this on LinkedIn by clicking this <a href='$linkedInUrl'>link</a></p>
                <p>Keep up the good work!</p>
            </div>
            <div class="footer">
                <p>If you have any questions, do not hesitate to contact us at <a href='mailto:support@example.com'>support@example.com</a>.</p>
            </div>
        </body>
        </html>
        EOD;

        $from = $myEmail; // This should be the same as your msmtp configuration

        $headers = "MIME-Version: 1.0\r\n" . // Add this line
                    "Content-type: text/html; charset=UTF-8\r\n" . // Ensure this line is correct
                    "From: $from\r\n" .
                    "Reply-To: $from\r\n" .
                    "X-Mailer: PHP/" . phpversion() . "\r\n";

        // Attempt to send the email
        if (mail($to, $subject, $message, $headers)) {
            echo "<p>Email successfully sent to $to</p>";
        } else {
            echo "<p>Email sending failed.</p>";
        }
    } else if (isset($_POST["home"])) {
        $learnerId = 0;
        $courseId = 0;
    }
}

$allLearners = getAllLearners($conn);
$allCourses = getAllCourses($conn);

// If no name provided, render form to enter name
if ($learnerId == 0 && $courseId == 0) {
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
                                            <input type="hidden" name="learner_id" value="<?php echo htmlspecialchars($learner['id']); ?>">
                                            <input type="hidden" name="learner_name" value="<?php echo htmlspecialchars($learner['name']); ?>">
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
                                            <input type="hidden" name="course_id" value="<?php echo htmlspecialchars($course['id']); ?>">
                                            <input type="hidden" name="course_name" value="<?php echo htmlspecialchars($course['name']); ?>">
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
} else if ($learnerId != 0) {
    // Prepare and execute the query
    $stmt = $conn->prepare("SELECT * FROM learners WHERE id = ?");
    $stmt->bind_param("i", $learnerId);
    $stmt->execute();
    $result = $stmt->get_result();

    if (empty($allCourses)) {
        echo "No courses available.";
    } else {
        if ($result->num_rows > 0) {
            // If the name exists in the database, display the page
            $learnerRow = $result->fetch_assoc();
            $completedCourses = getCompletedCourses($conn, $learnerId);
            $emailHistory = getEmailHistory($conn, $learnerId, -1);
    ?>
            <!DOCTYPE html>
            <html lang="en">

            <head>
                <meta charset="UTF-8">
                <title><?php echo htmlspecialchars($learnerName); ?>'s Course Completion Status</title>
                <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
                <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
            </head>

            <body>
                <div class="container" style="padding-top:50px">
                    <h2><?php echo htmlspecialchars($learnerName); ?>'s Course Completion Status</h2>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Course Name</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($allCourses as $course) : ?>
                                <?php
                                // Filter courses to find those matching the current course's ID
                                $filteredCourses = array_filter($completedCourses, function ($row) use ($course) {
                                    return $row['course_id'] == $course['id'];
                                });

                                // Use array_values to reset array keys
                                $filteredCourses = array_values($filteredCourses);
                                ?>
                                <tr>
                                    <td>
                                        <?php echo htmlspecialchars($course['name']); ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($filteredCourses)) : ?>
                                            Complete
                                        <?php else : ?>
                                            Incomplete
                                        <?php endif; ?>
                                    </td>
                                    <td style="display: flex; gap: 10px">
                                        <?php if (!empty($filteredCourses)) : ?>
                                            <form class="form-inline" method="post" action="certificate.php">
                                                <input type="hidden" name="course_id" value="<?php echo $course['id']; ?>">
                                                <input type="hidden" name="learner_id" value="<?php echo $learnerId; ?>">
                                                <input type="hidden" name="key" value="<?php echo $filteredCourses[0]["certificate_id"]; ?>">
                                                <button type="submit" id="pdf" name="generate_pdf" title="View certificate" class="btn btn-dark btn-sm">
                                                    <i class="bi bi-file-earmark-pdf-fill"></i>
                                                </button>

                                                <!-- <a href="https://www.linkedin.com/profile/add?startTask=CERTIFICATION_NAME&name=<?php echo urlencode($course['name']); ?>&organizationId=10000&issueYear=2023&issueMonth=11&expirationYear=2026&expirationMonth=11&certUrl=<?php echo urlencode('https://www.yourdomain.com/certificate.php?course_id=' . $course['id'] . '&learner_id=' . $learnerRow['id']); ?>&certId=<?php echo $course['id']; ?>" target="_blank" class="btn btn-dark btn">
                                                    <i class="bi bi-linkedin"></i>
                                                </a> -->
                                            </form>

                                            <form class="form-inline" method="post">
                                                <input type="hidden" name="course_id" value="<?php echo $course['id']; ?>">
                                                <input type="hidden" name="course_name" value="<?php echo $course['name']; ?>">
                                                <input type="hidden" name="learner_name" value="<?php echo $learnerName; ?>">
                                                <input type="hidden" name="learner_id" value="<?php echo $learnerId; ?>">
                                                <input type="hidden" name="key" value="<?php echo $filteredCourses[0]["certificate_id"]; ?>">
                                                <input type="hidden" name="email" value="<?php echo $learnerRow["email"]; ?>">
                                                <button type="submit" class="btn btn-dark btn-sm" title="Send Email" name="send_email"><i class="bi bi-envelope"></i></button>
                                            </form>

                                            <form class="form-inline" method="post">
                                                <input type="hidden" name="course_id" value="<?php echo $course['id']; ?>">
                                                <input type="hidden" name="learner_id" value="<?php echo $learnerId; ?>">
                                                <input type="hidden" name="page" value="learner">
                                                <input type="hidden" name="learner_name" value="<?php echo $learnerName; ?>">
                                                <button type="submit" class="btn btn-dark btn-sm" title="Mark as Incomplete" name="remove_completion"><i class="bi bi-x-circle-fill"></i></button>

                                                <!-- <a href="https://www.linkedin.com/profile/add?startTask=CERTIFICATION_NAME&name=<?php echo urlencode($course['name']); ?>&organizationId=10000&issueYear=2023&issueMonth=11&expirationYear=2026&expirationMonth=11&certUrl=<?php echo urlencode('https://www.yourdomain.com/certificate.php?course_id=' . $course['id'] . '&learner_id=' . $learnerRow['id']); ?>&certId=<?php echo $course['id']; ?>" target="_blank" class="btn btn-dark btn">
                                                    <i class="bi bi-linkedin"></i>
                                                </a> -->
                                            </form>
                                        <?php else : ?>
                                            <!-- <span class="text-muted">Not completed</span> -->
                                            <form class="form-inline" method="post">
                                                <input type="hidden" name="learner_id" value="<?php echo htmlspecialchars($learnerId); ?>">
                                                <input type="hidden" name="course_id" value="<?php echo htmlspecialchars($course['id']); ?>">
                                                <input type="hidden" name="learner_name" value="<?php echo htmlspecialchars($learnerName); ?>">
                                                <input type="hidden" name="page" value="learner">
                                                <!-- <button type="submit" class="btn btn-dark btn-sm" name="add_completion"><i class="bi bi-check-circle-fill"></i></i></button> -->
                                                <button type="submit" class="btn btn-dark btn-sm" title="Mark as Complete" name="add_completion"><i class="bi bi-check-circle-fill"></i></button>
                                            </form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <form class="form-inline" method="post">
                        <button type="submit" class="btn btn-dark" name="home">Return</button>
                    </form>
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
    $stmt = $conn->prepare("SELECT * FROM courses WHERE id = ?");
    $stmt->bind_param("s", $courseId);
    $stmt->execute();
    $result = $stmt->get_result();
    if (empty($allLearners)) {
        echo "No Learners in records.";
    } else {
        if ($result->num_rows > 0) {
            // If the name exists in the database, display the page
            $courseRow = $result->fetch_assoc();
            $completedLearners = getCompletedLearners($conn, $courseId);
        ?>
            <!DOCTYPE html>
            <html lang="en">

            <head>
                <meta charset="UTF-8">
                <title><?php echo htmlspecialchars($courseName); ?> Completion Status</title>
                <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
                <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
            </head>

            <body>
                <div class="container" style="padding-top:50px">
                    <h2><?php echo htmlspecialchars($courseName); ?> Course Completion Status</h2>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Course Name</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($allLearners as $learner) : ?>
                                <?php
                                // Filter learners to find those matching the current course's ID
                                $filteredLearners = array_filter($completedLearners, function ($row) use ($learner) {
                                    return $row['learner_id'] == $learner['id'];
                                });

                                // Use array_values to reset array keys
                                $filteredLearners = array_values($filteredLearners);
                                ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($learner['name']); ?></td>
                                    <td>
                                        <?php if (!empty($filteredLearners)) : ?>
                                            Complete
                                        <?php else : ?>
                                            Incomplete
                                        <?php endif; ?>
                                    </td>
                                    <td style="display: flex; gap: 10px">
                                        <?php if (!empty($filteredLearners)) : ?>
                                            <form class="form-inline" method="post" action="certificate.php">
                                                <input type="hidden" name="course_id" value="<?php echo $courseId; ?>">
                                                <input type="hidden" name="learner_id" value="<?php echo $learner['id']; ?>">
                                                <input type="hidden" name="key" value="<?php echo $filteredLearners[0]["certificate_id"]; ?>">
                                                <button type="submit" id="pdf" name="generate_pdf" title="View certificate" class="btn btn-dark btn-sm">
                                                    <i class="bi bi-file-earmark-pdf-fill"></i>
                                                </button>

                                                <!-- <a href="https://www.linkedin.com/profile/add?startTask=CERTIFICATION_NAME&name=<?php echo urlencode($course['name']); ?>&organizationId=10000&issueYear=2023&issueMonth=11&expirationYear=2026&expirationMonth=11&certUrl=<?php echo urlencode('https://www.yourdomain.com/certificate.php?course_id=' . $course['id'] . '&learner_id=' . $learnerRow['id']); ?>&certId=<?php echo $course['id']; ?>" target="_blank" class="btn btn-dark btn">
                                                    <i class="bi bi-linkedin"></i>
                                                </a> -->
                                            </form>

                                            <form class="form-inline" method="post">
                                                <input type="hidden" name="course_id" value="<?php echo $courseId; ?>">
                                                <input type="hidden" name="course_name" value="<?php echo $courseName; ?>">
                                                <input type="hidden" name="learner_name" value="<?php echo $learner['name']; ?>">
                                                <input type="hidden" name="learner_id" value="<?php echo $learner['id']; ?>">
                                                <input type="hidden" name="key" value="<?php echo $filteredLearners[0]["certificate_id"]; ?>">
                                                <input type="hidden" name="email" value="<?php echo $learner["email"]; ?>">
                                                <button type="submit" class="btn btn-dark btn-sm" title="Send Email" name="send_email"><i class="bi bi-envelope"></i></button>
                                            </form>

                                            <form class="form-inline" method="post">
                                                <input type="hidden" name="course_id" value="<?php echo $courseId; ?>">
                                                <input type="hidden" name="learner_id" value="<?php echo $learner['id']; ?>">
                                                <input type="hidden" name="page" value="course">
                                                <input type="hidden" name="course_name" value="<?php echo $courseName; ?>">
                                                <button type="submit" class="btn btn-dark btn-sm" title="Mark as Incomplete" name="remove_completion"><i class="bi bi-x-circle-fill"></i></button>

                                                <!-- <a href="https://www.linkedin.com/profile/add?startTask=CERTIFICATION_NAME&name=<?php echo urlencode($course['name']); ?>&organizationId=10000&issueYear=2023&issueMonth=11&expirationYear=2026&expirationMonth=11&certUrl=<?php echo urlencode('https://www.yourdomain.com/certificate.php?course_id=' . $course['id'] . '&learner_id=' . $learnerRow['id']); ?>&certId=<?php echo $course['id']; ?>" target="_blank" class="btn btn-dark btn">
                                                    <i class="bi bi-linkedin"></i>
                                                </a> -->
                                            </form>
                                        <?php else : ?>
                                            <!-- <span class="text-muted">Not completed</span> -->
                                            <form class="form-inline" method="post">
                                                <input type="hidden" name="learner_id" value="<?php echo htmlspecialchars($learner['id']); ?>">
                                                <input type="hidden" name="course_id" value="<?php echo htmlspecialchars($courseId); ?>">
                                                <input type="hidden" name="course_name" value="<?php echo $courseName; ?>">
                                                <input type="hidden" name="page" value="course">
                                                <!-- <button type="submit" class="btn btn-dark btn-sm" name="add_completion"><i class="bi bi-check-circle-fill"></i></i></button> -->
                                                <button type="submit" class="btn btn-dark btn-sm" title="Mark as Complete" name="add_completion"><i class="bi bi-check-circle-fill"></i></button>
                                            </form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <form class="form-inline" method="post">
                        <button type="submit" class="btn btn-dark" name="home">Return</button>
                    </form>
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