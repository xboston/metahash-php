<?php declare(strict_types=1);
/**
 * Copyright (c) 2019.
 */

namespace Metahash;

use JsonSerializable;

/**
 * Class HistoryFilters
 *
 * @package Metahash
 */
class HistoryFilters implements JsonSerializable
{
    public $isInput = false;   // - Display only isInput transactions
    public $isOutput = false;  // - Display only isOutput transactions
    public $isSuccess = false; // - Display only success transactions
    public $isForging = false; // - Display only forging transactions
    public $isTest = false;    // - Display only test transactions
    public $isDelegate = false;// - Display only delegation transactions

    /**
     * @return array
     */
    public function jsonSerialize(): array
    {
        $filters = \get_object_vars($this);
        $result = [];
        foreach ($filters as $filter => $value) {
            if ($value) {
                $result[$filter] = true;
            }
        }
        return $result;
    }

    /**
     * @param  bool  $isInput
     */
    public function setIsInput(bool $isInput): void
    {
        $this->isInput = $isInput;
    }

    /**
     * @param  bool  $isOutput
     */
    public function setIsOutput(bool $isOutput): void
    {
        $this->isOutput = $isOutput;
    }

    /**
     * @param  bool  $isSuccess
     */
    public function setIsSuccess(bool $isSuccess): void
    {
        $this->isSuccess = $isSuccess;
    }

    /**
     * @param  bool  $isForging
     */
    public function setIsForging(bool $isForging): void
    {
        $this->isForging = $isForging;
    }

    /**
     * @param  bool  $isTest
     */
    public function setIsTest(bool $isTest): void
    {
        $this->isTest = $isTest;
    }

    /**
     * @param  bool  $isDelegate
     */
    public function setIsDelegate(bool $isDelegate): void
    {
        $this->isDelegate = $isDelegate;
    }
}
