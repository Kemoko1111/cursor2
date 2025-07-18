<?php
class EmailService {
    private $host;
    private $port;
    private $username;
    private $password;
    private $fromName;
    private $fromEmail;

    public function __construct() {
        $this->host = MAIL_HOST;
        $this->port = MAIL_PORT;
        $this->username = MAIL_USERNAME;
        $this->password = MAIL_PASSWORD;
        $this->fromName = MAIL_FROM_NAME;
        $this->fromEmail = MAIL_FROM_EMAIL;
    }

    public function sendEmail($to, $subject, $body, $isHtml = true) {
        try {
            // Create email headers
            $headers = [
                'MIME-Version: 1.0',
                'Content-Type: ' . ($isHtml ? 'text/html' : 'text/plain') . '; charset=UTF-8',
                'From: ' . $this->fromName . ' <' . $this->fromEmail . '>',
                'Reply-To: ' . $this->fromEmail,
                'X-Mailer: PHP/' . phpversion()
            ];

            // Send email using PHP's mail function
            // In production, you should use PHPMailer or similar for SMTP
            $success = mail($to, $subject, $body, implode("\r\n", $headers));
            
            if ($success) {
                error_log("Email sent successfully to: " . $to);
                return true;
            } else {
                error_log("Failed to send email to: " . $to);
                return false;
            }
        } catch (Exception $e) {
            error_log("Email error: " . $e->getMessage());
            return false;
        }
    }

    public function sendVerificationEmail($user, $token) {
        $verificationUrl = APP_URL . "/auth/verify.php?token=" . $token;
        
        $subject = "Verify Your Menteego Account";
        $body = $this->getEmailTemplate('verification', [
            'name' => $user['first_name'],
            'verification_url' => $verificationUrl,
            'org_name' => ORG_NAME
        ]);

        return $this->sendEmail($user['email'], $subject, $body);
    }

    public function sendPasswordResetEmail($user, $token) {
        $resetUrl = APP_URL . "/auth/reset-password.php?token=" . $token;
        
        $subject = "Reset Your Menteego Password";
        $body = $this->getEmailTemplate('password_reset', [
            'name' => $user['first_name'],
            'reset_url' => $resetUrl,
            'org_name' => ORG_NAME
        ]);

        return $this->sendEmail($user['email'], $subject, $body);
    }

    public function sendMentorshipRequestEmail($mentor, $mentee, $requestId) {
        $subject = "New Mentorship Request from " . $mentee['first_name'] . " " . $mentee['last_name'];
        $body = $this->getEmailTemplate('mentorship_request', [
            'mentor_name' => $mentor['first_name'],
            'mentee_name' => $mentee['first_name'] . " " . $mentee['last_name'],
            'mentee_department' => $mentee['department'],
            'mentee_year' => $mentee['year_of_study'],
            'request_url' => APP_URL . "/dashboard.php#requests",
            'org_name' => ORG_NAME
        ]);

        return $this->sendEmail($mentor['email'], $subject, $body);
    }

    public function sendRequestResponseEmail($mentee, $mentor, $status) {
        $subject = "Mentorship Request " . ucfirst($status);
        $body = $this->getEmailTemplate('request_response', [
            'mentee_name' => $mentee['first_name'],
            'mentor_name' => $mentor['first_name'] . " " . $mentor['last_name'],
            'status' => $status,
            'dashboard_url' => APP_URL . "/dashboard.php",
            'org_name' => ORG_NAME
        ]);

        return $this->sendEmail($mentee['email'], $subject, $body);
    }

    public function sendNewMessageEmail($recipient, $sender, $mentorshipId) {
        $subject = "New Message from " . $sender['first_name'] . " " . $sender['last_name'];
        $body = $this->getEmailTemplate('new_message', [
            'recipient_name' => $recipient['first_name'],
            'sender_name' => $sender['first_name'] . " " . $sender['last_name'],
            'messages_url' => APP_URL . "/messages.php?mentorship=" . $mentorshipId,
            'org_name' => ORG_NAME
        ]);

        return $this->sendEmail($recipient['email'], $subject, $body);
    }

    private function getEmailTemplate($template, $data) {
        switch ($template) {
            case 'verification':
                return $this->getVerificationTemplate($data);
            case 'password_reset':
                return $this->getPasswordResetTemplate($data);
            case 'mentorship_request':
                return $this->getMentorshipRequestTemplate($data);
            case 'request_response':
                return $this->getRequestResponseTemplate($data);
            case 'new_message':
                return $this->getNewMessageTemplate($data);
            default:
                return '';
        }
    }

