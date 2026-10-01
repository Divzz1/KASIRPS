<?php
include 'koneksi.php';
$error = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
  $u = $_POST['username'];
  $p = $_POST['password'];
  $stmt = $db->prepare("SELECT password, peran FROM admin WHERE username=?");
  $stmt->bind_param('s', $u);
  $stmt->execute();
  $row = $stmt->get_result()->fetch_assoc();
  // Akun lama memakai MD5, akun baru (kasir) memakai password_hash
  if ($row && ((strlen($row['password']) == 32 && hash_equals($row['password'], md5($p))) || password_verify($p, $row['password']))) {
    session_regenerate_id(true);
    $_SESSION['admin'] = $u;
    $_SESSION['peran'] = $row['peran'];
    header('Location: index.php'); exit;
  }
  $error = 'Username atau password salah!';
}
?>
<!DOCTYPE html>
<html lang="id">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Login - D1vzz PlayStation</title><link rel="stylesheet" href="style.css"><script src="tema.js"></script></head>
<body class="login-page">
  <button id="tema" class="tema tema-login" type="button">Mode gelap</button>
  <div class="login-box">
    <h1><svg class="logo-ps" viewBox="0 0 74 16" width="56" height="12" fill="none" stroke-width="1.7" stroke-linejoin="round" stroke-linecap="round"><path class="s1" d="M8 2 L14 13 L2 13 Z"/><circle class="s2" cx="28" cy="8" r="5.5"/><path class="s3" d="M40 3 L51 13 M51 3 L40 13"/><rect class="s4" x="61" y="3" width="10" height="10" rx="1"/></svg>D1vzz PlayStation</h1>
    <?php if ($error) echo "<p class='error'>$error</p>"; ?>
    <form method="post">
      <input type="text" name="username" placeholder="Username" required>
      <input type="password" name="password" placeholder="Password" required>
      <button type="submit" class="btn btn-start">Masuk</button>
    </form>
  </div>
</body>
</html>