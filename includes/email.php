
<?php
/**
 * includes/email.php
 * ─────────────────────────────────────────────────────────────────
 * Central mailer for GOLA — uses PHPMailer + Gmail SMTP.
 * All emails sent through this file will land in the inbox,
 * not spam, because they are authenticated via Google's servers.
 *
 * SETUP (one-time):
 *  1. Enable 2-Step Verification on the Gmail account you use.
 *  2. Go to https://myaccount.google.com/apppasswords
 *  3. Create an App Password for "Mail" on "Other (custom name)".
 *  4. Copy the 16-char password and paste it below.
 *
 * NEVER commit real credentials to GitHub — keep this file in
 * .gitignore or store credentials in an env file outside the
 * web root.
 * ─────────────────────────────────────────────────────────────────
 */

// ── Load PHPMailer ──────────────────────────────────────────────
$phpmailer_base = __DIR__ . '/../vendor/phpmailer/src/';
require_once $phpmailer_base . 'Exception.php';
require_once $phpmailer_base . 'PHPMailer.php';
require_once $phpmailer_base . 'SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// ════════════════════════════════════════════════════════════════
// ▼▼▼  EDIT THESE CREDENTIALS  ▼▼▼
// ════════════════════════════════════════════════════════════════
define('MAIL_USERNAME',    'emmanuelfredrick66@gmail.com');   // Your Gmail address
define('MAIL_APP_PASSWORD','uhse uxdv tevy gntj');     // Gmail App Password (16 chars)
define('MAIL_FROM_NAME',   'GOLA Admissions');         // Display name in inbox
define('MAIL_REPLY_TO',    'golaedu2026@gmail.com');   // Reply-to address
// ════════════════════════════════════════════════════════════════



/**
 * createMailer() — returns a configured PHPMailer instance.
 * You rarely call this directly; use the functions below instead.
 */
function createMailer(): PHPMailer {
    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = MAIL_USERNAME;
    $mail->Password   = MAIL_APP_PASSWORD;
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;
    $mail->CharSet    = 'UTF-8';
    $mail->setFrom(MAIL_USERNAME, MAIL_FROM_NAME);
    $mail->addReplyTo(MAIL_REPLY_TO, MAIL_FROM_NAME);
    return $mail;
}

// ─────────────────────────────────────────────────────────────────
// 1.  PROSPECTUS REQUEST
//     Sends the prospectus PDF to the requester AND notifies admin.
// ─────────────────────────────────────────────────────────────────
/**
 * @param array  $data  Keys: parent_name, email, phone,
 *                            student_name, grade_level, how_heard
 * @param string $prospectus_path  Absolute path to the PDF file
 * @return array ['ok' => bool, 'error' => string]
 */
function sendProspectus(array $data, string $prospectus_path): array {
    try {
        $mail = createMailer();

        // ── Email to the requester ──
        $mail->addAddress($data['email'], $data['parent_name']);
        $mail->Subject = 'GOLA School Prospectus — ' . $data['grade_level'];

        // Attach the PDF
        if (file_exists($prospectus_path)) {
            $mail->addAttachment($prospectus_path, 'GOLA-Prospectus.pdf');
        }

        // Beautiful HTML body
        $mail->isHTML(true);
        $mail->Body = prospectusEmailHTML($data);
        $mail->AltBody = prospectusEmailText($data);
        $mail->send();

        // ── Notify admin ──
        $adminMail = createMailer();
        $adminMail->clearAddresses();
        $adminMail->addAddress(MAIL_USERNAME, 'GOLA Admin');
        $adminMail->Subject = '[GOLA] Prospectus Request — ' . $data['student_name'] . ' (' . $data['grade_level'] . ')';
        $adminMail->isHTML(true);
        $adminMail->Body = adminProspectusNotificationHTML($data);
        $adminMail->AltBody = adminProspectusNotificationText($data);
        $adminMail->send();

        return ['ok' => true, 'error' => ''];
    } catch (Exception $e) {
        error_log('GOLA Mailer [Prospectus]: ' . $e->getMessage());
        return ['ok' => false, 'error' => $e->getMessage()];
    }
}

