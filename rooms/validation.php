<?php
declare(strict_types=1);

function valid_room_number(string $value): bool
{
    return (bool) preg_match('/^[A-Za-z0-9-]{1,12}$/D', $value);
}

function valid_room_price(string $value): bool
{
    return is_numeric($value) && (float) $value > 0 && (float) $value <= 1000000;
}

function valid_room_capacity(int $value): bool
{
    return $value >= 1 && $value <= 20;
}

function valid_room_date(string $value): bool
{
    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
    return $date !== false && $date->format('Y-m-d') === $value;
}

function valid_block_range(string $from, string $to): bool
{
    return valid_room_date($from) && valid_room_date($to) && $from <= $to;
}
