# Change log

## 0.4.2

### Added

- Properly handle Json decode errors. 

### Removed

- Support for PHP 7.2

## 0.4.1

### Added

- `SerializerRouter` to choose the correct serializer. 

## 0.4.0

Allow `HydratorInterface::supportsHydrate` to throw `VersionNotSupportedException` when they support the message but not version. 

### Added

- `VersionNotSupportedException`
- `ConvertToMessageFailedException`
- `ConvertToArrayFailedException`
- `HydratorException` interface
- `TransformerException` interface

### Removed

- `HydratorException`
- `TransformerException`
