![Hoa](http://static.hoa-project.net/Image/Hoa_small.png)

Hoa is a **modular**, **extensible** and **structured** set of PHP libraries.
Moreover, Hoa aims at being a bridge between industrial and research worlds.

# Hoa\Exception ![state](http://central.hoa-project.net/State/Exception)

This library allows to use advanced exceptions. It provides generic exceptions
(that are sent over the `hoa://Event/Exception` event channel), idle exceptions
(that are not sent over an event channel), uncaught exception handlers, errors
to exceptions handler and group of exceptions (with transactions).

## Installation

With [Composer](http://getcomposer.org/), to include this library into your
dependencies, you need to require
[`hoa/exception`](https://packagist.org/packages/hoa/exception):

```json
{
    "require": {
        "hoa/exception": "~0.0"
    }
}
```

Please, read the website to [get more informations about how to
install](http://hoa-project.net/Source.html).

## Quick usage

We propose a quick overview of how to use generic exceptions, how to listen all
thrown exceptions through events and how to use group of exceptions.

### Generic exceptions

An exception is constitued of:
  * A message,
  * A code (optional),
  * A list of arguments for the message (à la `printf`, optional),
  * A previous exception (optional).

Thus, the following example builds an exception:

```php
$exception = new Hoa\Exception\Exception('Hello %s!', 0, 'world');
```

The exception message will be: `Hello world!`. The “raise” message (with all
information, not only the message) is:

```
{main}: (0) Hello world!
in … at line ….
```

Previous exceptions are shown too, for instance:

```php
$previous  = new Hoa\Exception\Exception('Hello previous.');
$exception = new Hoa\Exception\Exception('Hello %s!', 0, 'world', $previous);

echo $exception->raise(true);

/**
 * Will output:
 *     {main}: (0) Hello world!
 *     in … at line ….
 *     
 *         ⬇
 *     
 *     Nested exception (Hoa\Exception\Exception):
 *     {main}: (0) Hello previous.
 *     in … at line ….
 */
```

### Listen exceptions through events

Most exceptions in Hoa extend `Hoa\Exception\Exception`, which fire themselves
on the `hoa://Event/Exception` event channel (please, see [the `Hoa\Event`
library](http://central.hoa-project.net/Resource/Library/Event)). Consequently,
we can listen for all exceptions that are thrown in the application by writing:

```php
Hoa\Event\Event::getEvent('hoa://Event/Exception')->attach(
    function (Hoa\Event\Bucket $bucket) {
        $exception = $bucket->getData();
        // …
    }
);
```

Only the `Hoa\Exception\Idle` exceptions are not fired on the channel event.

### Group and transactions

Groups of exceptions are represented by the `Hoa\Exception\Group`. A group is an
exception that contains one or many exceptions. A transactional API is provided
to add more exceptions in the group with the following methods:
  * `beginTransaction` to start a transaction,
  * `rollbackTransaction` to remove all newly added exceptions since
    `beginTransaction` call,
  * `commitTransaction` to merge all newly added exceptions in the previous
    transaction,
  * `hasUncommittedExceptions` to check whether they are pending exceptions or
    not.

For instance, if an exceptional behavior is due to several reasons, a group of
exceptions can be thrown instead of one exception. Group can be nested too,
which is useful to represent a tree of exceptions. Thus:

```php
// A group of exceptions.
$group           = new Hoa\Exception\Group('Failed because of several reasons.');
$group['first']  = new Hoa\Exception\Exception('First reason');
$group['second'] = new Hoa\Exception\Exception('Second reason');

// Can nest another group.
$group['third']           = new Hoa\Exception\Group('Third reason');
$group['third']['fourth'] = new Hoa\Exception\Exception('Fourth reason');

echo $group->raise(true);

/**
 * Will output:
 *     {main}: (0) Failed because of several reasons.
 *     in … at line ….
 *     
 *     Contains the following exceptions:
 *     
 *       • {main}: (0) First reason
 *         in … at line ….
 *     
 *       • {main}: (0) Second reason
 *         in … at line ….
 *     
 *       • {main}: (0) Third reason
 *         in … at line ….
 *         
 *         Contains the following exceptions:
 *         
 *           • {main}: (0) Fourth reason
 *             in … at line ….
 */
```

The following example uses a transaction to add new exceptions in the group:

```php
$group   = new Hoa\Exception\Group('Failed because of several reasons.');
$group[] = new Hoa\Exception\Exception('Always present.');

$group->beginTransaction();

$group[] = new Hoa\Exception\Exception('Might be present.');

if (true === $condition) {
    $group->commitTransaction();
} else {
    $group->rollbackTransaction();
}
```

## Documentation

Different documentations can be found on the website:
[http://hoa-project.net/](http://hoa-project.net/).

## License

Hoa is under the New BSD License (BSD-3-Clause). Please, see
[`LICENSE`](http://hoa-project.net/LICENSE).
