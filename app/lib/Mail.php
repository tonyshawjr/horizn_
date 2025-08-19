<?php
/**
 * Mail System for horizn_ Analytics
 * 
 * Handles email sending with support for both SMTP and PHP mail()
 * with beautiful crypto-themed templates for magic link authentication.
 */

class Mail
{
    private static $config = null;
    
    /**
     * Load mail configuration
     */
    private static function loadConfig(): void
    {
        if (self::$config === null) {
            self::$config = require CONFIG_PATH . '/app.php';
        }
    }
    
    /**
     * Send magic link email
     */
    public static function sendMagicLink(array $user, string $magic_link, bool $isSetup = false): bool
    {
        self::loadConfig();
        
        $to = $user['email'];
        $subject = $isSetup ? 'Complete Your horizn_ Setup' : 'Your horizn_ Login Link';
        
        // Prepare template variables
        $variables = [
            'user_name' => $user['first_name'] ?? 'User',
            'magic_link' => $magic_link,
            'expires_minutes' => 15,
            'is_setup' => $isSetup,
            'app_name' => self::$config['app']['name'] ?? 'horizn_ Analytics',
            'app_url' => self::$config['app']['url'] ?? 'https://analytics.example.com',
            'email_title' => $isSetup ? 'Complete Your Setup' : 'Your Login Link',
            'email_message' => $isSetup ? 
                'Welcome to ' . (self::$config['app']['name'] ?? 'horizn_ Analytics') . '! You\'re one click away from accessing your analytics dashboard. Complete your setup by clicking the secure link below:' :
                'Click the secure link below to sign in to your ' . (self::$config['app']['name'] ?? 'horizn_ Analytics') . ' dashboard:',
            'button_text' => $isSetup ? 'Complete Setup' : 'Sign In to Dashboard'
        ];
        
        // Load and process email template
        $template = self::loadTemplate('magic-link', $variables);
        
        return self::send($to, $subject, $template);
    }
    
    /**
     * Send email using configured method
     */
    public static function send(string $to, string $subject, string $htmlContent, string $textContent = ''): bool
    {
        self::loadConfig();
        
        $mailConfig = self::$config['mail'] ?? [];
        $method = $mailConfig['method'] ?? 'mail';
        
        // Generate text version if not provided
        if (empty($textContent)) {
            $textContent = self::htmlToText($htmlContent);
        }
        
        switch ($method) {
            case 'smtp':
                return self::sendViaSMTP($to, $subject, $htmlContent, $textContent);
            case 'sendmail':
                return self::sendViaSendmail($to, $subject, $htmlContent, $textContent);
            case 'mail':
            default:
                return self::sendViaPHPMail($to, $subject, $htmlContent, $textContent);
        }
    }
    
