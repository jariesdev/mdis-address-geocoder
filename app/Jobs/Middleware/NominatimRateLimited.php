<?php

namespace App\Jobs\Middleware;

use App\Jobs\BatchCustomerCoordinateSearch;
use Illuminate\Support\Facades\Redis;

class NominatimRateLimited
{
    /**
     * Process the queued job.
     *
     * @param  mixed  $job
     * @param  callable  $next
     * @return mixed
     */
    public function handle(BatchCustomerCoordinateSearch $job, $next)
    {
        Redis::throttle('nominatim')
            ->block(0)->allow(1)->every(5)
            ->then(function () use ($job, $next) {
                // Lock obtained...
                $next($job);
            }, function () use ($job) {
                // Could not obtain lock...
                dispatch(new BatchCustomerCoordinateSearch($job->customers, $job->customerImport))->delay(now()->addMinute());
            });
    }
}
