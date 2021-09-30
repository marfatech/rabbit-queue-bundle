## [Unreleased]
### Added
- `RouterPublisher` added for using `direct` and `topic` RabbitMQ exchanges with routing key.
- Added ability to send `routingKey` and `properties` with `RabbitMQProducer`.

### Changed
- [BC BREAK] Refactoring namespace to `MarfaTech`.
- [BC BREAK] Renamed root config `wakeapp_rabbit_queue` to `marfatech_rabbit_queue`.
- [BC BREAK] Renamed tag `wakeapp_rabbit_queue.definition` to `marfatech_rabbit_queue.definition`.
- [BC BREAK] Renamed tag `wakeapp_rabbit_queue.hydrator` to `marfatech_rabbit_queue.hydrator`.
- [BC BREAK] Renamed tag `wakeapp_rabbit_queue.publisher` to `marfatech_rabbit_queue.publisher`.
- [BC BREAK] Renamed parameter `wakeapp_rabbit_queue.connection.host` to `marfatech_rabbit_queue.connection.host`.
- [BC BREAK] Renamed parameter `wakeapp_rabbit_queue.connection.port` to `marfatech_rabbit_queue.connection.port`.
- [BC BREAK] Renamed parameter `wakeapp_rabbit_queue.connection.username` to `marfatech_rabbit_queue.connection.username`.
- [BC BREAK] Renamed parameter `wakeapp_rabbit_queue.connection.password` to `marfatech_rabbit_queue.connection.password`.
- [BC BREAK] Renamed parameter `wakeapp_rabbit_queue.connection.vhost` to `marfatech_rabbit_queue.connection.vhost`.
- [BC BREAK] Renamed parameter `wakeapp_rabbit_queue.connection.connection_timeout` to `marfatech_rabbit_queue.connection.connection_timeout`.
- [BC BREAK] Renamed parameter `wakeapp_rabbit_queue.connection.read_write_timeout` to `marfatech_rabbit_queue.connection.read_write_timeout`.
- [BC BREAK] Renamed parameter `wakeapp_rabbit_queue.connection.heartbeat` to `marfatech_rabbit_queue.connection.heartbeat`.
- [BC BREAK] Renamed parameter `wakeapp_rabbit_queue.consumer.idle_timeout` to `marfatech_rabbit_queue.consumer.idle_timeout`.
- [BC BREAK] Renamed parameter `wakeapp_rabbit_queue.consumer.wait_timeout` to `marfatech_rabbit_queue.consumer.wait_timeout`.
- [BC BREAK] Change interface `RabbitmqProducerInterface::put` added parameter `routingKey` and `properties`.
- [BC BREAK] Change interface `PublisherInterface::publish` added parameter `routingKey` and `properties`.

## [2.1.2] - 2021-07-15
### Changed
- Change repository namespace to `marfatech`.

## [2.1.1] - 2021-05-18
### Added
- Added support `psr/container` with version `>=1.1.0`.

## [2.1.0] - 2021-05-06
### Added
- Added new configuration parameters: `connection_timeout`, `read_write_timeout`, `heartbeat`.

### Changed
- Change exception logging in `ConsumerRunCommand`.

## [2.0.0] - 2021-04-07
### Changed
- [BC BREAK] Constants moved from `ConsumerInterface` to `AbstractConsumer`.

## [1.0.1] - 2021-04-01
### Changed
- Changed return value usage from using Command::SUCCESS constant to scalar. 
- Commands changed: 
  * UpdateDefinitionCommand
  * ConsumerListCommand
  * ConsumerRunCommand

## [1.0.0] - 2021-03-04
### Added
- Added retry exchange for rewind message in queue with delay.
- Added config parameters `idle_timeout` and `wait_timeout`.
- Added publishers: `FifoPublisher`, `DelayPublisher`, `FifoPublisher`, `DeduplicatePublisher`, `DeduplicateDelayPublisher`.

### Changed
- Optimized receiving a batch of messages in `ConsumerRunCommand`.
- Extended supported queue types by `Delay`, `Deduplicate`.

### Fixed
- Fix rewind and release partial messages by delivery tag. Changed `ReleasePartialException`, `RewindDelayPartialException`, `RewindPartialException`.

## [0.1.1] - 2021-01-14
### Changed
- Change license type.

## [0.1.0] - 2021-01-14
### Added
- The first basic version of the bundle.
