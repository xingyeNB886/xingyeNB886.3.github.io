<?php
// 定义函数以获取目录中最大的文件编号
function getMaxFileName($dir, $ext) {
    $files = glob($dir . "/*." . $ext);
    if (empty($files)) return 0;
    $numbers = array_map(function($file) {
        return (int)preg_replace('/[^0-9]/', '', basename($file));
    }, $files);
    return max($numbers);
}

// 定义函数以上传文件并重命名
function uploadFile($file, $uploadDir, $maxNum, $ext = 'jpg') {
    $newFileName = str_pad($maxNum + 1, 5, '0', STR_PAD_LEFT) . '.' . $ext;
    $uploadPath = $uploadDir . '/' . $newFileName;
    if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
        return $newFileName;
    } else {
        return false;
    }
}

// 初始化消息数组
$messages = [];

// 处理图片上传
if (isset($_POST['upload_images'])) {
    if (isset($_FILES['images']) && !empty($_FILES['images']['name'][0])) {
        $imageFiles = $_FILES['images'];
        $maxImageNum = getMaxFileName('img', 'jpg');
        $uploadedImages = [];

        foreach ($imageFiles['tmp_name'] as $index => $tmpName) {
            if ($tmpName) {
                $newFileName = uploadFile(
                    ['tmp_name' => $tmpName],
                    'img',
                    $maxImageNum++
                );
                if ($newFileName) {
                    $uploadedImages[] = $newFileName;
                } else {
                    $messages[] = "第 " . ($index + 1) . " 张图片上传失败。";
                }
            }
        }

        if (!empty($uploadedImages)) {
            // 处理 index.html 文件，插入图片
            $htmlFile = 'index.html';
            if (file_exists($htmlFile)) {
                $htmlContent = file_get_contents($htmlFile);
                $fullscreenPos = strpos($htmlContent, '<div id="fullscreen" class="fullscreen" onclick="closeFullscreen(event)">');

                if ($fullscreenPos !== false) {
                    // 获取 fullscreen 之前的内容
                    $beforeFullscreen = substr($htmlContent, 0, $fullscreenPos);
                    
                    // 找到最后一个 </p> 或 </div> 的位置
                    $lastPPos = strrpos($beforeFullscreen, '</p>');
                    $lastDivPos = strrpos($beforeFullscreen, '</div>');
                    
                    $insertPos = $fullscreenPos;  // 默认插入到 fullscreen 之前
                    $imageHtml = "";

                    if ($lastPPos !== false && $lastPPos > $lastDivPos) {
                        // 最近的标签是 </p>，新建一个 div 插入图片
                        $insertPos = $lastPPos + 4; // 插入到 </p> 之后
                        $imageHtml = "<div class='section media-row hide-scrollbar'>\n";
                        foreach ($uploadedImages as $img) {
                            $imageHtml .= "<img data-src='img/$img' class='lazyload' onclick='openFullscreen(this.src)'>\n";
                        }
                        $imageHtml .= "</div>\n";
                    } elseif ($lastDivPos !== false) {
                        // 最近的标签是 </div>，直接插入到这个 div 内
                        $insertPos = $lastDivPos; // 插入到 </div> 之前
                        $imageHtml = "";
                        foreach ($uploadedImages as $img) {
                            $imageHtml .= "<img data-src='img/$img' class='lazyload' onclick='openFullscreen(this.src)'>\n";
                        }
                        // 插入到现有 div 中的末尾
                        $divClosePos = strpos($htmlContent, '</div>', $lastDivPos);
                        if ($divClosePos !== false) {
                            $insertPos = $divClosePos;
                        }
                    }

                    // 插入图片代码到指定位置
                    $htmlContent = substr_replace($htmlContent, $imageHtml, $insertPos, 0);
                    file_put_contents($htmlFile, $htmlContent);
                    $messages[] = "图片上传并插入成功！";
                } else {
                    $messages[] = "未找到 <div id=\"fullscreen\" ...> 位置。";
                }
            } else {
                $messages[] = "未找到 index.html 文件。";
            }
        }
    } else {
        $messages[] = "请先选择图片进行上传。";
    }
}