// ─────────────────────────────────────────────────────────────────
// 2.  CONTACT FORM
//     Sends a "we received your message" reply to the person,
//     and a notification email to admin with full message details.
// ─────────────────────────────────────────────────────────────────
/**
 * @param array $data  Keys: full_name, email, subject, message
 * @return array ['ok' => bool, 'error' => string]
 */
function sendContactNotification(array $data): array {
    try {
        // ── Auto-reply to the sender ──
        $mail = createMailer();
        $mail->addAddress($data['email'], $data['full_name']);
        $mail->addReplyTo(MAIL_REPLY_TO, MAIL_FROM_NAME);
        $mail->Subject  = 'We received your message — GOLA';
        $mail->isHTML(true);
        $mail->Body     = contactAutoReplyHTML($data);
        $mail->AltBody  = contactAutoReplyText($data);
        $mail->send();

        // ── Notification to admin ──
        $adminMail = createMailer();
        $adminMail->clearAddresses();
        $adminMail->addAddress(MAIL_USERNAME, 'GOLA Admin');
        $adminMail->addReplyTo($data['email'], $data['full_name']);
        $adminMail->Subject  = '[GOLA Contact] ' . $data['subject'] . ' — ' . $data['full_name'];
        $adminMail->isHTML(true);
        $adminMail->Body     = contactAdminNotificationHTML($data);
        $adminMail->AltBody  = contactAdminNotificationText($data);
        $adminMail->send();

        return ['ok' => true, 'error' => ''];
    } catch (Exception $e) {
        error_log('GOLA Mailer [Contact]: ' . $e->getMessage());
        return ['ok' => false, 'error' => $e->getMessage()];
    }
}

// ─────────────────────────────────────────────────────────────────
// 3.  APPLICATION SUBMITTED
//     Confirms receipt to the applicant after they submit.
// ─────────────────────────────────────────────────────────────────
/**
 * @param array $data  Keys: full_name, email, application_no,
 *                           grade_applying, session, exam_date, exam_venue
 * @return array ['ok' => bool, 'error' => string]
 */
function sendApplicationConfirmation(array $data): array {
    try {
        $mail = createMailer();
        $mail->addAddress($data['email'], $data['full_name']);
        $mail->Subject  = 'Application Received — ' . $data['application_no'] . ' | GOLA';
        $mail->isHTML(true);
        $mail->Body     = applicationConfirmationHTML($data);
        $mail->AltBody  = applicationConfirmationText($data);
        $mail->send();

        // Admin notification
        $adminMail = createMailer();
        $adminMail->clearAddresses();
        $adminMail->addAddress(MAIL_USERNAME, 'GOLA Admin');
        $adminMail->Subject  = '[GOLA Application] ' . $data['full_name'] . ' — ' . $data['application_no'];
        $adminMail->isHTML(true);
        $adminMail->Body     = "New application received.<br><br>
            <b>Applicant:</b> {$data['full_name']}<br>
            <b>App No:</b> {$data['application_no']}<br>
            <b>Grade:</b> {$data['grade_applying']}<br>
            <b>Email:</b> {$data['email']}<br><br>
            <a href='https://markpayhub.com/admin/manage_admissions.php'>View in Admin Panel →</a>";
        $adminMail->AltBody = "New application: {$data['full_name']} — {$data['application_no']}";
        $adminMail->send();

        return ['ok' => true, 'error' => ''];
    } catch (Exception $e) {
        error_log('GOLA Mailer [Application]: ' . $e->getMessage());
        return ['ok' => false, 'error' => $e->getMessage()];
    }
}


// ═══════════════════════════════════════════════════════════════
//  EMAIL TEMPLATES
// ═══════════════════════════════════════════════════════════════

