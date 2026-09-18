<?php

namespace Oak\Scheduler;

trait CrontabExpressionTrait
{
    /**
     * @var string $minute
     */
    private $minute = '*';

    /**
     * @var string $hour
     */
    private $hour = '*';

    /**
     * @var string $day
     */
    private $day = '*';

    /**
     * @var string $month
     */
    private $month = '*';

    /**
     * @var string $dayOfWeek
     */
    private $dayOfWeek = '*';

    /**
     * @var int|null $currentStep
     */
    private $currentStep;

    /**
     * @return string
     */
    public function getCronExpression(): string
    {
        return $this->minute .
            ' ' .
            $this->hour .
            ' ' .
            $this->day .
            ' ' .
            $this->month .
            ' ' .
            $this->dayOfWeek;
    }

    /**
     * @param int $step
     * @return $this
     */
    public function every(int $step)
    {
        $this->currentStep = $step;

        return $this;
    }

    /**
     * @return void
     */
    public function minutes()
    {
        if (!$this->currentStep) {
            return;
        }

        $this->minute = '*/' . $this->currentStep;

        $this->currentStep = null;
    }

    /**
     * @return void
     */
    public function hours()
    {
        if (!$this->currentStep) {
            return;
        }

        $this->minute = '0';
        $this->hour = '*/' . $this->currentStep;

        $this->currentStep = null;
    }

    /**
     * @return void
     */
    public function days()
    {
        if (!$this->currentStep) {
            return;
        }

        $this->minute = '0';
        $this->hour = '0';
        $this->day = '*/' . $this->currentStep;

        $this->currentStep = null;
    }

    /**
     * @return void
     */
    public function months()
    {
        if (!$this->currentStep) {
            return;
        }

        $this->minute = '0';
        $this->hour = '0';
        $this->day = '1';
        $this->month = '*/' . $this->currentStep;

        $this->currentStep = null;
    }

    /**
     * @return void
     */
    public function hourly()
    {
        $this->hour = '0';
    }

    /**
     * @return void
     */
    public function daily()
    {
        $this->minute = '0';
        $this->hour = '0';
    }

    /**
     * @return void
     */
    public function weekly()
    {
        $this->minute = '0';
        $this->hour = '0';
        $this->dayOfWeek = '0';
    }

    /**
     * @return void
     */
    public function monthly()
    {
        $this->minute = '0';
        $this->hour = '0';
        $this->day = '1';
    }
}
