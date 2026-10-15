<?php
setSeoMeta([
    'title'       => 'Contact CSV Repair Toolkit — Send Us a Message',
    'description' => 'Contact the CSV Repair Toolkit team with questions, bug reports, feature suggestions, or feedback.',
    'canonical'   => APP_URL . '/contact/',
]);

$success = false;
$errors  = [];
$fields  = [
    'name'    => '',
    'email'   => '',
    'phone'   => '',
    'subject' => '',
    'message' => '',
];

// ── Generate math CAPTCHA ─────────────────────────────────────────────────────
// Regenerate only when NOT a failed POST (so the question stays the same on error)
function generateCaptcha(): void {
    $ops = ['+', '-', '*'];
    $op  = $ops[array_rand($ops)];
    $a   = random_int(2, 15);
    $b   = random_int(1, 10);

    // Keep result positive
    if ($op === '-' && $b > $a) [$a, $b] = [$b, $a];

    $answer = match($op) {
        '+'  => $a + $b,
        '-'  => $a - $b,
        '*'  => $a * $b,
    };

    // Human-friendly operator symbols
    $symbol = match($op) {
        '+'  => '+',
        '-'  => '−',
        '*'  => '×',
    };

    $_SESSION['captcha_answer']   = $answer;
    $_SESSION['captcha_question'] = "What is {$a} {$symbol} {$b} ?";
}

// Generate a fresh question on GET, or keep existing on failed POST
if (!isPost() || empty($_SESSION['captcha_question'])) {
    generateCaptcha();
}

$captchaQuestion = $_SESSION['captcha_question'] ?? 'What is 3 + 4 ?';

