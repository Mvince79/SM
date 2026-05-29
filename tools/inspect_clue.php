<?php
require __DIR__ . '/../vendor/autoload.php';

try {
    $rc = new ReflectionClass('App\\Http\\Controllers\\Api\\V1\\ClueApiController');
    echo 'File: ' . $rc->getFileName() . PHP_EOL;
    echo 'Start: ' . $rc->getStartLine() . ' End: ' . $rc->getEndLine() . PHP_EOL;
    foreach ($rc->getMethods() as $m) {
        echo $m->name . ' (' . ($m->isPublic() ? 'public' : ($m->isProtected() ? 'protected' : 'private')) . ')' . PHP_EOL;
    }
} catch (\Throwable $e) {
    echo 'Error: ' . $e->getMessage() . PHP_EOL;
}