    /**
     * Send email via SMTP
     */
    private static function sendViaSMTP(string $to, string $subject, string $htmlContent, string $textContent): bool
    {
        $config = self::$config['mail']['smtp'] ?? [];
        
        if (empty($config['host']) || empty($config['port'])) {
            error_log("SMTP configuration incomplete");
            return false;
        }
        
        try {
            // Basic SMTP implementation
            $boundary = md5(uniqid(time()));
            
            $headers = [
                "MIME-Version: 1.0",
                "Content-Type: multipart/alternative; boundary=\"{$boundary}\"",
                "From: " . (self::$config['app']['email'] ?? 'noreply@horizn.app'),
                "Reply-To: " . (self::$config['app']['email'] ?? 'noreply@horizn.app'),
                "X-Mailer: horizn_ Analytics Platform",
                "X-Priority: 1"
            ];
            
            $message = "--{$boundary}\r\n";
            $message .= "Content-Type: text/plain; charset=UTF-8\r\n";
            $message .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
            $message .= $textContent . "\r\n\r\n";
            
            $message .= "--{$boundary}\r\n";
            $message .= "Content-Type: text/html; charset=UTF-8\r\n";
            $message .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
            $message .= $htmlContent . "\r\n\r\n";
            $message .= "--{$boundary}--";
            
            // Connect to SMTP server
            $smtp = fsockopen(
                ($config['encryption'] === 'ssl' ? 'ssl://' : '') . $config['host'],
                $config['port'],
                $errno,
                $errstr,
                10
            );
            
            if (!$smtp) {
                error_log("SMTP connection failed: {$errstr} ({$errno})");
                return false;
            }
            
            // SMTP conversation
            $responses = [];
            $responses[] = fgets($smtp, 512);
            
            fputs($smtp, "HELO " . ($_SERVER['SERVER_NAME'] ?? 'localhost') . "\r\n");
            $responses[] = fgets($smtp, 512);
            
            if (!empty($config['username']) && !empty($config['password'])) {
                fputs($smtp, "AUTH LOGIN\r\n");
                $responses[] = fgets($smtp, 512);
                
                fputs($smtp, base64_encode($config['username']) . "\r\n");
                $responses[] = fgets($smtp, 512);
                
                fputs($smtp, base64_encode($config['password']) . "\r\n");
                $responses[] = fgets($smtp, 512);
            }
            
            fputs($smtp, "MAIL FROM: <" . (self::$config['app']['email'] ?? 'noreply@horizn.app') . ">\r\n");
            $responses[] = fgets($smtp, 512);
            
            fputs($smtp, "RCPT TO: <{$to}>\r\n");
            $responses[] = fgets($smtp, 512);
            
            fputs($smtp, "DATA\r\n");
            $responses[] = fgets($smtp, 512);
            
            fputs($smtp, "Subject: {$subject}\r\n");
            fputs($smtp, implode("\r\n", $headers) . "\r\n\r\n");
            fputs($smtp, $message . "\r\n.\r\n");
            $responses[] = fgets($smtp, 512);
            
            fputs($smtp, "QUIT\r\n");
            $responses[] = fgets($smtp, 512);
            
            fclose($smtp);
            
            // Check if all responses were successful
            foreach ($responses as $response) {
                if (!preg_match('/^2\d\d/', $response) && !preg_match('/^3\d\d/', $response)) {
                    error_log("SMTP error: {$response}");
                    return false;
                }
            }
            
            return true;
            
        } catch (Exception $e) {
            error_log("SMTP sending failed: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Send email via PHP mail() function
     */
    private static function sendViaPHPMail(string $to, string $subject, string $htmlContent, string $textContent): bool
    {
        try {
            $boundary = md5(uniqid(time()));
            
            $headers = [
                "MIME-Version: 1.0",
                "Content-Type: multipart/alternative; boundary=\"{$boundary}\"",
                "From: " . (self::$config['app']['email'] ?? 'noreply@horizn.app'),
                "Reply-To: " . (self::$config['app']['email'] ?? 'noreply@horizn.app'),
                "X-Mailer: horizn_ Analytics Platform",
                "X-Priority: 1"
            ];
            
            $message = "--{$boundary}\r\n";
            $message .= "Content-Type: text/plain; charset=UTF-8\r\n";
            $message .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
            $message .= $textContent . "\r\n\r\n";
            
            $message .= "--{$boundary}\r\n";
            $message .= "Content-Type: text/html; charset=UTF-8\r\n";
            $message .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
            $message .= $htmlContent . "\r\n\r\n";
            $message .= "--{$boundary}--";
            
            return mail($to, $subject, $message, implode("\r\n", $headers));
            
        } catch (Exception $e) {
            error_log("PHP mail() sending failed: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Send email via sendmail
     */
    private static function sendViaSendmail(string $to, string $subject, string $htmlContent, string $textContent): bool
    {
        try {
            $boundary = md5(uniqid(time()));
            
            $headers = "MIME-Version: 1.0\r\n";
            $headers .= "Content-Type: multipart/alternative; boundary=\"{$boundary}\"\r\n";
            $headers .= "From: " . (self::$config['app']['email'] ?? 'noreply@horizn.app') . "\r\n";
            $headers .= "Reply-To: " . (self::$config['app']['email'] ?? 'noreply@horizn.app') . "\r\n";
            $headers .= "X-Mailer: horizn_ Analytics Platform\r\n";
            $headers .= "X-Priority: 1\r\n";
            
            $message = "To: {$to}\r\n";
            $message .= "Subject: {$subject}\r\n";
            $message .= $headers . "\r\n";
            
            $message .= "--{$boundary}\r\n";
            $message .= "Content-Type: text/plain; charset=UTF-8\r\n";
            $message .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
            $message .= $textContent . "\r\n\r\n";
            
            $message .= "--{$boundary}\r\n";
            $message .= "Content-Type: text/html; charset=UTF-8\r\n";
            $message .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
            $message .= $htmlContent . "\r\n\r\n";
            $message .= "--{$boundary}--\r\n";
            
            $sendmail = popen('/usr/sbin/sendmail -t', 'w');
            if (!$sendmail) {
                return false;
            }
            
            fwrite($sendmail, $message);
            $result = pclose($sendmail);
            
            return $result === 0;
            
        } catch (Exception $e) {
            error_log("Sendmail sending failed: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Load email template and replace variables
     */
    private static function loadTemplate(string $templateName, array $variables = []): string
    {
        $templatePath = APP_PATH . '/lib/email-templates/' . $templateName . '.html';
        
        if (!file_exists($templatePath)) {
            // Fallback to inline template
            return self::getInlineMagicLinkTemplate($variables);
        }
        
        $template = file_get_contents($templatePath);
        
        // Replace variables - handle both simple replacement and conditional blocks
        foreach ($variables as $key => $value) {
            $template = str_replace('{{' . $key . '}}', $value, $template);
            
            // Handle conditional blocks for boolean values
            if (is_bool($value)) {
                if ($value) {
                    // Show content for {{#key}}...{{/key}}
                    $template = preg_replace('/\{\{#' . preg_quote($key) . '\}\}(.*?)\{\{\/' . preg_quote($key) . '\}\}/s', '$1', $template);
                    // Remove content for {{^key}}...{{/key}}
                    $template = preg_replace('/\{\{\^' . preg_quote($key) . '\}\}(.*?)\{\{\/\^' . preg_quote($key) . '\}\}/s', '', $template);
                } else {
                    // Remove content for {{#key}}...{{/key}}
                    $template = preg_replace('/\{\{#' . preg_quote($key) . '\}\}(.*?)\{\{\/' . preg_quote($key) . '\}\}/s', '', $template);
                    // Show content for {{^key}}...{{/key}}
                    $template = preg_replace('/\{\{\^' . preg_quote($key) . '\}\}(.*?)\{\{\/\^' . preg_quote($key) . '\}\}/s', '$1', $template);
                }
            }
        }
        
        // Clean up any remaining conditional blocks
        $template = preg_replace('/\{\{[#^][^}]*\}\}.*?\{\{\/[^}]*\}\}/s', '', $template);
        
        return $template;
    }
    
    /**
     * Convert HTML to plain text
     */
    private static function htmlToText(string $html): string
    {
        // Remove HTML tags and convert entities
        $text = html_entity_decode(strip_tags($html), ENT_QUOTES, 'UTF-8');
        
        // Clean up whitespace
        $text = preg_replace('/\s+/', ' ', $text);
        $text = trim($text);
        
        return $text;
    }
    
    /**
     * Inline magic link template (fallback)
     */
    private static function getInlineMagicLinkTemplate(array $variables): string
    {
        $isSetup = $variables['is_setup'] ?? false;
        $userName = $variables['user_name'] ?? 'User';
        $magicLink = $variables['magic_link'] ?? '#';
        $appName = $variables['app_name'] ?? 'horizn_ Analytics';
        $appUrl = $variables['app_url'] ?? 'https://horizn.app';
        $expiresMinutes = $variables['expires_minutes'] ?? 15;
        
        $title = $isSetup ? 'Complete Your Setup' : 'Your Login Link';
        $greeting = $isSetup ? 
            "Welcome to {$appName}! Complete your setup by clicking the link below:" :
            "Click the link below to sign in to your {$appName} dashboard:";
        
        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{$title} - {$appName}</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; margin: 0; padding: 0; background: #0a0a0a; color: #ffffff; }
        .container { max-width: 600px; margin: 0 auto; background: #111111; border: 1px solid #333333; }
        .header { background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%); padding: 40px 30px; text-align: center; }
        .logo { font-size: 32px; font-weight: 800; color: #ffffff; margin: 0; letter-spacing: -0.025em; }
        .content { padding: 40px 30px; }
        .greeting { font-size: 18px; color: #e5e7eb; line-height: 1.6; margin-bottom: 30px; }
        .button { display: inline-block; background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%); color: #ffffff; text-decoration: none; padding: 16px 32px; border-radius: 12px; font-weight: 600; font-size: 16px; text-align: center; margin: 20px 0; transition: all 0.2s; }
        .button:hover { transform: translateY(-1px); box-shadow: 0 8px 25px rgba(99, 102, 241, 0.3); }
        .security { background: #1f2937; border: 1px solid #374151; border-radius: 8px; padding: 20px; margin: 30px 0; }
        .security-title { font-size: 14px; font-weight: 600; color: #f59e0b; margin-bottom: 10px; }
        .security-text { font-size: 14px; color: #9ca3af; line-height: 1.5; }
        .footer { padding: 30px; background: #0f0f0f; border-top: 1px solid #333333; text-align: center; }
        .footer-text { font-size: 14px; color: #6b7280; }
        .footer-link { color: #8b5cf6; text-decoration: none; }
        .footer-link:hover { text-decoration: underline; }
        @media (max-width: 600px) {
            .container { margin: 0; border-radius: 0; }
            .content { padding: 30px 20px; }
            .header { padding: 30px 20px; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1 class="logo">{$appName}</h1>
        </div>
        
        <div class="content">
            <p class="greeting">Hello {$userName},</p>
            <p class="greeting">{$greeting}</p>
            
            <center>
                <a href="{$magicLink}" class="button">{$title}</a>
            </center>
            
            <div class="security">
                <div class="security-title">🔒 Security Notice</div>
                <div class="security-text">
                    • This link expires in {$expiresMinutes} minutes for security<br>
                    • The link can only be used once<br>
                    • If you didn't request this, please ignore this email
                </div>
            </div>
            
            <p style="font-size: 14px; color: #9ca3af; margin-top: 30px;">
                If the button doesn't work, copy and paste this link into your browser:<br>
                <span style="word-break: break-all; color: #8b5cf6;">{$magicLink}</span>
            </p>
        </div>
        
        <div class="footer">
            <p class="footer-text">
                Powered by <a href="{$appUrl}" class="footer-link">{$appName}</a><br>
                First-party analytics that respect user privacy
            </p>
        </div>
    </div>
</body>
</html>
HTML;
    }
    
    /**
     * Log email activity
     */
    private static function logEmailActivity(string $to, string $subject, bool $success): void
    {
        $status = $success ? 'sent' : 'failed';
        $ip = Auth::getClientIP();
        
        error_log("Email {$status} - To: {$to}, Subject: {$subject}, IP: {$ip}");
    }
    
    /**
     * Test email configuration
     */
    public static function testConfiguration(): array
    {
        self::loadConfig();
        
        $testEmail = self::$config['app']['email'] ?? 'test@example.com';
        $testSubject = 'horizn_ Analytics - Email Test';
        $testContent = '<p>This is a test email from horizn_ Analytics platform.</p>';
        
        $result = self::send($testEmail, $testSubject, $testContent);
        
        return [
            'success' => $result,
            'message' => $result ? 'Test email sent successfully' : 'Failed to send test email',
            'method' => self::$config['mail']['method'] ?? 'mail'
        ];
    }
}
?>