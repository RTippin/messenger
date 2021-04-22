# Changelog
- All notable changes to `Messenger` will be documented in this file.

### [v0.27]

#### Removed
- StoreMessengerIp listener removed. Up to end user to attach listener to heartbeat event should they want to use the IP provided.

### [v0.26 (04-12-2021)]

#### Added
- bool column `embeds` and nullable/text column `extra` on `messages` table.
- Optional extra payload when sending messages to allow custom json to be stored with the message.
- More extensive model factories.
- New routes for viewing group avatar when joining with an invitation.

#### Changed
- Renamed action class UpdateMessage to EditMessage.

### [v0.25 (04-10-2021)]

#### Added
- Ignore call option. When ignoring a private call, it will also end the call.

#### Changed
- Either party in a private call can end the call.

### [v0.24 (04-05-2021)]

#### Added
- Message reactions feature. New table `message_reactions`.
- Events/broadcast for reactions feature.
- bool `edited` and `reacted` columns on messages table.
- New providers scope using concat() on polymorph keys.
- Custom rule to verify an emoji exist in string.

#### Changed
- Message edits go by bool `edited` and not `updated_at` column now.
- Emoji converter is now an interface/service.
- New emoji picker added to the included UI.
- Emoji converter is now a service/interface.

### [v0.23 (03-24-2021)]

#### Added
- Message replies.
- reply_to_id column on the messages table.

#### Changed
- All store message actions accept params as array now instead of individual params.

### [v0.22 (03-15-2021)]

#### Added
- Audio message type to upload audio files.
~ Configs for audio files.
- Command to purge archived audio files.
- Routes to store/view/paginate and stream/download audio files.

#### Changed
- Updated the UI to include support for audio files in a thread.

#### Removed
- `message_documents.download` toggle in config removed.

### [v0.21 (03-11-2021)]

#### Added
- New configs to set mime types allowed to upload on each file type.

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
