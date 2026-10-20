<?php
function expect(bool $condition, string $name): void
{
    if (!$condition) { throw new RuntimeException($name); }
    echo "PASS {$name}\n";
}
function expectProblem(callable $operation, string $key, string $name): void
{
    try { $operation(); } catch (AdPilot\Core\Problem $error) {
        expect($error->key === $key, $name . ' [' . $error->key . ']');
        return;
    }
    throw new RuntimeException('Expected denial: ' . $name);
}
