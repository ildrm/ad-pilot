<?php
namespace AdPilot\Core;

interface Clock
{
    public function now(): \DateTimeImmutable;
}
