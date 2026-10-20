<?php
namespace AdPilot\Core;

final class Problem extends \RuntimeException
{
    public function __construct(public readonly string $key, string $message, public readonly int $status = 400)
    {
        parent::__construct($message);
    }
}
