<?php

/* Look up the certifcate on the server and print the certificate
   If the certificate cannot be found, print an error message.
*/
// global $cm_db_version;
// $cm_db_version = '1.0';
// $database_name = "learnlab_wordpress";


// Set our path to get the secret data
// set_include_path(get_include_path() . ":.:/var/www/html" );
// require_once("/var/external_includes/dbconnector.php");
include_once("connection.php");
require_once("fpdf/fpdf.php");
require('fpdf_protection.php');
//include("pdfprint.php");

define("MIN_CERTIFICATE_CHARS",  5);

/*
  An Example PDF Report Using FPDF
  by Matt Doyle

  From "Create Nice-Looking PDFs with PHP and FPDF"
  http://www.elated.com/articles/create-nice-looking-pdfs-php-fpdf/
*/


// Begin configuration


function pdfprint($certificate_id, $name, $coursename, $date)
{

	$textColour = array(0, 0, 0);
	$headerColour = array(100, 100, 100);
	$tableHeaderTopTextColour = array(255, 255, 255);
	$tableHeaderTopFillColour = array(125, 152, 179);
	$tableHeaderTopProductTextColour = array(0, 0, 0);
	$tableHeaderTopProductFillColour = array(143, 173, 204);
	$tableHeaderLeftTextColour = array(99, 42, 57);
	$tableHeaderLeftFillColour = array(184, 207, 229);
	$tableBorderColour = array(50, 50, 50);
	$tableRowFillColour = array(213, 170, 170);
	$reportName = "Certificate of Completion";
	$awarded = "Awarded to";
	$reportNameYPos = 10;
	$learnlabFile = "https://learnlab.org/wp-content/uploads/2016/06/Learnlab-Logo_tspnt-no-PSLC-small.png";
	$learnlabXPos = 20;
	$learnlabYPos = 168;
	$learnlabWidth = 75;

	$cmuFile = "https://learnlab.org/wp-content/uploads/2021/02/CMU_Logo_Horiz_Red.png";
	$cmuXPos = 100;
	$cmuYPos = 173;
	$cmuWidth = 95;

	$oliFile = "https://learnlab.org/wp-content/uploads/2023/01/oli-logo-78px-high-1.png";
	$oliXPos = 200;
	$oliYPos = 168;
	$oliWidth = 75;



	// End configuration


	/**
	   Create the title page
	 **/

	// Set the protection
	$pdf = new FPDF_Protection('L', 'mm', 'a4');
	$pdf->SetProtection(array('print'));


	$pdf->SetTextColor($textColour[0], $textColour[1], $textColour[2]);
	$pdf->AddPage();


	// Add a background image inside the rectangle
	$pdf->Image('assets/cmu-bg2.png', 4, 4, 289, 202);


	// Draw a border
	$pdf->setDrawColor(154, 154, 0);
	$pdf->SetLineWidth(1.0);
	$pdf->Rect(1, 1, 295, 208);
	$pdf->SetLineWidth(.5);
	$pdf->Rect(3, 3, 291, 204);


	// Report Name
	$pdf->SetFont('TIMES', 'B', 36);
	$pdf->setTextColor(154, 154, 0);
	$pdf->Ln($reportNameYPos);
	$pdf->Cell(0, 15, "Certificate of Completion", 0, 0, 'C');
	$pdf->Ln(12);
	$pdf->SetFont('TIMES', '', 15);
	$pdf->setTextColor(0, 0, 0);
	$pdf->Cell(0, 15, "This certificate is presented to", 0, 0, 'C');
	$pdf->Ln(12);
	$pdf->SetFont('TIMES', 'BI', 32);
	$pdf->setTextColor(0, 0, 0);
	$pdf->Ln(9);

	$pdf->Cell(0, 15, $name, 0, 0, 'C');
	$pdf->Ln(20);
	$pdf->SetFont('TIMES', 'B', 15);
	$pdf->Cell(0, 15, "on " . $date . " for successfully completing", 0, 0, 'C');
	$pdf->Ln(15);
	$pdf->SetFont('TIMES', 'B', 23);
	$pdf->Cell(0, 15, $coursename, 0, 0, 'C');
	$pdf->Ln(15);
	$pdf->SetFont('TIMES', '', 15);
	$pdf->Cell(0, 15, "An online evidence-based course offered in collaboration with", 0, 0, 'C');
	$pdf->Ln(7.5);
	$pdf->Cell(0, 15, "Carnegie Mellon University", 0, 0, 'C');


	$pdf->setXY(20, 140);
	$pdf->MultiCell(75, 5, "Ken Koedinger, Hillman Professor of Computer Science and Human-Computer Interaction", "T", "C");
	$pdf->Image("assets/kk_sig.png", 30, 128, 50);

	$pdf->Image("assets/mb_sig.png", 120, 130, 50);
	$pdf->setXY(110, 140);
	$pdf->MultiCell(75, 5, "Michael Bett, LearnLab Managing Director", "T", "C");

	$pdf->Image("assets/nb_sig.png", 210, 128, 50);
	$pdf->setXY(200, 140);
	$pdf->MultiCell(75, 5, "Norman Bier, Director, Open Learning Initiative & Director, Simon Initiative", "T", "C");

	// Print the logos
	$pdf->Image($learnlabFile, $learnlabXPos, $learnlabYPos, $learnlabWidth);
	$pdf->Image($oliFile, $oliXPos, $oliYPos, $oliWidth);
	$pdf->Image($cmuFile, $cmuXPos, $cmuYPos, $cmuWidth);

	$pdf->SetFont('TIMES', 'BI', 10);
	$pdf->setTextColor(0, 0, 0);
	$pdf->setXY(135, 189);
	$pdf->Cell(0, 0, "This certificate is valid if only viewed on LearnLab.org or a Carnegie Mellon University website.");


	/***
	     Serve the PDF
	 ***/

	//echo  $certificate_id . $name . $coursename . $date;
	$pdf->Output("/var/www/html/learnlab/certificates/report.pdf", "I");
}


