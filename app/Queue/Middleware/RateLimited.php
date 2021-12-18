<?php

namespace App\Queue\Middleware;

use Illuminate\Queue\Jobs\Job;
use Illuminate\Support\Facades\Cache;

class RateLimited
{
    /**
     * @param Job $job
     */
    public function handle($job, $next) {

        // We need some identifier for a group of jobs
        // In case we want to apply the same cache lock for all jobs,
        // set the same group to all jobs
        $jobGroup = $job->getJobGroup();

        // Create a cache lock for 5 seconds
        $lock = Cache::lock($jobGroup, 5);

        // Trying to get a lock and fire a job (if 5 seconds passed)
        if ($lock->get()) {
            return $next($job);
        }

        // Send a job back to the queue if the lock can't acquired
        return $job->release();
    }
}
