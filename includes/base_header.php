<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="author" content="Bruno Tipster">
    
    <title><?php echo isset($page_title) ? $page_title . ' - ' : ''; ?>InvestSystem</title>
    
    <!-- Favicon -->
    <link rel="shortcut icon" href="/investment/images/favicon.ico" type="image/x-icon">
    
    <!-- CSS Base -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <link href="/investment/css/main.css?v=<?php echo filemtime(BASE_PATH . '/css/main.css'); ?>" rel="stylesheet">
    
    <!-- Open Graph Base -->
    <meta property="og:title" content="<?php echo isset($page_title) ? $page_title . ' - ' : ''; ?>InvestSystem">
    <meta property="og:url" content="<?php echo SITE_URL . $_SERVER['REQUEST_URI']; ?>">
    
    <!-- Preload de fontes -->
    <link rel="preload" href="/investment/fonts/inter-v12-latin-regular.woff2" as="font" type="font/woff2" crossorigin>
    <link rel="preload" href="/investment/fonts/inter-v12-latin-600.woff2" as="font" type="font/woff2" crossorigin>

    <?php if (isset($extra_headers)) echo $extra_headers; ?>
</head>
<body class="<?php echo isset($body_class) ? $body_class : ''; ?>">