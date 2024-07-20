<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;


require './PHPMailer/src/Exception.php';
require './PHPMailer/src/PHPMailer.php';
require './PHPMailer/src/SMTP.php';
require './secrets/mail_black_list.php';


function checkBot($recaptcha_secret, $recaptcha_token) {
    $url = 'https://www.recaptcha.net/recaptcha/api/siteverify';
	$data = array(
		'secret' => $recaptcha_secret,
		'response' => $recaptcha_token
	);
	$options = array(
		'http' => array (
			'method' => 'POST',
			'content' => http_build_query($data)
		)
	);
	$context = stream_context_create($options);
	$captcha_ret = json_decode(file_get_contents($url, false, $context), true);

    return $captcha_ret["success"];
}


// WARNING. MAIL SCRIPT IS DISABLED DUE TO TRASH EMAILS,
// COMMENT OUT THE FOLLOWING CODE TO ENABLE THE FUNCTIONALITY.
echo "<script>alert('Messager has been temporarily DISABLED 😟');</script>";
exit("Messager Disabled!\n");


// Check if the notification should not be sent
if(empty($_POST["name"]) or empty($_POST["email"]) or empty($_POST["subject"]) or empty($_POST["message"])) {
    echo "<script>alert('Please do fill the form 🧐');</script>";
    exit("Message incomplete!\n");
}

// Check if the request comes from human
$recaptcha_secret_v2_invisible = rtrim(file_get_contents("/run/secrets/recaptcha_secret_v2_invisible"));
echo "The secret is " . $recaptcha_secret_v2_invisible;
$is_human = checkBot($recaptcha_secret_v2_invisible, $_POST["g-recaptcha-response"]);
if ($is_human !== true) {
    echo "<script>alert('Only HUMAN is allowed to submit the form!');</script>";
    exit("Not Human\n");
}

// Check mail black list
if(array_search($_POST["email"], $mail_black_list)) {
    echo "<script>alert('Message cannot be sent due to an unexpected ERROR 😟');</script>";
    exit("Sender in black list!\n");
}

// Prepare for sending email
$mail = new PHPMailer();
$email_passwd = rtrim(file_get_contents("/run/secrets/email_passwd"));

// Email sender's info
$mail->isSMTP();                            // Use SMTP
$mail->CharSet = "utf8";
$mail->Host = "smtp.zoho.com.cn";           // Email sender's SMTP server address
$mail->SMTPAuth = true;                     // Need authorization?
$mail->Username = "admin@shenjunda.com";    // Email sender's email username
$mail->Password = $email_passwd;            // Email sender's email password
$mail->SMTPSecure = "ssl";                  // What secure type?
$mail->Port = 465;                          // SSL port: 465/994

// Eamil receiver's info
$mail->setFrom("admin@shenjunda.com","Site Messager");              // Email sender
$mail->addAddress("me@shenjunda.com","Junda Shen");         // Email receiver
$mail->addReplyTo($_POST["email"]);                           // Where to reply?
// $mail->addCC("evolraelc9@163.com");                       // Email copy receiver
// $mail->addBCC("bbbb@163.com");                            // Email secret receiver
// $mail->addAttachment("bug0.jpg");                         // Attachment

$mail->Subject = "New Message from " . $_POST["name"];

$main_content = $_POST["message"] . "\n\n";
$mail->Body = "Subject: " . $_POST["subject"] . "\n\n" .
              $main_content .
              "---------------------------------\n" .
              "Time: " . date('Y-m-d H:i:s') .
              "\n---------------------------------\n" .
              "You can reply directly to this email.";

// Send email
if(!$mail->send()) {
    echo "<script>alert('Message could not be sent for some reason 😟');</script>";
} else {
    echo "<script>alert('Thanks for your message 😃');</script>";
}
?>