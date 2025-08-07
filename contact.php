<?php
/**
 * Contact Form Handler
 * Professional contact form for Dr. Parth's medical practice
 * 
 * @author Dr. Parth's Medical Practice
 * @version 2.0
 */

// Initialize variables
$form_data = [
    'name' => '',
    'email' => '',
    'phone' => '',
    'subject' => 'General Inquiry',
    'message' => ''
];

$errors = [];
$success_message = '';

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize and validate input data
    $form_data = [
        'name' => trim($_POST['name'] ?? ''),
        'email' => trim($_POST['email'] ?? ''),
        'phone' => trim($_POST['phone'] ?? ''),
        'subject' => trim($_POST['subject'] ?? 'General Inquiry'),
        'message' => trim($_POST['message'] ?? '')
    ];

    // Validate required fields
    if (empty($form_data['name'])) {
        $errors['name'] = 'Please enter your full name';
    } elseif (strlen($form_data['name']) < 2) {
        $errors['name'] = 'Name must be at least 2 characters long';
    }

    if (empty($form_data['email'])) {
        $errors['email'] = 'Please enter your email address';
    } elseif (!filter_var($form_data['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Please enter a valid email address';
    }

    if (empty($form_data['message'])) {
        $errors['message'] = 'Please enter your message';
    } elseif (strlen($form_data['message']) < 10) {
        $errors['message'] = 'Message must be at least 10 characters long';
    }

    // Validate phone number (optional field)
    if (!empty($form_data['phone'])) {
        $phone_clean = preg_replace('/[^0-9+\-\(\)\s]/', '', $form_data['phone']);
        if (strlen($phone_clean) < 10) {
            $errors['phone'] = 'Please enter a valid phone number';
        }
    }

    // Validate subject
    $valid_subjects = ['General Inquiry', 'Appointment', 'Billing', 'Emergency', 'Other'];
    if (!in_array($form_data['subject'], $valid_subjects)) {
        $errors['subject'] = 'Please select a valid subject';
    }

    // If no validation errors, process the form
    if (empty($errors)) {
        try {
            // Prepare email content
            $to = 'contact@drparth.com'; // Replace with actual email
            $subject = 'New Contact Form Submission: ' . htmlspecialchars($form_data['subject']);
            
            // Create HTML email body
            $email_body = createEmailBody($form_data);
            
            // Set email headers
            $headers = [
                'From: ' . $form_data['email'],
                'Reply-To: ' . $form_data['email'],
                'MIME-Version: 1.0',
                'Content-Type: text/html; charset=UTF-8',
                'X-Mailer: PHP/' . phpversion()
            ];

            // Send email
            $mail_sent = mail($to, $subject, $email_body, implode("\r\n", $headers));

            if ($mail_sent) {
                $success_message = 'Thank you for your message! We will respond within 24 hours.';
                // Clear form data after successful submission
                $form_data = [
                    'name' => '',
                    'email' => '',
                    'phone' => '',
                    'subject' => 'General Inquiry',
                    'message' => ''
                ];
                
                // Log successful submission (optional)
                logContactSubmission($form_data, true);
            } else {
                $errors['general'] = 'Sorry, there was an error sending your message. Please try again or contact us directly.';
                logContactSubmission($form_data, false);
            }
        } catch (Exception $e) {
            $errors['general'] = 'An unexpected error occurred. Please try again later.';
            error_log('Contact form error: ' . $e->getMessage());
        }
    }
}

/**
 * Create HTML email body for contact form submission
 */
function createEmailBody($data) {
    $timestamp = date('Y-m-d H:i:s');
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
    
    return "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: #2a6496; color: white; padding: 20px; text-align: center; }
            .content { background: #f9f9f9; padding: 20px; }
            .field { margin-bottom: 15px; }
            .label { font-weight: bold; color: #2a6496; }
            .footer { background: #eee; padding: 15px; text-align: center; font-size: 12px; color: #666; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h2>New Contact Form Submission</h2>
                <p>Dr. Parth's Medical Practice</p>
            </div>
            <div class='content'>
                <div class='field'>
                    <span class='label'>Name:</span> " . htmlspecialchars($data['name']) . "
                </div>
                <div class='field'>
                    <span class='label'>Email:</span> " . htmlspecialchars($data['email']) . "
                </div>
                <div class='field'>
                    <span class='label'>Phone:</span> " . htmlspecialchars($data['phone'] ?: 'Not provided') . "
                </div>
                <div class='field'>
                    <span class='label'>Subject:</span> " . htmlspecialchars($data['subject']) . "
                </div>
                <div class='field'>
                    <span class='label'>Message:</span><br>
                    " . nl2br(htmlspecialchars($data['message'])) . "
                </div>
                <hr>
                <div class='field'>
                    <span class='label'>Submitted:</span> $timestamp<br>
                    <span class='label'>IP Address:</span> $ip_address
                </div>
            </div>
            <div class='footer'>
                <p>This message was sent from the contact form on Dr. Parth's website.</p>
            </div>
        </div>
    </body>
    </html>";
}

/**
 * Log contact form submissions for monitoring
 */
function logContactSubmission($data, $success) {
    $log_entry = date('Y-m-d H:i:s') . ' | ' . 
                 ($success ? 'SUCCESS' : 'FAILED') . ' | ' .
                 'Name: ' . $data['name'] . ' | ' .
                 'Email: ' . $data['email'] . ' | ' .
                 'Subject: ' . $data['subject'] . ' | ' .
                 'IP: ' . ($_SERVER['REMOTE_ADDR'] ?? 'Unknown') . "\n";
    
    $log_file = __DIR__ . '/logs/contact_form.log';
    $log_dir = dirname($log_file);
    
    // Create logs directory if it doesn't exist
    if (!is_dir($log_dir)) {
        mkdir($log_dir, 0755, true);
    }
    
    file_put_contents($log_file, $log_entry, FILE_APPEND | LOCK_EX);
}

// Include header
include('includes/header.php');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Contact Dr. Parth for appointments, consultations, and medical inquiries. Professional healthcare services available.">
    <meta name="keywords" content="contact, appointment, Dr. Parth, medical consultation, healthcare">
    <title>Contact Dr. Parth - Professional Medical Consultation</title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="assets/images/favicon.ico">
    
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary-color: #2a6496;
            --primary-hover: #1d4b75;
            --secondary-color: #4a90e2;
            --accent-color: #e74c3c;
            --success-color: #28a745;
            --warning-color: #ffc107;
            --error-color: #dc3545;
            --light-gray: #f8f9fa;
            --medium-gray: #e9ecef;
            --dark-gray: #495057;
            --text-color: #333;
            --border-radius: 8px;
            --box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            --transition: all 0.3s ease;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            line-height: 1.6;
            color: var(--text-color);
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
        }

        .main-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }

        .page-header {
            text-align: center;
            margin-bottom: 3rem;
            padding: 2rem 0;
        }

        .page-header h1 {
            color: var(--primary-color);
            font-size: clamp(2rem, 5vw, 3rem);
            font-weight: 700;
            margin-bottom: 1rem;
            letter-spacing: -0.02em;
        }

        .page-header p {
            font-size: 1.1rem;
            color: var(--dark-gray);
            max-width: 600px;
            margin: 0 auto;
            line-height: 1.7;
        }

        .container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 3rem;
            margin-bottom: 3rem;
        }

        .contact-form {
            background: white;
            padding: 2.5rem;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            position: relative;
            overflow: hidden;
        }

        .contact-form::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary-color), var(--secondary-color));
        }

        .form-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--primary-color);
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
            position: relative;
        }

        label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: var(--dark-gray);
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .required-field::after {
            content: ' *';
            color: var(--error-color);
            font-weight: 700;
        }

        input,
        textarea,
        select {
            width: 100%;
            padding: 0.875rem 1rem;
            border: 2px solid var(--medium-gray);
            border-radius: var(--border-radius);
            font-size: 1rem;
            font-family: inherit;
            transition: var(--transition);
            background-color: white;
        }

        input:focus,
        textarea:focus,
        select:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(42, 100, 150, 0.1);
        }

        textarea {
            height: 120px;
            resize: vertical;
            line-height: 1.5;
        }

        select {
            cursor: pointer;
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='m6 8 4 4 4-4'/%3e%3c/svg%3e");
            background-position: right 0.5rem center;
            background-repeat: no-repeat;
            background-size: 1.5em 1.5em;
            padding-right: 2.5rem;
        }

        .btn {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            border: none;
            padding: 1rem 2rem;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            border-radius: var(--border-radius);
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            width: 100%;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(42, 100, 150, 0.3);
        }

        .btn:active {
            transform: translateY(0);
        }

        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }

        .error {
            color: var(--error-color);
            font-size: 0.875rem;
            margin-top: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }

        .error-input {
            border-color: var(--error-color) !important;
            box-shadow: 0 0 0 3px rgba(220, 53, 69, 0.1) !important;
        }

        .success-message {
            background: linear-gradient(135deg, rgba(40, 167, 69, 0.1), rgba(40, 167, 69, 0.05));
            color: var(--success-color);
            padding: 1rem;
            border-radius: var(--border-radius);
            margin-bottom: 1.5rem;
            border-left: 4px solid var(--success-color);
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .error-message {
            background: linear-gradient(135deg, rgba(220, 53, 69, 0.1), rgba(220, 53, 69, 0.05));
            color: var(--error-color);
            padding: 1rem;
            border-radius: var(--border-radius);
            margin-bottom: 1.5rem;
            border-left: 4px solid var(--error-color);
        }

        .error-message ul {
            margin: 0.5rem 0 0 1.5rem;
        }

        .contact-info {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .contact-card {
            background: white;
            padding: 1.5rem;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            transition: var(--transition);
            border-left: 4px solid var(--primary-color);
        }

        .contact-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }

        .contact-card h3 {
            color: var(--primary-color);
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .contact-card h3 i {
            color: var(--secondary-color);
            font-size: 1.1rem;
        }

        .contact-card p {
            margin: 0.5rem 0;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .contact-card a {
            color: var(--primary-color);
            text-decoration: none;
            transition: var(--transition);
        }

        .contact-card a:hover {
            color: var(--primary-hover);
            text-decoration: underline;
        }

        .contact-icon {
            color: var(--secondary-color);
            width: 1rem;
            text-align: center;
        }

        .business-hours {
            list-style: none;
            padding: 0;
        }

        .business-hours li {
            display: flex;
            justify-content: space-between;
            padding: 0.5rem 0;
            border-bottom: 1px solid var(--medium-gray);
        }

        .business-hours li:last-child {
            border-bottom: none;
        }

        .day {
            font-weight: 500;
            color: var(--dark-gray);
        }

        .hours {
            color: var(--text-color);
            font-weight: 600;
        }

        .map-container {
            margin-top: 1rem;
        }

        .map-container iframe {
            width: 100%;
            height: 250px;
            border: none;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
        }

        .loading {
            display: none;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .spinner {
            width: 1rem;
            height: 1rem;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-top: 2px solid white;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .main-container {
                padding: 1rem;
            }

            .container {
                grid-template-columns: 1fr;
                gap: 2rem;
            }

            .contact-form {
                padding: 1.5rem;
            }

            .page-header {
                margin-bottom: 2rem;
                padding: 1rem 0;
            }
        }

        @media (max-width: 480px) {
            .contact-form {
                padding: 1rem;
            }

            .btn {
                padding: 0.875rem 1.5rem;
            }
        }

        /* Accessibility */
        @media (prefers-reduced-motion: reduce) {
            * {
                animation-duration: 0.01ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: 0.01ms !important;
            }
        }

        /* Focus styles for keyboard navigation */
        .btn:focus,
        input:focus,
        textarea:focus,
        select:focus {
            outline: 2px solid var(--primary-color);
            outline-offset: 2px;
        }
    </style>
</head>

<body>
    <div class="main-container">
        <div class="page-header">
            <h1>Contact Dr. Parth</h1>
            <p>We're here to help with your healthcare needs. Send us a message and we'll respond within 24 hours during business days.</p>
        </div>

        <div class="container">
            <div class="contact-form">
                <h2 class="form-title">
                    <i class="fas fa-envelope"></i>
                    Send us a Message
                </h2>

                <?php if (!empty($errors) && is_array($errors)): ?>
                    <div class="error-message">
                        <h4><i class="fas fa-exclamation-triangle"></i> Please correct the following:</h4>
                        <ul>
                            <?php foreach ($errors as $field => $error): ?>
                                <li><?php echo htmlspecialchars($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <?php if ($success_message): ?>
                    <div class="success-message">
                        <i class="fas fa-check-circle"></i>
                        <?php echo htmlspecialchars($success_message); ?>
                    </div>
                <?php endif; ?>

                <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post" id="contactForm" novalidate>
                    <div class="form-group">
                        <label for="name" class="required-field">Full Name</label>
                        <input 
                            type="text" 
                            id="name" 
                            name="name" 
                            value="<?php echo htmlspecialchars($form_data['name']); ?>"
                            class="<?php echo isset($errors['name']) ? 'error-input' : ''; ?>"
                            placeholder="Enter your full name"
                            required
                            autocomplete="name"
                        >
                        <?php if (isset($errors['name'])): ?>
                            <span class="error">
                                <i class="fas fa-exclamation-circle"></i>
                                <?php echo htmlspecialchars($errors['name']); ?>
                            </span>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label for="email" class="required-field">Email Address</label>
                        <input 
                            type="email" 
                            id="email" 
                            name="email" 
                            value="<?php echo htmlspecialchars($form_data['email']); ?>"
                            class="<?php echo isset($errors['email']) ? 'error-input' : ''; ?>"
                            placeholder="Enter your email address"
                            required
                            autocomplete="email"
                        >
                        <?php if (isset($errors['email'])): ?>
                            <span class="error">
                                <i class="fas fa-exclamation-circle"></i>
                                <?php echo htmlspecialchars($errors['email']); ?>
                            </span>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label for="phone">Phone Number</label>
                        <input 
                            type="tel" 
                            id="phone" 
                            name="phone" 
                            value="<?php echo htmlspecialchars($form_data['phone']); ?>"
                            class="<?php echo isset($errors['phone']) ? 'error-input' : ''; ?>"
                            placeholder="Enter your phone number (optional)"
                            autocomplete="tel"
                        >
                        <?php if (isset($errors['phone'])): ?>
                            <span class="error">
                                <i class="fas fa-exclamation-circle"></i>
                                <?php echo htmlspecialchars($errors['phone']); ?>
                            </span>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label for="subject">Subject</label>
                        <select id="subject" name="subject">
                            <option value="General Inquiry" <?php echo $form_data['subject'] === 'General Inquiry' ? 'selected' : ''; ?>>General Inquiry</option>
                            <option value="Appointment" <?php echo $form_data['subject'] === 'Appointment' ? 'selected' : ''; ?>>Schedule Appointment</option>
                            <option value="Billing" <?php echo $form_data['subject'] === 'Billing' ? 'selected' : ''; ?>>Billing Question</option>
                            <option value="Emergency" <?php echo $form_data['subject'] === 'Emergency' ? 'selected' : ''; ?>>Emergency Contact</option>
                            <option value="Other" <?php echo $form_data['subject'] === 'Other' ? 'selected' : ''; ?>>Other</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="message" class="required-field">Message</label>
                        <textarea 
                            id="message" 
                            name="message"
                            class="<?php echo isset($errors['message']) ? 'error-input' : ''; ?>"
                            placeholder="Please describe how we can help you..."
                            required
                            rows="5"
                        ><?php echo htmlspecialchars($form_data['message']); ?></textarea>
                        <?php if (isset($errors['message'])): ?>
                            <span class="error">
                                <i class="fas fa-exclamation-circle"></i>
                                <?php echo htmlspecialchars($errors['message']); ?>
                            </span>
                        <?php endif; ?>
                    </div>

                    <button type="submit" class="btn" id="submitBtn">
                        <span class="btn-text">
                            <i class="fas fa-paper-plane"></i>
                            Send Message
                        </span>
                        <span class="loading">
                            <div class="spinner"></div>
                            Sending...
                        </span>
                    </button>
                </form>
            </div>

            <div class="contact-info">
                <div class="contact-card">
                    <h3><i class="fas fa-map-marker-alt"></i> Office Location</h3>
                    <p><i class="fas fa-building contact-icon"></i> 123 Medical Center Drive, Suite 456</p>
                    <p><i class="fas fa-city contact-icon"></i> Cityville, State 12345</p>
                    <p><i class="fas fa-globe-americas contact-icon"></i> 
                        <a href="https://maps.google.com" target="_blank" rel="noopener">View on Map</a>
                    </p>
                </div>

                <div class="contact-card">
                    <h3><i class="fas fa-phone-alt"></i> Contact Information</h3>
                    <p><i class="fas fa-phone contact-icon"></i> 
                        <a href="tel:+11234567890">(123) 456-7890</a>
                    </p>
                    <p><i class="fas fa-fax contact-icon"></i> (123) 456-7891</p>
                    <p><i class="fas fa-envelope contact-icon"></i> 
                        <a href="mailto:info@drparth.com">info@drparth.com</a>
                    </p>
                </div>

                <div class="contact-card">
                    <h3><i class="far fa-clock"></i> Office Hours</h3>
                    <ul class="business-hours">
                        <li>
                            <span class="day">Monday - Friday</span>
                            <span class="hours">9:00 AM - 5:00 PM</span>
                        </li>
                        <li>
                            <span class="day">Saturday</span>
                            <span class="hours">10:00 AM - 2:00 PM</span>
                        </li>
                        <li>
                            <span class="day">Sunday</span>
                            <span class="hours">Closed</span>
                        </li>
                    </ul>
                    <p style="margin-top: 1rem; font-size: 0.9rem; color: var(--dark-gray);">
                        <i class="fas fa-info-circle contact-icon"></i>
                        Emergency calls are available 24/7
                    </p>
                </div>

                <div class="contact-card">
                    <h3><i class="fas fa-map-marked-alt"></i> Our Location</h3>
                    <div class="map-container">
                        <iframe 
                            src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3022.215373510547!2d-73.9878449242395!3d40.74844097138992!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x0%3A0x0!2zNDDCsDQ0JzU0LjQiTiA3M8KwNTknMTkuNyJX!5e0!3m2!1sen!2sus!4v1620000000000!5m2!1sen!2sus"
                            allowfullscreen="" 
                            loading="lazy"
                            title="Dr. Parth's Office Location"
                        ></iframe>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('contactForm');
            const submitBtn = document.getElementById('submitBtn');
            const btnText = submitBtn.querySelector('.btn-text');
            const loading = submitBtn.querySelector('.loading');

            // Real-time validation
            const inputs = form.querySelectorAll('input, textarea, select');
            inputs.forEach(input => {
                input.addEventListener('blur', validateField);
                input.addEventListener('input', clearFieldError);
            });

            // Form submission
            form.addEventListener('submit', function(e) {
                if (!validateForm()) {
                    e.preventDefault();
                    return;
                }

                // Show loading state
                submitBtn.disabled = true;
                btnText.style.display = 'none';
                loading.style.display = 'flex';
            });

            function validateField(e) {
                const field = e.target;
                const value = field.value.trim();
                let isValid = true;
                let errorMessage = '';

                // Remove existing error styling
                clearFieldError(e);

                // Validate based on field type
                switch (field.type) {
                    case 'email':
                        if (value && !isValidEmail(value)) {
                            isValid = false;
                            errorMessage = 'Please enter a valid email address';
                        }
                        break;
                    case 'tel':
                        if (value && !isValidPhone(value)) {
                            isValid = false;
                            errorMessage = 'Please enter a valid phone number';
                        }
                        break;
                }

                // Required field validation
                if (field.hasAttribute('required') && !value) {
                    isValid = false;
                    errorMessage = 'This field is required';
                }

                // Specific field validations
                if (field.id === 'name' && value && value.length < 2) {
                    isValid = false;
                    errorMessage = 'Name must be at least 2 characters long';
                }

                if (field.id === 'message' && value && value.length < 10) {
                    isValid = false;
                    errorMessage = 'Message must be at least 10 characters long';
                }

                if (!isValid) {
                    showFieldError(field, errorMessage);
                }
            }

            function clearFieldError(e) {
                const field = e.target;
                field.classList.remove('error-input');
                const errorElement = field.parentNode.querySelector('.error');
                if (errorElement) {
                    errorElement.style.display = 'none';
                }
            }

            function showFieldError(field, message) {
                field.classList.add('error-input');
                
                // Remove existing error message
                const existingError = field.parentNode.querySelector('.error');
                if (existingError) {
                    existingError.remove();
                }

                // Create new error message
                const errorElement = document.createElement('span');
                errorElement.className = 'error';
                errorElement.innerHTML = `<i class="fas fa-exclamation-circle"></i> ${message}`;
                field.parentNode.appendChild(errorElement);
            }

            function validateForm() {
                let isValid = true;
                const requiredFields = form.querySelectorAll('[required]');
                
                requiredFields.forEach(field => {
                    if (!field.value.trim()) {
                        isValid = false;
                        showFieldError(field, 'This field is required');
                    }
                });

                // Validate email
                const emailField = form.querySelector('#email');
                if (emailField.value && !isValidEmail(emailField.value)) {
                    isValid = false;
                    showFieldError(emailField, 'Please enter a valid email address');
                }

                // Validate message length
                const messageField = form.querySelector('#message');
                if (messageField.value && messageField.value.trim().length < 10) {
                    isValid = false;
                    showFieldError(messageField, 'Message must be at least 10 characters long');
                }

                return isValid;
            }

            function isValidEmail(email) {
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                return emailRegex.test(email);
            }

            function isValidPhone(phone) {
                const phoneRegex = /^[\+]?[1-9][\d]{0,15}$/;
                const cleanPhone = phone.replace(/[\s\-\(\)]/g, '');
                return phoneRegex.test(cleanPhone);
            }

            // Auto-resize textarea
            const textarea = document.querySelector('#message');
            textarea.addEventListener('input', function() {
                this.style.height = 'auto';
                this.style.height = this.scrollHeight + 'px';
            });
        });
    </script>
</body>
</html>

<?php include('includes/footer.php'); ?>