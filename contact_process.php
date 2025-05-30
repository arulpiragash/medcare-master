<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';

// === DB Configuration ===
$host = "localhost";
$dbname = "medcare";
$username = "root";
$password = "";

// === Gmail SMTP Configuration ===
$gmail_user = "arulpiragashj19@gmail.com";
$gmail_pass = "essc fohz jvuq msei"; // App password only (NOT your Gmail password)

function clean_input($data) {
    return htmlspecialchars(stripslashes(trim($data)));
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Sanitize inputs
    $name    = clean_input($_POST["name"] ?? '');
    $email   = clean_input($_POST["email"] ?? '');
    $subject = clean_input($_POST["subject"] ?? '');
    $message = clean_input($_POST["message"] ?? '');

    // Basic validation
    if (empty($name) || empty($email) || empty($subject) || empty($message)) {
        http_response_code(400);
        echo "Please fill in all fields.";
        exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo "Invalid email format.";
        exit;
    }

    // === Step 1: Save to Database ===
    $conn = new mysqli($host, $username, $password, $dbname);
    if ($conn->connect_error) {
        http_response_code(500);
        echo "Database connection failed: " . $conn->connect_error;
        exit;
    }

    $stmt = $conn->prepare("INSERT INTO contact_messages (name, email, subject, message) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $name, $email, $subject, $message);
    $saved = $stmt->execute();
    $stmt->close();
    $conn->close();

    // === Step 2: Send Email using PHPMailer ===
    if ($saved) {
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = $gmail_user;
            $mail->Password   = $gmail_pass;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;

            $mail->setFrom($gmail_user, 'Website Contact');
            $mail->addAddress('arulpiragashj19@gmail.com', 'You');

            $mail->isHTML(true);
            $mail->Subject = "New Message from Website: $subject";
            $mail->Body    = "
                <strong>Name:</strong> $name<br>
                <strong>Email:</strong> $email<br>
                <strong>Subject:</strong> $subject<br><br>
                <strong>Message:</strong><br>" . nl2br($message);
            $mail->AltBody = "Name: $name\nEmail: $email\nSubject: $subject\nMessage:\n$message";

            $mail->send();
            http_response_code(200);
            echo "Thank you! Your message has been sent and saved.";
        } catch (Exception $e) {
            http_response_code(500);
            echo "Message saved, but email could not be sent. Mailer Error: {$mail->ErrorInfo}";
        }
    } else {
        http_response_code(500);
        echo "Something went wrong while saving your message.";
    }
} else {
    http_response_code(403);
    echo "Access denied.";
}
?>
