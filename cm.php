<?php
/*
Plugin Name:  Certificate Managenent Plugin for LearnLab Certificate Courses and Workshops
Plugin URI:   https://einstein.andrew.cmu.edu/certificate-management
Description:  Simple certificate management for creating and verifying certificates. Works with LinkedIn and most other sites.
Version:      1.0
Author:       Michael Bett
Author URI:   https://www.learnlab.org
License:      GPL2
License URI:  https://www.gnu.org/licenses/gpl-2.0.html
Text Domain:  cm
Domain Path:  /languages
*/



/* Register the activation and deactivation functions for the plugin */
register_activation_hook( __FILE__, 'cm_activate' );
add_action('admin_menu', 'cm_setup_admin_menu');
register_activation_hook( __FILE__, 'cm_install' );
register_activation_hook( __FILE__, 'cm_install_data' );

/**
 * Register the "certificate" custom post type
  */
function cm_setup_post_type() {
	 register_post_type( 'certificate', ['public' => true ] );
}
add_action( 'init', 'cm_setup_post_type' );


/**
 * Activate the Certificate Manager plugin.
  */
function cm_activate() {
	 // Trigger our function that registers the custom post type plugin.
	 cm_setup_post_type();
	 // Clear the permalinks after the post type has been registered.
	 flush_rewrite_rules();

	 // Add a menu to the admin panel
	 
 }



/**
 * Deactivation hook.
  */
function cm_deactivate() {
	 // Unregister the post type, so the rules are no longer in memory.
	 unregister_post_type( 'certificate' );

	 // Clear the permalinks to remove our post type's rules from the database.
	 flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'cm_deactivate' );


// Source https://blog.idrsolutions.com/wordpress-plugin-part-1/

// Add a menu page on wp-admin for the certificate manager
//
// *How to allow different users to access a plugin*
//
// To allow Editors to access the plugin, change all occurances
// of ‘manage_options’ to ‘edit_pages’.
// To allow Authors to access the plugin, change to ‘publish_posts’.
// To allow Contributors to access the plugin, change to ‘edit_posts’.
// To allow Subscribers to access the plugin, change to ‘read’.
//
// Source: https://wordpress.org/support/topic/how-to-allow-non-admins-editors-authors-to-use-certain-wordpress-plugins/
//


function cm_setup_admin_menu(){



    // Allow WordPress Editors to use this plugin
    add_menu_page( 'Certificate Page', 'Certificate Manager', 'edit_posts', 'cm-plugin', 'upload_menu_page' );

//    Original line for admin access only
//    add_menu_page( 'Certificate Page', 'Certificate Manager', 'manage_options', 'cm-plugin', 'upload_menu_page' );
}


// Display a table containing all the certificates that are currently stored
// in the database
//
function list_all_certificates(){

	 global $wpdb;

         $table_name = $wpdb->prefix . 'certificates';
	  
	 // Setup the table for display

	 echo "<style>
	    table, th, td {
	      border: 1px solid black;
	      border-collapse: collapse;
	    }
	    </style>
	    <table><br/>";

	 $sql = "select * from information_schema.columns where table_name = '" .$table_name . "'";

	 // Perform query to get table headings
	 $result = $wpdb->get_results( $sql );
	 if ( $result ) {


	  // Print out the headers for the table
	  echo '<tr>';
	  echo '<th>Select</th>';
	  foreach ( $result as $data ) {
	      $col = 0;
              foreach ( $data as $c ) {
		 if  (++$col == 4)
		   echo "<th>" .  $c . "</th> ";
	      }
				
	  }
	  echo '</tr>';


	 } else {
             echo "</table><br/>";
	     return;  // there was an error
      	 }

	 $sql = "SELECT * FROM " . $table_name ;


	 // Perform query to get table contents
	 $result = $wpdb->get_results( $sql );
	 if ( $result ) {
           
          // Print out the data in a form as a table
          //
	  echo '<form>';
	  $row = 0;
	  foreach ( $result as $data ) {
//echo '<td>  <input type="checkbox"></td>';
	    echo '<td>  <input type="checkbox" id="row'. $row . '" name="row'. $row . '" value=' . $row . ' ></td>';
            // Print out each column value
            foreach ( $data as $c ) {
	          echo "<td>" . $c . "</td>";
            }
   	    echo "</tr>";
            $row++;

	   }
  //          $result -> free_result();
         }

         echo "</table><br/>";
	 echo '<input type="submit" value="Resend Selected Emails">';
         echo  "</form>";




}

