<?php
// 启用错误报告
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 返回简单的JSON响应
header('Content-Type: application/json');
echo json_encode(['success' => true, 'message' => 'PHP is working']); 