# Changelog
- All notable changes to `Messenger` will be documented in this file.

## [v0.15 (02-13-2021)]

### Removed
- StoreEditMessage listener.

### Changed
- Edit message action will store the edit history immediately after update message.

## [v0.14 (02-09-2021)]

### Added
- New Exceptions.

### Changed
- Exceptions thrown throughout out package.
- Moved some authorization logic done in controllers or model into actions.

## [v0.13 (02-07-2021)]

### Added
- Edit message table to store edit history.
- Route to view edit history.
- Config option to disable both edit message and viewing edit history.

## [v0.12 (02-05-2021)]

### Added
- Edit message feature.

## [v0.11 (02-03-2021)]

### Changed
- `teardown_complete` added to calls table used to avoid duplicate tear downs. Added a short cache lockout upon ending a call to avoid race conditions with automated EndCallIfEmpty job.

## [v0.10 (02-03-2021)]

### Changed
- To avoid conflicts with channel names across apps, our channels are now prefixed with `messenger.` All impacted test and frontend assets have been updated to reflect this change.

## [v0.9 (02-01-2021)]

### Added
- Test.

## [v0.1]

### Added
- Everything.
