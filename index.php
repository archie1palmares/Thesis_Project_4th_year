<?php
session_start();
include 'connect.php';

$login_error    = '';
$signup_error   = '';
$signup_success = '';

// ── SIGN UP ──────────────────────────────────────────────
if (isset($_POST['signUp'])) {
    $firstName = trim($_POST['Fname']);
    $lastName  = trim($_POST['Lname']);
    $email     = trim($_POST['email']);
    $password  = md5($_POST['password']);

    $checkEmail = "SELECT * FROM users WHERE email='$email'";
    $result     = $conn->query($checkEmail);

    if ($result->num_rows > 0) {
        $signup_error = "That email is already registered. Please log in instead.";
    } else {
        $insertQuery = "INSERT INTO users (firstName, lastName, email, password) VALUES ('$firstName', '$lastName', '$email', '$password')";
        if ($conn->query($insertQuery) === TRUE) {
            $signup_success = "Account created successfully! You can now log in.";
        } else {
            $signup_error = "Something went wrong. Please try again.";
        }
    }
}

// ── LOGIN ─────────────────────────────────────────────────
if (isset($_POST['login'])) {
    $email    = trim($_POST['email']);
    $password = md5($_POST['password']);

    $sql    = "SELECT * FROM users WHERE email='$email' AND password='$password'";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $_SESSION['email'] = $row['email'];
        header("Location: admin.php");
        exit();
    } else {
        $emailCheck = $conn->query("SELECT * FROM users WHERE email='$email'");
        if ($emailCheck->num_rows === 0) {
            $login_error = "no_account";
        } else {
            $login_error = "wrong_password";
        }
    }
}

