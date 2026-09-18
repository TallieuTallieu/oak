<?php

namespace Oak\Scheduler;

use Cron\CronExpression;
use Oak\Contracts\Console\OutputInterface;
use Oak\Contracts\Scheduler\JobInterface;
use Oak\Contracts\Scheduler\SchedulerInterface;

class Scheduler implements SchedulerInterface
{
    /**
     * @var array<int, JobInterface> $jobs
     */
    private $jobs = [];

    /**
     * @param string $command
     * @return JobInterface
     */
    public function run(string $command): JobInterface
    {
        $job = new Job($command);
        $this->jobs[] = $job;

        return $job;
    }

    /**
     * @param JobInterface $job
     * @return bool
     */
    public function isDue(JobInterface $job)
    {
        return (new CronExpression($job->getCronExpression()))->isDue();
    }

    public function runSchedule(OutputInterface $output)
    {
        foreach ($this->jobs as $job) {
            if ($this->isDue($job)) {
                $output->writeLine(
                    '[Scheduler] Running ' . $job->getCommand(),
                    OutputInterface::TYPE_INFO
                );
                $output->write($job->execute());
                $output->writeLine(
                    '[Scheduler] Done.',
                    OutputInterface::TYPE_INFO
                );
            } else {
                $output->writeLine(
                    'Nothing to run',
                    OutputInterface::TYPE_WARNING
                );
            }
        }
    }
}