if (isPost()) {
    verifyCsrf();

    // ── Collect & sanitise ────────────────────────────────────────────────────
    $fields['name']    = trim($_POST['name']    ?? '');
    $fields['email']   = trim($_POST['email']   ?? '');
    $fields['phone']   = trim($_POST['phone']   ?? '');
    $fields['subject'] = trim($_POST['subject'] ?? '');
    $fields['message'] = trim($_POST['message'] ?? '');

    // ── Server-side validations ───────────────────────────────────────────────

    // Name: 2–80 chars, letters/spaces/hyphens only
    if ($fields['name'] === '') {
        $errors['name'] = 'Name is required.';
    } elseif (strlen($fields['name']) < 2) {
        $errors['name'] = 'Name must be at least 2 characters.';
    } elseif (strlen($fields['name']) > 80) {
        $errors['name'] = 'Name must not exceed 80 characters.';
    } elseif (!preg_match('/^[\p{L}\s\'\-\.]+$/u', $fields['name'])) {
        $errors['name'] = 'Name may only contain letters, spaces, hyphens, and apostrophes.';
    }

    // Email: valid format, max 160 chars
    if ($fields['email'] === '') {
        $errors['email'] = 'Email address is required.';
    } elseif (strlen($fields['email']) > 160) {
        $errors['email'] = 'Email address is too long.';
    } elseif (!filter_var($fields['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Please enter a valid email address (e.g. name@example.com).';
    }

    // Phone: optional but if filled must be valid
    if ($fields['phone'] !== '') {
        // Strip spaces, dashes, dots, parens for digit count
        $digitsOnly = preg_replace('/[\s\-\.\(\)\+]/', '', $fields['phone']);
        if (!preg_match('/^\+?[\d\s\-\.\(\)]{7,20}$/', $fields['phone'])) {
            $errors['phone'] = 'Please enter a valid phone number (7–20 digits, optional +, spaces, dashes).';
        } elseif (strlen($digitsOnly) < 7) {
            $errors['phone'] = 'Phone number must have at least 7 digits.';
        } elseif (strlen($digitsOnly) > 15) {
            $errors['phone'] = 'Phone number must not exceed 15 digits.';
        }
    }

    // Subject: 3–200 chars
    if ($fields['subject'] === '') {
        $errors['subject'] = 'Subject is required.';
    } elseif (strlen($fields['subject']) < 3) {
        $errors['subject'] = 'Subject must be at least 3 characters.';
    } elseif (strlen($fields['subject']) > 200) {
        $errors['subject'] = 'Subject must not exceed 200 characters.';
    }

    // Message: 10–5000 chars
    if ($fields['message'] === '') {
        $errors['message'] = 'Message is required.';
    } elseif (strlen($fields['message']) < 10) {
        $errors['message'] = 'Message must be at least 10 characters.';
    } elseif (strlen($fields['message']) > 5000) {
        $errors['message'] = 'Message must not exceed 5000 characters.';
    }

    // Rate limit: max 3 submissions per IP per hour
    $ip      = $_SERVER['REMOTE_ADDR'] ?? '';
    $sessKey = 'contact_count_' . date('YmdH');
    $_SESSION[$sessKey] = ($_SESSION[$sessKey] ?? 0) + 1;
    if ($_SESSION[$sessKey] > 3) {
        $errors['rate'] = 'Too many submissions. Please try again in an hour.';
    }

    // ── Math CAPTCHA validation ───────────────────────────────────────────────
    $captchaInput  = trim($_POST['captcha_answer'] ?? '');
    $captchaExpected = $_SESSION['captcha_answer'] ?? null;

    if ($captchaInput === '') {
        $errors['captcha'] = 'Please solve the math question to prove you are not a robot.';
    } elseif (!is_numeric($captchaInput)) {
        $errors['captcha'] = 'Please enter a number as your answer.';
    } elseif ((int)$captchaInput !== (int)$captchaExpected) {
        $errors['captcha'] = 'Incorrect answer. Please try again.';
        // Regenerate a new question on wrong answer
        generateCaptcha();
        $captchaQuestion = $_SESSION['captcha_question'];
    }

    // ── Save if no errors ─────────────────────────────────────────────────────
    if (empty($errors)) {
        try {
            dbExecute(
                'INSERT INTO contact_messages (name, email, phone, subject, message, ip_address)
                 VALUES (?, ?, ?, ?, ?, ?)',
                [
                    $fields['name'],
                    $fields['email'],
                    $fields['phone'] ?: null,
                    $fields['subject'],
                    $fields['message'],
                    $ip,
                ]
            );
            $success = true;
            $fields  = ['name'=>'','email'=>'','phone'=>'','subject'=>'','message'=>''];
            // Regenerate CAPTCHA after successful submit
            generateCaptcha();
            $captchaQuestion = $_SESSION['captcha_question'];
        } catch (Exception $e) {
            $errors['db'] = 'Could not send your message. Please try again later.';
        }
    }
}

$bodyClass = 'page-contact';
require_once ROOT_PATH . '/includes/header.php';
?>

<div class="container py-5">
    <?php renderBreadcrumbs([['label'=>'Home','url'=>url()],['label'=>'Contact']]); ?>

    <div class="row g-5">

        <!-- ── Form column ─────────────────────────────────────────────────── -->
        <div class="col-lg-7">
            <h1 class="fw-bold mb-1">Contact Us</h1>
            <p class="text-muted mb-4">
                Have a question, found a bug, or want to suggest a new tool?
                Send us a message and we'll get back to you.
            </p>

            <!-- Success message -->
            <?php if ($success): ?>
            <div class="alert alert-success d-flex align-items-center gap-2 mb-4" role="alert">
                <i class="bi bi-check-circle-fill fs-5" aria-hidden="true"></i>
                <div>
                    <strong>Message sent!</strong>
                    Thank you — we'll get back to you within 24–48 hours.
                </div>
            </div>
            <?php endif; ?>

            <!-- DB / rate errors -->
            <?php if (!empty($errors['db']) || !empty($errors['rate'])): ?>
            <div class="alert alert-danger d-flex gap-2" role="alert">
                <i class="bi bi-exclamation-triangle-fill flex-shrink-0 mt-1" aria-hidden="true"></i>
                <div><?= e($errors['db'] ?? $errors['rate']) ?></div>
            </div>
            <?php endif; ?>

            <form method="post" action="<?= url('contact/') ?>"
                  id="contactForm" novalidate autocomplete="on">
                <?= csrfField() ?>

                <div class="row g-3">

                    <!-- Name -->
                    <div class="col-sm-6">
                        <label for="name" class="form-label fw-medium">
                            Full Name <span class="text-danger" aria-hidden="true">*</span>
                        </label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-person" aria-hidden="true"></i></span>
                            <input type="text" id="name" name="name"
                                   class="form-control<?= isset($errors['name']) ? ' is-invalid' : '' ?>"
                                   value="<?= e($fields['name']) ?>"
                                   placeholder="e.g. John Smith"
                                   required minlength="2" maxlength="80"
                                   autocomplete="name">
                            <?php if (isset($errors['name'])): ?>
                            <div class="invalid-feedback"><?= e($errors['name']) ?></div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Email -->
                    <div class="col-sm-6">
                        <label for="email" class="form-label fw-medium">
                            Email Address <span class="text-danger" aria-hidden="true">*</span>
                        </label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-envelope" aria-hidden="true"></i></span>
                            <input type="email" id="email" name="email"
                                   class="form-control<?= isset($errors['email']) ? ' is-invalid' : '' ?>"
                                   value="<?= e($fields['email']) ?>"
                                   placeholder="e.g. john@example.com"
                                   required maxlength="160"
                                   autocomplete="email">
                            <?php if (isset($errors['email'])): ?>
                            <div class="invalid-feedback"><?= e($errors['email']) ?></div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Phone (optional) -->
                    <div class="col-sm-6">
                        <label for="phone" class="form-label fw-medium">
                            Phone Number
                            <span class="text-muted small fw-normal">(optional)</span>
                        </label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-telephone" aria-hidden="true"></i></span>
                            <input type="tel" id="phone" name="phone"
                                   class="form-control<?= isset($errors['phone']) ? ' is-invalid' : '' ?>"
                                   value="<?= e($fields['phone']) ?>"
                                   placeholder="e.g. +1 555 123 4567"
                                   maxlength="25"
                                   autocomplete="tel"
                                   pattern="^\+?[\d\s\-\.\(\)]{7,20}$">
                            <?php if (isset($errors['phone'])): ?>
                            <div class="invalid-feedback"><?= e($errors['phone']) ?></div>
                            <?php else: ?>
                            <div class="valid-feedback">Looks good!</div>
                            <?php endif; ?>
                        </div>
                        <div class="form-text">
                            Include country code for international numbers (e.g. +44 20 1234 5678).
                        </div>
                    </div>

                    <!-- Subject -->
                    <div class="col-sm-6">
                        <label for="subject" class="form-label fw-medium">
                            Subject <span class="text-danger" aria-hidden="true">*</span>
                        </label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-chat-left-text" aria-hidden="true"></i></span>
                            <input type="text" id="subject" name="subject"
                                   class="form-control<?= isset($errors['subject']) ? ' is-invalid' : '' ?>"
                                   value="<?= e($fields['subject']) ?>"
                                   placeholder="e.g. Bug report — CSV Repair"
                                   required minlength="3" maxlength="200">
                            <?php if (isset($errors['subject'])): ?>
                            <div class="invalid-feedback"><?= e($errors['subject']) ?></div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Message -->
                    <div class="col-12">
                        <label for="message" class="form-label fw-medium">
                            Message <span class="text-danger" aria-hidden="true">*</span>
                        </label>
                        <textarea id="message" name="message" rows="6"
                                  class="form-control<?= isset($errors['message']) ? ' is-invalid' : '' ?>"
                                  placeholder="Describe your question, bug, or suggestion in detail…"
                                  required minlength="10" maxlength="5000"><?= e($fields['message']) ?></textarea>
                        <?php if (isset($errors['message'])): ?>
                        <div class="invalid-feedback"><?= e($errors['message']) ?></div>
                        <?php endif; ?>
                        <!-- Live char counter -->
                        <div class="d-flex justify-content-end mt-1">
                            <span class="small text-muted" id="msgCounter">
                                <?= strlen($fields['message']) ?> / 5000
                            </span>
                        </div>
                    </div>

                    <!-- Math CAPTCHA -->
                    <div class="col-12">
                        <label for="captcha_answer" class="form-label fw-medium">
                            <i class="bi bi-shield-check me-1 text-success"></i>
                            Security Check <span class="text-danger" aria-hidden="true">*</span>
                        </label>
                        <div class="captcha-box d-flex align-items-center gap-3 p-3 rounded border bg-light">
                            <!-- Math question display -->
                            <div class="captcha-question text-center">
                                <div class="captcha-label small text-muted mb-1">Solve this to continue</div>
                                <div class="captcha-math fw-bold fs-5 text-primary font-mono px-3 py-2 bg-white rounded border"
                                     id="captchaQuestion"
                                     aria-label="Math question: <?= eAttr($captchaQuestion) ?>">
                                    <?= e($captchaQuestion) ?>
                                </div>
                            </div>
                            <div class="captcha-equals text-muted fs-4 fw-bold">=</div>
                            <!-- Answer input -->
                            <div class="captcha-input flex-grow-1" style="max-width:140px">
                                <input type="number"
                                       id="captcha_answer"
                                       name="captcha_answer"
                                       class="form-control form-control-lg text-center font-mono fw-bold<?= isset($errors['captcha']) ? ' is-invalid' : '' ?>"
                                       placeholder="?"
                                       value="<?= e($_POST['captcha_answer'] ?? '') ?>"
                                       required
                                       min="-200" max="200"
                                       autocomplete="off"
                                       aria-label="Your answer to the math question"
                                       aria-describedby="captchaHelp">
                                <?php if (isset($errors['captcha'])): ?>
                                <div class="invalid-feedback fw-medium"><?= e($errors['captcha']) ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div id="captchaHelp" class="form-text">
                            <i class="bi bi-info-circle me-1"></i>
                            Enter the numeric answer to the math question above. This helps us prevent spam.
                        </div>
                    </div>

                    <!-- Required note -->
                    <div class="col-12">
                        <p class="small text-muted mb-0">
                            <span class="text-danger">*</span> Required fields
                        </p>
                    </div>

                    <!-- Submit -->
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary px-5 btn-lg" id="submitBtn">
                            <i class="bi bi-send me-2" aria-hidden="true"></i>Send Message
                        </button>
                    </div>

                </div><!-- /row -->
            </form>
        </div>

        <!-- ── Info column ─────────────────────────────────────────────────── -->
        <div class="col-lg-5">

            <!-- Contact info card -->
            <div class="card border-0 shadow-sm p-4 mb-4">
                <h2 class="h5 fw-bold mb-3">Other Ways to Reach Us</h2>
                <ul class="list-unstyled mb-0">
                    <li class="mb-3 d-flex gap-3">
                        <i class="bi bi-envelope-fill text-primary mt-1 flex-shrink-0 fs-5" aria-hidden="true"></i>
                        <div>
                            <strong>Email</strong>
                            <p class="text-muted small mb-0">
                                <a href="mailto:hello@csvtoolkit.online">hello@csvtoolkit.online</a>
                            </p>
                        </div>
                    </li>
                    <li class="mb-3 d-flex gap-3">
                        <i class="bi bi-clock-fill text-success mt-1 flex-shrink-0 fs-5" aria-hidden="true"></i>
                        <div>
                            <strong>Response Time</strong>
                            <p class="text-muted small mb-0">We aim to respond within 24–48 hours.</p>
                        </div>
                    </li>
                    <li class="d-flex gap-3">
                        <i class="bi bi-bug-fill text-danger mt-1 flex-shrink-0 fs-5" aria-hidden="true"></i>
                        <div>
                            <strong>Bug Reports</strong>
                            <p class="text-muted small mb-0">
                                Please include the file structure and steps to reproduce.
                            </p>
                        </div>
                    </li>
                </ul>
            </div>

            <!-- Validation summary card -->
            <div class="card border-0 shadow-sm p-4 mb-4">
                <h2 class="h6 fw-bold mb-3">
                    <i class="bi bi-shield-check me-2 text-primary"></i>Form Rules
                </h2>
                <ul class="list-unstyled small text-muted mb-0">
                    <li class="mb-2">
                        <i class="bi bi-person me-2 text-primary"></i>
                        <strong>Name:</strong> 2–80 characters, letters only
                    </li>
                    <li class="mb-2">
                        <i class="bi bi-envelope me-2 text-primary"></i>
                        <strong>Email:</strong> valid format required
                    </li>
                    <li class="mb-2">
                        <i class="bi bi-telephone me-2 text-primary"></i>
                        <strong>Phone:</strong> optional — 7–15 digits,
                        include country code (e.g. +92, +1, +44)
                    </li>
                    <li class="mb-2">
                        <i class="bi bi-chat-left-text me-2 text-primary"></i>
                        <strong>Subject:</strong> 3–200 characters
                    </li>
                    <li>
                        <i class="bi bi-text-paragraph me-2 text-primary"></i>
                        <strong>Message:</strong> 10–5000 characters
                    </li>
                    <li class="mt-2">
                        <i class="bi bi-shield-check me-2 text-success"></i>
                        <strong>Security:</strong> solve the math question to verify you are human
                    </li>
                </ul>
            </div>

            <!-- Privacy card -->
            <div class="card border-0 bg-light p-4">
                <h2 class="h6 fw-bold mb-2">
                    <i class="bi bi-shield-lock me-2 text-success" aria-hidden="true"></i>Your Privacy
                </h2>
                <p class="small text-muted mb-0">
                    Your contact information is used only to respond to your message.
                    We never share it with third parties or send marketing emails.
                </p>
            </div>

        </div>
    </div>
</div>

<!-- ── Client-side validation & UX ──────────────────────────────────────────── -->
<script>
(function () {
    'use strict';

    var form      = document.getElementById('contactForm');
    var submitBtn = document.getElementById('submitBtn');
    var msgArea   = document.getElementById('message');
    var msgCounter= document.getElementById('msgCounter');
    var phoneInput= document.getElementById('phone');

    // ── Live character counter for message ────────────────────────────────────
    if (msgArea && msgCounter) {
        msgArea.addEventListener('input', function () {
            var len = this.value.length;
            msgCounter.textContent = len + ' / 5000';
            msgCounter.className   = len > 4800
                ? 'small text-danger fw-semibold'
                : len > 4000
                    ? 'small text-warning'
                    : 'small text-muted';
        });
    }

    // ── Phone: live format hint ───────────────────────────────────────────────
    if (phoneInput) {
        phoneInput.addEventListener('input', function () {
            var val      = this.value.trim();
            var digits   = val.replace(/[\s\-\.\(\)\+]/g, '');
            var feedback = this.nextElementSibling; // .valid-feedback or none

            if (val === '') {
                this.classList.remove('is-invalid', 'is-valid');
                return;
            }
            // Must match: optional +, then digits/spaces/dashes/dots/parens, 7–20 chars
            var ok = /^\+?[\d\s\-\.\(\)]{7,20}$/.test(val) &&
                     digits.length >= 7 && digits.length <= 15;

            this.classList.toggle('is-invalid', !ok);
            this.classList.toggle('is-valid',    ok);
        });
    }

    // ── Name: only letters, spaces, hyphens, apostrophes ─────────────────────
    var nameInput = document.getElementById('name');
    if (nameInput) {
        nameInput.addEventListener('blur', function () {
            var val = this.value.trim();
            var ok  = val.length >= 2 && val.length <= 80 &&
                      /^[\p{L}\s'\-\.]+$/u.test(val);
            this.classList.toggle('is-invalid', val.length > 0 && !ok);
            this.classList.toggle('is-valid',   val.length > 0 && ok);
        });
    }

    // ── Email: live format check ──────────────────────────────────────────────
    var emailInput = document.getElementById('email');
    if (emailInput) {
        emailInput.addEventListener('blur', function () {
            var val = this.value.trim();
            var ok  = /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(val);
            this.classList.toggle('is-invalid', val.length > 0 && !ok);
            this.classList.toggle('is-valid',   val.length > 0 && ok);
        });
    }

    // ── CAPTCHA: live feedback ────────────────────────────────────────────────
    var captchaInput = document.getElementById('captcha_answer');
    if (captchaInput) {
        captchaInput.addEventListener('input', function () {
            var val = this.value.trim();
            if (val === '') {
                this.classList.remove('is-invalid', 'is-valid');
            } else if (isNaN(parseInt(val))) {
                this.classList.add('is-invalid');
                this.classList.remove('is-valid');
            } else {
                // We don't know the answer client-side — just mark as filled
                this.classList.remove('is-invalid');
                this.classList.add('is-valid');
            }
        });
    }

    // ── Full form validation on submit ────────────────────────────────────────
    if (form) {
        form.addEventListener('submit', function (e) {
            var valid = true;

            // Name
            var name = nameInput ? nameInput.value.trim() : '';
            if (name.length < 2) {
                markInvalid(nameInput, 'Name must be at least 2 characters.');
                valid = false;
            } else if (name.length > 80) {
                markInvalid(nameInput, 'Name must not exceed 80 characters.');
                valid = false;
            } else {
                markValid(nameInput);
            }

            // Email
            var email = emailInput ? emailInput.value.trim() : '';
            if (!/^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(email)) {
                markInvalid(emailInput, 'Please enter a valid email address.');
                valid = false;
            } else {
                markValid(emailInput);
            }

            // Phone (optional)
            if (phoneInput && phoneInput.value.trim() !== '') {
                var pVal    = phoneInput.value.trim();
                var pDigits = pVal.replace(/[\s\-\.\(\)\+]/g, '');
                var pOk     = /^\+?[\d\s\-\.\(\)]{7,20}$/.test(pVal) &&
                              pDigits.length >= 7 && pDigits.length <= 15;
                if (!pOk) {
                    markInvalid(phoneInput, 'Please enter a valid phone number.');
                    valid = false;
                } else {
                    markValid(phoneInput);
                }
            }

            // Subject
            var subjectInput = document.getElementById('subject');
            var subject      = subjectInput ? subjectInput.value.trim() : '';
            if (subject.length < 3) {
                markInvalid(subjectInput, 'Subject must be at least 3 characters.');
                valid = false;
            } else if (subject.length > 200) {
                markInvalid(subjectInput, 'Subject must not exceed 200 characters.');
                valid = false;
            } else {
                markValid(subjectInput);
            }

            // Message
            var msg = msgArea ? msgArea.value.trim() : '';
            if (msg.length < 10) {
                markInvalid(msgArea, 'Message must be at least 10 characters.');
                valid = false;
            } else if (msg.length > 5000) {
                markInvalid(msgArea, 'Message must not exceed 5000 characters.');
                valid = false;
            } else {
                markValid(msgArea);
            }

            if (!valid) {
                e.preventDefault();
                // Scroll to first error
                var firstErr = form.querySelector('.is-invalid');
                if (firstErr) firstErr.scrollIntoView({ behavior: 'smooth', block: 'center' });
                return;
            }

            // Disable button to prevent double-submit
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Sending…';
            }

            // CAPTCHA: must have a numeric value
            var captchaVal = captchaInput ? captchaInput.value.trim() : '';
            if (captchaVal === '' || isNaN(parseInt(captchaVal))) {
                if (captchaInput) markInvalid(captchaInput, 'Please solve the math question.');
                valid = false;
            }

            if (!valid && submitBtn) {
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="bi bi-send me-2"></i>Send Message';
            }
        });
    }

    // ── Helpers ───────────────────────────────────────────────────────────────
    function markInvalid(el, msg) {
        if (!el) return;
        el.classList.add('is-invalid');
        el.classList.remove('is-valid');
        var fb = el.parentElement.querySelector('.invalid-feedback');
        if (!fb) {
            fb = document.createElement('div');
            fb.className = 'invalid-feedback';
            el.parentElement.appendChild(fb);
        }
        fb.textContent = msg;
    }

    function markValid(el) {
        if (!el) return;
        el.classList.remove('is-invalid');
        el.classList.add('is-valid');
    }

})();
</script>

<style>
/* ── CAPTCHA box ─────────────────────────────────── */
.captcha-box {
    border: 2px solid #dee2e6 !important;
    transition: border-color .2s;
}
.captcha-box:focus-within {
    border-color: #0d6efd !important;
    box-shadow: 0 0 0 3px rgba(13,110,253,.1);
}
.captcha-math {
    font-size: 1.3rem;
    letter-spacing: .05em;
    min-width: 160px;
}
#captcha_answer {
    font-size: 1.4rem;
    letter-spacing: .1em;
}
#captcha_answer.is-valid  { border-color: #198754; }
#captcha_answer.is-invalid{ border-color: #dc3545; }
</style>

<?php require_once ROOT_PATH . '/includes/footer.php'; ?>
