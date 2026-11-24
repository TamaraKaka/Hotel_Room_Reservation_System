<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';

$pageTitle = 'Login - Hotel Reservation System';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!csrfValid($_POST['csrf_token'] ?? '')) {

        $error = 'Invalid form submission. Please try again.';

    } else {

        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

       $result = attemptLogin($email, $password);

if ($result['ok']) {

    loginUser($result['user'], false);

    redirect('index.php');

} else {

    $error = $result['error'];
}
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<section class="section">

    <div class="container">

        <h2>Login</h2>

        <p>Sign in to your hotel reservation account.</p>

        <?php if ($error): ?>

            <div class="alert alert-danger">
                <?= e($error) ?>
            </div>

        <?php endif; ?>

        <form method="POST" action="">

            <?= csrfField() ?>

            <div class="form-group">

                <label for="email">Email Address</label>

                <input
                    type="email"
                    id="email"
                    name="email"
                    required
                >

            </div>

            <div class="form-group">

                <label for="password">Password</label>

                <input
                    type="password"
                    id="password"
                    name="password"
                    required
                >

            </div>

            <button type="submit" class="btn btn-primary">
                Login
            </button>

        </form>

        <p>
            Don't have an account?
            <a href="<?= htmlspecialchars(base_url('auth/register.php')) ?>">
                Register here
            </a>
        </p>

    </div>

</section>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>
