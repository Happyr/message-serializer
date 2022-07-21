# Change log

## 0.5.2

- Added support for Symfony 6
- Make sure `Hydrator` does not return null
- Added LoggerInterface as optional third constructor argument for `Serializer`

## 0.5.1

### Added

- Support for PHP8

## 0.5.0

### Added

- Better exception message on `TransformerNotFoundException`

### Removed

- Support Symfony Messenger 4.3

## 0.4.3

### Added

- Support messenger retry strategy. 

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
