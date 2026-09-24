<?php

declare(strict_types=1);

$directory = __DIR__ . '/generated';
if (!is_dir($directory)) {
    mkdir($directory, 0777, true);
}

for ($i = 0; $i < 200; $i++) {
    $name = sprintf('Class%03d', $i);
    $source = "<?php final class $name {\n";
    if ($i === 0) {
        for ($method = 0; $method < 12000; $method++) {
            $source .= "    public function method$method(): int { return $method; }\n";
        }
    }
    file_put_contents("$directory/$name.php", $source . "}\n");
}

echo "Generated 200 independent classes, one with 12000 methods.\n";
