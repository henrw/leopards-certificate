<?php
// Check if the form has been submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $to = htmlspecialchars($_POST['to']);
    $subject = htmlspecialchars($_POST['subject']);
    $message = htmlspecialchars($_POST['message']);
    $from = 'your-email@gmail.com'; // This should be the same as your msmtp configuration

    $headers = "From: $from\r\n" .
        "Reply-To: $from\r\n" .
        "X-Mailer: PHP/" . phpversion();

    // Attempt to send the email
    if (mail($to, $subject, $message, $headers)) {
        echo "<p>Email successfully sent to $to</p>";
    } else {
        echo "<p>Email sending failed.</p>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Send Email</title>
</head>

<body>
    <h1>Send Email</h1>
    <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
        <label for="to">To:</label><br>
        <input type="email" id="to" name="to" required><br><br>

        <label for="subject">Subject:</label><br>
        <input type="text" id="subject" name="subject" required><br><br>

        <label for="message">Message:</label><br>
        <textarea id="message" name="message" rows="4" required></textarea><br><br>

        <button type="submit">Send Email</button>
    </form>
</body>

</html>