<?php

namespace MWUnit\Runner\Result;

/**
 * Class FailureTestResult
 *
 * @package MWUnit\Runner\Result
 */
class FailureTestResult extends TestResult {
    /**
     * @var string
     */
    private $message;

    /**
     * FailureTestResult constructor.
     *
     * @param string $message
     * @param int $assertion_count
     * @param string $testname
     */
    public function __construct( string $message, string $testname, int $assertion_count ) {
        $this->message = $message;

        parent::__construct( $testname, $assertion_count );
    }

    /**
     * @inheritDoc
     */
    public function toString(): string {
        return "\033[41mF\033[0m";
    }

    /**
     * @inheritDoc
     */
    public function getResult(): int {
       return self::T_FAILED;
    }

    /**
     * @inheritDoc
     */
    public function getMessage(): string {
        return $this->message;
    }
}