// Decide which panel is active on load
$showSignup = ($signup_error || $signup_success) ? true : false;
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | LibFlow AI</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="split-wrapper">

    <!-- ══════════ LEFT: Hero Panel ══════════ -->
    <div class="hero-panel">

        <!-- Logo -->
        <div class="hero-logo">
            <div class="hero-logo-icon">
                <i class="fas fa-book-reader"></i>
            </div>
            <div class="hero-logo-text">Lib<span>Flow</span></div>
        </div>

        <h2 class="hero-tagline">Smart Library<br>Access System</h2>
        <p class="hero-sub">
            Scan your student QR code to check in, get your internet voucher, and access library resources instantly.
        </p>

        <div class="hero-features">
            <div class="feature-pill">
                <i class="fas fa-qrcode"></i>
                QR-Enabled Attendance Tracking
            </div>
            <div class="feature-pill">
                <i class="fas fa-wifi"></i>
                Instant Internet Voucher Issuance
            </div>
            <div class="feature-pill">
                <i class="fas fa-chart-line"></i>
                Real-Time Admin Dashboard
            </div>
        </div>
    </div>

    <!-- ══════════ RIGHT: Form Panel ══════════ -->
    <div class="form-panel">

        <!-- Mobile-only logo -->
        <div class="form-top-logo">
            <i class="fas fa-book-reader"></i> LibFlow AI
        </div>

        <!-- ── LOGIN FORM ── -->
        <div class="container" id="login"
            style="<?= $showSignup ? 'display:none' : '' ?>">

            <h1 class="form-title">Welcome back</h1>
            <p class="form-welcome">Sign in to your admin account</p>

            <!-- Error alerts -->
            <?php if ($login_error === 'no_account'): ?>
            <div class="alert alert-warning" id="alert-box">
                <span class="alert-icon">🔍</span>
                <div>
                    <strong>Account not found.</strong><br>
                    No account is registered with that email.
                    <a href="#" onclick="switchToSignup(event)" style="color:inherit;font-weight:700;text-decoration:underline;">Sign up here →</a>
                </div>
                <button class="alert-close" onclick="dismissAlert(this)" title="Dismiss">✕</button>
            </div>
            <?php elseif ($login_error === 'wrong_password'): ?>
            <div class="alert alert-error" id="alert-box">
                <span class="alert-icon">🔒</span>
                <div>
                    <strong>Incorrect password.</strong><br>
                    The password you entered doesn't match our records.
                </div>
                <button class="alert-close" onclick="dismissAlert(this)" title="Dismiss">✕</button>
            </div>
            <?php endif; ?>

            <?php if (isset($_GET['registered'])): ?>
            <div class="alert alert-success" id="alert-box">
                <span class="alert-icon">✅</span>
                <div><strong>Account created!</strong> You can now log in.</div>
                <button class="alert-close" onclick="dismissAlert(this)">✕</button>
            </div>
            <?php endif; ?>

            <form action="register.php" method="post" id="login-form" onsubmit="return validateLogin()">
                <div class="input-group" id="login-email-group">
                    <i class="fas fa-envelope field-icon"></i>
                    <input type="email" name="email" id="login-email" placeholder="Email address"
                        value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                    <label for="login-email">Email</label>
                </div>

                <div class="input-group" id="login-pw-group">
                    <i class="fas fa-lock field-icon"></i>
                    <input type="password" name="password" id="login-pw" placeholder="Password" required>
                    <label for="login-pw">Password</label>
                    <button type="button" class="pw-toggle" onclick="togglePw('login-pw', this)" title="Show/hide password">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>

                <p class="recover"><a href="#">Forgot Password?</a></p>
                <input type="submit" value="Login" class="btn" name="login">
            </form>

            <p class="or">OR</p>
            <div class="icons">
                <a href="#" class="icon"><i class="fab fa-facebook-f"></i></a>
                <a href="#" class="icon"><i class="fab fa-google"></i></a>
                <a href="#" class="icon"><i class="fab fa-twitter"></i></a>
            </div>
            <div class="signup-link">
                <p>Don't have an account? <button id="signupBtn" onclick="switchToSignup(event)">Sign up</button></p>
            </div>
        </div><!-- /login -->


        <!-- ── SIGNUP FORM ── -->
        <div class="container" id="signup"
            style="<?= $showSignup ? '' : 'display:none' ?>">

            <h1 class="form-title">Create account</h1>
            <p class="form-welcome">Join LibFlow — it only takes a minute</p>

            <!-- Signup alerts -->
            <?php if ($signup_error): ?>
            <div class="alert alert-error" id="signup-alert">
                <span class="alert-icon">⚠️</span>
                <div><?= htmlspecialchars($signup_error) ?></div>
                <button class="alert-close" onclick="dismissAlert(this)">✕</button>
            </div>
            <?php elseif ($signup_success): ?>
            <div class="alert alert-success" id="signup-alert">
                <span class="alert-icon">✅</span>
                <div>
                    <?= htmlspecialchars($signup_success) ?><br>
                    <a href="#" onclick="switchToLogin(event)" style="color:inherit;font-weight:700;text-decoration:underline;">Go to Login →</a>
                </div>
                <button class="alert-close" onclick="dismissAlert(this)">✕</button>
            </div>
            <?php endif; ?>

            <form action="register.php" method="post" id="signup-form" onsubmit="return validateSignup()">
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px">
                    <div class="input-group">
                        <i class="fas fa-user field-icon"></i>
                        <input type="text" name="Fname" placeholder="First name" required>
                        <label>First Name</label>
                    </div>
                    <div class="input-group">
                        <i class="fas fa-user field-icon"></i>
                        <input type="text" name="Lname" placeholder="Last name" required>
                        <label>Last Name</label>
                    </div>
                </div>

                <div class="input-group">
                    <i class="fas fa-envelope field-icon"></i>
                    <input type="email" name="email" id="su-email" placeholder="Email address" required>
                    <label>Email</label>
                </div>

                <div class="input-group">
                    <i class="fas fa-lock field-icon"></i>
                    <input type="password" name="password" id="su-pw" placeholder="Password (min 6 chars)" required minlength="6">
                    <label>Password</label>
                    <button type="button" class="pw-toggle" onclick="togglePw('su-pw', this)"><i class="fas fa-eye"></i></button>
                </div>

                <div class="input-group">
                    <i class="fas fa-lock field-icon"></i>
                    <input type="password" id="su-pw2" placeholder="Confirm password" required>
                    <label>Confirm Password</label>
                    <button type="button" class="pw-toggle" onclick="togglePw('su-pw2', this)"><i class="fas fa-eye"></i></button>
                </div>

                <input type="submit" value="Create Account" class="btn" name="signUp">
            </form>

            <p class="or">OR</p>
            <div class="icons">
                <a href="#" class="icon"><i class="fab fa-facebook-f"></i></a>
                <a href="#" class="icon"><i class="fab fa-google"></i></a>
                <a href="#" class="icon"><i class="fab fa-twitter"></i></a>
            </div>
            <div class="login-link">
                <p>Already have an account? <button id="loginBtn" onclick="switchToLogin(event)">Login here</button></p>
            </div>
        </div><!-- /signup -->

    </div><!-- /form-panel -->