// ── Shared wrapper ──────────────────────────────────────────────
function emailWrap(string $content, string $preheader = ''): string {
    return '<!DOCTYPE html>
<html><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>GOLA</title>
<style>
body{margin:0;padding:0;background:#f1f5f9;font-family:Inter,Arial,sans-serif;}
.wrap{max-width:580px;margin:32px auto;background:#fff;border-radius:16px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,.08);}
.header{background:#0A2E4D;padding:32px 40px;text-align:center;}
.header img{height:48px;}
.header h1{color:#C5A059;font-size:13px;font-weight:700;letter-spacing:.15em;text-transform:uppercase;margin:10px 0 0;}
.body{padding:36px 40px;color:#334155;font-size:15px;line-height:1.7;}
.body h2{color:#0A2E4D;font-size:22px;font-weight:800;margin:0 0 8px;}
.body p{margin:0 0 16px;}
.badge{display:inline-block;background:#0A2E4D;color:#fff;border-radius:8px;padding:10px 20px;font-size:13px;font-weight:700;letter-spacing:.05em;margin:8px 0 20px;}
.badge span{display:block;font-size:22px;font-weight:900;color:#C5A059;letter-spacing:.12em;margin-top:4px;}
.detail-table{width:100%;border-collapse:collapse;margin:16px 0;}
.detail-table td{padding:8px 12px;font-size:13px;border-bottom:1px solid #f1f5f9;}
.detail-table td:first-child{font-weight:700;color:#64748b;width:38%;text-transform:uppercase;font-size:11px;letter-spacing:.05em;}
.btn{display:inline-block;background:#C5A059;color:#0A2E4D;text-decoration:none;font-weight:800;padding:14px 28px;border-radius:10px;font-size:15px;margin:16px 0;}
.footer{background:#f8fafc;padding:20px 40px;text-align:center;font-size:12px;color:#94a3b8;border-top:1px solid #e2e8f0;}
.footer a{color:#C5A059;text-decoration:none;}
.divider{height:1px;background:#e2e8f0;margin:24px 0;}
</style></head>
<body>
<div style="display:none;font-size:1px;color:#f1f5f9;max-height:0;overflow:hidden;">'.$preheader.'</div>
<div class="wrap">
    <div class="header">
        <div style="font-size:20px;font-weight:900;color:#fff;letter-spacing:.08em;">G.O.L.A.</div>
        <h1>Goodness Omogo Leadership Academy</h1>
    </div>
    <div class="body">'.$content.'</div>
    <div class="footer">
        &copy; '.date('Y').' Goodness Omogo Leadership Academy, Ntezi, Ebonyi State, Nigeria.<br>
        <a href="https://markpayhub.com">markpayhub.com</a> &nbsp;|&nbsp;
        <a href="mailto:golaedu2026@gmail.com">golaedu2026@gmail.com</a>
    </div>
</div>
</body></html>';
}

// ── Prospectus email to requester ───────────────────────────────
function prospectusEmailHTML(array $d): string {
    $name  = htmlspecialchars($d['parent_name']);
    $grade = htmlspecialchars($d['grade_level']);
    $body  = "
        <h2>Hello, $name!</h2>
        <p>Thank you for your interest in <strong>Goodness Omogo Leadership Academy</strong>. We are delighted to share our school prospectus with you.</p>
        <p>Please find the <strong>GOLA School Prospectus</strong> attached to this email as a PDF. It contains everything you need to know about:</p>
        <ul style='margin:0 0 16px;padding-left:20px;'>
            <li>Our academic programmes and curriculum</li>
            <li>Boarding facilities and daily life at GOLA</li>
            <li>Leadership and extracurricular programmes</li>
            <li>The full admissions process and requirements</li>
            <li>Fee structure for the current academic session</li>
        </ul>
        <div class='badge'>Enquiry for Grade: <span>$grade</span></div>
        <p>When you are ready to apply, visit our website to complete the full online application form:</p>
        <a href='https://markpayhub.com/admissions_form.php' class='btn'>Start Application →</a>
        <div class='divider'></div>
        <p style='font-size:13px;color:#64748b;'>If you have any questions, simply reply to this email or call us on <strong>09125128213</strong>. Our admissions team is available Monday–Friday, 8:00 AM–4:00 PM.</p>
    ";
    return emailWrap($body, 'Your GOLA prospectus is attached — everything about our school in one document.');
}
function prospectusEmailText(array $d): string {
    return "Hello {$d['parent_name']},\n\nThank you for requesting the GOLA School Prospectus. Please find it attached to this email.\n\nIf you have questions, reply to this email or call 09125128213.\n\nGOLA Admissions Team";
}

// ── Prospectus admin notification ──────────────────────────────
function adminProspectusNotificationHTML(array $d): string {
    $body = "<h2>New Prospectus Request</h2>
        <p>Someone has requested the school prospectus. The prospectus has been sent to their email automatically.</p>
        <table class='detail-table'>
            <tr><td>Parent/Guardian</td><td>".htmlspecialchars($d['parent_name'])."</td></tr>
            <tr><td>Email</td><td>".htmlspecialchars($d['email'])."</td></tr>
            <tr><td>Phone</td><td>".htmlspecialchars($d['phone'] ?? '—')."</td></tr>
            <tr><td>Student Name</td><td>".htmlspecialchars($d['student_name'])."</td></tr>
            <tr><td>Grade Interested</td><td>".htmlspecialchars($d['grade_level'])."</td></tr>
            <tr><td>How Heard</td><td>".htmlspecialchars($d['how_heard'] ?? '—')."</td></tr>
        </table>
        <a href='https://markpayhub.com/admin/manage_prospectus_requests.php' class='btn'>View in Admin Panel →</a>";
    return emailWrap($body, 'New prospectus request from '.$d['parent_name']);
}
function adminProspectusNotificationText(array $d): string {
    return "New prospectus request:\n\nParent: {$d['parent_name']}\nEmail: {$d['email']}\nPhone: ".($d['phone']??'—')."\nStudent: {$d['student_name']}\nGrade: {$d['grade_level']}\nHow heard: ".($d['how_heard']??'—');
}

// ── Contact auto-reply ──────────────────────────────────────────
function contactAutoReplyHTML(array $d): string {
    $name    = htmlspecialchars($d['full_name']);
    $subject = htmlspecialchars($d['subject']);
    $message = nl2br(htmlspecialchars($d['message']));
    $body    = "
        <h2>Thank you, $name!</h2>
        <p>We have received your message and our team will get back to you within <strong>24 hours</strong> (Monday–Friday).</p>
        <div class='divider'></div>
        <p style='font-size:13px;color:#64748b;font-weight:700;text-transform:uppercase;letter-spacing:.05em;'>Your message:</p>
        <table class='detail-table'>
            <tr><td>Subject</td><td>$subject</td></tr>
            <tr><td>Message</td><td>$message</td></tr>
        </table>
        <div class='divider'></div>
        <p>While you wait, feel free to explore our school:</p>
        <a href='https://markpayhub.com/admissions.php' class='btn'>Learn About Admissions →</a>
        <p style='font-size:13px;color:#64748b;margin-top:20px;'>You can also reach us directly:<br>
        📞 <strong>09125128213</strong><br>
        ✉️ <strong>golaedu2026@gmail.com</strong></p>
    ";
    return emailWrap($body, 'We\'ve received your message and will reply within 24 hours.');
}
function contactAutoReplyText(array $d): string {
    return "Hello {$d['full_name']},\n\nThank you for contacting GOLA. We have received your message and will respond within 24 hours.\n\nYour message:\nSubject: {$d['subject']}\n\n{$d['message']}\n\nGOLA Team\n09125128213";
}

// ── Contact admin notification ──────────────────────────────────
function contactAdminNotificationHTML(array $d): string {
    $name    = htmlspecialchars($d['full_name']);
    $email   = htmlspecialchars($d['email']);
    $subject = htmlspecialchars($d['subject']);
    $message = nl2br(htmlspecialchars($d['message']));
    $body    = "
        <h2>New Contact Form Message</h2>
        <p>Someone has sent a message through the website contact form. Reply directly to this email to respond to them.</p>
        <table class='detail-table'>
            <tr><td>Name</td><td>$name</td></tr>
            <tr><td>Email</td><td><a href='mailto:$email'>$email</a></td></tr>
            <tr><td>Subject</td><td>$subject</td></tr>
        </table>
        <p style='font-weight:700;color:#64748b;font-size:13px;text-transform:uppercase;letter-spacing:.05em;margin-top:20px;'>Message:</p>
        <div style='background:#f8fafc;border-left:4px solid #C5A059;border-radius:0 8px 8px 0;padding:16px 20px;font-size:14px;line-height:1.7;color:#334155;'>$message</div>
        <p style='margin-top:20px;font-size:13px;color:#64748b;'>Simply <strong>reply to this email</strong> to respond — it will go directly to $email.</p>
    ";
    return emailWrap($body, 'New contact form submission from '.$d['full_name']);
}
function contactAdminNotificationText(array $d): string {
    return "New contact form message:\n\nFrom: {$d['full_name']} <{$d['email']}>\nSubject: {$d['subject']}\n\nMessage:\n{$d['message']}\n\nReply to this email to respond.";
}

// ── Application confirmation ────────────────────────────────────
function applicationConfirmationHTML(array $d): string {
    $name   = htmlspecialchars($d['full_name']);
    $appno  = htmlspecialchars($d['application_no']);
    $grade  = htmlspecialchars($d['grade_applying']);
    $exam   = !empty($d['exam_date']) ? date('l, F j, Y', strtotime($d['exam_date'])) : '';
    $venue  = htmlspecialchars($d['exam_venue'] ?? '');
    $body   = "
        <h2>Application Received, $name!</h2>
        <p>Your boarding entrance application has been received successfully. Please save your application number:</p>
        <div class='badge'>Application Number<span>$appno</span></div>
        <table class='detail-table'>
            <tr><td>Applicant</td><td>$name</td></tr>
            <tr><td>Grade Applied</td><td>$grade</td></tr>
            <tr><td>Session</td><td>".htmlspecialchars($d['session'] ?? '')."</td></tr>
        </table>
        ".($exam ? "<p>🗓 <strong>Entrance Exam:</strong> $exam".($venue ? " at $venue" : '')."</p>" : "")."
        <div class='divider'></div>
        <p><strong>What happens next?</strong></p>
        <ol style='padding-left:20px;margin:0 0 16px;font-size:14px;line-height:1.8;'>
            <li>Our admissions team will verify your payment.</li>
            <li>You will receive an exam date and venue confirmation.</li>
            <li>Shortlisted candidates will be invited for entrance examination.</li>
            <li>Successful candidates receive an offer letter within 14 days.</li>
        </ol>
        <p style='font-size:13px;color:#64748b;'>Questions? Reply to this email or call <strong>09125128213</strong>.</p>
    ";
    return emailWrap($body, 'Application '.$appno.' confirmed — keep this number safe.');
}
function applicationConfirmationText(array $d): string {
    return "Hello {$d['full_name']},\n\nYour application has been received.\n\nApplication Number: {$d['application_no']}\nGrade: {$d['grade_applying']}\n\nWe will contact you with the entrance exam details.\n\nGOLA Admissions\n09125128213";
}

// ─────────────────────────────────────────────────────────────────
// 4.  STATUS UPDATE EMAIL
//     Called from manage_admissions.php whenever admin changes
//     an application status. Each status gets its own message.
//
//     Statuses handled:
//       Under Review → "We are reviewing your application"
//       Shortlisted  → "You are shortlisted — here is your exam number"
//       Admitted     → "Congratulations — you have been admitted"
//       Rejected     → "Unfortunately you were not successful this time"
//       Enrolled     → "Welcome to GOLA — your student ID is ready"
// ─────────────────────────────────────────────────────────────────
/**
 * @param string $status   One of the status values above
 * @param array  $data     Keys: full_name, email, application_no,
 *                               exam_no, grade_applying, session,
 *                               exam_date, exam_venue, admin_notes,
 *                               student_id (for Enrolled only)
 * @return array ['ok' => bool, 'error' => string]
 */
function sendStatusUpdateEmail(string $status, array $data): array {
    try {
        $mail = createMailer();
        $mail->addAddress($data['email'], $data['full_name']);

        switch ($status) {
            case 'Under Review':
                $mail->Subject = 'Application Under Review — ' . $data['application_no'] . ' | GOLA';
                $mail->Body    = statusUnderReviewHTML($data);
                $mail->AltBody = statusUnderReviewText($data);
                break;

            case 'Shortlisted':
                $mail->Subject = "You've Been Shortlisted — Exam Details Inside | GOLA";
                $mail->Body    = statusShortlistedHTML($data);
                $mail->AltBody = statusShortlistedText($data);
                break;

            case 'Admitted':
                $mail->Subject = "🎉 Congratulations! You've Been Admitted to GOLA";
                $mail->Body    = statusAdmittedHTML($data);
                $mail->AltBody = statusAdmittedText($data);
                break;

            case 'Rejected':
                $mail->Subject = 'Update on Your GOLA Application — ' . $data['application_no'];
                $mail->Body    = statusRejectedHTML($data);
                $mail->AltBody = statusRejectedText($data);
                break;

            case 'Enrolled':
                $mail->Subject = 'Welcome to GOLA! Your Student ID is Ready';
                $mail->Body    = statusEnrolledHTML($data);
                $mail->AltBody = statusEnrolledText($data);
                break;

            default:
                return ['ok' => false, 'error' => 'Unknown status: ' . $status];
        }

        $mail->isHTML(true);
        $mail->send();
        return ['ok' => true, 'error' => ''];

    } catch (Exception $e) {
        error_log('GOLA Mailer [Status ' . $status . ']: ' . $e->getMessage());
        return ['ok' => false, 'error' => $e->getMessage()];
    }
}


// ── Under Review ────────────────────────────────────────────────
function statusUnderReviewHTML(array $d): string {
    $name  = htmlspecialchars($d['full_name']);
    $appno = htmlspecialchars($d['application_no']);
    $grade = htmlspecialchars($d['grade_applying']);
    $body  = "
        <h2>Hello, $name!</h2>
        <p>Thank you for submitting your application to <strong>Goodness Omogo Leadership Academy</strong>. We can confirm that your application is now being reviewed by our admissions team.</p>
        <div class='badge'>Application Number<span>$appno</span></div>
        <table class='detail-table'>
            <tr><td>Grade Applied</td><td>$grade</td></tr>
            <tr><td>Session</td><td>".htmlspecialchars($d['session'])."</td></tr>
            <tr><td>Current Status</td><td>Under Review</td></tr>
        </table>
        <div class='divider'></div>
        <p><strong>What happens next?</strong></p>
        <p>Our team will carefully review your application and supporting documents. You will receive another email as soon as a decision has been made. This typically takes <strong>5–10 working days</strong>.</p>
        <p style='font-size:13px;color:#64748b;'>If you have any questions, reply to this email or call <strong>09125128213</strong>.</p>
    ";
    return emailWrap($body, 'Your GOLA application is currently under review.');
}
function statusUnderReviewText(array $d): string {
    return "Hello {$d['full_name']},

Your application ({$d['application_no']}) is now under review. We will contact you with a decision within 5–10 working days.

GOLA Admissions
09125128213";
}


// ── Shortlisted ─────────────────────────────────────────────────
function statusShortlistedHTML(array $d): string {
    $name    = htmlspecialchars($d['full_name']);
    $appno   = htmlspecialchars($d['application_no']);
    $exam_no = htmlspecialchars($d['exam_no'] ?? '');
    $grade   = htmlspecialchars($d['grade_applying']);
    $exam_date  = !empty($d['exam_date'])  ? date('l, F j, Y', strtotime($d['exam_date']))  : 'To be announced';
    $exam_venue = !empty($d['exam_venue']) ? htmlspecialchars($d['exam_venue']) : 'GOLA Main Campus, Ntezi, Ebonyi State';

    $body = "
        <h2>Great News, $name!</h2>
        <p>We are pleased to inform you that your application to Goodness Omogo Leadership Academy has been <strong>shortlisted</strong>. You are invited to sit our entrance examination.</p>

        ".($exam_no ? "
        <div style='background:#0A2E4D;border-radius:12px;padding:20px 28px;margin:20px 0;text-align:center;'>
            <p style='color:#C5A059;font-size:11px;font-weight:700;letter-spacing:.15em;text-transform:uppercase;margin:0 0 6px;'>Your Entrance Examination Number</p>
            <p style='color:#fff;font-size:36px;font-weight:900;font-family:monospace;letter-spacing:.15em;margin:0;'>$exam_no</p>
            <p style='color:rgba(255,255,255,.5);font-size:11px;margin:8px 0 0;'>Present this number on exam day — keep it safe</p>
        </div>" : "")."

        <table class='detail-table'>
            <tr><td>Application No</td><td>$appno</td></tr>
            <tr><td>Grade Applied</td><td>$grade</td></tr>
            <tr><td>Exam Date</td><td><strong>$exam_date</strong></td></tr>
            <tr><td>Exam Venue</td><td><strong>$exam_venue</strong></td></tr>
        </table>

        <div class='divider'></div>
        <p><strong>What to bring on exam day:</strong></p>
        <ul style='padding-left:20px;margin:0 0 16px;font-size:14px;line-height:1.9;'>
            <li>This email (printed or on your phone) showing your exam number</li>
            <li>Two recent passport photographs</li>
            <li>Copy of your last school result</li>
            <li>A valid means of identification (birth certificate or school ID)</li>
            <li>Writing materials (pens, pencils, ruler)</li>
        </ul>
        <p style='font-size:13px;color:#64748b;'>Please arrive at least <strong>30 minutes early</strong>. Late arrivals may not be admitted to the hall.</p>
        <p style='font-size:13px;color:#64748b;'>Questions? Reply to this email or call <strong>09125128213</strong>.</p>
    ";
    return emailWrap($body, "You've been shortlisted! Your exam number is inside.");
}
function statusShortlistedText(array $d): string {
    $exam_no = $d['exam_no'] ?? 'To be assigned';
    $exam_date = !empty($d['exam_date']) ? date('d M Y', strtotime($d['exam_date'])) : 'TBA';
    return "Hello {$d['full_name']},

Congratulations! You have been shortlisted for the GOLA entrance examination.

Exam Number: $exam_no
Exam Date: $exam_date
Venue: ".($d['exam_venue']?:'GOLA Main Campus, Ntezi, Ebonyi State')."

Bring this email, passport photos, last school result and writing materials.

GOLA Admissions
09125128213";
}


// ── Admitted ────────────────────────────────────────────────────
function statusAdmittedHTML(array $d): string {
    $name  = htmlspecialchars($d['full_name']);
    $appno = htmlspecialchars($d['application_no']);
    $grade = htmlspecialchars($d['grade_applying']);
    $notes = !empty($d['admin_notes']) ? '<p style="background:#f0fdf4;border-left:4px solid #16a34a;border-radius:0 8px 8px 0;padding:12px 16px;font-size:14px;color:#166534;">'.nl2br(htmlspecialchars($d['admin_notes'])).'</p>' : '';

    $body = "
        <h2 style='color:#166534;'>🎉 Congratulations, $name!</h2>
        <p>We are absolutely delighted to inform you that you have been <strong>offered admission</strong> to Goodness Omogo Leadership Academy for the <strong>{$d['session']}</strong> academic session.</p>

        <div class='badge'>Application Number<span>$appno</span></div>

        <table class='detail-table'>
            <tr><td>Admitted For</td><td>$grade</td></tr>
            <tr><td>Session</td><td>".htmlspecialchars($d['session'])."</td></tr>
            <tr><td>Status</td><td style='color:#166534;font-weight:700;'>✅ ADMITTED</td></tr>
        </table>

        $notes

        <div class='divider'></div>
        <p><strong>Next steps to secure your place:</strong></p>
        <ol style='padding-left:20px;margin:0 0 16px;font-size:14px;line-height:1.9;'>
            <li>Pay the <strong>acceptance fee</strong> within 14 days to confirm your place.</li>
            <li>Complete and return the boarding registration forms.</li>
            <li>Submit all original documents to the school office.</li>
            <li>Collect your resumption date and welcome pack details.</li>
        </ol>

        <a href='https://markpayhub.com/admissions.php' class='btn'>Visit Admissions Page →</a>

        <p style='font-size:13px;color:#64748b;margin-top:20px;'>To pay the acceptance fee or for any enquiries, contact us:<br>
        📞 <strong>09125128213</strong><br>
        ✉️ <strong>golaedu2026@gmail.com</strong></p>
    ";
    return emailWrap($body, 'Congratulations! You have been admitted to GOLA.');
}
function statusAdmittedText(array $d): string {
    return "Congratulations {$d['full_name']}!

You have been offered admission to GOLA for the {$d['session']} session.

Application No: {$d['application_no']}
Grade: {$d['grade_applying']}

Next steps:
1. Pay the acceptance fee within 14 days.
2. Submit all original documents.
3. Contact us for resumption details.

GOLA Admissions
09125128213
golaedu2026@gmail.com";
}


// ── Rejected ────────────────────────────────────────────────────
function statusRejectedHTML(array $d): string {
    $name  = htmlspecialchars($d['full_name']);
    $appno = htmlspecialchars($d['application_no']);
    $notes = !empty($d['admin_notes']) ? '<p style="background:#fef9f0;border-left:4px solid #C5A059;border-radius:0 8px 8px 0;padding:12px 16px;font-size:14px;color:#78350f;">'.nl2br(htmlspecialchars($d['admin_notes'])).'</p>' : '';

    $body = "
        <h2>Dear $name,</h2>
        <p>Thank you for your interest in Goodness Omogo Leadership Academy and for taking the time to apply.</p>
        <p>After careful review, we regret to inform you that we are <strong>unable to offer you a place</strong> at this time for the <strong>{$d['session']}</strong> academic session.</p>

        <div class='badge'>Application Number<span>$appno</span></div>

        $notes

        <div class='divider'></div>
        <p>This decision does not reflect on your potential or abilities. We receive many strong applications and the selection process is highly competitive.</p>
        <p><strong>You are welcome to reapply</strong> in a future intake. Please contact our admissions office if you would like feedback or guidance on future applications.</p>

        <p style='font-size:13px;color:#64748b;margin-top:20px;'>
        📞 <strong>09125128213</strong> &nbsp;|&nbsp; ✉️ <strong>golaedu2026@gmail.com</strong></p>
        <p style='font-size:13px;color:#64748b;'>We wish you every success in your academic journey.</p>
    ";
    return emailWrap($body, 'Update on your GOLA application ' . $d['application_no']);
}
function statusRejectedText(array $d): string {
    return "Dear {$d['full_name']},

Thank you for applying to GOLA. After careful review, we are unable to offer you a place at this time for the {$d['session']} session.

You are welcome to reapply in a future intake. Please contact us for feedback.

09125128213 | golaedu2026@gmail.com

We wish you every success.";
}


// ── Enrolled ────────────────────────────────────────────────────
function statusEnrolledHTML(array $d): string {
    $name       = htmlspecialchars($d['full_name']);
    $appno      = htmlspecialchars($d['application_no']);
    $student_id = htmlspecialchars($d['student_id'] ?? '');
    $grade      = htmlspecialchars($d['grade_applying']);

    $body = "
        <h2 style='color:#0A2E4D;'>Welcome to GOLA, $name!</h2>
        <p>We are thrilled to welcome you to the <strong>Goodness Omogo Leadership Academy</strong> family. Your enrolment is now complete.</p>

        ".($student_id ? "
        <div style='background:#0A2E4D;border-radius:12px;padding:20px 28px;margin:20px 0;text-align:center;'>
            <p style='color:#C5A059;font-size:11px;font-weight:700;letter-spacing:.15em;text-transform:uppercase;margin:0 0 6px;'>Your Student ID</p>
            <p style='color:#fff;font-size:32px;font-weight:900;font-family:monospace;letter-spacing:.12em;margin:0;'>$student_id</p>
            <p style='color:rgba(255,255,255,.5);font-size:11px;margin:8px 0 0;'>Use this ID for all school activities and result checking</p>
        </div>" : "")."

        <table class='detail-table'>
            <tr><td>Application No</td><td>$appno</td></tr>
            <tr><td>Class</td><td>$grade</td></tr>
            <tr><td>Session</td><td>".htmlspecialchars($d['session'])."</td></tr>
            <tr><td>Status</td><td style='color:#0A2E4D;font-weight:700;'>🎓 ENROLLED</td></tr>
        </table>

        <div class='divider'></div>
        <p><strong>Before resumption, please ensure you have:</strong></p>
        <ul style='padding-left:20px;margin:0 0 16px;font-size:14px;line-height:1.9;'>
            <li>Paid all required school fees</li>
            <li>Submitted all outstanding original documents</li>
            <li>Obtained the school uniform from the approved supplier</li>
            <li>Reviewed the school rules and boarding handbook</li>
        </ul>

        <p style='font-size:13px;color:#64748b;'>Contact us with any questions:<br>
        📞 <strong>09125128213</strong><br>
        ✉️ <strong>golaedu2026@gmail.com</strong></p>
        <p style='font-size:13px;color:#64748b;'>We look forward to an excellent journey together. <em>To Learn, To Grow, To Lead.</em></p>
    ";
    return emailWrap($body, 'Welcome to GOLA! Your student ID is ready.');
}
function statusEnrolledText(array $d): string {
    $sid = isset($d['student_id']) ? "
Student ID: {$d['student_id']}" : '';
    return "Welcome to GOLA, {$d['full_name']}!

Your enrolment is complete.$sid
Application No: {$d['application_no']}
Class: {$d['grade_applying']}
Session: {$d['session']}

Please ensure all fees and documents are submitted before resumption.

GOLA
09125128213";
}