<?php
require_once 'config.php';

createDirectories();
initializeDatabase();

$isLoggedIn = isLoggedIn();
$currentUser = getCurrentUser();
$remainingUsage = getRemainingUsage();
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>文件格式转换系统</title>
    <link rel="stylesheet" href="css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="container">
        <header class="header">
            <div class="header-content">
                <h1 class="logo">
                    <span class="logo-icon">📄</span>
                    <span class="logo-text">文件转换中心</span>
                </h1>
                <nav class="nav">
                    <div class="usage-info">
                        <?php if ($isLoggedIn): ?>
                            <span class="user-name">欢迎, <?php echo htmlspecialchars($currentUser['username']); ?></span>
                            <span class="usage-badge unlimited">无限次数</span>
                        <?php else: ?>
                            <span class="usage-text">剩余免费次数:</span>
                            <span class="usage-count"><?php echo $remainingUsage; ?></span>
                            <span class="usage-text">次</span>
                        <?php endif; ?>
                    </div>
                    <div class="auth-buttons">
                        <?php if ($isLoggedIn): ?>
                            <button id="logoutBtn" class="btn btn-outline">退出登录</button>
                        <?php else: ?>
                            <button id="loginBtn" class="btn btn-outline">登录</button>
                            <button id="registerBtn" class="btn btn-primary">注册</button>
                        <?php endif; ?>
                    </div>
                </nav>
            </div>
        </header>

        <main class="main">
            <div class="hero">
                <h2 class="hero-title">简单、快速的文件格式转换</h2>
                <p class="hero-subtitle">支持Word转PDF、PDF转图片、图片合并，安全便捷</p>
            </div>

            <div class="converter-tabs">
                <div class="tabs-header">
                    <button class="tab-btn active" data-tab="word-to-pdf">
                        <span class="tab-icon">📝</span>
                        <span>Word转PDF</span>
                    </button>
                    <button class="tab-btn" data-tab="pdf-to-image">
                        <span class="tab-icon">🖼️</span>
                        <span>PDF转图片</span>
                    </button>
                    <button class="tab-btn" data-tab="merge-images">
                        <span class="tab-icon">🎨</span>
                        <span>图片合并</span>
                    </button>
                </div>

                <div class="tabs-content">
                    <!-- Word to PDF Tab -->
                    <div id="word-to-pdf-tab" class="tab-content active">
                        <div class="converter-card">
                            <div class="card-header">
                                <h3>Word文档转换为PDF</h3>
                                <p class="card-description">支持 .doc, .docx, .rtf, .odt 格式</p>
                            </div>
                            
                            <div class="upload-area" id="wordUploadArea">
                                <div class="upload-placeholder">
                                    <span class="upload-icon">📁</span>
                                    <p class="upload-text">拖拽文件到此处或点击上传</p>
                                    <p class="upload-hint">支持最大 50MB 的Word文档</p>
                                </div>
                                <input type="file" id="wordFileInput" accept=".doc,.docx,.rtf,.odt" hidden>
                            </div>
                            
                            <div class="file-info" id="wordFileInfo" style="display: none;">
                                <div class="file-item">
                                    <span class="file-icon">📄</span>
                                    <span class="file-name" id="wordFileName"></span>
                                    <span class="file-size" id="wordFileSize"></span>
                                    <button class="file-remove" id="wordFileRemove">×</button>
                                </div>
                            </div>
                            
                            <div class="action-area">
                                <button id="wordConvertBtn" class="btn btn-primary btn-lg" disabled>
                                    <span class="btn-text">开始转换</span>
                                    <span class="btn-spinner" style="display: none;">⟳</span>
                                </button>
                            </div>
                            
                            <div class="result-area" id="wordResultArea" style="display: none;">
                                <div class="result-success">
                                    <span class="result-icon">✅</span>
                                    <span class="result-text">转换成功！</span>
                                </div>
                                <a id="wordDownloadLink" href="#" class="btn btn-success btn-lg" download>
                                    <span class="btn-icon">⬇️</span>
                                    <span>下载PDF文件</span>
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- PDF to Image Tab -->
                    <div id="pdf-to-image-tab" class="tab-content">
                        <div class="converter-card">
                            <div class="card-header">
                                <h3>PDF转换为图片</h3>
                                <p class="card-description">支持自定义导出选项，高质量转换</p>
                            </div>
                            
                            <div class="upload-area" id="pdfUploadArea">
                                <div class="upload-placeholder">
                                    <span class="upload-icon">📁</span>
                                    <p class="upload-text">拖拽PDF文件到此处或点击上传</p>
                                    <p class="upload-hint">支持最大 100MB 的PDF文件</p>
                                </div>
                                <input type="file" id="pdfFileInput" accept=".pdf" hidden>
                            </div>
                            
                            <div class="file-info" id="pdfFileInfo" style="display: none;">
                                <div class="file-item">
                                    <span class="file-icon">📑</span>
                                    <span class="file-name" id="pdfFileName"></span>
                                    <span class="file-size" id="pdfFileSize"></span>
                                    <button class="file-remove" id="pdfFileRemove">×</button>
                                </div>
                            </div>
                            
                            <div class="options-section">
                                <h4 class="options-title">导出选项</h4>
                                <div class="options-grid">
                                    <div class="option-group">
                                        <label class="option-label">页面方向</label>
                                        <div class="option-radio-group">
                                            <label class="option-radio">
                                                <input type="radio" name="pdfOrientation" value="portrait" checked>
                                                <span class="radio-text">竖版</span>
                                            </label>
                                            <label class="option-radio">
                                                <input type="radio" name="pdfOrientation" value="landscape">
                                                <span class="radio-text">横版</span>
                                            </label>
                                        </div>
                                    </div>
                                    
                                    <div class="option-group">
                                        <label class="option-label">导出方式</label>
                                        <div class="option-radio-group">
                                            <label class="option-radio">
                                                <input type="radio" name="pdfMode" value="single" checked>
                                                <span class="radio-text">仅第一页</span>
                                            </label>
                                            <label class="option-radio">
                                                <input type="radio" name="pdfMode" value="multiple">
                                                <span class="radio-text">全部页面</span>
                                            </label>
                                        </div>
                                    </div>
                                    
                                    <div class="option-group">
                                        <label class="option-label" for="pdfFormat">图片格式</label>
                                        <select id="pdfFormat" class="option-select">
                                            <option value="png">PNG (高质量)</option>
                                            <option value="jpg">JPG (压缩)</option>
                                            <option value="webp">WebP</option>
                                        </select>
                                    </div>
                                    
                                    <div class="option-group">
                                        <label class="option-label" for="pdfDpi">分辨率 (DPI)</label>
                                        <select id="pdfDpi" class="option-select">
                                            <option value="72">72 DPI (低质量)</option>
                                            <option value="150" selected>150 DPI (标准)</option>
                                            <option value="300">300 DPI (高质量)</option>
                                            <option value="600">600 DPI (超高清)</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="action-area">
                                <button id="pdfConvertBtn" class="btn btn-primary btn-lg" disabled>
                                    <span class="btn-text">开始转换</span>
                                    <span class="btn-spinner" style="display: none;">⟳</span>
                                </button>
                            </div>
                            
                            <div class="result-area" id="pdfResultArea" style="display: none;">
                                <div class="result-success">
                                    <span class="result-icon">✅</span>
                                    <span class="result-text">转换成功！</span>
                                </div>
                                <div id="pdfDownloadLinks"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Merge Images Tab -->
                    <div id="merge-images-tab" class="tab-content">
                        <div class="converter-card">
                            <div class="card-header">
                                <h3>多张图片合并</h3>
                                <p class="card-description">支持 JPG, PNG, GIF, BMP, WebP 格式</p>
                            </div>
                            
                            <div class="upload-area" id="imagesUploadArea">
                                <div class="upload-placeholder">
                                    <span class="upload-icon">📁</span>
                                    <p class="upload-text">拖拽多张图片到此处或点击上传</p>
                                    <p class="upload-hint">支持同时上传多张图片</p>
                                </div>
                                <input type="file" id="imagesFileInput" accept="image/*" multiple hidden>
                            </div>
                            
                            <div class="file-info" id="imagesFileInfo" style="display: none;">
                                <div class="files-list" id="imagesFilesList"></div>
                            </div>
                            
                            <div class="options-section">
                                <h4 class="options-title">合并选项</h4>
                                <div class="options-grid">
                                    <div class="option-group">
                                        <label class="option-label">合并方向</label>
                                        <div class="option-radio-group">
                                            <label class="option-radio">
                                                <input type="radio" name="mergeOrientation" value="vertical" checked>
                                                <span class="radio-text">垂直排列（竖版）</span>
                                            </label>
                                            <label class="option-radio">
                                                <input type="radio" name="mergeOrientation" value="horizontal">
                                                <span class="radio-text">水平排列（横版）</span>
                                            </label>
                                        </div>
                                    </div>
                                    
                                    <div class="option-group">
                                        <label class="option-label" for="mergeFormat">输出格式</label>
                                        <select id="mergeFormat" class="option-select">
                                            <option value="png">PNG (高质量)</option>
                                            <option value="jpg">JPG (压缩)</option>
                                            <option value="webp">WebP</option>
                                        </select>
                                    </div>
                                    
                                    <div class="option-group">
                                        <label class="option-label" for="mergeSpacing">图片间距 (像素)</label>
                                        <input type="number" id="mergeSpacing" class="option-input" value="10" min="0" max="100">
                                    </div>
                                    
                                    <div class="option-group">
                                        <label class="option-label" for="mergeBgColor">背景颜色</label>
                                        <div class="color-picker-wrapper">
                                            <input type="color" id="mergeBgColor" value="#ffffff">
                                            <span class="color-preview"></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="action-area">
                                <button id="imagesMergeBtn" class="btn btn-primary btn-lg" disabled>
                                    <span class="btn-text">开始合并</span>
                                    <span class="btn-spinner" style="display: none;">⟳</span>
                                </button>
                            </div>
                            
                            <div class="result-area" id="imagesResultArea" style="display: none;">
                                <div class="result-success">
                                    <span class="result-icon">✅</span>
                                    <span class="result-text">合并成功！</span>
                                </div>
                                <a id="imagesDownloadLink" href="#" class="btn btn-success btn-lg" download>
                                    <span class="btn-icon">⬇️</span>
                                    <span>下载合并后的图片</span>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="features-section">
                <div class="features-grid">
                    <div class="feature-card">
                        <div class="feature-icon">🔒</div>
                        <h4 class="feature-title">安全可靠</h4>
                        <p class="feature-description">上传的文件将在1小时后自动删除，保护您的隐私</p>
                    </div>
                    <div class="feature-card">
                        <div class="feature-icon">⚡</div>
                        <h4 class="feature-title">快速转换</h4>
                        <p class="feature-description">高性能服务器，快速完成文件转换</p>
                    </div>
                    <div class="feature-card">
                        <div class="feature-icon">🎯</div>
                        <h4 class="feature-title">高质量输出</h4>
                        <p class="feature-description">保留原始文件质量，支持自定义输出参数</p>
                    </div>
                    <div class="feature-card">
                        <div class="feature-icon">🆓</div>
                        <h4 class="feature-title">免费试用</h4>
                        <p class="feature-description">未登录用户可免费使用1次，注册登录后无限制</p>
                    </div>
                </div>
            </div>
        </main>

        <footer class="footer">
            <p>&copy; 2024 文件转换中心. 保留所有权利.</p>
        </footer>
    </div>

    <!-- Login Modal -->
    <div id="loginModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3>登录账户</h3>
                <button class="modal-close">&times;</button>
            </div>
            <form id="loginForm" class="auth-form">
                <div class="form-group">
                    <label class="form-label" for="loginInput">用户名或邮箱</label>
                    <input type="text" id="loginInput" name="login" class="form-input" required>
                </div>
                <div class="form-group">
                    <label class="form-label" for="loginPassword">密码</label>
                    <input type="password" id="loginPassword" name="password" class="form-input" required>
                </div>
                <div class="form-error" id="loginError" style="display: none;"></div>
                <button type="submit" class="btn btn-primary btn-full">
                    <span class="btn-text">登录</span>
                    <span class="btn-spinner" style="display: none;">⟳</span>
                </button>
                <p class="auth-switch">
                    还没有账户？ <a href="#" id="switchToRegister">立即注册</a>
                </p>
            </form>
        </div>
    </div>

    <!-- Register Modal -->
    <div id="registerModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3>创建账户</h3>
                <button class="modal-close">&times;</button>
            </div>
            <form id="registerForm" class="auth-form">
                <div class="form-group">
                    <label class="form-label" for="registerUsername">用户名</label>
                    <input type="text" id="registerUsername" name="username" class="form-input" required>
                </div>
                <div class="form-group">
                    <label class="form-label" for="registerEmail">邮箱</label>
                    <input type="email" id="registerEmail" name="email" class="form-input" required>
                </div>
                <div class="form-group">
                    <label class="form-label" for="registerPassword">密码</label>
                    <input type="password" id="registerPassword" name="password" class="form-input" required>
                </div>
                <div class="form-group">
                    <label class="form-label" for="registerConfirmPassword">确认密码</label>
                    <input type="password" id="registerConfirmPassword" name="confirm_password" class="form-input" required>
                </div>
                <div class="form-error" id="registerError" style="display: none;"></div>
                <button type="submit" class="btn btn-primary btn-full">
                    <span class="btn-text">注册</span>
                    <span class="btn-spinner" style="display: none;">⟳</span>
                </button>
                <p class="auth-switch">
                    已有账户？ <a href="#" id="switchToLogin">立即登录</a>
                </p>
            </form>
        </div>
    </div>

    <!-- Toast Notification -->
    <div id="toast" class="toast" style="display: none;">
        <span class="toast-icon"></span>
        <span class="toast-message"></span>
    </div>

    <script src="js/main.js"></script>
</body>
</html>
