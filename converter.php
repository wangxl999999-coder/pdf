<?php
require_once 'config.php';

createDirectories();
initializeDatabase();
cleanOldUploads();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => '无效的请求方法']);
    exit;
}

$action = $_POST['action'] ?? '';

switch ($action) {
    case 'word_to_pdf':
        handleWordToPdf();
        break;
    case 'pdf_to_image':
        handlePdfToImage();
        break;
    case 'merge_images':
        handleMergeImages();
        break;
    case 'check_usage':
        checkUsage();
        break;
    default:
        echo json_encode(['success' => false, 'message' => '未知的操作']);
}

function checkUsage() {
    $remaining = getRemainingUsage();
    $isLoggedIn = isLoggedIn();
    
    echo json_encode([
        'success' => true,
        'can_use' => $isLoggedIn || $remaining > 0,
        'remaining' => $remaining,
        'is_logged_in' => $isLoggedIn,
        'username' => $_SESSION['username'] ?? null
    ]);
}

function handleWordToPdf() {
    if (!canUseService()) {
        echo json_encode(['success' => false, 'message' => '使用次数已用完，请登录后继续使用', 'need_login' => true]);
        return;
    }
    
    if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['success' => false, 'message' => '文件上传失败']);
        return;
    }
    
    $file = $_FILES['file'];
    $originalName = $file['name'];
    $fileExtension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    
    $allowedExtensions = ['doc', 'docx', 'rtf', 'odt'];
    if (!in_array($fileExtension, $allowedExtensions)) {
        echo json_encode(['success' => false, 'message' => '不支持的文件格式，请上传Word文档']);
        return;
    }
    
    $uniqueId = generateUniqueId();
    $sanitizedName = sanitizeFilename($originalName);
    $inputPath = UPLOAD_DIR . $uniqueId . '_' . $sanitizedName;
    $outputPath = UPLOAD_DIR . $uniqueId . '_output.pdf';
    
    if (!move_uploaded_file($file['tmp_name'], $inputPath)) {
        echo json_encode(['success' => false, 'message' => '文件保存失败']);
        return;
    }
    
    $result = convertWordToPdf($inputPath, $outputPath, $fileExtension);
    
    unlink($inputPath);
    
    if ($result['success']) {
        incrementUsage();
        echo json_encode([
            'success' => true,
            'message' => '转换成功',
            'download_url' => 'download.php?file=' . urlencode(basename($outputPath)) . '&name=' . urlencode(pathinfo($sanitizedName, PATHINFO_FILENAME) . '.pdf'),
            'file_size' => filesize($outputPath)
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => $result['message']]);
    }
}

function handlePdfToImage() {
    if (!canUseService()) {
        echo json_encode(['success' => false, 'message' => '使用次数已用完，请登录后继续使用', 'need_login' => true]);
        return;
    }
    
    if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['success' => false, 'message' => '文件上传失败']);
        return;
    }
    
    $file = $_FILES['file'];
    $originalName = $file['name'];
    $fileExtension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    
    if ($fileExtension !== 'pdf') {
        echo json_encode(['success' => false, 'message' => '请上传PDF文件']);
        return;
    }
    
    $orientation = $_POST['orientation'] ?? 'portrait';
    $mode = $_POST['mode'] ?? 'single';
    $format = $_POST['format'] ?? 'png';
    $dpi = intval($_POST['dpi'] ?? 150);
    
    $uniqueId = generateUniqueId();
    $sanitizedName = sanitizeFilename($originalName);
    $inputPath = UPLOAD_DIR . $uniqueId . '_' . $sanitizedName;
    
    if (!move_uploaded_file($file['tmp_name'], $inputPath)) {
        echo json_encode(['success' => false, 'message' => '文件保存失败']);
        return;
    }
    
    $result = convertPdfToImage($inputPath, $uniqueId, $orientation, $mode, $format, $dpi);
    
    unlink($inputPath);
    
    if ($result['success']) {
        incrementUsage();
        
        if ($mode === 'single') {
            echo json_encode([
                'success' => true,
                'message' => '转换成功',
                'download_url' => 'download.php?file=' . urlencode($result['output_file']) . '&name=' . urlencode(pathinfo($sanitizedName, PATHINFO_FILENAME) . '.' . $format),
                'file_size' => filesize(UPLOAD_DIR . $result['output_file']),
                'mode' => 'single'
            ]);
        } else {
            echo json_encode([
                'success' => true,
                'message' => '转换成功',
                'files' => $result['files'],
                'count' => count($result['files']),
                'mode' => 'multiple'
            ]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => $result['message']]);
    }
}

