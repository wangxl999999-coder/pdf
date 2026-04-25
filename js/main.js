document.addEventListener('DOMContentLoaded', function() {
    // Tab switching
    const tabBtns = document.querySelectorAll('.tab-btn');
    const tabContents = document.querySelectorAll('.tab-content');
    
    tabBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const tabId = this.getAttribute('data-tab');
            
            tabBtns.forEach(b => b.classList.remove('active'));
            tabContents.forEach(c => c.classList.remove('active'));
            
            this.classList.add('active');
            document.getElementById(tabId + '-tab').classList.add('active');
        });
    });

    // Word to PDF functionality
    const wordUploadArea = document.getElementById('wordUploadArea');
    const wordFileInput = document.getElementById('wordFileInput');
    const wordFileInfo = document.getElementById('wordFileInfo');
    const wordFileName = document.getElementById('wordFileName');
    const wordFileSize = document.getElementById('wordFileSize');
    const wordFileRemove = document.getElementById('wordFileRemove');
    const wordConvertBtn = document.getElementById('wordConvertBtn');
    const wordResultArea = document.getElementById('wordResultArea');
    const wordDownloadLink = document.getElementById('wordDownloadLink');
    
    let selectedWordFile = null;

    setupDragDrop(wordUploadArea, wordFileInput, handleWordFileSelect);
    wordFileInput.addEventListener('change', function() {
        if (this.files.length > 0) {
            handleWordFileSelect(this.files[0]);
        }
    });

    function handleWordFileSelect(file) {
        const allowedTypes = [
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/rtf',
            'application/vnd.oasis.opendocument.text'
        ];
        const allowedExtensions = ['.doc', '.docx', '.rtf', '.odt'];
        const fileExtension = '.' + file.name.split('.').pop().toLowerCase();
        
        if (!allowedTypes.includes(file.type) && !allowedExtensions.includes(fileExtension)) {
            showToast('请上传有效的Word文档', 'error');
            return;
        }
        
        if (file.size > 50 * 1024 * 1024) {
            showToast('文件大小不能超过50MB', 'error');
            return;
        }
        
        selectedWordFile = file;
        wordFileName.textContent = file.name;
        wordFileSize.textContent = formatFileSize(file.size);
        wordFileInfo.style.display = 'block';
        wordUploadArea.style.display = 'none';
        wordConvertBtn.disabled = false;
        wordResultArea.style.display = 'none';
    }

    wordFileRemove.addEventListener('click', function() {
        selectedWordFile = null;
        wordFileInfo.style.display = 'none';
        wordUploadArea.style.display = 'block';
        wordConvertBtn.disabled = true;
        wordResultArea.style.display = 'none';
        wordFileInput.value = '';
    });

    wordConvertBtn.addEventListener('click', function() {
        if (!selectedWordFile) return;
        
        const btn = this;
        const btnText = btn.querySelector('.btn-text');
        const btnSpinner = btn.querySelector('.btn-spinner');
        
        btnText.textContent = '转换中...';
        btnSpinner.style.display = 'inline';
        btn.disabled = true;
        
        const formData = new FormData();
        formData.append('action', 'word_to_pdf');
        formData.append('file', selectedWordFile);
        
        fetch('converter.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            btnText.textContent = '开始转换';
            btnSpinner.style.display = 'none';
            btn.disabled = false;
            
            if (data.success) {
                wordResultArea.style.display = 'block';
                wordDownloadLink.href = data.download_url;
                showToast('转换成功！', 'success');
                
                updateUsageInfo();
            } else {
                if (data.need_login) {
                    showToast(data.message, 'warning');
                    setTimeout(() => {
                        document.getElementById('loginModal').style.display = 'flex';
                    }, 1500);
                } else {
                    showToast(data.message, 'error');
                }
            }
        })
        .catch(error => {
            btnText.textContent = '开始转换';
            btnSpinner.style.display = 'none';
            btn.disabled = false;
            showToast('网络错误，请重试', 'error');
            console.error('Error:', error);
        });
    });

    // PDF to Image functionality
    const pdfUploadArea = document.getElementById('pdfUploadArea');
    const pdfFileInput = document.getElementById('pdfFileInput');
    const pdfFileInfo = document.getElementById('pdfFileInfo');
    const pdfFileName = document.getElementById('pdfFileName');
    const pdfFileSize = document.getElementById('pdfFileSize');
    const pdfFileRemove = document.getElementById('pdfFileRemove');
    const pdfConvertBtn = document.getElementById('pdfConvertBtn');
    const pdfResultArea = document.getElementById('pdfResultArea');
    const pdfDownloadLinks = document.getElementById('pdfDownloadLinks');
    
    let selectedPdfFile = null;

    setupDragDrop(pdfUploadArea, pdfFileInput, handlePdfFileSelect);
    pdfFileInput.addEventListener('change', function() {
        if (this.files.length > 0) {
            handlePdfFileSelect(this.files[0]);
        }
    });

    function handlePdfFileSelect(file) {
        const fileExtension = '.' + file.name.split('.').pop().toLowerCase();
        
        if (file.type !== 'application/pdf' && fileExtension !== '.pdf') {
            showToast('请上传PDF文件', 'error');
            return;
        }
        
        if (file.size > 100 * 1024 * 1024) {
            showToast('文件大小不能超过100MB', 'error');
            return;
        }
        
        selectedPdfFile = file;
        pdfFileName.textContent = file.name;
        pdfFileSize.textContent = formatFileSize(file.size);
        pdfFileInfo.style.display = 'block';
        pdfUploadArea.style.display = 'none';
        pdfConvertBtn.disabled = false;
        pdfResultArea.style.display = 'none';
    }

    pdfFileRemove.addEventListener('click', function() {
        selectedPdfFile = null;
        pdfFileInfo.style.display = 'none';
        pdfUploadArea.style.display = 'block';
        pdfConvertBtn.disabled = true;
        pdfResultArea.style.display = 'none';
        pdfFileInput.value = '';
    });

    pdfConvertBtn.addEventListener('click', function() {
        if (!selectedPdfFile) return;
        
        const btn = this;
        const btnText = btn.querySelector('.btn-text');
        const btnSpinner = btn.querySelector('.btn-spinner');
        
        btnText.textContent = '转换中...';
        btnSpinner.style.display = 'inline';
        btn.disabled = true;
        
        const orientation = document.querySelector('input[name="pdfOrientation"]:checked').value;
        const mode = document.querySelector('input[name="pdfMode"]:checked').value;
        const format = document.getElementById('pdfFormat').value;
        const dpi = document.getElementById('pdfDpi').value;
        
        const formData = new FormData();
        formData.append('action', 'pdf_to_image');
        formData.append('file', selectedPdfFile);
        formData.append('orientation', orientation);
        formData.append('mode', mode);
        formData.append('format', format);
        formData.append('dpi', dpi);
        
        fetch('converter.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            btnText.textContent = '开始转换';
            btnSpinner.style.display = 'none';
            btn.disabled = false;
            
            if (data.success) {
                pdfResultArea.style.display = 'block';
                pdfDownloadLinks.innerHTML = '';
                
                if (data.mode === 'single') {
                    const link = document.createElement('a');
                    link.href = data.download_url;
                    link.className = 'btn btn-success btn-lg';
                    link.innerHTML = '<span class="btn-icon">⬇️</span><span>下载图片</span>';
                    link.setAttribute('download', '');
                    pdfDownloadLinks.appendChild(link);
                } else {
                    const linksContainer = document.createElement('div');
                    linksContainer.className = 'pdf-download-links';
                    
                    data.files.forEach(file => {
                        const pageDiv = document.createElement('div');
                        pageDiv.className = 'page-download';
                        pageDiv.innerHTML = `
                            <span class="page-info">
                                <span class="file-icon">🖼️</span>
                                <span>第 ${file.page} 页</span>
                            </span>
                            <a href="${file.download_url}" class="btn btn-primary download-page-btn" download>下载</a>
                        `;
                        linksContainer.appendChild(pageDiv);
                    });
                    
                    pdfDownloadLinks.appendChild(linksContainer);
                }
                
                showToast('转换成功！共转换 ' + (data.count || 1) + ' 页', 'success');
                updateUsageInfo();
            } else {
                if (data.need_login) {
                    showToast(data.message, 'warning');
                    setTimeout(() => {
                        document.getElementById('loginModal').style.display = 'flex';
                    }, 1500);
                } else {
                    showToast(data.message, 'error');
                }
            }
        })
        .catch(error => {
            btnText.textContent = '开始转换';
            btnSpinner.style.display = 'none';
            btn.disabled = false;
            showToast('网络错误，请重试', 'error');
            console.error('Error:', error);
        });
    });

    // Merge Images functionality
    const imagesUploadArea = document.getElementById('imagesUploadArea');
    const imagesFileInput = document.getElementById('imagesFileInput');
    const imagesFileInfo = document.getElementById('imagesFileInfo');
    const imagesFilesList = document.getElementById('imagesFilesList');
    const imagesMergeBtn = document.getElementById('imagesMergeBtn');
    const imagesResultArea = document.getElementById('imagesResultArea');
    const imagesDownloadLink = document.getElementById('imagesDownloadLink');
    
    let selectedImageFiles = [];

    setupDragDrop(imagesUploadArea, imagesFileInput, handleImagesSelect, true);
    imagesFileInput.addEventListener('change', function() {
        if (this.files.length > 0) {
            handleImagesSelect(Array.from(this.files));
        }
    });

    function handleImagesSelect(files) {
        const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/bmp', 'image/webp'];
        const allowedExtensions = ['.jpg', '.jpeg', '.png', '.gif', '.bmp', '.webp'];
        
        const validFiles = [];
        
        (Array.isArray(files) ? files : [files]).forEach(file => {
            const fileExtension = '.' + file.name.split('.').pop().toLowerCase();
            
            if (!allowedTypes.includes(file.type) && !allowedExtensions.includes(fileExtension)) {
                showToast(`文件 ${file.name} 不是有效的图片格式`, 'error');
                return;
            }
            
            if (file.size > 50 * 1024 * 1024) {
                showToast(`文件 ${file.name} 大小不能超过50MB`, 'error');
                return;
            }
            
            validFiles.push(file);
        });
        
        if (validFiles.length === 0) {
            return;
        }
        
        selectedImageFiles = [...selectedImageFiles, ...validFiles];
        updateImagesList();
        
        if (selectedImageFiles.length > 0) {
            imagesFileInfo.style.display = 'block';
            imagesUploadArea.style.display = 'none';
            imagesMergeBtn.disabled = false;
            imagesResultArea.style.display = 'none';
        }
    }

    function updateImagesList() {
        imagesFilesList.innerHTML = '';
        
        selectedImageFiles.forEach((file, index) => {
            const fileItem = document.createElement('div');
            fileItem.className = 'file-item';
            fileItem.innerHTML = `
                <span class="file-icon">🖼️</span>
                <span class="file-name">${file.name}</span>
                <span class="file-size">${formatFileSize(file.size)}</span>
                <button class="file-remove" data-index="${index}">×</button>
            `;
            
            const removeBtn = fileItem.querySelector('.file-remove');
            removeBtn.addEventListener('click', function() {
                const idx = parseInt(this.getAttribute('data-index'));
                selectedImageFiles.splice(idx, 1);
                updateImagesList();
                
                if (selectedImageFiles.length === 0) {
                    imagesFileInfo.style.display = 'none';
                    imagesUploadArea.style.display = 'block';
                    imagesMergeBtn.disabled = true;
                    imagesResultArea.style.display = 'none';
                    imagesFileInput.value = '';
                }
            });
            
            imagesFilesList.appendChild(fileItem);
        });
    }

    imagesMergeBtn.addEventListener('click', function() {
        if (selectedImageFiles.length === 0) return;
        
        const btn = this;
        const btnText = btn.querySelector('.btn-text');
        const btnSpinner = btn.querySelector('.btn-spinner');
        
        btnText.textContent = '合并中...';
        btnSpinner.style.display = 'inline';
        btn.disabled = true;
        
        const orientation = document.querySelector('input[name="mergeOrientation"]:checked').value;
        const format = document.getElementById('mergeFormat').value;
        const spacing = document.getElementById('mergeSpacing').value;
        const bgColor = document.getElementById('mergeBgColor').value;
        
        const formData = new FormData();
        formData.append('action', 'merge_images');
        formData.append('orientation', orientation);
        formData.append('format', format);
        formData.append('spacing', spacing);
        formData.append('bg_color', bgColor);
        
        selectedImageFiles.forEach((file, index) => {
            formData.append(`files[${index}]`, file);
        });
        
        fetch('converter.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            btnText.textContent = '开始合并';
            btnSpinner.style.display = 'none';
            btn.disabled = false;
            
            if (data.success) {
                imagesResultArea.style.display = 'block';
                imagesDownloadLink.href = data.download_url;
                showToast(`合并成功！共合并 ${data.images_count} 张图片`, 'success');
                updateUsageInfo();
            } else {
                if (data.need_login) {
                    showToast(data.message, 'warning');
                    setTimeout(() => {
                        document.getElementById('loginModal').style.display = 'flex';
                    }, 1500);
                } else {
                    showToast(data.message, 'error');
                }
            }
        })
        .catch(error => {
            btnText.textContent = '开始合并';
            btnSpinner.style.display = 'none';
            btn.disabled = false;
            showToast('网络错误，请重试', 'error');
            console.error('Error:', error);
        });
    });

    // Auth functionality
    const loginBtn = document.getElementById('loginBtn');
    const registerBtn = document.getElementById('registerBtn');
    const logoutBtn = document.getElementById('logoutBtn');
    const loginModal = document.getElementById('loginModal');
    const registerModal = document.getElementById('registerModal');
    const loginForm = document.getElementById('loginForm');
    const registerForm = document.getElementById('registerForm');
    const switchToRegister = document.getElementById('switchToRegister');
    const switchToLogin = document.getElementById('switchToLogin');
    const loginError = document.getElementById('loginError');
    const registerError = document.getElementById('registerError');

    if (loginBtn) {
        loginBtn.addEventListener('click', function() {
            loginModal.style.display = 'flex';
        });
    }

    if (registerBtn) {
        registerBtn.addEventListener('click', function() {
            registerModal.style.display = 'flex';
        });
    }

    if (logoutBtn) {
        logoutBtn.addEventListener('click', function() {
            const formData = new FormData();
            formData.append('action', 'logout');
            
            fetch('auth.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                }
            });
        });
    }

    // Modal close buttons
    document.querySelectorAll('.modal-close').forEach(btn => {
        btn.addEventListener('click', function() {
            this.closest('.modal').style.display = 'none';
            clearFormErrors();
        });
    });

    // Close modal on outside click
    document.querySelectorAll('.modal').forEach(modal => {
        modal.addEventListener('click', function(e) {
            if (e.target === this) {
                this.style.display = 'none';
                clearFormErrors();
            }
        });
    });

    if (switchToRegister) {
        switchToRegister.addEventListener('click', function(e) {
            e.preventDefault();
            loginModal.style.display = 'none';
            registerModal.style.display = 'flex';
            clearFormErrors();
        });
    }

    if (switchToLogin) {
        switchToLogin.addEventListener('click', function(e) {
            e.preventDefault();
            registerModal.style.display = 'none';
            loginModal.style.display = 'flex';
            clearFormErrors();
        });
    }

    function clearFormErrors() {
        if (loginError) loginError.style.display = 'none';
        if (registerError) registerError.style.display = 'none';
    }

    if (loginForm) {
        loginForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            formData.append('action', 'login');
            
            const submitBtn = this.querySelector('button[type="submit"]');
            const btnText = submitBtn.querySelector('.btn-text');
            const btnSpinner = submitBtn.querySelector('.btn-spinner');
            
            btnText.textContent = '登录中...';
            btnSpinner.style.display = 'inline';
            submitBtn.disabled = true;
            loginError.style.display = 'none';
            
            fetch('auth.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                btnText.textContent = '登录';
                btnSpinner.style.display = 'none';
                submitBtn.disabled = false;
                
                if (data.success) {
                    loginModal.style.display = 'none';
                    showToast('登录成功！', 'success');
                    setTimeout(() => {
                        location.reload();
                    }, 500);
                } else {
                    loginError.textContent = data.message;
                    loginError.style.display = 'block';
                }
            })
            .catch(error => {
                btnText.textContent = '登录';
                btnSpinner.style.display = 'none';
                submitBtn.disabled = false;
                showToast('网络错误，请重试', 'error');
                console.error('Error:', error);
            });
        });
    }

    if (registerForm) {
        registerForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            formData.append('action', 'register');
            
            const submitBtn = this.querySelector('button[type="submit"]');
            const btnText = submitBtn.querySelector('.btn-text');
            const btnSpinner = submitBtn.querySelector('.btn-spinner');
            
            btnText.textContent = '注册中...';
            btnSpinner.style.display = 'inline';
            submitBtn.disabled = true;
            registerError.style.display = 'none';
            
            fetch('auth.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                btnText.textContent = '注册';
                btnSpinner.style.display = 'none';
                submitBtn.disabled = false;
                
                if (data.success) {
                    registerModal.style.display = 'none';
                    showToast('注册成功！', 'success');
                    setTimeout(() => {
                        location.reload();
                    }, 500);
                } else {
                    registerError.textContent = data.message;
                    registerError.style.display = 'block';
                }
            })
            .catch(error => {
                btnText.textContent = '注册';
                btnSpinner.style.display = 'none';
                submitBtn.disabled = false;
                showToast('网络错误，请重试', 'error');
                console.error('Error:', error);
            });
        });
    }

    // Helper functions
    function setupDragDrop(area, input, handler, multiple = false) {
        area.addEventListener('click', function() {
            input.click();
        });
        
        area.addEventListener('dragover', function(e) {
            e.preventDefault();
            this.classList.add('dragover');
        });
        
        area.addEventListener('dragleave', function(e) {
            e.preventDefault();
            this.classList.remove('dragover');
        });
        
        area.addEventListener('drop', function(e) {
            e.preventDefault();
            this.classList.remove('dragover');
            
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                if (multiple) {
                    handler(Array.from(files));
                } else {
                    handler(files[0]);
                }
            }
        });
    }

    function formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }

    function showToast(message, type = 'success') {
        const toast = document.getElementById('toast');
        const toastIcon = toast.querySelector('.toast-icon');
        const toastMessage = toast.querySelector('.toast-message');
        
        toast.className = 'toast ' + type;
        
        switch(type) {
            case 'success':
                toastIcon.textContent = '✅';
                break;
            case 'error':
                toastIcon.textContent = '❌';
                break;
            case 'warning':
                toastIcon.textContent = '⚠️';
                break;
        }
        
        toastMessage.textContent = message;
        toast.style.display = 'flex';
        
        setTimeout(() => {
            toast.style.display = 'none';
        }, 4000);
    }

    function updateUsageInfo() {
        fetch('converter.php', {
            method: 'POST',
            body: new URLSearchParams('action=check_usage')
        })
        .then(response => response.json())
        .then(data => {
            if (data.success && !data.is_logged_in) {
                const usageCount = document.querySelector('.usage-count');
                if (usageCount) {
                    usageCount.textContent = data.remaining;
                }
            }
        });
    }

    // Color picker preview
    const bgColorInput = document.getElementById('mergeBgColor');
    if (bgColorInput) {
        bgColorInput.addEventListener('input', function() {
            const preview = this.nextElementSibling;
            if (preview && preview.classList.contains('color-preview')) {
                preview.style.backgroundColor = this.value;
            }
        });
    }
});
