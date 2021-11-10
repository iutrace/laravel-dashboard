<?php

declare(strict_types=1);

namespace Iutrace\Dashboard;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;

abstract class Metric implements Jsonable, Arrayable
{
    /**
     * @return Builder|\Illuminate\Database\Eloquent\Builder
     */
    abstract public function query(Request $request);

    abstract public function name(): string;

    public function dateField(): ?string
    {
        return 'created_at';
    }

    /**
     * Default value when no data present
     * @return mixed
     */
    public function defaultValue()
    {
        return 0.0;
    }

    public function toJson($options = 0)
    {
        return $this->toArray();
    }

    public function toArray()
    {
        return [
            'name' => $this->name(),
        ];
    }
}
