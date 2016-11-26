<?php

namespace Nanbando\Core\Events;

/**
 * Trait for cancelable events.
 */
trait CancelTrait
{
    /**
     * @var bool
     */
    private $canceled;

    /**
     * Returns canceled.
     *
     * @return bool
     */
    public function isCanceled()
    {
        return $this->canceled;
    }

    /**
     * Cancel Event.
     *
     * @param bool $canceled
     *
     * @return $this
     */
    public function cancel($canceled = true)
    {
        $this->canceled = $canceled;
        $this->stopPropagation();

        return $this;
    }

    /**
     * Stops the propagation of the event to further event listeners.
     *
     * If multiple event listeners are connected to the same event, no
     * further event listener will be triggered once any trigger calls
     * stopPropagation().
     */
    abstract public function stopPropagation();
}
