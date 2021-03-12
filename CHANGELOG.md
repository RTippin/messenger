# Changelog
- All notable changes to `Messenger` will be documented in this file.

### [v0.21 (03-11-2021)]

#### Changed
- threads, messages, participants and message_edits tables use precision 6 for timestamps now.

### [v0.20 (03-09-2021)]

#### Changed
- Allow more mime types on uploads, frontend assets updated with this as well.
- Misc bug fixes along with image service not resizing gif/svg/webp.

### [v0.19 (02-28-2021)]

#### Added
- New configs to set upload limit sizes.
- New commands to temporarily shutdown calling system and end all active calls, as well as put the system back online.

### [v0.18 (02-20-2021)]

#### Changed
- More file moves/renames.
- Set broadcast/video driver on demand.

### [v0.17 (02-19-2021)]

#### Removed
- helpers.php methods except messenger().

#### Changed
- New Helpers class and support directory. Moved files around.
- Added intermediate modal to confirm joining call once page loads.

### [v0.16 (02-17-2021)]

#### Changed
- Bugfix on join call skipping generating participant resource on response.
- Broadcast broker resets private/presence each time to method called.

### [v0.15 (02-14-2021)]

#### Removed
- StoreEditMessage listener.

#### Changed
- Edit message action will store the edit history immediately after update message.
- Put a primary key back onto messenger model and table messengers.

### [v0.14 (02-09-2021)]

#### Added
- New Exceptions.

#### Changed
- Exceptions thrown throughout out package.
- Moved some authorization logic done in controllers or model into actions.

### [v0.13 (02-07-2021)]

#### Added
- Edit message table to store edit history.
- Route to view edit history.
- Config option to disable both edit message and viewing edit history.

### [v0.12 (02-05-2021)]

#### Added
- Edit message feature.

### [v0.11 (02-03-2021)]

#### Changed
- `teardown_complete` added to calls table used to avoid duplicate tear downs. Added a short cache lockout upon ending a call to avoid race conditions with automated EndCallIfEmpty job.

### [v0.10 (02-03-2021)]

#### Changed
- To avoid conflicts with channel names across apps, our channels are now prefixed with `messenger.` All impacted test and frontend assets have been updated to reflect this change.

### [v0.9 (02-01-2021)]

#### Added
- Test.

### [v0.1]

#### Added
- Everything.