// Allow the admin to create a single certificate OR upload a CSV file that contains all the current certificates
//
function upload_menu_page(){

     // try to upload a CSV file	 
     $csv_upload = upload_handle_post();



    if (($csv_upload == false ) && (!empty($_POST["list-all"]))) {
       list_all_certificates();
    } else
        if (($csv_upload == false ) && ($_SERVER["REQUEST_METHOD"] == "POST")) {
            $first_name = $_POST["first_name"];
            $last_name = $_POST["last_name"];
            $email = $_POST["email"];
            $completion_date = $_POST["completion_date"];
            $course_name = $_POST["certificate_course"];
	    $certificate_id = $_POST["certificate_id"];
            $issued_by = $_POST["issued_by"];


            // Format the completion date
	    $formatted_completion_date = date("F j, Y", strtotime($completion_date));


	    // create the certificate in the database
	    $created = cm_update_record(

		array( $first_name, $last_name, $email, $course_name, $certificate_id,
		       $issued_by, $formatted_completion_date )

             );

	    // if successful, send an email indicating that the record was created
	    if ( $created == 0 )
	       echo '<p>Certificate was **not** created. Check that the certificate id is not already in use.</p>';
	    else {
	       echo '<p>Certificate was successfully created. Sending email to the recipient and learnlab-help.</p>';



               $cert_url = 'https://learnlab.org/certificate-manager/verify.php?certificate_id=' . $certificate_id;


	       echo 'View the certificate here <a target="_blank" href="'  . $cert_url . '">' . $cert_url . '</a>';

	       $time=strtotime($completion_date);
	       $month=date("n",$time);
	       $year=date("Y",$time);


	       $add_to_linkedin_url = 'https://www.linkedin.com/profile/add/?certId=' . $certificate_id . '&certUrl=https%3A%2F%2Flearnlab.org%2Fcertificate-manager%2Fverify.php%3Fcertificate_id%3D' . $certificate_id . '&isFromA2p=true&issueMonth=' . $month . '&issueYear=' . $year . '&name=' . $course_name . '&organizationName=' . $issued_by;
	       

	       $message = 'Dear ' . $first_name . ' ' . $last_name . ', <br><br><p>

Congratulations on completing the certificate course ' . $course_name . '. You have been awarded a certificate and can access it at this link: ' . $cert_url . '. It is a pdf file, so please download it for your records. </p>
<p>We also encourage you to add it to your LinkedIn profile. Use the button below to add it in one click.</p>

<div><a href="' . $add_to_linkedin_url . '"><img src="https://download.linkedin.com/desktop/add2profile/buttons/en_US.png " alt="LinkedIn Add to Profile button"></a></div>

<p></p>

--<br/>

<strong>Michael Bett</strong><br/>
<a href="https://www.LearnLab.org">LearnLab</a> Managing Director<br/>
<a href="https://metals.hcii.cmu.edu">METALS</a> Managing Director<br/>
Follow us on <a href="https://www.linkedin.com/company/learnlab-part-of-carnegie-mellon-university-simon-initiative/posts/?feedView=all">LinkedIn</a>, <a href="https://www.facebook.com/pages/Learnlab/379480872145833">Facebook</a> , and <a href="https://twitter.com/learnlabslc">Twitter</a><br/>
<br>
<a href="learnlab.org/expertise">
See the growing list of learning engineering certificate courses available through Carnegie Mellon</a>
<p></p>';

	       $subject = "CMU Certificate for Successfully Completing " . $course_name;
	       $from = "LearnLab Help <learnlab-help@lists.andrew.cmu.edu>";
	       $headers = "From:" . $from . "\r\n" . "Reply-To:" . $from . "\r\n";
	       $headers .= "cc:learnlab-help@lists.andrew.cmu.edu\r\n";

	       // Add the current logged in user to the bcc
	       $current_user = wp_get_current_user();
//	       if ($current_user)
        	 $headers .= "Bcc:" . $current_user->user_email . "\r\n";

	       $headers .= "MIME-Version: 1.0\r\n";
	       $headers .= "Content-Type: text/html; charset=ISO-8859-1\r\n";

	       wp_mail( $email, $subject, $message, $headers );

	    }


    }
    // Read options from the text file and populate the dropdown
    // Keep the file in a location not accessible via the web so it cannot be hacked
    $options = file("/var/external_includes/course-names.txt", FILE_IGNORE_NEW_LINES);

    ?>

    <h1>Certificate Manager</h1>
    <h2>Choose one of the following options</h2>
    <br/>
    <br/>
    <h2>1. Display a table of all the certificates currently stored in the Certificate Table database</h2>
    <!-- Form to handle the upload - The enctype value here is very important -->
    <form action="#" method="post">
    	 <input type="hidden" id="list-all" name="list-all" value="list-all">
         <input type="submit" value="List All Certificates">
    </form>

    <br/>
    <br/>
    <h2>2. Add a Single Certificate - Automatically Emails the Recipient and Learnlab-help</h2>

    <form action="#" method="post">

    	<table>
	<tr>
    	<td><label for="first_name">First Name:</label></td>
	            <td><input type="text" id="first_name" name="first_name" required><br><br></td>
 
        </tr><tr>

        <td><label for="last_name">Last Name:</label></td>
	        <td><input type="text" id="last_name" name="last_name" required><br><br></td>

        </tr><tr>

	<td><label for="email">Email:</label></td>
	        <td><input type="text" id="email" name="email" required><br><br></td>

        </tr><tr>

	<td><label for="completion_date">Completion Date</label></td>
	        <td><input type="date" id="completion_date" name="completion_date" required><br><br></td>

 	</tr><tr>

	<td><label for="dropdown">Select a certificate course:</label></td>
	        <td><select id="dropdown" name="certificate_course">
                     <?php
		        // Populate the dropdown with options from the text file
	                foreach ($options as $option) {
		               echo "<option value=\"$option\">$option</option>";
		        }
	             ?>
		     </select>

	<br><br></td>


 	</tr><tr>

	<td><label for="certificate_id">Certificate ID:</label></td>
	        <td><input type="text" minlength=15 maxlength=15 id="certificate_id" name="certificate_id" required><br><br></td>

 	</tr><tr>


        <td><label for="issued_by">Issued by:</label></td>
	        <td><input type="text" size = 60 id="issued_by" name="issued_by" value="LearnLab, part of Carnegie Mellon University Simon Initiative" required><br><br></td>

	</tr>
	</table>

         <input type="submit" id="create-one" value="Create Certificate">
    </form>

    <br/></br/>  
    <h2>3. Upload a File - Will ignore lines and not create a certificate if the certificate_id is already in use.<br/><br/> <i>Does not send emails</i> to awardees since this may be used to recreate the database.</h2>
    <p>The CSV file's first row must have the following headers:<br/><br/>
    first_name, last_name, email, course_name, certificate_id, issued_by, issue_date, year, month
    <br/><br/>
     &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;issue_date must be in the format MM/DD/YEAR and<br/>
     &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;issued_by must be: LearnLab, part of Carnegie Mellon University Simon Initiative
    
    </p>
    <!-- Form to handle the upload - The enctype value here is very important -->
    <form  method="post" enctype="multipart/form-data">
        <input type='file' id='cm_upload_csv' name='cm_upload_csv'></input>
        <?php submit_button('Upload CSV File') ?>
    </form>
<?php
}
 
