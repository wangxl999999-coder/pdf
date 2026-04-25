<?php
require_once 'config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => '无效的请求方法']);
    exit;
}

$action = $_POST['action'] ?? '';

switch ($action) {
    case 'register':
        handleRegister();
        break;
    case 'login':
        handleLogin();
        break;
    case 'logout':
        handleLogout();
        break;
    default:
        echo json_encode(['success' => false, 'message' => '未知的操作']);
}

function handleRegister() {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    if (empty($username) || empty($email) || empty($password)) {
        echo json_encode(['success' => false, 'message' => '请填写所有必填字段']);
        return;
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => '请输入有效的邮箱地址']);
        return;
    }
    
    if ($password !== $confirmPassword) {
        echo json_encode(['success' => false, 'message' => '两次输入的密码不一致']);
        return;
    }
    
    if (strlen($password) < 6) {
        echo json_encode(['success' => false, 'message' => '密码至少需要6个字符']);
        return;
    }
    
    $db = new SQLite3(USERS_DB);
    
    $stmt = $db->prepare('SELECT id FROM users WHERE username = :username OR email = :email');
    $stmt->bindValue(':username', $username);
    $stmt->bindValue(':email', $email);
    $result = $stmt->execute();
    
    if ($result->fetchArray()) {
        $db->close();
        echo json_encode(['success' => false, 'message' => '用户名或邮箱已存在']);
        return;
    }
    
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    $stmt = $db->prepare('INSERT INTO users (username, email, password) VALUES (:username, :email, :password)');
    $stmt->bindValue(':username', $username);
    $stmt->bindValue(':email', $email);
    $stmt->bindValue(':password', $hashedPassword);
    
    if ($stmt->execute()) {
        $_SESSION['user_id'] = $db->lastInsertRowID();
        $_SESSION['username'] = $username;
        $db->close();
        echo json_encode(['success' => true, 'message' => '注册成功']);
    } else {
        $db->close();
        echo json_encode(['success' => false, 'message' => '注册失败，请重试']);
    }
}

function handleLogin() {
    $login = trim($_POST['login'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($login) || empty($password)) {
        echo json_encode(['success' => false, 'message' => '请填写用户名和密码']);
        return;
    }
    
    $db = new SQLite3(USERS_DB);
    
    $stmt = $db->prepare('SELECT * FROM users WHERE username = :login OR email = :login');
    $stmt->bindValue(':login', $login);
    $result = $stmt->execute();
    $user = $result->fetchArray(SQLITE3_ASSOC);
    
    $db->close();
    
    if (!$user || !password_verify($password, $user['password'])) {
        echo json_encode(['success' => false, 'message' => '用户名或密码错误']);
        return;
    }
    
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    
    echo json_encode(['success' => true, 'message' => '登录成功', 'username' => $user['username']]);
}

function handleLogout() {
    session_destroy();
    echo json_encode(['success' => true, 'message' => '已退出登录']);
}
