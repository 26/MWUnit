<?php

namespace MWUnit\Runner\Result;

/**
 * Class RiskyTestResult
 *
 * @package MWUnit\Runner\Result
 */
class RiskyTestResult extends TestResult {
    /**
     * @var string
     */
    private $message;

    /**
     * RiskyTestResult constructor.
     *
     * @param string $message
     * @param string $testname
     * @param int $assertion_count
     */
    public function __construct( string $message, string $testname, int $assertion_count ) {
        $this->message = $message;

        parent::__construct( $testname, $assertion_count );
    }

    /**
     * @inheritDoc
     */
    public function toString(): string {
        return "\033[43mR\033[0m";
    }

    /**
     * @inheritDoc
     */
    public function getResult(): int {
        return self::T_RISKY;
    }

    /**
     * @inheritDoc
     */
    public function getMessage(): string {
        return $this->message;
    }
}