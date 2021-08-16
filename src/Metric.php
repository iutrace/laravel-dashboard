<?php


namespace Iutrace\Dashboard;


use Illuminate\Database\Query\Builder;

abstract class Metric
{
    public abstract function query(): Builder;

    public function name(): string
    {
        return get_class($this);
    }

    public function dateField(): string
    {
        return 'created_at';
    }

    public function url(): ?string
    {
        return null;
    }

    public function channel(): ?string
    {
        return null;
    }
}