// 处理封面上传
if (isset($_POST['upload_cover'])) {
    if (isset($_FILES['cover']) && !empty($_FILES['cover']['name'])) {
        $coverFile = $_FILES['cover'];
        $maxCoverNum = getMaxFileName('page', 'jpg');
        $newCoverFileName = uploadFile($coverFile, 'page', $maxCoverNum);

        if ($newCoverFileName) {
            // 生成对应的视频路径
            $videoFileName = 'video/' . str_pad($maxCoverNum + 1, 5, '0', STR_PAD_LEFT) . '.mp4';

            // 处理 index.html 文件，插入封面
            $htmlFile = 'index.html';
            if (file_exists($htmlFile)) {
                $htmlContent = file_get_contents($htmlFile);
                $fullscreenPos = strpos($htmlContent, '<div id="fullscreen" class="fullscreen" onclick="closeFullscreen(event)">');

                if ($fullscreenPos !== false) {
                    // 获取 fullscreen 之前的内容
                    $beforeFullscreen = substr($htmlContent, 0, $fullscreenPos);
                    
                    // 找到最后一个 </p> 或 </div> 的位置
                    $lastPPos = strrpos($beforeFullscreen, '</p>');
                    $lastDivPos = strrpos($beforeFullscreen, '</div>');
                    
                    $insertPos = $fullscreenPos;  // 默认插入到 fullscreen 之前
                    $coverHtml = "";

                    if ($lastPPos !== false && $lastPPos > $lastDivPos) {
                        // 最近的标签是 </p>，新建一个 div 插入封面
                        $insertPos = $lastPPos + 4; // 插入到 </p> 之后
                        $coverHtml = "<div class='section media-row hide-scrollbar'>\n";
                        $coverHtml .= "<img data-src='page/$newCoverFileName' class='lazyload' onclick='openFullscreen(\"$videoFileName\", true)'>\n";
                        $coverHtml .= "</div>\n";
                    } elseif ($lastDivPos !== false) {
                        // 最近的标签是 </div>，直接插入到这个 div 内
                        $insertPos = $lastDivPos; // 插入到 </div> 之前
                        $coverHtml = "<img data-src='page/$newCoverFileName' class='lazyload' onclick='openFullscreen(\"$videoFileName\", true)'>\n";
                        // 插入到现有 div 中的末尾
                        $divClosePos = strpos($htmlContent, '</div>', $lastDivPos);
                        if ($divClosePos !== false) {
                            $insertPos = $divClosePos;
                        }
                    }

                    // 插入封面代码到指定位置
                    $htmlContent = substr_replace($htmlContent, $coverHtml, $insertPos, 0);
                    file_put_contents($htmlFile, $htmlContent);
                    $messages[] = "封面上传并插入成功！对应的视频路径为 $videoFileName 。";
                } else {
                    $messages[] = "未找到 <div id=\"fullscreen\" ...> 位置。";
                }
            } else {
                $messages[] = "未找到 index.html 文件。";
            }
        } else {
            $messages[] = "封面上传失败。";
        }
    } else {
        $messages[] = "请先选择封面图片进行上传。";
    }
}



// 处理文本上传
if (isset($_POST['upload_text'])) {
    if (isset($_POST['text']) && !empty(trim($_POST['text']))) {
        $textInput = htmlspecialchars(trim($_POST['text']));

        // 处理 index.html 文件，插入文本
        $htmlFile = 'index.html';
        if (file_exists($htmlFile)) {
            $htmlContent = file_get_contents($htmlFile);
            $fullscreenPos = strpos($htmlContent, '<div id="fullscreen" class="fullscreen" onclick="closeFullscreen(event)">');

            if ($fullscreenPos !== false) {
                // 构建文本HTML，直接用 <p> 包裹文本
                $textHtml = "<p>$textInput</p>\n";

                // 直接在 fullscreen div 之前插入文本
                $htmlContent = substr_replace($htmlContent, $textHtml, $fullscreenPos, 0);
                file_put_contents($htmlFile, $htmlContent);
                $messages[] = "文本上传并插入成功！";
            } else {
                $messages[] = "未找到 <div id=\"fullscreen\" ...> 位置。";
            }
        } else {
            $messages[] = "未找到 index.html 文件。";
        }
    } else {
        $messages[] = "请输入要上传的文本。";
    }
}

?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>上传图片、封面和文字</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #eef2f5;
            padding: 20px;
        }
        .upload-section {
            margin-bottom: 30px;
            padding: 20px;
            border: 2px solid #4CAF50;
            border-radius: 10px;
            background-color: #ffffff;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .upload-section h3 {
            margin-bottom: 15px;
            color: #4CAF50;
        }
        .upload-section label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
        }
        .upload-section input[type="file"], 
        .upload-section textarea {
            width: 100%;
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }
        .upload-section textarea {
            resize: vertical;
            height: 100px;
        }
        .upload-section button {
            padding: 10px 25px;
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
        }
        .upload-section button:hover {
            background-color: #45a049;
        }
        .messages {
            margin-bottom: 20px;
            padding: 10px;
            border-radius: 5px;
        }
        .messages.success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .messages.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>
<body>

    <h1>上传后台</h1>
<h2>图片在img目录，视频在video，封面在page</h2>
    <!-- 显示消息 -->
    <?php if (!empty($messages)): ?>
        <?php foreach ($messages as $msg): ?>
            <div class="messages <?php echo (strpos($msg, '成功') !== false) ? 'success' : 'error'; ?>">
                <?php echo $msg; ?>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <!-- 图片上传部分 -->
    <div class="upload-section">
        <h3>图片上传</h3>
        <form action="yyds.php" method="post" enctype="multipart/form-data">
            <label for="images">选择图片（可多选）:</label>
            <input type="file" name="images[]" id="images" multiple accept="image/*">
            <button type="submit" name="upload_images">上传图片</button>
        </form>
    </div>

    <!-- 封面上传部分 -->
    <h2>（视频请通过其他方式，并与封面图相同命名，如page/12345.jpg对应video/12345.mp4）</h2>
    <div class="upload-section">
        <h3>封面上传</h3>
        <form action="yyds.php" method="post" enctype="multipart/form-data">
            <label for="cover">选择封面图:</label>
            <input type="file" name="cover" id="cover" accept="image/*">
            <button type="submit" name="upload_cover">上传封面</button>
        </form>
    </div>

    <!-- 文本上传部分 -->
    <div class="upload-section">
        <h3>文本输入</h3>
        <form action="yyds.php" method="post">
            <label for="text">输入文字:</label>
            <textarea name="text" id="text" placeholder="请输入要上传的文本内容..."></textarea>
            <button type="submit" name="upload_text">上传文本</button>
        </form>
    </div>

</body>
</html>