//
// This function does three things:
// Make sure that only one file is selected and that it is a CSV file
//
// Add the newly granted certificates to the certificate table.
// Update records that did not have certifcates previously awareded
// Do not make changes to previously granted certificates
//
// To prevent malicous access to the certificate information,
// delete the file after processing using wp_delete_file( string $file-path )
//

//
function upload_handle_post(){



    // First check if the file appears on the _FILES array
    

    if(isset($_FILES['cm_upload_csv'])){
	$csv = $_FILES['cm_upload_csv']['name'];

         // Check if the file is a CSV file
	 //
         if ( str_ends_with( $csv, ".csv" ) == FALSE ) {
	    echo "<p>Please enter a CSV file with the extension .csv<br/></p>\n";
	    return false;
	 }   

	 // We should have file with a .csv extension. Hopefully it's valid!
	 // Use the wordpress function to upload the file
         // cm_upload_csv corresponds to the position in the $_FILES array
         // 0 means the content is not associated with any other posts
         $uploaded=media_handle_upload('cm_upload_csv', 0);

	 // Error checking using WP functions
         if(is_wp_error( $uploaded )){
            echo "Error uploading file: " . $uploaded->get_error_message();
         }else{
            echo "File uploaded successfully!";

            // Since the file was uploaded successfully, we need to open and
	    // process the file contents
	    //
	    cm_upload_data_from_csv( $uploaded );
	    
        }
    } else
      return false;

    // otherwise we at least attempted to load a csv file
    return true;
}