</div><!-- /split-wrapper -->


<script>
/* ── Panel switching ── */
function switchToSignup(e) {
    if (e) e.preventDefault();
    document.getElementById('login').style.display  = 'none';
    document.getElementById('signup').style.display = 'block';
    document.getElementById('signup').style.animation = 'none';
    document.getElementById('signup').offsetHeight;
    document.getElementById('signup').style.animation = 'fadeUp 0.4s ease both';
}
function switchToLogin(e) {
    if (e) e.preventDefault();
    document.getElementById('signup').style.display = 'none';
    document.getElementById('login').style.display  = 'block';
    document.getElementById('login').style.animation = 'none';
    document.getElementById('login').offsetHeight;
    document.getElementById('login').style.animation = 'fadeUp 0.4s ease both';
}

/* ── Dismiss alert ── */
function dismissAlert(btn) {
    const el = btn.closest('.alert');
    el.style.transition = 'opacity 0.3s, transform 0.3s';
    el.style.opacity    = '0';
    el.style.transform  = 'translateY(-8px)';
    setTimeout(() => el.remove(), 300);
}

/* ── Auto-dismiss alerts after 6 s ── */
document.querySelectorAll('.alert').forEach(a => {
    setTimeout(() => {
        if (a && a.parentNode) {
            a.style.transition = 'opacity 0.4s';
            a.style.opacity    = '0';
            setTimeout(() => { if (a.parentNode) a.remove(); }, 400);
        }
    }, 6000);
});

/* ── Shake alert on load if login error ── */
window.addEventListener('DOMContentLoaded', () => {
    const a = document.getElementById('alert-box');
    if (a) setTimeout(() => { a.style.animation = 'shake 0.4s ease'; }, 80);
});

/* ── Password visibility toggle ── */
function togglePw(id, btn) {
    const inp  = document.getElementById(id);
    const icon = btn.querySelector('i');
    if (inp.type === 'password') {
        inp.type = 'text';
        icon.classList.replace('fa-eye','fa-eye-slash');
    } else {
        inp.type = 'password';
        icon.classList.replace('fa-eye-slash','fa-eye');
    }
}

/* ── Client-side login validation ── */
function validateLogin() {
    let ok = true;
    ['login-email-group','login-pw-group'].forEach(id => {
        const g   = document.getElementById(id);
        const inp = g.querySelector('input');
        if (!inp.value.trim()) {
            g.classList.add('shake');
            g.addEventListener('animationend', () => g.classList.remove('shake'), {once:true});
            ok = false;
        }
    });
    return ok;
}

/* ── Client-side signup validation ── */
function validateSignup() {
    const pw  = document.getElementById('su-pw').value;
    const pw2 = document.getElementById('su-pw2').value;
    if (pw !== pw2) {
        inlineAlert('signup-form', '🔒 Passwords do not match. Please re-enter them.', 'error');
        return false;
    }
    if (pw.length < 6) {
        inlineAlert('signup-form', '🔒 Password must be at least 6 characters long.', 'error');
        return false;
    }
    return true;
}

/* ── Insert inline alert above a form ── */
function inlineAlert(formId, msg, type) {
    const form = document.getElementById(formId);
    const old  = form.querySelector('.inline-alert');
    if (old) old.remove();
    const div = document.createElement('div');
    div.className = `alert alert-${type} inline-alert`;
    div.innerHTML = `<span class="alert-icon">${type==='error'?'⚠️':'✅'}</span>
                        <div>${msg}</div>
                        <button class="alert-close" onclick="dismissAlert(this)">✕</button>`;
    form.insertBefore(div, form.firstChild);
    setTimeout(() => { if (div.parentNode) { div.style.opacity='0'; setTimeout(()=>div.remove(),400); } }, 5000);
}

/* CSS for shake (injected so it works without extra stylesheet changes) */
const style = document.createElement('style');
style.textContent = `
@keyframes shake {
    0%,100%{transform:translateX(0)} 20%{transform:translateX(-7px)}
    40%{transform:translateX(7px)}   60%{transform:translateX(-5px)}
    80%{transform:translateX(5px)}
}
.shake { animation: shake 0.4s ease; }
`;
document.head.appendChild(style);
</script>

</body>
</html>
<?php $conn->close(); ?>