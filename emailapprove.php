<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Managment @ Edutrue</title>
  <link href="Datatables/css/jquery.dataTables.min.css" rel="stylesheet"/>
    <link href="Datatables/css/material-components-web.min.css" rel="stylesheet"/>


    <script src="Datatables/js/jquery-3.5.1.js"></script> 
    <script src="Datatables/js/jquery.dataTables.min.js"></script>
    <script src="Datatables/js/dataTables.material.min.js"></script> 


    <script src="https://cdn.tailwindcss.com?plugins=forms,typography,aspect-ratio,line-clamp"></script>
    <script src="Tailwind/cdn.tailwindcss.com_3.3.3_plugins=forms@0.5.4,typography@0.5.9,aspect-ratio@0.4.2,line-clamp@0.4.4.css"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    

</head>
<body>



 
  <?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start a session
session_start();


// Database connection
$servername = '127.0.0.1';
$username = 'root';
$password = '';
$database = 'emailid_fetcher';

$hostnamemail = '{imap.gmail.com:993/imap/ssl}INBOX';
$usernamemail = 'edutruebackend@gmail.com';
$passwordmail = 'uayhkywjgroprgdr';

$conn = new mysqli($servername, $username, $password, $database);

if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}

// Fetch emails
$inbox = imap_open($hostnamemail, $usernamemail, $passwordmail) or die('Cannot connect to mailbox: ' . imap_last_error());
$emails = imap_search($inbox, 'UNSEEN');

if ($emails) {
    echo '<table border="1" id="example" width="100%">';
    echo '<thead><tr><th>Subject</th><th>From</th><th>Action</th></tr></thead>';
    echo '<tbody>';

    foreach ($emails as $email_number) {
        $overview = imap_fetch_overview($inbox, $email_number)[0];

        echo '<tr>';
        echo '<td>' . $overview->subject . '</td>';
        echo '<td>' . $overview->from . '</td>';
        echo '<td>';
        echo '<form method="post">';
        echo '<input type="hidden" name="email_number" value="' . $email_number . '">';
        echo '<input type="submit" name="editEmail" value="Edit Email" class="bg-blue-500 text-white text-semibold justify-items rounded px-10 py-4 my-2 hover:bg-blue-300">';
        echo '</form>';
        echo '</td>';
        echo '</tr>';
    }

    echo '</tbody>';
    echo '</table>';
} else {
    echo 'No new emails for approval.';
}

if (isset($_POST['editEmail']) && isset($_POST['email_number'])) {
    $email_number = $_POST['email_number'];

    // Store the email number in a session variable
    $_SESSION['email_number'] = $email_number;
}