function handleMergeImages() {
    if (!canUseService()) {
        echo json_encode(['success' => false, 'message' => '使用次数已用完，请登录后继续使用', 'need_login' => true]);
        return;
    }
    
    if (!isset($_FILES['files']) || empty($_FILES['files']['name'][0])) {
        echo json_encode(['success' => false, 'message' => '请至少上传一张图片']);
        return;
    }
    
    $files = $_FILES['files'];
    $orientation = $_POST['orientation'] ?? 'portrait';
    $format = $_POST['format'] ?? 'png';
    $spacing = intval($_POST['spacing'] ?? 10);
    $bgColor = $_POST['bg_color'] ?? '#ffffff';
    
    $imagePaths = [];
    $uniqueId = generateUniqueId();
    
    for ($i = 0; $i < count($files['name']); $i++) {
        if ($files['error'][$i] !== UPLOAD_ERR_OK) {
            continue;
        }
        
        $originalName = $files['name'][$i];
        $fileExtension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp'];
        if (!in_array($fileExtension, $allowedExtensions)) {
            continue;
        }
        
        $sanitizedName = sanitizeFileName($originalName);
        $tempPath = UPLOAD_DIR . $uniqueId . '_img_' . $i . '_' . $sanitizedName;
        
        if (move_uploaded_file($files['tmp_name'][$i], $tempPath)) {
            $imagePaths[] = $tempPath;
        }
    }
    
    if (count($imagePaths) < 1) {
        foreach ($imagePaths as $path) {
            if (file_exists($path)) unlink($path);
        }
        echo json_encode(['success' => false, 'message' => '没有有效的图片文件']);
        return;
    }
    
    $outputPath = UPLOAD_DIR . $uniqueId . '_merged.' . $format;
    $result = mergeImages($imagePaths, $outputPath, $orientation, $format, $spacing, $bgColor);
    
    foreach ($imagePaths as $path) {
        if (file_exists($path)) unlink($path);
    }
    
    if ($result['success']) {
        incrementUsage();
        echo json_encode([
            'success' => true,
            'message' => '合并成功',
            'download_url' => 'download.php?file=' . urlencode(basename($outputPath)) . '&name=' . urlencode('merged_images.' . $format),
            'file_size' => filesize($outputPath),
            'images_count' => count($imagePaths)
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => $result['message']]);
    }
}

function sanitizeFileName($filename) {
    return preg_replace('/[^a-zA-Z0-9._-]/', '_', $filename);
}

function convertWordToPdf($inputPath, $outputPath, $extension) {
    if (!class_exists('COM')) {
        return ['success' => false, 'message' => '服务器不支持COM组件，无法转换Word文档'];
    }
    
    try {
        $word = new COM("word.application") or die("无法启动Word");
        $word->Visible = 0;
        $word->Documents->Open($inputPath);
        
        $wdFormatPDF = 17;
        $word->ActiveDocument->SaveAs($outputPath, $wdFormatPDF);
        $word->ActiveDocument->Close(false);
        $word->Quit();
        
        unset($word);
        
        if (file_exists($outputPath)) {
            return ['success' => true];
        }
        
        return ['success' => false, 'message' => '转换失败，输出文件未生成'];
    } catch (Exception $e) {
        return ['success' => false, 'message' => '转换过程中出错: ' . $e->getMessage()];
    }
}

