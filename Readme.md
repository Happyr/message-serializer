# Message serializer

[![Latest Version](https://img.shields.io/github/release/Happyr/message-serializer.svg?style=flat-square)](https://github.com/Happyr/message-serializer/releases)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE)
[![Build Status](https://img.shields.io/travis/Happyr/message-serializer.svg?style=flat-square)](https://travis-ci.org/Happyr/message-serializer)
[![Code Coverage](https://img.shields.io/scrutinizer/coverage/g/Happyr/message-serializer.svg?style=flat-square)](https://scrutinizer-ci.com/g/Happyr/message-serializer)
[![Quality Score](https://img.shields.io/scrutinizer/g/Happyr/message-serializer.svg?style=flat-square)](https://scrutinizer-ci.com/g/Happyr/message-serializer)
[![Total Downloads](https://img.shields.io/packagist/dt/happyr/message-serializer.svg?style=flat-square)](https://packagist.org/packages/happyr/message-serializer)

This package contains some interfaces and classes to help you serialize and deserialize
a PHP class to an array. The package does not do any magic for you but rather help you
to define your serialization rules yourself. 

## Install

```
composer require happyr/message-serializer
```

See integration with [Symfony Messenger](#integration-with-symfony-messenger).

## The Problem

When you serialize a PHP class to show the output for a different user or application there
is one thing you should really keep in mind. That output is part of a public contract
that you cannot change without possibly breaking other applications. 

Consider this example: 

```php
class Foo {
    private $bar;

    public function getBar()
    {
        return $this->bar;
    }

    public function setBar($bar)
    {
        $this->bar = $bar;
    }
}

$x = new Foo();
$x->setBar('test string');

$output = serialize($x);
echo $output;
```

This will output: 
```
O:3:"Foo":1:{s:8:"Foobar";s:11:"test string";}
```

Even if you doing something smart with `json_encode` you will get: 
```
{"bar":"test string"}
```

This might seem fine at first. But if you change the `Foo` class slightly, say, 
rename the private property or add another property, then your output will differ 
and you have broken your contract with your users. 

## The solution

To avoid this problem we need to separate the class from the plain representation. 
The way we do that is to use a `Transformer` to take a class and produce an array. 

```php
class FooTransformer implements TransformerInterface
{
    public function getVersion(): int
    {
        return 1;
    }

    public function getIdentifier(): string
    {
        return 'foo';
    }

    public function getPayload($message): array
    {
        return [
            'bar' => $message->getBar(),
        ];
    }

    public function supportsTransform($message): bool
    {
        return $message instanceof Foo;
    }
}
``` 

This transformer is only responsible to convert a `Foo` class to an array. The
reverse operation is handled by a `Hydrator`: 

```php
class FooHydrator implements HydratorInterface
{
    public function toMessage(array $payload, int $version)
    {
        $object = new Foo();
        $object->setBar($payload['bar']);

        return $object;
    }

    public function supportsHydrate(string $identifier, int $version): bool
    {
        return $identifier === 'foo' && $version === 1;
    }
}
```

With transformers and hydrators you are sure to never accidentally change the output
to the user. 

### Manage versions

If you need to change the output you may do so with help of the version property. 
As an example, say you want to rename the key `bar` to something differently. Then 
you create a new `Hydrator` like:

 ```php
 class FooHydrator2 implements HydratorInterface
 {
     public function toMessage(array $payload, int $version)
     {
         $object = new Foo();
         $object->setBar($payload['new_bar']);
 
         return $object;
     }
 
     public function supportsHydrate(string $identifier, int $version): bool
     {
         return $identifier === 'foo' && $version === 2;
     }
 }
```

Now you simply update the transformer to your new contract: 

```php
class FooTransformer implements TransformerInterface
{
    public function getVersion(): int
    {
        return 2;
    }

    public function getIdentifier(): string
    {
        return 'foo';
    }

    public function getPayload($message): array
    {
        return [
            'new_bar' => $message->getBar(),
        ];
    }

    public function supportsTransform($message): bool
    {
        return $message instanceof Foo;
    }
}
```

## Integration with Symfony Messenger

To make it work with Symfony Messenger, add the following service definition: 

```yaml
# config/packages/happyr_message_serializer.yaml

services:
  Happyr\MessageSerializer\Serializer:
    autowire: true

  Happyr\MessageSerializer\Transformer\MessageToArrayInterface: '@happyr.message_serializer.transformer'
  happyr.message_serializer.transformer:
    class: Happyr\MessageSerializer\Transformer\Transformer
    arguments: [!tagged happyr.message_serializer.transformer]


  Happyr\MessageSerializer\Hydrator\ArrayToMessageInterface: '@happyr.message_serializer.hydrator'
  happyr.message_serializer.hydrator:
    class: Happyr\MessageSerializer\Hydrator\Hydrator
    arguments: [!tagged happyr.message_serializer.hydrator]
```

If you automatically want to tag all your Transformers and Hydrators, add this to your
main service file: 

```yaml
# config/packages/happyr_message_serializer.yaml
services:
    # ...

    _instanceof:
        Happyr\MessageSerializer\Transformer\TransformerInterface:
            tags:
                - 'happyr.message_serializer.transformer'

        Happyr\MessageSerializer\Hydrator\HydratorInterface:
            tags:
                - 'happyr.message_serializer.hydrator'
```

Then finally, make sure you configure your transport to use this serializer: 

```yaml
# config/packages/messenger.yaml

framework:
    messenger:
        transports:
            amqp: '%env(MESSENGER_TRANSPORT_DSN)%'
            
            to_foobar_application:
              dsn: '%env(MESSENGER_TRANSPORT_FOOBAR)%'
              serializer: 'Happyr\MessageSerializer\Serializer'
```


## Pro tip

You can let your messages implement th `HydratorInterface` and `TransformerInterface`:

```php
use Happyr\MessageSerializer\Hydrator\HydratorInterface;
use Happyr\MessageSerializer\Transformer\TransformerInterface;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\Messenger\Envelope;

class CreateUser implements HydratorInterface, TransformerInterface
{
    private $uuid;
    private $username;

    public static function create(UuidInterface $uuid, string $username): self
    {
        $message = new self();
        $message->uuid = $uuid;
        $message->username = $username;
        
        return $message;
    }

    public function getUuid(): UuidInterface
    {
        return $this->uuid;
    }
    
    public function getUsername(): string
    {
        return $this->username;
    }

    public function toMessage(array $payload, int $version)
    {
        return new self(Uuid::fromString($payload['id']), $payload['username']);
    }

    public function supportsHydrate(string $identifier, int $version): bool
    {
        return $identifier === 'create-user' && $version === 1;
    }

    public function getVersion(): int
    {
        return 1;
    }

    public function getIdentifier(): string
    {
        return 'create-user';
    }

    public function getPayload($message): array
    {
        return [
            'id' => $message->getUuid()->toString(),
            'username' => $message->getUsername(),
        ];
    }

    public function supportsTransform($message): bool
    {
        if ($message instanceof Envelope) {
            $message = $message->getMessage();
        }

        return $message instanceof self;
    }
}
```

Just note that we cannot use an constructor to this class since it will work both as a value object and a service. 
