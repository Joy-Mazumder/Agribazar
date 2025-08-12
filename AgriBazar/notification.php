<?php
session_start();

$success_message = $_SESSION['success_message'] ?? '';
$error_message = $_SESSION['error_message'] ?? '';

unset($_SESSION['success_message'], $_SESSION['error_message']);

if (!$success_message && !$error_message) {
    header("Location: seller_dashboard.php");
    exit;
}

$redirect_url = "seller_dashboard.php";
$redirect_delay = 5;
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Notification</title>
<style>
  @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap');

  * {
    box-sizing: border-box;
  }

  body {
    margin: 0;
    font-family: 'Poppins', sans-serif;
    background: linear-gradient(135deg, #a8e063 0%, #56ab2f 100%);
    display: flex;
    justify-content: center;
    align-items: center;
    height: 100vh;
    color: #333;
  }

  .notification-box {
    background: #fff;
    padding: 40px 35px;
    border-radius: 16px;
    box-shadow: 0 20px 40px rgba(0,0,0,0.15);
    max-width: 480px;
    width: 90%;
    text-align: center;
    animation: slideDownFade 0.6s ease forwards;
  }

  @keyframes slideDownFade {
    from {
      opacity: 0;
      transform: translateY(-20px);
    }
    to {
      opacity: 1;
      transform: translateY(0);
    }
  }

  .notification-box h2 {
    margin: 0 0 20px;
    font-size: 2.5rem;
  }

  .success {
    color: #388e3c;
  }

  .error {
    color: #d32f2f;
  }

  .message-text {
    font-size: 1.15rem;
    margin-bottom: 30px;
  }

  .redirect-info {
    font-size: 0.9rem;
    color: #666;
  }

  .redirect-info a {
    color: #388e3c;
    text-decoration: none;
    font-weight: 600;
  }

  .redirect-info a:hover {
    text-decoration: underline;
  }
</style>
<meta http-equiv="refresh" content="<?= $redirect_delay ?>;url=<?= htmlspecialchars($redirect_url) ?>" />
</head>
<body>
  <div class="notification-box" role="alert" aria-live="polite">
    <?php if ($success_message): ?>
      <h2 class="success">Success!</h2>
      <p class="message-text"><?= htmlspecialchars($success_message) ?></p>
    <?php elseif ($error_message): ?>
      <h2 class="error">Error!</h2>
      <p class="message-text"><?= htmlspecialchars($error_message) ?></p>
    <?php endif; ?>

    <div class="redirect-info">
      You will be redirected in <span id="countdown"><?= $redirect_delay ?></span> second<span id="plural">s</span>.<br />
      If not, <a href="<?= htmlspecialchars($redirect_url) ?>">click here</a>.
    </div>
  </div>

  <script>
    let countdownEl = document.getElementById('countdown');
    let pluralEl = document.getElementById('plural');
    let timeLeft = <?= $redirect_delay ?>;

    let interval = setInterval(() => {
      timeLeft--;
      countdownEl.textContent = timeLeft;
      pluralEl.textContent = timeLeft === 1 ? '' : 's';
      if (timeLeft <= 0) {
        clearInterval(interval);
      }
    }, 1000);
  </script>
</body>
</html>
