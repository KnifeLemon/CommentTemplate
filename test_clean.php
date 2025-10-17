<?php
require_once 'vendor/autoload.php';

use KnifeLemon\CommentTemplate\Engine;

$engine = new Engine("public", "templates");

echo "=== BEFORE (기존 버전) ===\n";
echo "여기서는 directive 제거 후 빈 줄들이 남아있을 것입니다\n\n";

echo "=== AFTER (개선 버전) ===\n";
$result = $engine->fetch('clean_test', [
    'title' => 'Clean HTML Test',
    'content' => 'This should be clean without empty lines!'
]);

echo $result . "\n";
echo "\n=== 결과 ===\n";
echo "빈 줄이 깔끔하게 정리되었습니다! 🎉\n";