function convertPdfToImage($inputPath, $uniqueId, $orientation, $mode, $format, $dpi) {
    if (!class_exists('Imagick')) {
        return ['success' => false, 'message' => '服务器未安装Imagick扩展，无法转换PDF'];
    }
    
    try {
        $imagick = new Imagick();
        $imagick->setResolution($dpi, $dpi);
        $imagick->readImage($inputPath);
        
        $pageCount = $imagick->getNumberImages();
        $resultFiles = [];
        
        for ($i = 0; $i < $pageCount; $i++) {
            $imagick->setIteratorIndex($i);
            $page = $imagick->getImage();
            
            if ($orientation === 'landscape') {
                $width = $page->getImageWidth();
                $height = $page->getImageHeight();
                if ($height > $width) {
                    $page->rotateImage(new ImagickPixel('none'), 90);
                }
            }
            
            $page->setImageFormat($format);
            $page->setImageCompressionQuality(90);
            
            $outputFile = $uniqueId . '_page_' . ($i + 1) . '.' . $format;
            $outputPath = UPLOAD_DIR . $outputFile;
            
            $page->writeImage($outputPath);
            $resultFiles[] = [
                'file' => $outputFile,
                'page' => $i + 1,
                'download_url' => 'download.php?file=' . urlencode($outputFile) . '&name=' . urlencode('page_' . ($i + 1) . '.' . $format)
            ];
            
            $page->clear();
            $page->destroy();
        }
        
        $imagick->clear();
        $imagick->destroy();
        
        if ($mode === 'single' && $pageCount > 0) {
            return ['success' => true, 'output_file' => $resultFiles[0]['file']];
        }
        
        return ['success' => true, 'files' => $resultFiles];
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'PDF转换失败: ' . $e->getMessage()];
    }
}

function mergeImages($imagePaths, $outputPath, $orientation, $format, $spacing, $bgColor) {
    if (!class_exists('Imagick')) {
        return ['success' => false, 'message' => '服务器未安装Imagick扩展，无法合并图片'];
    }
    
    try {
        $images = [];
        $totalWidth = 0;
        $totalHeight = 0;
        $maxWidth = 0;
        $maxHeight = 0;
        
        foreach ($imagePaths as $path) {
            $img = new Imagick($path);
            $img->setImageFormat('png');
            
            $width = $img->getImageWidth();
            $height = $img->getImageHeight();
            
            $maxWidth = max($maxWidth, $width);
            $maxHeight = max($maxHeight, $height);
            
            $images[] = [
                'image' => $img,
                'width' => $width,
                'height' => $height
            ];
        }
        
        if (empty($images)) {
            return ['success' => false, 'message' => '没有可合并的图片'];
        }
        
        $canvas = new Imagick();
        
        if ($orientation === 'vertical') {
            $totalWidth = $maxWidth;
            $totalHeight = array_sum(array_column($images, 'height')) + ($spacing * (count($images) - 1));
        } else {
            $totalWidth = array_sum(array_column($images, 'width')) + ($spacing * (count($images) - 1));
            $totalHeight = $maxHeight;
        }
        
        $bg = new ImagickPixel($bgColor);
        $canvas->newImage($totalWidth, $totalHeight, $bg);
        $canvas->setImageFormat($format);
        
        $currentX = 0;
        $currentY = 0;
        
        foreach ($images as $imgData) {
            $img = $imgData['image'];
            
            if ($orientation === 'vertical') {
                $x = ($maxWidth - $imgData['width']) / 2;
                $y = $currentY;
                $currentY += $imgData['height'] + $spacing;
            } else {
                $x = $currentX;
                $y = ($maxHeight - $imgData['height']) / 2;
                $currentX += $imgData['width'] + $spacing;
            }
            
            $canvas->compositeImage($img, Imagick::COMPOSITE_OVER, $x, $y);
            $img->clear();
            $img->destroy();
        }
        
        $canvas->setImageCompressionQuality(90);
        $canvas->writeImage($outputPath);
        $canvas->clear();
        $canvas->destroy();
        
        return ['success' => true];
    } catch (Exception $e) {
        return ['success' => false, 'message' => '图片合并失败: ' . $e->getMessage()];
    }
}
