<?php

namespace Iutrace\Dashboard\Models;

use Illuminate\Support\Str;

abstract class Metric
{
    public abstract function data();

    public abstract function template(): string;

    public function name(): string
    {
        return Str::of(substr(strrchr(get_class($this), '\\'), 1))->snake();
    }

    public function dateField(): string
    {
        return 'created_at';
    }

    public function query()
    {
        return null;
    }

    public function layout(): ?string
    {
        return null;
    }

    public function url(): ?string
    {
        return null;
    }

    public function channel(): ?string
    {
        return null;
    }

    public function hasPeriodSelector(): ?bool
    {
        return null;
    }
}