ini_set('display_errors', 1);
error_reporting(E_ALL);

// Get the cetificate id from the url
//
// $certificate_id = $_GET["certificate_id"];
// Get learner_id and course_id from POST request
$learner_id = isset($_POST['learner_id']) ? $_POST['learner_id'] : null;
$course_id = isset($_POST['course_id']) ? $_POST['course_id'] : null;
$key = isset($_POST['key']) ? $_POST['key'] : null;

// Define the SQL query to check completion and fetch course details

// Define the SQL query
// $select_sql = "SELECT * FROM wp_certificates WHERE certificate_id=" . "'" . $certificate_id . "'";


$connString = (new dbObj())->getConnstring();
if (!$connString) {
	die("Database connection failed: " . mysqli_connect_error());
}
$stmt = $connString->prepare("
    SELECT l.name AS learner_name, c.name AS course_name, cp.completion_date
    FROM learners l
    JOIN completion cp ON l.id = cp.learner_id
    JOIN courses c ON c.id = cp.course_id
    WHERE l.id = ? AND c.id = ? AND cp.certificate_id = ?
");

if (!$stmt) {
    die("Prepare failed: " . $connString->error);
}

// Bind parameters to the query
$stmt->bind_param("iis", $learner_id, $course_id, $key);
if (!$stmt->execute()) {
	die("Execution failed: " . $stmt->error);
}
$result = $stmt->get_result();
if (!$result) {
	die("Fetching result failed: " . $stmt->error);
}


if ($result->num_rows > 0) {
	// If the learner has completed the course, fetch details and generate the certificate
	$row = $result->fetch_assoc();
	$learner_name = $row['learner_name'];
	$course_name = $row['course_name'];
	$completion_date = $row['completion_date'];

	// Assuming pdfprint function exists and is ready to generate PDF certificates
	pdfprint("Certificate ID", $learner_name, $course_name, $completion_date); // Please check the date and adjust accordingly
	echo "Certificate generated successfully.";
} else {
	// If the learner has not completed the course or no record exists, display an error
	echo "No completion record found for the requested course and learner.";
	http_response_code(404);
};

// Don't forget to free the result and close the connection
mysqli_free_result($result);
mysqli_close($connString);


// // Connect to the database
// $dbc = new DbConnector( $database_name );

// if (!$dbc->connect()){
//    echo "Failed to connect to the database. If this problem persists, send email to learnlab-help@lists.andrew.cmu.edu";
// } else {
// 	 // Query for the events that need email notification
// 	 //

// 	 $result = $dbc->getConnection()->query( $select_sql );

// 	 if ((strlen($certificate_id) > MIN_CERTIFICATE_CHARS) && ($result->num_rows >= 1)) {
//  	    // If found display the certificate
//             //

// 	    while ($row = $result->fetch_assoc()) {
// 	    	   pdfprint( $certificate_id, $row['first_name'] . ' ' . $row['last_name'], $row['course_name'], $row['issue_date'] );

//  echo $row['first_name'] . ' ' . $row['last_name'] . ' earned a certificate for ' . $row['course_name'];

// 	    }



// 	 } else {
// 	    // If not found print an error message
//             //
// 	    	 echo '<center> <br/> <br/>
// 		 <img src="https://learnlab.org/wp-content/uploads/2016/06/Learnlab-Logo_tspnt-no-PSLC-small.png">
// 		 <h2>Certificate Management Service</h2> <br/> <br/>
//                   The certificate ' . $certificate_id . ' was not found in the database. ';
//                   echo 'If you believe that this was an error, email learnlab-help@lists.andrew.cmu.edu for assitance. </center>';

//          }



//          $dbc->close();


// }