// Open and process the file contents
// Print out the contents as a table
//
function cm_upload_data_from_csv( $file_id ) {

echo "<p> Start upload <br/><p>\n";

	 // Get the file id
	 $file = get_attached_file( $file_id );

	 // Get the local path to the file so we can read it in.
	 $filePath = realpath($file);


	 // Process and display the file


         // Cycle through the data print out the table and check that the data is consistent
	 //
	 $row = 1;
	 $first_num = 0;
	 $error = FALSE;
	 if (($handle = fopen($filePath, "r")) !== FALSE) {
	    // Setup the table for display
	    echo "<style>
	    table, th, td {
	      border: 1px solid black;
	      border-collapse: collapse;
	    }
	    </style>
	    <table>\n";
	     while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
	           echo "<tr>";
             	   $num = count($data);
		   // Not all the rows are consistent - so don't upload
		   if (($row > 1) && ($first_num != $num)) {
		      echo "ERROR: Number of columns expected: " . $first_num . " Found: " . $num . " on line " . $row . ".\n";
		      $error = TRUE;
		   } else
		     // Get the number of columns in the first row. This is important since
		     // all rows should contain the same number of elements
		     // in a fixed order
		     $first_num = $num;
		     
		   // Print out the data in the table
		   //
        	   for ($c=0; $c < $num; $c++) {
		       if ($row > 1)
		          echo "<td>" . $data[$c] . "</td>";
		       else
            	          echo "<th>" . $data[$c] . "</th>";
        	   }
   	           echo "</tr>";
        	   $row++;
    	      }
	 echo "</table>\n";
    	 fclose($handle);
	 }


	 if ($error !== FALSE){
	    echo "Please correct the errors in the file and upload it again.\n";
	 } else {
	    // Load the data into the database
	    $row = 1;
	    if (($handle = fopen($filePath, "r")) !== FALSE) {

	       while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
	           
             	   $num = count($data);
		   // Add the data into the table if the certificate id is unique
		   //
		   if ($row > 1) {
		     $created = cm_update_record( $data );
		     if ( $created == 0 )
		       echo '<p>Certificate for line ' . $row . ' was **not** created. Check that the certificate id is not already in use.</p>';
		   }
        	   $row++;
    	      	}
    	      	fclose($handle);
	     }
	    
	 }
       
}
//
// Does not currently resort the uploaded data to correspond with the
// database table order. Columns must be in the order of the database
// for now.
//
// Also need an update function since it will not overwrite an existing entry.
//
// First blank certificate_id gets inserted; others do not.
// Need to include a counter field?
//
//
function cm_update_record( $record ) {

	 global $wpdb;

         $table_name = $wpdb->prefix . 'certificates';

	 // Are we inserting or updating?

	 // Lookup and see if the certifcate_id exists
	 //  ********



         $count = $wpdb->insert(
		$table_name,
		array(
		      'first_name' => $record[0],
    	      	      'last_name' => $record[1],
              	      'email' => $record[2],
              	      'course_name' => $record[3],
              	      'certificate_id' => $record[4],
              	      'issued_by' => $record[5],
	      	      'issue_date' => $record[6],
	       	  )
		);

	return $count;
}


/* Create the database table to hold the certificate data */
global $cm_db_version;
$cm_db_version = '1.0';

function cm_install() {

	 global $wpdb;
	 $table_name = $wpdb->prefix . 'certificates';

	 $charset_collate = $wpdb->get_charset_collate();

	 $sql = "CREATE TABLE $table_name (
	      first_name varchar(50) NOT NULL,
    	      last_name varchar(50) NOT NULL,
              email varchar(50) NOT NULL,
              course_name varchar(100) NOT NULL,
              certificate_id varchar(20) NOT NULL,
              issued_by varchar(100) NOT NULL,
	      issue_date varchar(40) NOT NULL,
       	   PRIMARY KEY  (certificate_id)

         ) $charset_collate;";

         require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	 dbDelta( $sql );
	 add_option( 'cm_db_version', $cm_db_version );

}

function cm_install_data() {

	 global $wpdb;

	 $welcome_name = 'Certificate Manager';
         $welcome_text = 'Congratulations, you just completed the installation!';

         $table_name = $wpdb->prefix . 'certificates';

         $wpdb->insert(
			$table_name,
			array(
				'time' => current_time( 'mysql' ),
	       	  		'name' => $welcome_name,
				'text' => $welcome_text,
		       	  )
			);
}





?>
