<?php
declare(strict_types=1);

namespace Iutrace\Dashboard\Models;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Database\Query\Builder;

abstract class Metric
{
    protected Request $request;

    protected string $period;

    protected Carbon $from;

    protected Carbon $to;

    public function __construct(
        Request $request,
        Carbon $from,
        Carbon $to,
        string $period
    ) {
        $this->request = $request;
        $this->period = $period;
        $this->from = $from;
        $this->to = $to;
    }

    public abstract function query(): Builder;

    public static function name(): string
    {
        return '';
    }

    public function dateField(): string
    {
        return 'created_at';
    }
}
