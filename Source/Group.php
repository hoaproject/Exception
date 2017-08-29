<?php

declare(strict_types=1);

/**
 * Hoa
 *
 *
 * @license
 *
 * New BSD License
 *
 * Copyright © 2007-2017, Hoa community. All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *     * Redistributions of source code must retain the above copyright
 *       notice, this list of conditions and the following disclaimer.
 *     * Redistributions in binary form must reproduce the above copyright
 *       notice, this list of conditions and the following disclaimer in the
 *       documentation and/or other materials provided with the distribution.
 *     * Neither the name of the Hoa nor the names of its contributors may be
 *       used to endorse or promote products derived from this software without
 *       specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
 * AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
 * IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE
 * ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDERS AND CONTRIBUTORS BE
 * LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR
 * CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
 * SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
 * INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
 * CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
 * ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 */

namespace Hoa\Exception;

/**
 * An exception that contains a group of exceptions.
 */
class Group extends Exception implements \ArrayAccess, \IteratorAggregate, \Countable
{
    /**
     * All stack of exceptions.
     */
    protected $_group = null;



    /**
     * Allocates a new exception.
     */
    public function __construct(
        string $message,
        int $code            = 0,
        array $arguments     = [],
        \Exception $previous = null
    ) {
        parent::__construct($message, $code, $arguments, $previous);
        $this->_group = new \SplStack();
        $this->beginTransaction();

        return;
    }

    /**
     * Raises an exception as a string.
     */
    public function raise(bool $includePrevious = false): string
    {
        $out = parent::raise($includePrevious);

        if (0 >= count($this)) {
            return $out;
        }

        $out .= "\n\n" . 'Contains the following exceptions:';

        foreach ($this as $exception) {
            $out .=
                "\n\n" . '  • ' .
                str_replace(
                    "\n",
                    "\n" . '    ',
                    $exception->raise($includePrevious)
                );
        }

        return $out;
    }

    /**
     * Begins a transaction.
     */
    public function beginTransaction(): Group
    {
        $this->_group->push(new \ArrayObject());

        return $this;
    }

    /**
     * Rollbacks a transaction.
     */
    public function rollbackTransaction(): Group
    {
        if (1 >= count($this->_group)) {
            return $this;
        }

        $this->_group->pop();

        return $this;
    }

    /**
     * Commits a transaction.
     */
    public function commitTransaction(): Group
    {
        if (false === $this->hasUncommittedExceptions()) {
            $this->_group->pop();

            return $this;
        }

        foreach ($this->_group->pop() as $index => $exception) {
            $this[$index] = $exception;
        }

        return $this;
    }

    /**
     * Checks if there is uncommitted exceptions.
     */
    public function hasUncommittedExceptions(): bool
    {
        return
            1 < count($this->_group) &&
            0 < count($this->_group->top());
    }

    /**
     * Checks if an index in the group exists.
     */
    public function offsetExists($index): bool
    {
        foreach ($this->_group as $group) {
            if (isset($group[$index])) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns an exception from the group.
     */
    public function offsetGet($index): ?Exception
    {
        foreach ($this->_group as $group) {
            if (isset($group[$index])) {
                return $group[$index];
            }
        }

        return null;
    }

    /**
     * Sets an exception in the group.
     */
    public function offsetSet($index, $exception)
    {
        if (!($exception instanceof \Exception)) {
            return null;
        }

        $group = $this->_group->top();

        if (null === $index ||
            true === is_int($index)) {
            $group[] = $exception;
        } else {
            $group[$index] = $exception;
        }

        return;
    }

    /**
     * Removes an exception in the group.
     */
    public function offsetUnset($index): void
    {
        foreach ($this->_group as $group) {
            if (isset($group[$index])) {
                unset($group[$index]);
            }
        }
    }

    /**
     * Get committed exceptions in the group.
     *
     * @return  \ArrayObject
     */
    public function getExceptions(): \ArrayObject
    {
        return $this->_group->bottom();
    }

    /**
     * Get an iterator over all exceptions (committed or not).
     *
     * @return  \ArrayIterator
     */
    public function getIterator(): \ArrayIterator
    {
        return $this->getExceptions()->getIterator();
    }

    /**
     * Count the number of committed exceptions.
     *
     * @return  int
     */
    public function count(): int
    {
        return count($this->getExceptions());
    }

    /**
     * Count the stack size, i.e. the number of opened transactions.
     *
     * @return  int
     */
    public function getStackSize(): int
    {
        return count($this->_group);
    }
}