    private function getVerificationTemplate($data) {
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <title>Verify Your Account</title>
        </head>
        <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
            <div style='max-width: 600px; margin: 0 auto; padding: 20px;'>
                <h2 style='color: #2c3e50;'>Welcome to Menteego!</h2>
                <p>Hi {$data['name']},</p>
                <p>Thank you for joining {$data['org_name']} mentorship platform. To complete your registration, please verify your email address by clicking the button below:</p>
                <div style='text-align: center; margin: 30px 0;'>
                    <a href='{$data['verification_url']}' style='background-color: #3498db; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; display: inline-block;'>Verify Email Address</a>
                </div>
                <p>If the button doesn't work, copy and paste this link into your browser:</p>
                <p style='word-break: break-all;'>{$data['verification_url']}</p>
                <p>This verification link will expire in 24 hours.</p>
                <p>Best regards,<br>The Menteego Team</p>
            </div>
        </body>
        </html>";
    }

    private function getPasswordResetTemplate($data) {
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <title>Reset Your Password</title>
        </head>
        <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
            <div style='max-width: 600px; margin: 0 auto; padding: 20px;'>
                <h2 style='color: #2c3e50;'>Password Reset Request</h2>
                <p>Hi {$data['name']},</p>
                <p>We received a request to reset your password for your Menteego account. Click the button below to reset your password:</p>
                <div style='text-align: center; margin: 30px 0;'>
                    <a href='{$data['reset_url']}' style='background-color: #e74c3c; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; display: inline-block;'>Reset Password</a>
                </div>
                <p>If the button doesn't work, copy and paste this link into your browser:</p>
                <p style='word-break: break-all;'>{$data['reset_url']}</p>
                <p>This reset link will expire in 1 hour. If you didn't request this password reset, please ignore this email.</p>
                <p>Best regards,<br>The Menteego Team</p>
            </div>
        </body>
        </html>";
    }

    private function getMentorshipRequestTemplate($data) {
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <title>New Mentorship Request</title>
        </head>
        <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
            <div style='max-width: 600px; margin: 0 auto; padding: 20px;'>
                <h2 style='color: #2c3e50;'>New Mentorship Request</h2>
                <p>Hi {$data['mentor_name']},</p>
                <p>You have received a new mentorship request from <strong>{$data['mentee_name']}</strong>.</p>
                <div style='background-color: #f8f9fa; padding: 15px; border-radius: 5px; margin: 20px 0;'>
                    <p><strong>Student Details:</strong></p>
                    <ul>
                        <li>Name: {$data['mentee_name']}</li>
                        <li>Department: {$data['mentee_department']}</li>
                        <li>Year of Study: {$data['mentee_year']}</li>
                    </ul>
                </div>
                <div style='text-align: center; margin: 30px 0;'>
                    <a href='{$data['request_url']}' style='background-color: #27ae60; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; display: inline-block;'>View Request</a>
                </div>
                <p>Please log in to your dashboard to review the request and respond.</p>
                <p>Best regards,<br>The Menteego Team</p>
            </div>
        </body>
        </html>";
    }

    private function getRequestResponseTemplate($data) {
        $statusColor = $data['status'] === 'accepted' ? '#27ae60' : '#e74c3c';
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <title>Mentorship Request {$data['status']}</title>
        </head>
        <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
            <div style='max-width: 600px; margin: 0 auto; padding: 20px;'>
                <h2 style='color: {$statusColor};'>Mentorship Request " . ucfirst($data['status']) . "</h2>
                <p>Hi {$data['mentee_name']},</p>
                <p>Your mentorship request to <strong>{$data['mentor_name']}</strong> has been <strong style='color: {$statusColor};'>" . $data['status'] . "</strong>.</p>";
        
        if ($data['status'] === 'accepted') {
            $template .= "
                <div style='background-color: #d4edda; padding: 15px; border-radius: 5px; margin: 20px 0; border-left: 4px solid #27ae60;'>
                    <p>🎉 Congratulations! You can now start your mentorship journey. Use the messaging system to communicate with your mentor and schedule your first meeting.</p>
                </div>";
        }
        
        $template .= "
                <div style='text-align: center; margin: 30px 0;'>
                    <a href='{$data['dashboard_url']}' style='background-color: #3498db; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; display: inline-block;'>Go to Dashboard</a>
                </div>
                <p>Best regards,<br>The Menteego Team</p>
            </div>
        </body>
        </html>";
        
        return $template;
    }

    private function getNewMessageTemplate($data) {
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <title>New Message</title>
        </head>
        <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
            <div style='max-width: 600px; margin: 0 auto; padding: 20px;'>
                <h2 style='color: #2c3e50;'>New Message</h2>
                <p>Hi {$data['recipient_name']},</p>
                <p>You have received a new message from <strong>{$data['sender_name']}</strong> in your mentorship conversation.</p>
                <div style='text-align: center; margin: 30px 0;'>
                    <a href='{$data['messages_url']}' style='background-color: #9b59b6; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; display: inline-block;'>View Message</a>
                </div>
                <p>Best regards,<br>The Menteego Team</p>
            </div>
        </body>
        </html>";
    }
}
?>