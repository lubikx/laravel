<?php declare(strict_types=1);

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tmdb\Laravel\Tests;

use Prophecy\Prophet;
use Symfony\Contracts\EventDispatcher\Event;
use Tmdb\Laravel\Adapters\EventDispatcherLaravel as AdapterDispatcher;

class EventDispatcherTest extends \PHPUnit\Framework\TestCase
{
    const EVENT = 'foo';

    protected AdapterDispatcher $dispatcher;

    protected $laravel;
    protected $symfony;

    private $listener;

    protected function setUp(): void
    {
        $this->dispatcher = $this->createEventDispatcher();
    }

    public function test_it_dispatches_events_through_both_laravel_and_symfony()
    {
        $this->expectNotToPerformAssertions();

        $event = new Event();

        $this->laravel->dispatch(static::EVENT, [$event])->shouldBeCalled();
        $this->symfony->dispatch($event, static::EVENT)->shouldBeCalled();

        $this->dispatcher->dispatch($event,static::EVENT);
    }

    public function test_it_returns_the_event_returned_by_the_symfony_dispatcher()
    {
        $event = new Event();
        $this->symfony->dispatch($event, static::EVENT)->willReturn($event);
        $this->assertEquals($event, $this->dispatcher->dispatch($event, static::EVENT));
    }

    public function test_it_adds_listeners_to_the_symfony_dispatcher()
    {
        $this->expectNotToPerformAssertions();
        $listener = function () { };
        $this->dispatcher->addListener(static::EVENT, $listener, 1);
        $this->symfony->addListener(static::EVENT, $listener, 1)->shouldHaveBeenCalled();
    }

    public function test_it_adds_a_subscriber_to_the_symfony_dispatcher()
    {
        $this->expectNotToPerformAssertions();
        $prophet = new Prophet();
        $subscriber = $prophet->prophesize('Symfony\Component\EventDispatcher\EventSubscriberInterface');
        $this->dispatcher->addSubscriber($subscriber->reveal());
        $this->symfony->addSubscriber($subscriber->reveal())->shouldHaveBeenCalled();
    }

    public function test_it_removes_listeners_from_the_symfony_dispatcher()
    {
        $this->expectNotToPerformAssertions();
        $listener = function ($event) {};
        $this->dispatcher->removeListener(static::EVENT, $listener);
        $this->symfony->removeListener(static::EVENT, $listener)->shouldHaveBeenCalled();
    }

    public function test_it_removes_subscriptions_from_the_symfony_dispatcher()
    {
        $this->expectNotToPerformAssertions();
        $prophet = new Prophet();
        $subscriber = $prophet->prophesize('Symfony\Component\EventDispatcher\EventSubscriberInterface');
        $this->dispatcher->removeSubscriber($subscriber->reveal());
        $this->symfony->removeSubscriber($subscriber->reveal())->shouldHaveBeenCalled();
    }

    /*
     * We are not checking Laravel's listeners as its interface does not contain a getListeners function
     */
    public function test_it_gets_listeners_from_the_symfony_dispatcher()
    {
        $this->symfony->getListeners(static::EVENT)->willReturn(['bar']);
        $this->assertEquals(['bar'], $this->dispatcher->getListeners(static::EVENT));
    }

    public function test_it_asks_the_symfony_dispatcher_if_it_has_a_listener()
    {
        $this->symfony->hasListeners(static::EVENT)->willReturn(true);
        $this->assertTrue($this->dispatcher->hasListeners(static::EVENT));
    }

    public function test_it_asks_the_laravel_dispatcher_if_it_has_a_listener()
    {
        $this->symfony->hasListeners(static::EVENT)->willReturn(false);
        $this->laravel->hasListeners(static::EVENT)->willReturn(true);
        $this->assertTrue($this->dispatcher->hasListeners(static::EVENT));
    }

    public function test_it_asks_both_the_symfony_and_laravel_dispatcher_if_it_has_a_listener()
    {
        $this->symfony->hasListeners(static::EVENT)->willReturn(false);
        $this->laravel->hasListeners(static::EVENT)->willReturn(false);
        $this->assertFalse($this->dispatcher->hasListeners(static::EVENT));
    }

    protected function createEventDispatcher(): AdapterDispatcher
    {
        $prophet = new Prophet();

        $this->laravel = $prophet->prophesize('Illuminate\Events\Dispatcher');
        $this->symfony = $prophet->prophesize('Symfony\Component\EventDispatcher\EventDispatcher');

        return new AdapterDispatcher(
            $this->laravel->reveal(),
            $this->symfony->reveal()
        );
    }
}