if (isset($_SESSION['email_number'])) {
    $email_number = $_SESSION['email_number'];

    $email_overview = imap_fetch_overview($inbox, $email_number)[0];
    $email_subject = $email_overview->subject;
    $email_body = imap_fetchbody($inbox, $email_number, 1);
    $defaultSubject = ' | DO NOT EDIT THE SUBJECT,ELSE THE MAILL WILL NOT BE PROCESSED';
    $defaultBody = 'ONLY ONE REPLY PER EMAIL IS ALLOWED.(HOWEVER THIS LIMIT WILL BE REMOVED IN FUTURE ENHANCEMENTS).YOUR EMAIL SHALL BE PROCESS WITHIN 24hrs - 92hrs (IST) [WORKING TIMINGS: 8AM TO 6PM].THE REPLY TO THE EMAIL IS SUBJECT TO THE USER CHOICE.AND ALL THE TERMS AND CONDITIONS OF THE GMAIL (OWNED BY GOOGLE INC.) SHALL BE APPLICABLE.THE EMAIL DELIVERY IS ALSO SUBJECT TO THE REPLY FROM OUR TEAM.AND WE HEREBY HOLD ALL RIGHTS TO REJECT THE EMAIL WITHOUT ANY VALID REASON AND WITHOUT ANY PRIOR INTIMATION. THE EMAIL UPTIME AND EFFICINECNY IS NOT GUARENTEED IN ANY WAY,THE EDUTRUE TEAM SHALL NOT BE HELD LIABLE TO ANY OF THE MATTER.';
    // Extract the user ID from the email subject (modify the regex pattern as needed)
    if (preg_match('/ID:(\d+)/', $email_subject, $matches)) {
        $userId = $matches[1];
       
        // Query the database to fetch the recipient's email based on $userId
$sql = "SELECT email_address FROM email_recipients WHERE email_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $userId);
$stmt->execute();
$stmt->bind_result($recipientEmail);
        // If a recipient email is found, display it for editing
        if ($stmt->fetch()) {
            echo '<center>';
            echo ' <br><br><br><br><br>Email Approval:';
            echo '<div>' . nl2br($email_body) . '</div>'; // Display email content with line breaks
            echo '<hr>';

            echo '<form method="post" class="container">';
            echo 'To: ' . $recipientEmail . '<br><br><br>';
            echo '<label class="text-black" for="emailSubject">Subject</label>';
            echo '<textarea name="emailSubject" readonly class="block w-full px-4 py-2 mt-2 text-gray-700 bg-gray-300 border border-gray-200 rounded-md dark:border-gray-600 focus:border-blue-400 focus:ring-blue-300 focus:ring-opacity-40 dark:focus:border-blue-300 focus:outline-none focus:ring">' . $email_subject . $defaultSubject. '</textarea><br><br><br>';
            echo '<label class="text-black" for="emailBody">Body</label>';
            echo '<textarea name="emailBody" class="block w-full px-4 py-2 mt-2 text-black bg-white border border-gray-200 rounded-md dark:border-gray-600 focus:border-blue-400 focus:ring-blue-300 focus:ring-opacity-40 dark:focus:border-blue-300 focus:outline-none focus:ring">' . $email_body . $defaultBody. '</textarea><br><br><br>';
            echo '<input type="submit" name="sendEmail" value="Send Email" class="bg-green-500 text-white text-semibold justify-items rounded px-10 py-4 my-2 hover:bg-green-300">';
            echo '</form>';
            echo '</center>';
        } else {
            echo 'Recipient email not found in the database.';
        }

        $stmt->close();
    }
   



        if (isset($_POST['sendEmail']) && !empty($_POST['emailBody'])) {
            $emailSubject = $_POST['emailSubject'];
            $emailBody = $_POST['emailBody'];
    
            require 'PHPMailer/PHPMailer.php';
            require 'PHPMailer/SMTP.php';
    
            $mail = new PHPMailer\PHPMailer\PHPMailer(true);
            $mail->isSMTP();
            $mail->SMTPAuth = true;
            $mail->SMTPSecure = 'tls';
            $mail->Host = 'smtp.gmail.com';
            $mail->Port = 587;
            $mail->Username = 'edutruebackend@gmail.com';
            $mail->Password = 'uayhkywjgroprgdr';
            
    
            $mail->setFrom("edutruebackend@gmail.com", 'Edutrue');
            $mail->addAddress($recipientEmail);
            $mail->isHTML(true);
            $mail->Subject = $emailSubject;
            $mail->Body = $emailBody;
    
            try {
                $mail->send();
                echo '<center>';
                echo 'Email sent successfully.';
                echo '</center>';
            } catch (Exception $e) {
                echo 'Email could not be sent. Error: ' . $mail->ErrorInfo;
            }
        }
    }





// Close the mailbox
imap_close($inbox);
?>
<script>
    let table = new DataTable('#myTable');
    
// styling
$(document).ready(function () {
    $('#example').DataTable({
        autoWidth: false,
        columnDefs: [
            {
                targets: ['_all'],
                className: 'mdc-data-table__cell',
            },
        ],
    });
});
</script>


</body>
</html>

